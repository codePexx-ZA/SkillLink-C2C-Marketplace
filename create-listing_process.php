<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/listing_helpers.php";
require_once __DIR__ . "/listing_thumbnail.php";

requireLogin();

if (isAdminSession()) {
    header("Location: admin-dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: create-listing.php");
    exit;
}

$userId = (int) $_SESSION["logged_in_user_id"];

try {
    $pdo = getPdo();
    requireSellerWithProfile($pdo, $userId);

    $data = parseListingFormData($_POST);
    $validationError = validateListingData($data);
    if ($validationError !== null) {
        header("Location: create-listing.php?error=" . $validationError);
        exit;
    }

    if (!categoryExists($pdo, $data["category_id"])) {
        header("Location: create-listing.php?error=category");
        exit;
    }

    $thumbnailUpload = processListingThumbnailUpload(0, $userId, null, true);
    if ($thumbnailUpload["error"] !== null) {
        header("Location: create-listing.php?error=photo_" . $thumbnailUpload["error"]);
        exit;
    }

    $thumbnailUrl = $thumbnailUpload["path"] ?? "";
    if ($thumbnailUrl === "") {
        header("Location: create-listing.php?error=photo_required");
        exit;
    }

    $priceAmount = round((float) $data["price_amount"], 2);

    $stmt = $pdo->prepare(
        "INSERT INTO listings (
            seller_id, category_id, business_name, price_snapshot, thumbnail_url,
            status, description, location, price_amount, price_unit
         ) VALUES (
            :seller_id, :category_id, :business_name, :price_snapshot, :thumbnail_url,
            'pending_review', :description, :location, :price_amount, :price_unit
         )"
    );
    $stmt->execute([
        ":seller_id" => $userId,
        ":category_id" => $data["category_id"],
        ":business_name" => $data["business_name"],
        ":price_snapshot" => $priceAmount,
        ":thumbnail_url" => $thumbnailUrl,
        ":description" => $data["description"],
        ":location" => $data["location"],
        ":price_amount" => $priceAmount,
        ":price_unit" => $data["price_unit"],
    ]);

    header("Location: market.php?posted=1");
    exit;
} catch (Throwable $exception) {
    header("Location: create-listing.php?error=server");
    exit;
}

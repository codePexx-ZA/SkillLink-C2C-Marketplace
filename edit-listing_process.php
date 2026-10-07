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
    header("Location: edit-listings.php");
    exit;
}

$userId = (int) $_SESSION["logged_in_user_id"];
$listingId = (int) ($_POST["listing_id"] ?? 0);

if ($listingId <= 0) {
    header("Location: edit-listings.php?error=missing");
    exit;
}

try {
    $pdo = getPdo();
    requireSellerWithProfile($pdo, $userId);

    if (!listingBelongsToSeller($pdo, $listingId, $userId)) {
        header("Location: edit-listings.php?error=notfound");
        exit;
    }

    $thumbStmt = $pdo->prepare(
        "SELECT thumbnail_url FROM listings WHERE id = :id AND seller_id = :seller_id LIMIT 1"
    );
    $thumbStmt->execute([
        ":id" => $listingId,
        ":seller_id" => $userId,
    ]);
    $thumbRow = $thumbStmt->fetch();
    $existingThumbnail = $thumbRow["thumbnail_url"] ?? "";

    $data = parseListingFormData($_POST);
    $validationError = validateListingData($data);
    if ($validationError !== null) {
        header("Location: edit-listings.php?error=" . $validationError);
        exit;
    }

    if (!categoryExists($pdo, $data["category_id"])) {
        header("Location: edit-listings.php?error=category");
        exit;
    }

    $thumbnailUpload = processListingThumbnailUpload($listingId, $userId, $existingThumbnail, false);
    if ($thumbnailUpload["error"] !== null) {
        header("Location: edit-listings.php?error=photo_" . $thumbnailUpload["error"]);
        exit;
    }

    $thumbnailUrl = $thumbnailUpload["path"] !== null ? $thumbnailUpload["path"] : $existingThumbnail;

    $priceAmount = round((float) $data["price_amount"], 2);

    $stmt = $pdo->prepare(
        "UPDATE listings
         SET category_id = :category_id,
             business_name = :business_name,
             price_snapshot = :price_snapshot,
             thumbnail_url = :thumbnail_url,
             description = :description,
             location = :location,
             price_amount = :price_amount,
             price_unit = :price_unit
         WHERE id = :id AND seller_id = :seller_id"
    );
    $stmt->execute([
        ":category_id" => $data["category_id"],
        ":business_name" => $data["business_name"],
        ":price_snapshot" => $priceAmount,
        ":thumbnail_url" => $thumbnailUrl,
        ":description" => $data["description"],
        ":location" => $data["location"],
        ":price_amount" => $priceAmount,
        ":price_unit" => $data["price_unit"],
        ":id" => $listingId,
        ":seller_id" => $userId,
    ]);

    header("Location: edit-listings.php?saved=1");
    exit;
} catch (Throwable $exception) {
    header("Location: edit-listings.php?error=server");
    exit;
}

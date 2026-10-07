<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/profile_photo.php";

requireLogin();

if (isAdminSession()) {
    header("Location: admin-dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile-creation.php");
    exit;
}

$userId = (int) $_SESSION["logged_in_user_id"];
$username = trim($_POST["username"] ?? "");
$displayName = trim($_POST["display-name"] ?? "");
$fullname = trim($_POST["fullname"] ?? "");
$categorySlug = trim($_POST["category"] ?? "");

$billingEmail = trim($_POST["billing_email"] ?? "");
$cardBrand = trim($_POST["card_brand"] ?? "");
$billingAddress = trim($_POST["billing_address"] ?? "");

if ($displayName === "" && $fullname !== "") {
    $displayName = $fullname;
}

if ($username === "" || $displayName === "") {
    header("Location: profile-creation.php?error=missing");
    exit;
}

try {
    $pdo = getPdo();
    $hasProfile = userHasProfile($pdo, $userId);

    $existingPhoto = null;
    if ($hasProfile) {
        $photoStmt = $pdo->prepare("SELECT profile_photo FROM profiles WHERE user_id = :user_id LIMIT 1");
        $photoStmt->execute([":user_id" => $userId]);
        $photoRow = $photoStmt->fetch();
        $existingPhoto = $photoRow["profile_photo"] ?? null;
    }

    $photoUpload = processProfilePhotoUpload($userId, $existingPhoto);
    if ($photoUpload["error"] !== null) {
        $editQuery = $hasProfile ? "?edit=1&" : "?";
        header("Location: profile-creation.php" . $editQuery . "error=photo_" . $photoUpload["error"]);
        exit;
    }
    $newPhotoPath = $photoUpload["path"];

    $pdo->beginTransaction();

    if ($hasProfile) {
        if ($newPhotoPath !== null) {
            $profileStmt = $pdo->prepare(
                "UPDATE profiles
                 SET display_name = :display_name,
                     username = :username,
                     profile_photo = :profile_photo,
                     primary_category_slug = :primary_category_slug
                 WHERE user_id = :user_id"
            );
            $profileStmt->execute([
                ":display_name" => $displayName,
                ":username" => $username,
                ":profile_photo" => $newPhotoPath,
                ":primary_category_slug" => $categorySlug !== "" ? $categorySlug : null,
                ":user_id" => $userId,
            ]);
        } else {
            $profileStmt = $pdo->prepare(
                "UPDATE profiles
                 SET display_name = :display_name,
                     username = :username,
                     primary_category_slug = :primary_category_slug
                 WHERE user_id = :user_id"
            );
            $profileStmt->execute([
                ":display_name" => $displayName,
                ":username" => $username,
                ":primary_category_slug" => $categorySlug !== "" ? $categorySlug : null,
                ":user_id" => $userId,
            ]);
        }
    } else {
        $profileStmt = $pdo->prepare(
            "INSERT INTO profiles (user_id, display_name, username, profile_photo, primary_category_slug)
             VALUES (:user_id, :display_name, :username, :profile_photo, :primary_category_slug)"
        );
        $profileStmt->execute([
            ":user_id" => $userId,
            ":display_name" => $displayName,
            ":username" => $username,
            ":profile_photo" => $newPhotoPath,
            ":primary_category_slug" => $categorySlug !== "" ? $categorySlug : null,
        ]);
    }

    if ($billingEmail !== "" && $cardBrand !== "" && $billingAddress !== "") {
        $billingCheck = $pdo->prepare("SELECT user_id FROM billing_info WHERE user_id = :user_id LIMIT 1");
        $billingCheck->execute([":user_id" => $userId]);

        if ($billingCheck->fetch()) {
            $billingStmt = $pdo->prepare(
                "UPDATE billing_info
                 SET billing_email = :billing_email,
                     card_brand = :card_brand,
                     billing_address = :billing_address
                 WHERE user_id = :user_id"
            );
        } else {
            $billingStmt = $pdo->prepare(
                "INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
                 VALUES (:user_id, :billing_email, :card_brand, :billing_address)"
            );
        }

        $billingStmt->execute([
            ":user_id" => $userId,
            ":billing_email" => $billingEmail,
            ":card_brand" => $cardBrand,
            ":billing_address" => $billingAddress,
        ]);
    }

    $pdo->commit();

    if ($hasProfile) {
        header("Location: profile.php");
        exit;
    }

    header("Location: profile-created.php");
    exit;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $editMode = isset($hasProfile) && $hasProfile;
    if (!$editMode && isset($pdo)) {
        $editMode = userHasProfile($pdo, $userId);
    }
    $redirect = $editMode ? "profile-creation.php?edit=1&error=server" : "profile-creation.php?error=server";
    header("Location: " . $redirect);
    exit;
}

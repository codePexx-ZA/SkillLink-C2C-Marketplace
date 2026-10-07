<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_helpers.php";

requireAdminLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-dashboard.php");
    exit;
}

$listingId = (int) ($_POST["listing_id"] ?? 0);
$action = trim($_POST["action"] ?? "");

if ($listingId <= 0 || $action === "") {
    header("Location: admin-dashboard.php?error=invalid_request");
    exit;
}

try {
    $pdo = getPdo();
    $updated = updateListingStatus($pdo, $listingId, $action);

    if (!$updated) {
        header("Location: admin-dashboard.php?error=listing_not_updated");
        exit;
    }

    $messages = [
        "approve" => "approved",
        "reject" => "rejected",
        "suspend" => "suspended",
        "archive" => "archived",
    ];

    $message = $messages[$action] ?? "updated";
    header("Location: admin-dashboard.php?listing_" . $message . "=1");
    exit;
} catch (Throwable $exception) {
    header("Location: admin-dashboard.php?error=server");
    exit;
}

<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_helpers.php";

requireAdminLogin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-dashboard.php");
    exit;
}

$adminId = (int) $_SESSION["logged_in_user_id"];
$action = trim($_POST["action"] ?? "");
$userId = (int) ($_POST["user_id"] ?? 0);
$userAction = trim($_POST["user_action"] ?? "");

$redirectParams = ["tab" => "completed"];

try {
    $pdo = getPdo();
    $success = false;

    if ($action === "apply" && $userId > 0 && $userAction !== "") {
        $success = applyAccountActionImmediate($pdo, $userId, $adminId, $userAction);
        if ($success) {
            $redirectParams["user_applied"] = 1;
        } else {
            unset($redirectParams["tab"]);
            $redirectParams["error"] = "user_action_failed";
        }
    } else {
        unset($redirectParams["tab"]);
        $redirectParams["error"] = "invalid_request";
    }
} catch (Throwable $exception) {
    unset($redirectParams["tab"]);
    $redirectParams["error"] = "server";
}

$query = http_build_query($redirectParams);
header("Location: admin-dashboard.php?" . $query);
exit;

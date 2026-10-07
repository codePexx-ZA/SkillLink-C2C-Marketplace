<?php
function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function requireLogin(): void
{
    ensureSessionStarted();
    if (empty($_SESSION["logged_in_user_id"])) {
        header("Location: index.php");
        exit;
    }
}

function isAdminSession(): bool
{
    ensureSessionStarted();
    return !empty($_SESSION["logged_in_role"]) && $_SESSION["logged_in_role"] === "admin";
}

function requireAdminLogin(): void
{
    requireLogin();
    if (!isAdminSession()) {
        header("Location: index.php");
        exit;
    }
}

function userHasProfile(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT user_id FROM profiles WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([":user_id" => $userId]);
    return (bool) $stmt->fetch();
}

function redirectAfterLogin(PDO $pdo, array $user): void
{
    if ($user["role"] === "admin") {
        header("Location: admin-dashboard.php");
        exit;
    }

    if (!userHasProfile($pdo, (int) $user["id"])) {
        header("Location: profile-creation.php");
        exit;
    }

    header("Location: market.php");
    exit;
}

function categoryLabel(string $slug): string
{
    $labels = [
        "tree-felling" => "Tree Felling",
        "plumbing" => "Plumbing",
        "electrical" => "Electrical",
        "gardening" => "Gardening",
        "painting" => "Painting",
        "cleaning" => "Cleaning",
        "carpentry" => "Carpentry",
        "other" => "Other",
    ];

    return $labels[$slug] ?? ucfirst(str_replace("-", " ", $slug));
}

function getUserAccountStatus(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare(
        "SELECT account_status FROM users WHERE id = :id LIMIT 1"
    );
    $stmt->execute([":id" => $userId]);
    $row = $stmt->fetch();

    if (!$row || empty($row["account_status"])) {
        return "active";
    }

    return (string) $row["account_status"];
}

function accountStatusDisplayLabel(string $status): string
{
    $labels = [
        "active" => "Online",
        "promoted" => "Promoted",
        "restricted" => "Restricted",
        "deleted" => "Deleted",
    ];

    return $labels[$status] ?? "Online";
}

function navAccountStatusClass(string $status): string
{
    $classes = [
        "active" => "nav-menu-user-badge-online",
        "promoted" => "nav-menu-user-badge-promoted",
        "restricted" => "nav-menu-user-badge-restricted",
        "deleted" => "nav-menu-user-badge-deleted",
    ];

    return $classes[$status] ?? "nav-menu-user-badge-online";
}

function accountStatusCssClass(string $status): string
{
    $classes = [
        "active" => "account-status-active",
        "promoted" => "account-status-promoted",
        "restricted" => "account-status-restricted",
        "deleted" => "account-status-deleted",
    ];

    return $classes[$status] ?? "account-status-active";
}

function enforceAccountAccess(PDO $pdo, int $userId): void
{
    $status = getUserAccountStatus($pdo, $userId);

    if ($status === "deleted") {
        ensureSessionStarted();
        $_SESSION = [];
        session_destroy();
        header("Location: index.php?account=deleted");
        exit;
    }
}

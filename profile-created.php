<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/profile_photo.php";

requireLogin();

if (isAdminSession()) {
    header("Location: admin-dashboard.php");
    exit;
}

$pdo = getPdo();
$userId = (int) $_SESSION["logged_in_user_id"];

if (!userHasProfile($pdo, $userId)) {
    header("Location: profile-creation.php");
    exit;
}

$userStmt = $pdo->prepare("SELECT email, created_at FROM users WHERE id = :id LIMIT 1");
$userStmt->execute([":id" => $userId]);
$user = $userStmt->fetch();

$profileStmt = $pdo->prepare(
    "SELECT display_name, username, profile_photo, primary_category_slug FROM profiles WHERE user_id = :user_id LIMIT 1"
);
$profileStmt->execute([":user_id" => $userId]);
$profile = $profileStmt->fetch();

$displayName = $profile["display_name"] ?? "";
$username = $profile["username"] ?? "";
$email = $user["email"] ?? "";
$categorySlug = $profile["primary_category_slug"] ?? "";
$categoryLabel = $categorySlug !== "" ? categoryLabel($categorySlug) : "Not selected";
$profilePhotoPath = $profile["profile_photo"] ?? "";
$hasProfilePhoto = isSafeProfilePhotoPath($profilePhotoPath);

$memberSince = "—";
if (!empty($user["created_at"])) {
    $memberSince = date("j F Y", strtotime($user["created_at"]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Profile Created</title>
    <link rel="stylesheet" href="profile-created.css">
</head>
<body class="profile-created-page">
    <nav class="nav-bar profile-nav">
        <label class="hamburger" for="menu-toggle-created">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <input type="checkbox" id="menu-toggle-created" class="menu-toggle">
        <div class="nav-menu">
            <a href="market.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="OrdersPage.php">Orders</a>
            <a href="ContactUs.php">Contact Us</a>
            <div class="nav-menu-footer">
                <a href="logout.php">Log Out</a>
            </div>
        </div>
        <a href="index.php" class="logo">SkillLink</a>
    </nav>

    <main class="created-main">
        <header class="created-header">
            <h1>Profile Has Been Created</h1>
            <p>Your profile details are saved. You can view them or continue to the market.</p>
        </header>

        <section class="created-layout">
            <aside class="profile-summary-card">
                <h2 class="profile-summary-name"><?php echo htmlspecialchars($displayName); ?></h2>
                <p class="profile-summary-username">@<?php echo htmlspecialchars($username); ?></p>
                <div class="profile-avatar-wrap">
                    <?php if ($hasProfilePhoto): ?>
                        <img src="<?php echo htmlspecialchars($profilePhotoPath); ?>" alt="Profile photo" class="profile-avatar-image" width="140" height="140">
                    <?php else: ?>
                        <div class="profile-avatar">
                            <span><?php echo htmlspecialchars(strtoupper(substr($displayName, 0, 1))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="profile-member-since">Member since: <?php echo htmlspecialchars($memberSince); ?></p>
            </aside>

            <section class="created-details-card">
                <h2 class="profile-section-heading">Profile Information</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Display Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($displayName); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Username</span>
                        <span class="detail-value"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="detail-item detail-item-wide">
                        <span class="detail-label">Service Category</span>
                        <span class="detail-value"><?php echo htmlspecialchars($categoryLabel); ?></span>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="profile.php" class="btn-secondary">View profile</a>
                    <a href="market.php" class="btn-primary">Continue to market</a>
                </div>
            </section>
        </section>
    </main>
</body>
</html>

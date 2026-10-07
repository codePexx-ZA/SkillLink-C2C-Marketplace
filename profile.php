<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/profile_photo.php";

requireLogin();
require_once __DIR__ . "/nav_bar.php";

$isAdmin = isAdminSession();
$pdo = getPdo();
$userId = (int) $_SESSION["logged_in_user_id"];

if (!$isAdmin) {
    enforceAccountAccess($pdo, $userId);
}

if (!$isAdmin && !userHasProfile($pdo, $userId)) {
    header("Location: profile-creation.php");
    exit;
}

$userStmt = $pdo->prepare("SELECT email, created_at, account_status FROM users WHERE id = :id LIMIT 1");
$userStmt->execute([":id" => $userId]);
$user = $userStmt->fetch();

$profileStmt = $pdo->prepare(
    "SELECT display_name, username, profile_photo, primary_category_slug FROM profiles WHERE user_id = :user_id LIMIT 1"
);
$profileStmt->execute([":user_id" => $userId]);
$profile = $profileStmt->fetch();

$billingStmt = $pdo->prepare(
    "SELECT billing_email, card_brand, billing_address FROM billing_info WHERE user_id = :user_id LIMIT 1"
);
$billingStmt->execute([":user_id" => $userId]);
$billing = $billingStmt->fetch();

$email = $user["email"] ?? ($_SESSION["logged_in_user"] ?? "");
$hasProfile = is_array($profile);
$displayName = $hasProfile ? ($profile["display_name"] ?? "") : ($isAdmin ? "Administrator" : "");
$username = $hasProfile ? ($profile["username"] ?? "") : ($isAdmin ? "admin" : "");
$categorySlug = $hasProfile ? ($profile["primary_category_slug"] ?? "") : "";
$categoryLabel = $categorySlug !== "" ? categoryLabel($categorySlug) : ($isAdmin ? "Administrator" : "Not selected");
$profilePhotoPath = $hasProfile ? ($profile["profile_photo"] ?? "") : "";
$hasProfilePhoto = isSafeProfilePhotoPath($profilePhotoPath);

$hasBilling = is_array($billing);
$billingEmail = $hasBilling ? $billing["billing_email"] : "";
$cardBrand = $hasBilling ? $billing["card_brand"] : "";
$billingAddress = $hasBilling ? $billing["billing_address"] : "";

$memberSince = "—";
if (!empty($user["created_at"])) {
    $memberSince = date("j F Y", strtotime($user["created_at"]));
}

$accountStatus = $isAdmin ? "active" : (string) ($user["account_status"] ?? "active");
$accountStatusLabel = accountStatusDisplayLabel($accountStatus);
$accountStatusClass = accountStatusCssClass($accountStatus);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - My Profile</title>
    <link rel="stylesheet" href="profile-creation.css">
    <link rel="stylesheet" href="app-nav.css">
</head>
<body class="profile-page">
    <?php renderNavBar("menu-toggle-profile-view", "profile-nav"); ?>

    <div class="profile-curve-container">
        <div class="svg-container">
            <svg viewBox="0 0 800 400" class="profile-curve-svg" preserveAspectRatio="none">
                <path id="curve" d="M 800 300 Q 400 350 0 300 L 0 0 L 800 0 L 800 300 Z"></path>
            </svg>
        </div>
        <header class="profile-curve-header">
            <h1>Your Profile</h1>
            <?php if ($isAdmin): ?>
                <p>Administrator account — browse the site as admin.</p>
            <?php else: ?>
                <p>View your saved information from your account.</p>
            <?php endif; ?>
        </header>
    </div>

    <main class="profile-main">
        <div class="profile-layout">
            <div class="profile-form-column">
                <div class="profile-form">
                <div class="edit-profile-card">
                    <h2 class="profile-section-heading">Personal Details</h2>
                    <h3 class="profile-subheading">User info</h3>
                    <?php if ($hasProfilePhoto): ?>
                    <div class="form-group profile-photo-display">
                        <label>Profile Photo</label>
                        <img
                            src="<?php echo htmlspecialchars($profilePhotoPath); ?>"
                            alt="Profile photo for <?php echo htmlspecialchars($displayName); ?>"
                            class="profile-photo-view"
                            width="120"
                            height="120"
                        >
                    </div>
                    <?php endif; ?>
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($displayName); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="text" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Member Since</label>
                            <input type="text" value="<?php echo htmlspecialchars($memberSince); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="profile-creation.php?edit=1" class="btn-secondary">Edit information</a>
                    </div>
                </div>

                <section class="profile-section profile-section-billing edit-profile-card">
                    <h2 class="profile-section-heading">Billing Information</h2>
                    <h3 class="profile-subheading">Billing details</h3>
                    <?php if ($hasBilling): ?>
                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label>Billing Email</label>
                            <input type="text" value="<?php echo htmlspecialchars($billingEmail); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Card Brand</label>
                            <input type="text" value="<?php echo htmlspecialchars($cardBrand); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Billing Address</label>
                        <input type="text" value="<?php echo htmlspecialchars($billingAddress); ?>" readonly>
                    </div>
                    <?php else: ?>
                    <p style="color: #285A48; margin: 0 0 1rem 0;">No billing details saved yet.</p>
                    <?php endif; ?>
                    <div class="form-actions">
                        <a href="profile-creation.php?edit=1" class="btn-secondary">Edit information</a>
                    </div>
                </section>

                <section class="profile-section edit-profile-card">
                    <h2 class="profile-section-heading">Profile Information</h2>
                    <h3 class="profile-subheading">Profile details</h3>
                    <div class="form-group">
                        <label>Service Category</label>
                        <input type="text" value="<?php echo htmlspecialchars($categoryLabel); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Reviews (visible to buyers)</label>
                        <div class="reviews-preview">
                            <p class="reviews-hint">Ratings will appear here as you receive them</p>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="market.php" class="btn-primary">Go to Market</a>
                    </div>
                </section>
                </div>
            </div>
        </div>
    </main>

    <div
        class="account-status-indicator <?php echo htmlspecialchars($accountStatusClass); ?>"
        role="status"
        aria-label="Account status: <?php echo htmlspecialchars($accountStatusLabel); ?>"
    >
        <?php echo htmlspecialchars($accountStatusLabel); ?>
    </div>

    <script>
        (function() {
            var curve = document.getElementById("curve");
            if (!curve) return;
            var defaultCurveValue = 350;
            var curveRate = 3;
            var ticking = false;
            function scrollEvent(scrollPos) {
                if (scrollPos >= 0 && scrollPos < defaultCurveValue) {
                    var curveValue = defaultCurveValue - parseFloat(scrollPos / curveRate);
                    curve.setAttribute("d", "M 800 300 Q 400 " + curveValue + " 0 300 L 0 0 L 800 0 L 800 300 Z");
                }
            }
            window.addEventListener("scroll", function() {
                var scrollPos = window.scrollY;
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        scrollEvent(scrollPos);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        })();
    </script>
</body>
</html>

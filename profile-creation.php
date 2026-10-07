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

$isEdit = isset($_GET["edit"]);
$existingProfile = null;
$existingBilling = null;

if (userHasProfile($pdo, $userId)) {
    if (!$isEdit) {
        header("Location: profile.php");
        exit;
    }
    $profileStmt = $pdo->prepare(
        "SELECT display_name, username, profile_photo, primary_category_slug FROM profiles WHERE user_id = :user_id LIMIT 1"
    );
    $profileStmt->execute([":user_id" => $userId]);
    $existingProfile = $profileStmt->fetch();

    $billingStmt = $pdo->prepare(
        "SELECT billing_email, card_brand, billing_address FROM billing_info WHERE user_id = :user_id LIMIT 1"
    );
    $billingStmt->execute([":user_id" => $userId]);
    $existingBilling = $billingStmt->fetch() ?: null;
}

$accountEmail = $_SESSION["logged_in_user"] ?? "";
$formError = "";
$prefillDisplayName = $existingProfile["display_name"] ?? "";
$prefillUsername = $existingProfile["username"] ?? "";
$prefillCategory = $existingProfile["primary_category_slug"] ?? "";
$prefillBillingEmail = $existingBilling["billing_email"] ?? "";
$prefillCardBrand = $existingBilling["card_brand"] ?? "";
$prefillBillingAddress = $existingBilling["billing_address"] ?? "";
$prefillCategoryLabel = $prefillCategory !== "" ? categoryLabel($prefillCategory) : "Select your main service";
$existingPhotoPath = $existingProfile["profile_photo"] ?? "";
$hasExistingPhoto = isSafeProfilePhotoPath($existingPhotoPath);
$existingPhotoFilename = $hasExistingPhoto ? basename($existingPhotoPath) : "";
$photoFilenameLabel = $hasExistingPhoto
    ? $existingPhotoFilename
    : "No file selected";

if (isset($_GET["error"])) {
    if ($_GET["error"] === "missing") {
        $formError = "Please enter a username and display name.";
    } elseif ($_GET["error"] === "server") {
        $formError = "Could not save your profile. Please try again.";
    } elseif ($_GET["error"] === "photo_invalid") {
        $formError = "Profile photo must be a JPG, PNG, GIF, or WebP image.";
    } elseif ($_GET["error"] === "photo_too_large") {
        $formError = "Profile photo must be 2 MB or smaller.";
    } elseif ($_GET["error"] === "photo_save_failed") {
        $formError = "Could not save your profile photo. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink — Create Profile</title>
    <link rel="stylesheet" href="profile-creation.css">
</head>
<body class="profile-page">
    <nav class="nav-bar profile-nav">
        <label class="hamburger" for="menu-toggle-profile">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <input type="checkbox" id="menu-toggle-profile" class="menu-toggle">
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

    <div class="profile-curve-container">
        <div class="svg-container">
            <svg viewBox="0 0 800 400" class="profile-curve-svg" preserveAspectRatio="none">
                <path id="curve" d="M 800 300 Q 400 350 0 300 L 0 0 L 800 0 L 800 300 Z"></path>
            </svg>
        </div>
        <header class="profile-curve-header">
            <h1><?php echo $isEdit ? "Edit Your Profile" : "Create Your Profile"; ?></h1>
            <p><?php echo $isEdit ? "Update your saved profile details below." : "Complete your profile to start using SkillLink. Your account email is already registered."; ?></p>
        </header>
    </div>

    <main class="profile-main">
        <div class="profile-layout">
            <div class="profile-form-column">
                <div class="edit-profile-card">
                    <?php if ($formError !== ""): ?>
                        <p style="color: #b00020; margin-bottom: 12px;"><?php echo htmlspecialchars($formError); ?></p>
                    <?php endif; ?>

                    <form class="profile-form" action="profile_process.php" method="post" enctype="multipart/form-data" id="profile-create-form">
                        <div id="panel-userinfo" class="profile-userinfo-block">
                            <h2 class="profile-section-heading">Personal Details</h2>
                            <h3 class="profile-subheading">User info</h3>
                            <div class="form-row two-cols">
                                <div class="form-group">
                                    <label for="fullname" class="visually-hidden">Full Name</label>
                                    <input type="text" id="fullname" name="fullname" placeholder="Enter full name" aria-label="Full name">
                                </div>
                                <div class="form-group">
                                    <label for="username" class="visually-hidden">Username</label>
                                    <input type="text" id="username" name="username" placeholder="Enter username" aria-label="Username" value="<?php echo htmlspecialchars($prefillUsername); ?>" required>
                                </div>
                            </div>
                            <div class="form-row two-cols">
                                <div class="form-group">
                                    <label for="email" class="visually-hidden">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($accountEmail); ?>" readonly aria-label="Email address">
                                </div>
                                <div class="form-group">
                                    <label for="display-name" class="visually-hidden">Display Name</label>
                                    <input type="text" id="display-name" name="display-name" placeholder="Enter display name" aria-label="Display name" value="<?php echo htmlspecialchars($prefillDisplayName); ?>" required>
                                </div>
                            </div>
                        </div>

                        <section class="profile-section profile-section-billing">
                            <h2 class="profile-section-heading">Billing Information</h2>
                            <h3 class="profile-subheading">Optional — add now or later</h3>
                            <div class="form-row two-cols">
                                <div class="form-group">
                                    <label for="billing_email" class="visually-hidden">Billing Email</label>
                                    <input type="email" id="billing_email" name="billing_email" placeholder="Billing email" aria-label="Billing email" value="<?php echo htmlspecialchars($prefillBillingEmail); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="card_brand" class="visually-hidden">Card Brand</label>
                                    <input type="text" id="card_brand" name="card_brand" placeholder="e.g. Visa" aria-label="Card brand" value="<?php echo htmlspecialchars($prefillCardBrand); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="billing_address" class="visually-hidden">Billing Address</label>
                                <input type="text" id="billing_address" name="billing_address" placeholder="Billing address" aria-label="Billing address" value="<?php echo htmlspecialchars($prefillBillingAddress); ?>">
                            </div>
                        </section>

                        <section class="profile-section seller-fields">
                            <h2 class="profile-section-heading">Profile Information</h2>
                            <div class="form-group photo-upload">
                                <label for="profile_photo">Profile Photo <span class="photo-optional-label">(optional)</span></label>
                                <div class="photo-dropzone photo-dropzone-compact" id="profile-photo-dropzone">
                                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp">
                                    <p class="photo-filename" id="photo-filename"><?php echo htmlspecialchars($photoFilenameLabel); ?></p>
                                    <span class="photo-placeholder">Click to choose a photo</span>
                                </div>
                                <p class="profile-upload-hint">JPG, PNG, GIF, or WebP — max 2 MB</p>
                            </div>
                            <div class="form-group">
                                <label for="category">Service Category</label>
                                <input type="hidden" name="category" id="category" value="<?php echo htmlspecialchars($prefillCategory); ?>">
                                <div class="dropdown-wrapper category-dropdown">
                                    <button type="button" class="dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                                        <span class="dropdown-trigger-text"><?php echo htmlspecialchars($prefillCategoryLabel); ?></span>
                                        <svg class="chevron-icon" viewBox="0 0 24 24" width="16" height="16">
                                            <path d="M7 10l5 5 5-5z" fill="currentColor"></path>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu" role="listbox">
                                        <div class="dropdown-item" data-value="tree-felling">Tree Felling</div>
                                        <div class="dropdown-item" data-value="plumbing">Plumbing</div>
                                        <div class="dropdown-item" data-value="electrical">Electrical</div>
                                        <div class="dropdown-item" data-value="gardening">Gardening</div>
                                        <div class="dropdown-item" data-value="painting">Painting</div>
                                        <div class="dropdown-item" data-value="cleaning">Cleaning</div>
                                        <div class="dropdown-item" data-value="carpentry">Carpentry</div>
                                        <div class="dropdown-item" data-value="other">Other</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Reviews (visible to buyers)</label>
                                <div class="reviews-preview">
                                    <p class="reviews-hint">Ratings will appear here as you receive them</p>
                                </div>
                            </div>
                        </section>

                        <div class="form-actions">
                            <button type="submit" class="btn-secondary btn-update-info"><?php echo $isEdit ? "Save changes" : "Create Profile"; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
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
    <script>
        (function() {
            var wrapper = document.querySelector('.category-dropdown');
            if (!wrapper) return;
            var trigger = wrapper.querySelector('.dropdown-trigger');
            var triggerText = wrapper.querySelector('.dropdown-trigger-text');
            var hiddenInput = document.getElementById('category');
            var items = wrapper.querySelectorAll('.dropdown-item');

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                wrapper.classList.toggle('active');
                trigger.setAttribute('aria-expanded', wrapper.classList.contains('active'));
            });

            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    var value = item.getAttribute('data-value');
                    var text = item.textContent.trim();
                    if (hiddenInput) hiddenInput.value = value;
                    if (triggerText) triggerText.textContent = text;
                    wrapper.classList.remove('active');
                    trigger.setAttribute('aria-expanded', 'false');
                });
            });

            document.addEventListener('click', function(e) {
                if (!wrapper.contains(e.target)) {
                    wrapper.classList.remove('active');
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        })();
    </script>
    <script>
        (function() {
            var dropzone = document.getElementById('profile-photo-dropzone');
            var input = document.getElementById('profile_photo');
            var filenameEl = document.getElementById('photo-filename');
            var defaultLabel = filenameEl ? filenameEl.textContent : 'No file selected';
            if (!dropzone || !input) return;

            dropzone.addEventListener('click', function() {
                input.click();
            });

            input.addEventListener('change', function() {
                var file = input.files && input.files[0];
                if (!filenameEl) return;
                filenameEl.textContent = file ? file.name : defaultLabel;
            });
        })();
    </script>
</body>
</html>

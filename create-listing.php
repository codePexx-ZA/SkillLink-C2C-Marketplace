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
 
$pdo = getPdo();
$userId = (int) $_SESSION["logged_in_user_id"];
requireSellerWithProfile($pdo, $userId);

$categories = getAllCategories($pdo);
$formError = "";

if (isset($_GET["error"])) {
    $code = $_GET["error"];
    if ($code === "missing") {
        $formError = "Please complete all required fields.";
    } elseif ($code === "price") {
        $formError = "Price must be a number greater than zero.";
    } elseif ($code === "category") {
        $formError = "Please choose a valid service category.";
    } elseif ($code === "server") {
        $formError = "Could not post your listing. Please try again.";
    } elseif (str_starts_with($code, "photo_")) {
        $formError = listingThumbnailUploadErrorMessage(substr($code, 6));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink — Create Listing</title>
    <link rel="stylesheet" href="listing-form.css">
</head>
<body class="listing-form-page">
    <nav class="nav-bar market-nav">
        <label class="hamburger" for="menu-toggle-create-listing">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <input type="checkbox" id="menu-toggle-create-listing" class="menu-toggle">
        <div class="nav-menu">
            <a href="market.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="OrdersPage.php">Orders</a>
            <a href="ContactUs.php">Contact Us</a>
            <div class="nav-menu-footer">
                <a href="logout.php">Log Out</a>
            </div>
        </div>
        <a href="market.php" class="logo">SkillLink</a>
    </nav>

    <main class="listing-form-main">
        <header class="listing-form-header">
            <h1>Create a Listing</h1>
            <p>Add a service listing for buyers to find on the market.</p>
        </header>

        <?php if ($formError !== ""): ?>
            <p class="form-error"><?php echo htmlspecialchars($formError); ?></p>
        <?php endif; ?>

        <?php if (count($categories) === 0): ?>
            <p class="form-error">No categories found. Run the category seed SQL in phpMyAdmin first.</p>
        <?php else: ?>
        <form class="listing-form-card" action="create-listing_process.php" method="post" enctype="multipart/form-data">
            <h2>Listing details</h2>

            <div class="form-group">
                <label for="business_name">Business / service name</label>
                <input type="text" id="business_name" name="business_name" placeholder="e.g. Thabo Tree Services" required>
            </div>

            <div class="form-group">
                <label for="category_id">Service category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category["id"]; ?>">
                            <?php echo htmlspecialchars($category["name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Describe your service, experience, and what buyers can expect." required></textarea>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g. Pretoria East" required>
            </div>

            <div class="form-row two-cols">
                <div class="form-group">
                    <label for="price_amount">Price (ZAR)</label>
                    <input type="number" id="price_amount" name="price_amount" min="0.01" step="0.01" placeholder="350.00" required>
                </div>
                <div class="form-group">
                    <label for="price_unit">Price unit</label>
                    <select id="price_unit" name="price_unit" required>
                        <option value="">Select unit</option>
                        <option value="per hour">per hour</option>
                        <option value="per job">per job</option>
                        <option value="per visit">per visit</option>
                        <option value="per project">per project</option>
                    </select>
                </div>
            </div>

            <?php renderListingThumbnailField("listing_thumbnail", null, true); ?>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Post listing</button>
            </div>
        </form>
        <?php endif; ?>

        <p class="back-link-wrap">
            <a href="market.php" class="btn-secondary">Back to market</a>
        </p>
    </main>
    <script>
        (function() {
            document.querySelectorAll('[data-thumbnail-dropzone]').forEach(function(dropzone) {
                var input = dropzone.querySelector('input[type="file"]');
                var filenameEl = dropzone.querySelector('[data-thumbnail-filename]');
                if (!input || !filenameEl) return;
                var defaultLabel = filenameEl.textContent;
                dropzone.addEventListener('click', function() { input.click(); });
                input.addEventListener('change', function() {
                    var file = input.files && input.files[0];
                    filenameEl.textContent = file ? file.name : defaultLabel;
                });
            });
        })();
    </script>
</body>
</html>

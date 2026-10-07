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
$listings = getSellerListings($pdo, $userId);

$formError = "";
$successMessage = "";

if (isset($_GET["saved"])) {
    $successMessage = "Listing saved successfully.";
}

if (isset($_GET["error"])) {
    $code = $_GET["error"];
    if ($code === "missing") {
        $formError = "Please complete all required fields.";
    } elseif ($code === "price") {
        $formError = "Price must be a number greater than zero.";
    } elseif ($code === "category") {
        $formError = "Please choose a valid service category.";
    } elseif ($code === "notfound") {
        $formError = "That listing could not be found.";
    } elseif ($code === "server") {
        $formError = "Could not save your listing. Please try again.";
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
    <title>SkillLink — Edit Listings</title>
    <link rel="stylesheet" href="listing-form.css">
</head>
<body class="listing-form-page">
    <nav class="nav-bar market-nav">
        <label class="hamburger" for="menu-toggle-edit-listings">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <input type="checkbox" id="menu-toggle-edit-listings" class="menu-toggle">
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
            <h1>Edit Listings</h1>
            <p>Update your saved listings below.</p>
        </header>

        <?php if ($successMessage !== ""): ?>
            <p class="form-success"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>

        <?php if ($formError !== ""): ?>
            <p class="form-error"><?php echo htmlspecialchars($formError); ?></p>
        <?php endif; ?>

        <?php if (count($listings) === 0): ?>
            <div class="listing-form-card">
                <p class="empty-listings-message">You have no listings yet. Create one from the market page.</p>
                <div class="form-actions">
                    <a href="create-listing.php" class="btn-primary">Add listing</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($listings as $listing): ?>
                <form class="edit-listing-card" action="edit-listing_process.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="listing_id" value="<?php echo (int) $listing["id"]; ?>">
                    <h2><?php echo htmlspecialchars($listing["business_name"]); ?></h2>
                    <div class="edit-listing-meta">
                        <span>Posted: <?php echo htmlspecialchars(date("j M Y", strtotime($listing["created_at"]))); ?></span>
                        <span class="edit-listing-status"><?php echo htmlspecialchars(formatListingStatus($listing["status"])); ?></span>
                    </div>

                    <div class="form-group">
                        <label for="business_name_<?php echo (int) $listing["id"]; ?>">Business / service name</label>
                        <input type="text" id="business_name_<?php echo (int) $listing["id"]; ?>" name="business_name" value="<?php echo htmlspecialchars($listing["business_name"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id_<?php echo (int) $listing["id"]; ?>">Service category</label>
                        <select id="category_id_<?php echo (int) $listing["id"]; ?>" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category["id"]; ?>" <?php echo (int) $listing["category_id"] === (int) $category["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($category["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description_<?php echo (int) $listing["id"]; ?>">Description</label>
                        <textarea id="description_<?php echo (int) $listing["id"]; ?>" name="description" required><?php echo htmlspecialchars($listing["description"]); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="location_<?php echo (int) $listing["id"]; ?>">Location</label>
                        <input type="text" id="location_<?php echo (int) $listing["id"]; ?>" name="location" value="<?php echo htmlspecialchars($listing["location"]); ?>" required>
                    </div>

                    <div class="form-row two-cols">
                        <div class="form-group">
                            <label for="price_amount_<?php echo (int) $listing["id"]; ?>">Price (ZAR)</label>
                            <input type="number" id="price_amount_<?php echo (int) $listing["id"]; ?>" name="price_amount" min="0.01" step="0.01" value="<?php echo htmlspecialchars((string) $listing["price_amount"]); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="price_unit_<?php echo (int) $listing["id"]; ?>">Price unit</label>
                            <select id="price_unit_<?php echo (int) $listing["id"]; ?>" name="price_unit" required>
                                <?php
                                $units = ["per hour", "per job", "per visit", "per project"];
                                foreach ($units as $unit):
                                ?>
                                    <option value="<?php echo htmlspecialchars($unit); ?>" <?php echo $listing["price_unit"] === $unit ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($unit); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php renderListingThumbnailField("listing_thumbnail_" . (int) $listing["id"], $listing["thumbnail_url"], false); ?>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save listing</button>
                    </div>
                </form>
            <?php endforeach; ?>
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

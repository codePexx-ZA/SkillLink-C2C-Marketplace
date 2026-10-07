<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/listing_helpers.php";
require_once __DIR__ . "/listing_thumbnail.php";

requireLogin();
require_once __DIR__ . "/nav_bar.php";

$isAdmin = isAdminSession();
$search = trim($_GET["q"] ?? "");
$sort = $_GET["sort"] ?? "newest";
$userId = (int) $_SESSION["logged_in_user_id"];

$allowedSort = [
    "newest" => "l.created_at DESC",
    "price-low" => "l.price_amount ASC",
    "price-high" => "l.price_amount DESC",
];

$orderBy = $allowedSort[$sort] ?? $allowedSort["newest"];
$listings = [];
$myListings = [];
$successMessage = "";

if (isset($_GET["posted"])) {
    $successMessage = "Your listing was posted and is pending review.";
}

try {
    $pdo = getPdo();
    $sql = "SELECT l.id, l.business_name, l.location, l.price_amount, l.price_unit, l.status, l.thumbnail_url, c.name AS category_name
            FROM listings l
            INNER JOIN categories c ON c.id = l.category_id
            LEFT JOIN profiles p ON p.user_id = l.seller_id
            WHERE l.status = 'active'";
    $params = [];

    if ($search !== "") {
        $sql .= " AND (
            l.business_name LIKE :q_listing
            OR p.display_name LIKE :q_profile
            OR p.username LIKE :q_username
        )";
        $searchTerm = "%" . $search . "%";
        $params[":q_listing"] = $searchTerm;
        $params[":q_profile"] = $searchTerm;
        $params[":q_username"] = $searchTerm;
    }

    $sql .= " ORDER BY {$orderBy} LIMIT 60";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();

    $myListings = $isAdmin ? [] : getSellerListings($pdo, $userId);
} catch (Throwable $exception) {
    $listings = [];
    $myListings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink — Market</title>
    <link rel="stylesheet" href="market.css?v=listing-image-fit">
    <link rel="stylesheet" href="app-nav.css">
</head>
<body class="market-page">
    <?php renderNavBar("menu-toggle", "market-nav"); ?>

    <main class="market-main">
        <?php if ($successMessage !== ""): ?>
            <p class="market-success"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>

        <div class="market-layout<?php echo $isAdmin ? " market-layout--no-sidebar" : ""; ?>">
            <?php if (!$isAdmin): ?>
            <aside class="market-sidebar">
                <a href="create-listing.php" class="btn-add-listing">Add listing</a>

                <section class="my-listings-panel">
                    <h2 class="my-listings-title">My listings</h2>
                    <?php if (count($myListings) === 0): ?>
                        <p class="my-listings-empty">No listings yet.</p>
                    <?php else: ?>
                        <ul class="my-listings-list">
                            <?php foreach ($myListings as $myListing): ?>
                                <li class="my-listing-item">
                                    <span class="my-listing-emoji"><?php echo htmlspecialchars(mapListingEmoji($myListing["category_name"])); ?></span>
                                    <div class="my-listing-copy">
                                        <p class="my-listing-name"><?php echo htmlspecialchars($myListing["business_name"]); ?></p>
                                        <p class="my-listing-meta">
                                            <?php echo htmlspecialchars(formatListingStatus($myListing["status"])); ?>
                                            · R<?php echo htmlspecialchars(number_format((float) $myListing["price_amount"], 0)); ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <a href="edit-listings.php" class="btn-edit-listings">Edit listings</a>
            </aside>
            <?php endif; ?>

            <div class="market-content">
                <form class="market-toolbar" method="get" action="market.php">
                    <div class="toolbar-left">
                        <div class="filter-dropdown">
                            <label for="sort">Sort</label>
                            <div class="dropdown-wrapper market-dropdown" data-dropdown="sort">
                                <button type="button" id="sort" class="dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="dropdown-trigger-text">
                                        <?php echo htmlspecialchars($sort === "price-low" ? "Price: Low to High" : ($sort === "price-high" ? "Price: High to Low" : "Newest")); ?>
                                    </span>
                                    <svg class="chevron-icon" viewBox="0 0 24 24" width="16" height="16">
                                        <path d="M7 10l5 5 5-5z" fill="currentColor"></path>
                                    </svg>
                                </button>
                                <div class="dropdown-menu" role="listbox">
                                    <div class="dropdown-item" data-value="newest">Newest</div>
                                    <div class="dropdown-item" data-value="price-high">Price: High to Low</div>
                                    <div class="dropdown-item" data-value="price-low">Price: Low to High</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="sort" id="sort-hidden" value="<?php echo htmlspecialchars($sort); ?>">
                    <div class="search-wrap">
                        <label class="search-label" for="market-search">Search</label>
                        <div class="search-field">
                            <input type="search" id="market-search" name="q" placeholder="Search by profile or listing name..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn-search">Search</button>
                        </div>
                    </div>
                </form>

                <section class="listings">
                    <?php if (count($listings) === 0): ?>
                        <article class="listing-card">
                            <div class="listing-image">ℹ️</div>
                            <div class="listing-body">
                                <h3>No listings found</h3>
                                <p class="listing-category">
                                    <?php if ($search !== ""): ?>
                                        No results for "<?php echo htmlspecialchars($search); ?>". Try a profile or listing name.
                                    <?php else: ?>
                                        Try a different search or add your own listing.
                                    <?php endif; ?>
                                </p>
                                <?php if (!$isAdmin): ?>
                                <p class="listing-location">Use Add listing on the left to get started.</p>
                                <?php endif; ?>
                                <span class="listing-price">—</span>
                            </div>
                        </article>
                    <?php else: ?>
                        <?php foreach ($listings as $listing): ?>
                            <?php $listingImageSrc = listingThumbnailSrc($listing["thumbnail_url"]); ?>
                            <article class="listing-card">
                                <div class="listing-image<?php echo $listingImageSrc ? " has-photo" : ""; ?>">
                                    <?php if ($listingImageSrc): ?>
                                        <img src="<?php echo htmlspecialchars($listingImageSrc); ?>" alt="<?php echo htmlspecialchars($listing["business_name"]); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(mapListingEmoji($listing["category_name"])); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="listing-body">
                                    <h3><?php echo htmlspecialchars($listing["business_name"]); ?></h3>
                                    <p class="listing-category"><?php echo htmlspecialchars($listing["category_name"]); ?></p>
                                    <p class="listing-location"><?php echo htmlspecialchars($listing["location"]); ?></p>
                                    <span class="listing-rating"><?php echo htmlspecialchars(formatListingStatus($listing["status"])); ?></span>
                                    <span class="listing-price">
                                        From R<?php echo htmlspecialchars(number_format((float) $listing["price_amount"], 2)); ?>
                                        <?php if (!empty($listing["price_unit"])): ?>
                                            /<?php echo htmlspecialchars($listing["price_unit"]); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
    <script>
        (function() {
            var wrappers = document.querySelectorAll('.market-dropdown');
            wrappers.forEach(function(wrapper) {
                var trigger = wrapper.querySelector('.dropdown-trigger');
                var triggerText = wrapper.querySelector('.dropdown-trigger-text');
                var items = wrapper.querySelectorAll('.dropdown-item');
                var sortHidden = document.getElementById('sort-hidden');

                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    wrappers.forEach(function(w) {
                        if (w !== wrapper) w.classList.remove('active');
                    });
                    wrapper.classList.toggle('active');
                    trigger.setAttribute('aria-expanded', wrapper.classList.contains('active'));
                });

                items.forEach(function(item) {
                    item.addEventListener('click', function() {
                        var text = item.textContent.trim();
                        var value = item.getAttribute('data-value');
                        if (triggerText) triggerText.textContent = text;
                        if (sortHidden) sortHidden.value = value;
                        wrapper.classList.remove('active');
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.closest('form').submit();
                    });
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.market-dropdown')) {
                    document.querySelectorAll('.market-dropdown').forEach(function(w) {
                        w.classList.remove('active');
                        var t = w.querySelector('.dropdown-trigger');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        })();
    </script>
</body>
</html>

<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/admin_helpers.php";

requireAdminLogin();

$adminUserId = (int) $_SESSION["logged_in_user_id"];
$stats = [];
$pendingListings = [];
$disputes = [];
$manageableUsers = [];
$completedAccountActions = [];
$userEmailSearch = trim($_GET["user_q"] ?? "");
$adminTab = trim($_GET["tab"] ?? "current");
$adminName = "Administrator";
$flashMessage = "";
$flashType = "success";

if (isset($_GET["listing_approved"])) {
    $flashMessage = "Listing approved and is now live on the market.";
} elseif (isset($_GET["listing_rejected"])) {
    $flashMessage = "Listing rejected and archived.";
} elseif (isset($_GET["user_applied"])) {
    $flashMessage = "Account action applied and recorded in Completed Accounts.";
} elseif (isset($_GET["error"])) {
    $flashType = "error";
    $code = $_GET["error"];
    if ($code === "invalid_request") {
        $flashMessage = "Invalid approval request.";
    } elseif ($code === "listing_not_updated") {
        $flashMessage = "That listing could not be updated. It may already have been reviewed.";
    } elseif ($code === "user_action_failed") {
        $flashMessage = "That account action could not be completed.";
    } else {
        $flashMessage = "Something went wrong. Please try again.";
    }
}

try {
    $pdo = getPdo();
    $adminName = getAdminDisplayName($pdo, $adminUserId);
    $stats = getAdminDashboardStats($pdo);
    $pendingListings = getPendingListingApprovals($pdo);
    $disputes = getRecentDisputes($pdo);
    $manageableUsers = getManageableUsers($pdo, $userEmailSearch);
    $completedAccountActions = getCompletedAccountActions($pdo);
} catch (Throwable $exception) {
    $stats = [
        "total_users" => 0,
        "users_this_week" => 0,
        "active_listings" => 0,
        "pending_review" => 0,
        "open_disputes" => 0,
        "unassigned_disputes" => 0,
        "pending_payout_total" => 0.0,
        "pending_payout_sellers" => 0,
        "completed_account_actions" => 0,
    ];
    if ($flashMessage === "") {
        $flashType = "error";
        $flashMessage = "Could not load dashboard data. Check your database connection.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Admin Dashboard</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="app-nav.css">
</head>
<body class="admin-page">
    <?php
    require_once __DIR__ . "/nav_bar.php";
    renderNavBar("menu-toggle-admin", "admin-nav", "admin-dashboard.php");
    ?>

    <main class="admin-main">
        <section class="admin-header-row" aria-label="Dashboard header">
            <div class="admin-header admin-header-welcome">
                <p class="admin-welcome-eyebrow">Welcome back</p>
                <h2 class="admin-welcome-name"><?php echo htmlspecialchars($adminName); ?></h2>
                <p class="admin-welcome-meta">Signed in as Administrator</p>
            </div>
            <div class="admin-header admin-header-title">
                <h1 class="admin-title">SkillLink Admin Dashboard</h1>
                <p class="admin-subtitle">Listings, disputes, and user accounts — live data</p>
            </div>
        </section>

        <?php if ($flashMessage !== ""): ?>
            <p class="admin-flash admin-flash-<?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flashMessage); ?>
            </p>
        <?php endif; ?>

        <section class="admin-stats admin-stats-4" aria-label="Key metrics">
            <article class="admin-stat-card">
                <p class="admin-stat-label">Total Users</p>
                <p class="admin-stat-value"><?php echo (int) $stats["total_users"]; ?></p>
                <p class="admin-stat-meta">
                    <?php if ((int) $stats["users_this_week"] > 0): ?>
                        +<?php echo (int) $stats["users_this_week"]; ?> this week
                    <?php else: ?>
                        No new users this week
                    <?php endif; ?>
                </p>
            </article>
            <article class="admin-stat-card">
                <p class="admin-stat-label">Active Listings</p>
                <p class="admin-stat-value"><?php echo (int) $stats["active_listings"]; ?></p>
                <p class="admin-stat-meta">
                    <?php echo (int) $stats["pending_review"]; ?> awaiting approval
                </p>
            </article>
            <article class="admin-stat-card">
                <p class="admin-stat-label">Open Disputes</p>
                <p class="admin-stat-value"><?php echo (int) $stats["open_disputes"]; ?></p>
                <p class="admin-stat-meta">
                    <?php echo (int) $stats["unassigned_disputes"]; ?> unassigned
                </p>
            </article>
            <article class="admin-stat-card">
                <p class="admin-stat-label">Account Actions</p>
                <p class="admin-stat-value"><?php echo (int) $stats["completed_account_actions"]; ?></p>
                <p class="admin-stat-meta">
                    <?php if ((int) $stats["completed_account_actions"] > 0): ?>
                        Recorded in completed history
                    <?php else: ?>
                        No account actions yet
                    <?php endif; ?>
                </p>
            </article>
        </section>

        <section class="admin-stack">
            <section class="admin-card">
                <h2 class="admin-card-title">Listing Approval Queue</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Listing</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendingListings) === 0): ?>
                                <tr>
                                    <td colspan="6" class="admin-empty-cell">No listings waiting for approval.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingListings as $listing): ?>
                                    <?php $badge = listingReviewBadge((int) $listing["flag_count"]); ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($listing["business_name"]); ?>
                                            <span class="admin-row-meta">
                                                R<?php echo htmlspecialchars(number_format((float) $listing["price_amount"], 0)); ?>
                                                <?php if (!empty($listing["price_unit"])): ?>
                                                    / <?php echo htmlspecialchars($listing["price_unit"]); ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($listing["seller_name"]); ?></td>
                                        <td><?php echo htmlspecialchars($listing["category_name"]); ?></td>
                                        <td><?php echo htmlspecialchars($listing["location"]); ?></td>
                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($badge["class"]); ?>">
                                                <?php echo htmlspecialchars($badge["label"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" action="admin-listing_process.php" class="inline-actions">
                                                <input type="hidden" name="listing_id" value="<?php echo (int) $listing["id"]; ?>">
                                                <button type="submit" name="action" value="approve" class="btn-chip btn-chip-approve">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn-chip btn-chip-reject">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card">
                <h2 class="admin-card-title">Recent Disputes and Safety Cases</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Summary</th>
                                <th>Buyer vs Seller</th>
                                <th>Status</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($disputes) === 0): ?>
                                <tr>
                                    <td colspan="5" class="admin-empty-cell">No disputes on record.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($disputes as $dispute): ?>
                                    <?php
                                    $statusBadge = disputeStatusBadge($dispute["status"]);
                                    $summary = $dispute["description"];
                                    if (strlen($summary) > 72) {
                                        $summary = substr($summary, 0, 69) . "...";
                                    }
                                    ?>
                                    <tr>
                                        <td>#DS-<?php echo (int) $dispute["id"]; ?></td>
                                        <td><?php echo htmlspecialchars($summary); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($dispute["buyer_name"]); ?>
                                            vs
                                            <?php echo htmlspecialchars($dispute["seller_name"]); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($statusBadge["class"]); ?>">
                                                <?php echo htmlspecialchars($statusBadge["label"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($dispute["admin_name"])): ?>
                                                Admin: <?php echo htmlspecialchars($dispute["admin_name"]); ?>
                                            <?php else: ?>
                                                <span class="admin-row-meta">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-card admin-user-card">
                    <h2 class="admin-card-title">User Profile Management</h2>

                    <div class="admin-tabs" role="tablist" aria-label="User account tabs">
                        <button
                            type="button"
                            class="admin-tab <?php echo $adminTab !== "completed" ? "admin-tab-active" : ""; ?>"
                            role="tab"
                            aria-selected="<?php echo $adminTab !== "completed" ? "true" : "false"; ?>"
                            aria-controls="tab-current-accounts"
                            id="tab-btn-current"
                            data-admin-tab="current"
                        >
                            Current Accounts
                        </button>
                        <button
                            type="button"
                            class="admin-tab <?php echo $adminTab === "completed" ? "admin-tab-active" : ""; ?>"
                            role="tab"
                            aria-selected="<?php echo $adminTab === "completed" ? "true" : "false"; ?>"
                            aria-controls="tab-completed-accounts"
                            id="tab-btn-completed"
                            data-admin-tab="completed"
                        >
                            Completed Accounts
                        </button>
                    </div>

                    <div
                        class="admin-tab-panel <?php echo $adminTab !== "completed" ? "admin-tab-panel-active" : ""; ?>"
                        id="tab-current-accounts"
                        role="tabpanel"
                        aria-labelledby="tab-btn-current"
                        <?php echo $adminTab === "completed" ? "hidden" : ""; ?>
                    >
                        <p class="admin-tab-intro">Choose an action to apply instantly. Ignored accounts are logged without changing status.</p>
                        <form method="get" action="admin-dashboard.php" class="admin-user-search-form">
                            <div class="search-wrap">
                                <label class="search-label" for="admin-user-search">Search</label>
                                <div class="search-field">
                                    <input
                                        type="search"
                                        id="admin-user-search"
                                        name="user_q"
                                        placeholder="Search by user email..."
                                        class="search-input"
                                        value="<?php echo htmlspecialchars($userEmailSearch); ?>"
                                    >
                                    <button type="submit" class="btn-search">Search</button>
                                </div>
                            </div>
                        </form>
                        <div class="admin-table-wrap">
                            <table class="admin-table admin-user-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($manageableUsers) === 0): ?>
                                        <tr>
                                            <td colspan="3" class="admin-empty-cell">
                                                <?php if ($userEmailSearch !== ""): ?>
                                                    No users found for "<?php echo htmlspecialchars($userEmailSearch); ?>".
                                                <?php else: ?>
                                                    No user accounts to manage.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($manageableUsers as $managedUser): ?>
                                            <?php
                                            $managedUserId = (int) $managedUser["id"];
                                            $statusBadge = accountStatusBadge((string) $managedUser["account_status"]);
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($managedUser["display_name"]); ?>
                                                    <span class="admin-row-meta">
                                                        <?php echo htmlspecialchars($managedUser["email"]); ?>
                                                        <?php if (!empty($managedUser["username"])): ?>
                                                            · @<?php echo htmlspecialchars($managedUser["username"]); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo htmlspecialchars($statusBadge["class"]); ?>">
                                                        <?php echo htmlspecialchars($statusBadge["label"]); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="post" action="admin-user_process.php" class="inline-actions">
                                                        <input type="hidden" name="user_id" value="<?php echo $managedUserId; ?>">
                                                        <input type="hidden" name="action" value="apply">
                                                        <button type="submit" name="user_action" value="promote" class="btn-chip btn-chip-promote">Promote</button>
                                                        <button type="submit" name="user_action" value="restrict" class="btn-chip btn-chip-restrict">Restrict</button>
                                                        <button type="submit" name="user_action" value="delete" class="btn-chip btn-chip-delete">Delete</button>
                                                        <button type="submit" name="user_action" value="ignore" class="btn-chip btn-chip-ignore">Ignore</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="admin-tab-panel <?php echo $adminTab === "completed" ? "admin-tab-panel-active" : ""; ?>"
                        id="tab-completed-accounts"
                        role="tabpanel"
                        aria-labelledby="tab-btn-completed"
                        <?php echo $adminTab !== "completed" ? "hidden" : ""; ?>
                    >
                        <p class="admin-tab-intro">History of applied account changes.</p>
                        <div class="admin-table-wrap">
                            <table class="admin-table admin-user-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Result</th>
                                        <th>Admin</th>
                                        <th>Completed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($completedAccountActions) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="admin-empty-cell">No completed account changes yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($completedAccountActions as $historyRow): ?>
                                            <?php
                                            if ($historyRow["action"] === "ignore") {
                                                $resultBadge = ["class" => "badge-muted", "label" => "Unchanged"];
                                            } else {
                                                $resultBadge = accountStatusBadge((string) $historyRow["account_status"]);
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($historyRow["display_name"]); ?>
                                                    <span class="admin-row-meta"><?php echo htmlspecialchars($historyRow["email"]); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars(accountActionLabel($historyRow["action"])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo htmlspecialchars($resultBadge["class"]); ?>">
                                                        <?php echo htmlspecialchars($resultBadge["label"]); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($historyRow["admin_name"]); ?></td>
                                                <td>
                                                    <?php if (!empty($historyRow["completed_at"])): ?>
                                                        <?php echo htmlspecialchars(date("j M Y H:i", strtotime($historyRow["completed_at"]))); ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
        </section>
    </main>

    <script>
        (function() {
            var tabs = document.querySelectorAll("[data-admin-tab]");
            var panels = document.querySelectorAll(".admin-tab-panel");

            tabs.forEach(function(tab) {
                tab.addEventListener("click", function() {
                    var target = tab.getAttribute("data-admin-tab");

                    tabs.forEach(function(item) {
                        var isActive = item === tab;
                        item.classList.toggle("admin-tab-active", isActive);
                        item.setAttribute("aria-selected", isActive ? "true" : "false");
                    });

                    panels.forEach(function(panel) {
                        var isCurrent = panel.id === "tab-" + target + "-accounts";
                        panel.classList.toggle("admin-tab-panel-active", isCurrent);
                        panel.hidden = !isCurrent;
                    });
                });
            });
        })();
    </script>
</body>
</html>

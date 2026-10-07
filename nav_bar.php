<?php
require_once __DIR__ . "/auth.php";

function renderNavBar(string $toggleId, string $navClass = "market-nav", ?string $logoHref = null): void
{
    ensureSessionStarted();

    $isAdmin = isAdminSession();
    $logoHref = $logoHref ?? ($isAdmin ? "admin-dashboard.php" : "market.php");
    $logoText = $isAdmin ? "SkillLink Admin" : "SkillLink";
    $adminClass = $isAdmin ? " nav-admin-session" : "";
    $userStatusLabel = "";
    $userStatusClass = "";

    if (!$isAdmin && !empty($_SESSION["logged_in_user_id"])) {
        require_once __DIR__ . "/db.php";
        $pdo = getPdo();
        $accountStatus = getUserAccountStatus($pdo, (int) $_SESSION["logged_in_user_id"]);
        $userStatusLabel = accountStatusDisplayLabel($accountStatus);
        $userStatusClass = navAccountStatusClass($accountStatus);
    }
    ?>
    <nav class="nav-bar <?php echo htmlspecialchars($navClass . $adminClass); ?>">
        <input type="checkbox" id="<?php echo htmlspecialchars($toggleId); ?>" class="menu-toggle">
        <label class="hamburger" for="<?php echo htmlspecialchars($toggleId); ?>">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <div class="nav-menu">
            <?php if ($isAdmin): ?>
                <p class="nav-menu-admin-badge">Signed in as Admin</p>
                <a href="admin-dashboard.php">Admin Dashboard</a>
            <?php elseif ($userStatusLabel !== ""): ?>
                <p class="nav-menu-user-badge <?php echo htmlspecialchars($userStatusClass); ?>">
                    Account: <?php echo htmlspecialchars($userStatusLabel); ?>
                </p>
            <?php endif; ?>
            <a href="market.php">Home</a>
            <a href="profile.php">Profile</a>
            <a href="OrdersPage.php">Orders</a>
            <a href="ContactUs.php">Contact Us</a>
            <div class="nav-menu-footer">
                <?php if ($isAdmin): ?>
                    <span class="nav-menu-role">Administrator</span>
                <?php endif; ?>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
        <a href="<?php echo htmlspecialchars($logoHref); ?>" class="logo"><?php echo htmlspecialchars($logoText); ?></a>
    </nav>
    <?php
}

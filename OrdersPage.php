<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/listing_thumbnail.php";

requireLogin();
require_once __DIR__ . "/nav_bar.php";

$userId = (int) $_SESSION["logged_in_user_id"];
$orders = [];
$message = "";
 
try {
    $pdo = getPdo();

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_order_id"])) {
        $cancelOrderId = (int) $_POST["cancel_order_id"];
        $cancelStmt = $pdo->prepare(
            "UPDATE orders
             SET status = 'cancelled', cancelled_at = NOW()
             WHERE id = :id AND buyer_id = :buyer_id AND status IN ('placed', 'paid', 'confirmed')"
        );
        $cancelStmt->execute([
            ":id" => $cancelOrderId,
            ":buyer_id" => $userId,
        ]);
        $message = $cancelStmt->rowCount() > 0 ? "Order cancelled." : "Order cannot be cancelled in its current state.";
    }

    $stmt = $pdo->prepare(
        "SELECT o.id, o.status, o.price_snapshot, o.ordered_at, o.paid_at, o.confirmed_at, o.completed_at, o.cancelled_at,
                l.business_name, l.thumbnail_url
         FROM orders o
         INNER JOIN listings l ON l.id = o.listing_id
         WHERE o.buyer_id = :buyer_id
         ORDER BY o.ordered_at DESC
         LIMIT 50"
    );
    $stmt->execute([":buyer_id" => $userId]);
    $orders = $stmt->fetchAll();
} catch (Throwable $exception) {
    $orders = [];
    if ($message === "") {
        $message = "Could not load orders right now.";
    }
}

function stepComplete(array $order, string $step): bool
{
    $status = $order["status"];
    $rank = [
        "placed" => 1,
        "paid" => 2,
        "confirmed" => 3,
        "completed" => 4,
        "cancelled" => 4,
        "disputed" => 4,
    ];
    $required = [
        "ordered" => 1,
        "paid" => 2,
        "confirmed" => 3,
        "completed" => 4,
    ];
    return ($rank[$status] ?? 0) >= ($required[$step] ?? 99);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink — Orders</title>
    <link href="https://fonts.googleapis.com/css?family=Titillium+Web:400,200,300,600,700" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="OrdersPage.css">
    <link rel="stylesheet" href="app-nav.css">
</head>
<body class="orders-page">
    <?php renderNavBar("menu-toggle-orders", "market-nav"); ?>

    <main class="orders-main">
        <h1 class="orders-page-title">My Orders</h1>
        <?php if ($message !== ""): ?>
            <p style="margin: 0 0 16px 0; color: #285A48; font-weight: 600;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <section class="orders-list">
            <?php if (count($orders) === 0): ?>
                <article class="order-card">
                    <div class="order-info">
                        <p class="order-desc">No orders yet.</p>
                        <span class="order-price">Place your first order from the market.</span>
                    </div>
                </article>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <article class="order-card">
                        <div class="order-thumb">
                            <?php $orderThumb = listingThumbnailSrc($order["thumbnail_url"]); ?>
                            <img src="<?php echo htmlspecialchars($orderThumb ?: "https://picsum.photos/seed/order/200/150"); ?>" alt="Order item">
                        </div>
                        <div class="order-info">
                            <p class="order-desc"><?php echo htmlspecialchars($order["business_name"]); ?></p>
                            <span class="order-price">R<?php echo htmlspecialchars(number_format((float) $order["price_snapshot"], 2)); ?></span>
                        </div>
                        <div class="order-timeline">
                            <ul class="timeline">
                                <li class="li <?php echo stepComplete($order, "ordered") ? "complete" : ""; ?>">
                                    <div class="timestamp"><span class="date"><?php echo htmlspecialchars(date("d/m/Y", strtotime($order["ordered_at"]))); ?></span></div>
                                    <div class="status"><h4>Ordered</h4></div>
                                </li>
                                <li class="li <?php echo stepComplete($order, "paid") ? "complete" : ""; ?>">
                                    <div class="timestamp"><span class="date"><?php echo !empty($order["paid_at"]) ? htmlspecialchars(date("d/m/Y", strtotime($order["paid_at"]))) : "—"; ?></span></div>
                                    <div class="status"><h4>Payment</h4></div>
                                </li>
                                <li class="li <?php echo stepComplete($order, "confirmed") ? "complete" : ""; ?>">
                                    <div class="timestamp"><span class="date"><?php echo !empty($order["confirmed_at"]) ? htmlspecialchars(date("d/m/Y", strtotime($order["confirmed_at"]))) : "—"; ?></span></div>
                                    <div class="status"><h4>Confirmation</h4></div>
                                </li>
                                <li class="li <?php echo stepComplete($order, "completed") ? "complete" : ""; ?>">
                                    <div class="timestamp"><span class="date"><?php echo !empty($order["completed_at"]) ? htmlspecialchars(date("d/m/Y", strtotime($order["completed_at"]))) : (!empty($order["cancelled_at"]) ? htmlspecialchars(date("d/m/Y", strtotime($order["cancelled_at"]))) : "—"); ?></span></div>
                                    <div class="status"><h4><?php echo $order["status"] === "cancelled" ? "Cancelled" : "Product Delivered / Service Completed"; ?></h4></div>
                                </li>
                            </ul>
                        </div>
                        <div class="order-actions">
                            <button type="button" class="btn-track"><?php echo htmlspecialchars(ucfirst($order["status"])); ?></button>
                            <?php if (in_array($order["status"], ["placed", "paid", "confirmed"], true)): ?>
                                <form method="post" action="OrdersPage.php">
                                    <input type="hidden" name="cancel_order_id" value="<?php echo (int) $order["id"]; ?>">
                                    <button type="submit" class="btn-cancel">Cancel Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

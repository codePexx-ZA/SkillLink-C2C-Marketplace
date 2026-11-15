<?php
function getAdminDisplayName(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare(
        "SELECT p.display_name, u.email
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.id = :user_id
         LIMIT 1"
    );
    $stmt->execute([":user_id" => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return "Administrator";
    }

    if (!empty($row["display_name"])) {
        return $row["display_name"];
    }

    $email = (string) $row["email"];
    $local = strstr($email, "@", true);

    return $local !== false ? ucfirst(str_replace([".", "_", "-"], " ", $local)) : $email;
}

function getAdminDashboardStats(PDO $pdo): array
{
    $counts = $pdo->query(
        "SELECT user_counts.total_users, user_counts.users_this_week,
                listing_counts.active_listings, listing_counts.pending_review,
                dispute_counts.open_disputes, dispute_counts.unassigned_disputes,
                action_counts.completed_account_actions
         FROM (
             SELECT COUNT(*) AS total_users,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS users_this_week
             FROM users
         ) AS user_counts
         CROSS JOIN (
             SELECT COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_listings,
                    COUNT(CASE WHEN status = 'pending_review' THEN 1 END) AS pending_review
             FROM listings
             WHERE status IN ('active', 'pending_review')
         ) AS listing_counts
         CROSS JOIN (
             SELECT COUNT(*) AS open_disputes,
                    COUNT(CASE WHEN assigned_admin_id IS NULL THEN 1 END) AS unassigned_disputes
             FROM disputes
             WHERE status = 'open'
         ) AS dispute_counts
         CROSS JOIN (
             SELECT COUNT(*) AS completed_account_actions
             FROM user_account_actions
             WHERE status = 'completed'
         ) AS action_counts"
    )->fetch(PDO::FETCH_ASSOC);

    return [
        "total_users" => (int) $counts["total_users"],
        "users_this_week" => (int) $counts["users_this_week"],
        "active_listings" => (int) $counts["active_listings"],
        "pending_review" => (int) $counts["pending_review"],
        "open_disputes" => (int) $counts["open_disputes"],
        "unassigned_disputes" => (int) $counts["unassigned_disputes"],
        "pending_payout_total" => 0.0,
        "pending_payout_sellers" => 0,
        "completed_account_actions" => (int) $counts["completed_account_actions"],
    ];
}

function getPendingListingApprovals(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT l.id, l.business_name, l.location, l.created_at, l.price_amount, l.price_unit,
                c.name AS category_name,
                COALESCE(p.display_name, u.email) AS seller_name,
                (
                    SELECT COUNT(*)
                    FROM moderation_flags mf
                    WHERE mf.listing_id = l.id
                ) AS flag_count
         FROM listings l
         INNER JOIN users u ON u.id = l.seller_id
         LEFT JOIN profiles p ON p.user_id = u.id
         INNER JOIN categories c ON c.id = l.category_id
         WHERE l.status = 'pending_review'
         ORDER BY l.created_at ASC"
    );

    return $stmt->fetchAll() ?: [];
}

function getRecentDisputes(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->prepare(
        "SELECT d.id, d.description, d.status, d.created_at,
                COALESCE(bp.display_name, bu.email) AS buyer_name,
                COALESCE(sp.display_name, su.email) AS seller_name,
                COALESCE(ap.display_name, au.email) AS admin_name
         FROM disputes d
         INNER JOIN users bu ON bu.id = d.buyer_id
         INNER JOIN users su ON su.id = d.seller_id
         LEFT JOIN profiles bp ON bp.user_id = d.buyer_id
         LEFT JOIN profiles sp ON sp.user_id = d.seller_id
         LEFT JOIN users au ON au.id = d.assigned_admin_id
         LEFT JOIN profiles ap ON ap.user_id = d.assigned_admin_id
         ORDER BY d.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function getPendingVerifications(PDO $pdo, int $limit = 8): array
{
    $stmt = $pdo->prepare(
        "SELECT v.id, v.notes, v.created_at,
                COALESCE(p.display_name, u.email) AS seller_name,
                p.primary_category_slug
         FROM verifications v
         INNER JOIN users u ON u.id = v.user_id
         LEFT JOIN profiles p ON p.user_id = v.user_id
         WHERE v.status = 'pending'
         ORDER BY v.created_at ASC
         LIMIT :limit"
    );
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function getPayoutBatches(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare(
        "SELECT pb.id, pb.batch_code, pb.total_amount, pb.status, pb.created_at,
                COUNT(po.id) AS payout_count,
                SUM(CASE WHEN po.status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
                SUM(CASE WHEN po.status = 'pending' THEN 1 ELSE 0 END) AS pending_count
         FROM payout_batches pb
         LEFT JOIN payouts po ON po.batch_id = pb.id
         GROUP BY pb.id, pb.batch_code, pb.total_amount, pb.status, pb.created_at
         ORDER BY pb.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function getFailedPayoutCount(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM payouts WHERE status = 'failed'"
    )->fetchColumn();
}

function formatMoney(float $amount): string
{
    return "R" . number_format($amount, 0, ".", ",");
}

function disputeStatusBadge(string $status): array
{
    if ($status === "open") {
        return ["class" => "badge-warning", "label" => "Open"];
    }

    return ["class" => "badge-muted", "label" => "Closed"];
}

function listingReviewBadge(int $flagCount): array
{
    if ($flagCount > 0) {
        return ["class" => "badge-warning", "label" => "Flagged"];
    }

    return ["class" => "badge-success", "label" => "Awaiting review"];
}

function payoutBatchStatusLabel(string $status): string
{
    return ucfirst($status);
}

function accountStatusBadge(string $status): array
{
    $map = [
        "active" => ["class" => "badge-success", "label" => "Online"],
        "promoted" => ["class" => "badge-promoted", "label" => "Promoted"],
        "restricted" => ["class" => "badge-warning", "label" => "Restricted"],
        "deleted" => ["class" => "badge-danger", "label" => "Deleted"],
    ];

    return $map[$status] ?? $map["active"];
}

function accountActionLabel(string $action): string
{
    $labels = [
        "promote" => "Promote",
        "restrict" => "Restrict",
        "delete" => "Delete",
        "ignore" => "Ignore",
    ];

    return $labels[$action] ?? ucfirst($action);
}

function getManageableUsers(PDO $pdo, string $emailSearch = ""): array
{
    $sql = "SELECT u.id, u.email, u.account_status, u.created_at,
                   COALESCE(p.display_name, u.email) AS display_name,
                   p.username
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            WHERE u.role = 'user'";
    $params = [];

    if ($emailSearch !== "") {
        $sql .= " AND u.email LIKE :email_search";
        $params[":email_search"] = "%" . $emailSearch . "%";
    }

    $sql .= " ORDER BY COALESCE(p.display_name, u.email) ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

function applyAccountActionImmediate(PDO $pdo, int $userId, int $adminId, string $action): bool
{
    $allowed = ["promote", "restrict", "delete", "ignore"];
    if (!in_array($action, $allowed, true)) {
        return false;
    }

    $userStmt = $pdo->prepare(
        "SELECT id, role FROM users WHERE id = :id LIMIT 1"
    );
    $userStmt->execute([":id" => $userId]);
    $user = $userStmt->fetch();

    if (!$user || $user["role"] === "admin") {
        return false;
    }

    $statusMap = [
        "promote" => "promoted",
        "restrict" => "restricted",
        "delete" => "deleted",
    ];

    $pdo->beginTransaction();

    try {
        if ($action !== "ignore") {
            $newStatus = $statusMap[$action];
            $updateUser = $pdo->prepare(
                "UPDATE users
                 SET account_status = :status
                 WHERE id = :user_id AND role = 'user'"
            );
            $updateUser->execute([
                ":status" => $newStatus,
                ":user_id" => $userId,
            ]);
        }

        $cancelStmt = $pdo->prepare(
            "UPDATE user_account_actions
             SET status = 'cancelled', completed_at = NOW()
             WHERE user_id = :user_id AND status = 'pending'"
        );
        $cancelStmt->execute([":user_id" => $userId]);

        $insertStmt = $pdo->prepare(
            "INSERT INTO user_account_actions (user_id, admin_id, action, status, completed_at)
             VALUES (:user_id, :admin_id, :action, 'completed', NOW())"
        );
        $insertStmt->execute([
            ":user_id" => $userId,
            ":admin_id" => $adminId,
            ":action" => $action,
        ]);

        if ($insertStmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function getCompletedAccountActions(PDO $pdo, int $limit = 50): array
{
    $stmt = $pdo->prepare(
        "SELECT a.id, a.user_id, a.action, a.status, a.created_at, a.completed_at,
                u.email, u.account_status,
                COALESCE(p.display_name, u.email) AS display_name,
                p.username,
                COALESCE(ap.display_name, au.email) AS admin_name
         FROM user_account_actions a
         INNER JOIN users u ON u.id = a.user_id
         LEFT JOIN profiles p ON p.user_id = u.id
         LEFT JOIN users au ON au.id = a.admin_id
         LEFT JOIN profiles ap ON ap.user_id = a.admin_id
         WHERE a.status = 'completed'
         ORDER BY a.completed_at DESC, a.created_at DESC
         LIMIT :limit"
    );
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function updateListingStatus(PDO $pdo, int $listingId, string $action): bool
{
    $transitions = [
        "approve" => ["from" => ["pending_review"], "to" => "active"],
        "reject" => ["from" => ["pending_review"], "to" => "archived"],
        "suspend" => ["from" => ["active"], "to" => "suspended"],
        "archive" => ["from" => ["active", "suspended"], "to" => "archived"],
    ];

    if (!isset($transitions[$action])) {
        return false;
    }

    $rule = $transitions[$action];
    $placeholders = implode(", ", array_fill(0, count($rule["from"]), "?"));

    $stmt = $pdo->prepare(
        "UPDATE listings
         SET status = ?
         WHERE id = ?
           AND status IN ({$placeholders})"
    );

    $params = array_merge([$rule["to"], $listingId], $rule["from"]);
    $stmt->execute($params);

    return $stmt->rowCount() > 0;
}

<?php
function mapListingEmoji(string $categoryName): string
{
    $key = strtolower(trim($categoryName));
    $map = [
        "tree felling" => "🌳",
        "plumbing" => "🔧",
        "electrical" => "⚡",
        "gardening" => "🌿",
        "painting" => "🎨",
        "cleaning" => "🧹",
        "carpentry" => "🪚",
        "other" => "🛠️",
    ];

    return $map[$key] ?? "🛠️";
}

function formatListingStatus(string $status): string
{
    return ucfirst(str_replace("_", " ", $status));
}

function getAllCategories(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, slug, name FROM categories ORDER BY name ASC");
    return $stmt->fetchAll() ?: [];
}

function getSellerListings(PDO $pdo, int $sellerId): array
{
    $stmt = $pdo->prepare(
        "SELECT l.id, l.business_name, l.description, l.location, l.price_amount, l.price_unit,
                l.price_snapshot, l.thumbnail_url, l.status, l.category_id, l.created_at,
                c.name AS category_name, c.slug AS category_slug
         FROM listings l
         INNER JOIN categories c ON c.id = l.category_id
         WHERE l.seller_id = :seller_id
         ORDER BY l.created_at DESC"
    );
    $stmt->execute([":seller_id" => $sellerId]);
    return $stmt->fetchAll() ?: [];
}

function requireSellerWithProfile(PDO $pdo, int $userId): void
{
    if (!userHasProfile($pdo, $userId)) {
        header("Location: profile-creation.php");
        exit;
    }
}

function parseListingFormData(array $post): array
{
    return [
        "business_name" => trim($post["business_name"] ?? ""),
        "category_id" => (int) ($post["category_id"] ?? 0),
        "description" => trim($post["description"] ?? ""),
        "location" => trim($post["location"] ?? ""),
        "price_amount" => trim($post["price_amount"] ?? ""),
        "price_unit" => trim($post["price_unit"] ?? ""),
    ];
}

function validateListingData(array $data): ?string
{
    if ($data["business_name"] === ""
        || $data["description"] === ""
        || $data["location"] === ""
        || $data["price_unit"] === ""
        || $data["category_id"] <= 0) {
        return "missing";
    }

    if (!is_numeric($data["price_amount"]) || (float) $data["price_amount"] <= 0) {
        return "price";
    }

    return null;
}

function categoryExists(PDO $pdo, int $categoryId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $categoryId]);
    return (bool) $stmt->fetch();
}

function listingBelongsToSeller(PDO $pdo, int $listingId, int $sellerId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM listings WHERE id = :id AND seller_id = :seller_id LIMIT 1");
    $stmt->execute([
        ":id" => $listingId,
        ":seller_id" => $sellerId,
    ]);
    return (bool) $stmt->fetch();
}

<?php
function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $httpHost = $_SERVER["HTTP_HOST"] ?? "";
    $isLocal = strpos($httpHost, "localhost") !== false
        || strpos($httpHost, "127.0.0.1") !== false;

    if ($isLocal) {
        $host = getenv("DB_HOST") ?: "127.0.0.1";
        $dbName = getenv("DB_NAME") ?: "skilllink";
        $dbUser = getenv("DB_USER") ?: "root";
        $dbPass = getenv("DB_PASS") ?: "";
    } else {
        $host = getenv("DB_HOST") ?: "PUT_YOUR_DB_HOST_HERE";
        $dbName = getenv("DB_NAME") ?: "PUT_YOUR_DB_NAME_HERE";
        $dbUser = getenv("DB_USER") ?: "PUT_YOUR_DB_USER_HERE";
        $dbPass = getenv("DB_PASS") ?: "PUT_YOUR_DB_PASSWORD_HERE";
    }
    $charset = "utf8mb4";

    if (!$isLocal && $dbPass === "PUT_YOUR_DB_PASSWORD_HERE") {
        throw new RuntimeException("Set your live database details in db.php (or as DB_* environment variables) before running live.");
    }

    $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    return $pdo;
}

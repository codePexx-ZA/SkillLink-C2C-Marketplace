<?php
session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$loginError = "";
$successMessage = "";

if (isset($_GET["registered"])) {
    $successMessage = "Account created successfully. Please log in.";
}

if (isset($_GET["account"]) && $_GET["account"] === "deleted") {
    $loginError = "Your account has been deleted and you have been signed out.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $loginError = "Please enter both email and password.";
    } else {
        try {
            $pdo = getPdo();
            $userStmt = $pdo->prepare(
                "SELECT id, email, password_hash, role, account_status FROM users WHERE email = :email LIMIT 1"
            );
            $userStmt->execute([":email" => $email]);
            $user = $userStmt->fetch();

            if (!$user) {
                $loginError = "No account found for that email. Please register first.";
            } elseif (!password_verify($password, $user["password_hash"])) {
                $loginError = "Incorrect password. Please try again.";
            } elseif (($user["account_status"] ?? "active") === "deleted") {
                $loginError = "This account has been deleted. Contact support if you need help.";
            } else {
                $_SESSION["logged_in_user_id"] = (int) $user["id"];
                $_SESSION["logged_in_user"] = $user["email"];
                $_SESSION["logged_in_role"] = $user["role"];

                redirectAfterLogin($pdo, $user);
            }
        } catch (Throwable $exception) {
            $loginError = "Database connection failed. Please check your DB setup.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Login</title>
    <link rel="stylesheet" href="ITECA.css">
</head>
<body class="landing-page">
    <nav class="nav-bar landing-nav">
        <a href="index.php" class="logo">SkillLink</a>
        <span class="nav-tagline">Tree fellers, plumbers, electricians & more</span>
    </nav>

    <main class="landing-main">
        <div class="landing-content">
            <div class="landing-brand">
                <h1>SkillLink</h1>
                <p class="tagline">Connect with skilled workers in your community</p>
            </div>

            <?php if ($successMessage !== ""): ?>
                <p style="color: #0a7f34; margin-bottom: 12px;"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php endif; ?>

            <?php if ($loginError !== ""): ?>
                <p style="color: #b00020; margin-bottom: 12px;"><?php echo htmlspecialchars($loginError); ?></p>
            <?php endif; ?>

            <form class="login-form" action="index.php" method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="........" required>
                </div>
                <button type="submit" class="btn-primary">Log In</button>
                <p class="form-footer">
                    Don't have an account? <a href="register.php">Sign up</a>
                </p>
            </form>
        </div>
        <div class="landing-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </main>
</body>
</html>

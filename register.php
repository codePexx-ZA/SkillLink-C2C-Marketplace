<?php
session_start();

$registerError = "";

if (isset($_GET["error"])) {
    $errorCode = $_GET["error"];

    if ($errorCode === "missing") {
        $registerError = "Please complete all fields.";
    } elseif ($errorCode === "short") {
        $registerError = "Password must be at least 6 characters long.";
    } elseif ($errorCode === "exists") {
        $registerError = "An account with that email already exists. Please log in.";
    } elseif ($errorCode === "server") {
        $registerError = "Could not create account. Check database setup and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink - Sign Up</title>
    <link rel="stylesheet" href="register.css">
</head>
<body class="landing-page register-page">
    <nav class="nav-bar landing-nav">
        <a href="index.php" class="logo">SkillLink</a>
        <span class="nav-tagline">Tree fellers, plumbers, electricians & more</span>
    </nav>

    <main class="landing-main">
        <div class="landing-content">
            <div class="landing-brand">
                <h1>Register An Account</h1>
                <p class="tagline">Create your account</p>
            </div>
            <?php if ($registerError !== ""): ?>
                <p style="color: #b00020; margin-bottom: 12px;"><?php echo htmlspecialchars($registerError); ?></p>
            <?php endif; ?>
            <form class="login-form" action="register_process.php" method="post">
                <div class="form-group">
                    <label for="reg-email">Choose your email</label>
                    <input type="email" id="reg-email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Set your password</label>
                    <input type="password" id="reg-password" name="password" placeholder="........" required>
                </div>
                <button type="submit" class="btn-primary">Create account</button>
                <p class="form-footer">
                    Already have an account? <a href="index.php">Log in</a>
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

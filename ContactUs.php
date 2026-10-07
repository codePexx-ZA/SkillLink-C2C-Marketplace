<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

ensureSessionStarted();

$isLoggedIn = !empty($_SESSION["logged_in_user_id"]);
$successMessage = "";
$errorMessage = "";

if ($isLoggedIn) {
    require_once __DIR__ . "/nav_bar.php";
}
 
$firstName = trim($_POST["firstname"] ?? "");
$lastName = trim($_POST["lastname"] ?? "");
$email = trim($_POST["email"] ?? "");
$messageBody = trim($_POST["message"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($firstName === "" || $lastName === "" || $email === "" || $messageBody === "") {
        $errorMessage = "Please complete all required fields.";
    } else {
        try {
            $pdo = getPdo();
            $stmt = $pdo->prepare(
                "INSERT INTO contact_messages (user_id, name, email, message, status)
                 VALUES (:user_id, :name, :email, :message, 'new')"
            );
            $stmt->execute([
                ":user_id" => $isLoggedIn ? (int) $_SESSION["logged_in_user_id"] : null,
                ":name" => trim($firstName . " " . $lastName),
                ":email" => $email,
                ":message" => $messageBody,
            ]);
            $successMessage = "Thanks, your message has been submitted.";
            $firstName = "";
            $lastName = "";
            $email = "";
            $messageBody = "";
        } catch (Throwable $exception) {
            $errorMessage = "Could not submit your message right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillLink — Contact Us</title>
    <link rel="stylesheet" href="ContactUs.css">
    <?php if ($isLoggedIn): ?>
    <link rel="stylesheet" href="app-nav.css">
    <?php endif; ?>
</head>
<body class="contact-page">
    <?php if ($isLoggedIn): ?>
        <?php renderNavBar("menu-toggle-contact", "market-nav"); ?>
    <?php else: ?>
    <nav class="nav-bar market-nav">
        <input type="checkbox" id="menu-toggle-contact" class="menu-toggle">
        <label class="hamburger" for="menu-toggle-contact">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <div class="nav-menu">
            <a href="index.php">Home</a>
            <a href="index.php">Profile</a>
            <a href="OrdersPage.html">Orders</a>
            <a href="ContactUs.php">Contact Us</a>
            <div class="nav-menu-footer">
                <a href="index.php">Log In</a>
            </div>
        </div>
        <a href="index.php" class="logo">SkillLink</a>
    </nav>
    <?php endif; ?>

    <main class="contact-main">
        <div class="contact-grid">
            <section class="contact-info">
                <h1 class="contact-title">Contact Us</h1>
                <p class="contact-intro">Email, call, or complete the form to learn how SkillLink can help you connect with skilled workers and services in your community.</p>
                <div class="contact-direct">
                    <a href="mailto:info@skilllink.io" class="contact-link">info@skilllink.io</a>
                    <span class="contact-phone">321-221-231</span>
                    <a href="#support" class="contact-link">Customer Support</a>
                </div>
                <div class="contact-sections">
                    <div class="contact-block" id="support">
                        <h3>Customer Support</h3>
                        <p>Our support team is available around the clock to address any concerns or queries you may have.</p>
                    </div>
                    <div class="contact-block">
                        <h3>Feedback and Suggestions</h3>
                        <p>We value your feedback and are continuously working to improve SkillLink. Your input is crucial in shaping the future of our platform.</p>
                    </div>
                    <div class="contact-block">
                        <h3>Media Inquiries</h3>
                        <p>For media-related questions or press inquiries, please contact us at media@skilllink.io.</p>
                    </div>
                </div>
            </section>
            <section class="contact-form-wrap">
                <div class="contact-form-card">
                    <h2 class="form-title">Get in Touch</h2>
                    <p class="form-subtitle">You can reach us anytime</p>
                    <?php if ($successMessage !== ""): ?>
                        <p style="color: #0a7f34; margin-bottom: 10px;"><?php echo htmlspecialchars($successMessage); ?></p>
                    <?php endif; ?>
                    <?php if ($errorMessage !== ""): ?>
                        <p style="color: #b00020; margin-bottom: 10px;"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>
                    <form class="contact-form" action="ContactUs.php" method="post">
                        <div class="form-row names">
                            <div class="form-group">
                                <input type="text" name="firstname" placeholder="First name" required value="<?php echo htmlspecialchars($firstName); ?>">
                            </div>
                            <div class="form-group">
                                <input type="text" name="lastname" placeholder="Last name" required value="<?php echo htmlspecialchars($lastName); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contact-email" class="visually-hidden">Your email</label>
                            <span class="input-icon-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                <input type="email" id="contact-email" name="email" placeholder="Your email" required value="<?php echo htmlspecialchars($email); ?>">
                            </span>
                        </div>
                        <div class="form-group phone-row">
                            <div class="phone-country">
                                <select name="countrycode" aria-label="Country code">
                                    <option value="+27">+27</option>
                                    <option value="+1">+1</option>
                                    <option value="+44">+44</option>
                                    <option value="+62">+62</option>
                                    <option value="+91">+91</option>
                                </select>
                            </div>
                            <input type="tel" name="phone" placeholder="Phone number" class="phone-input">
                        </div>
                        <div class="form-group">
                            <label for="contact-message" class="visually-hidden">How can we help?</label>
                            <textarea id="contact-message" name="message" placeholder="How can we help?" rows="4" maxlength="120" required><?php echo htmlspecialchars($messageBody); ?></textarea>
                            <span class="char-count" aria-live="polite"><span id="char-num">0</span>/120</span>
                        </div>
                        <button type="submit" class="btn-contact-submit">Submit</button>
                    </form>
                    <p class="form-disclaimer">By contacting us, you agree to our <a href="#terms">Terms of service</a> and <a href="#privacy">Privacy Policy</a>.</p>
                </div>
            </section>
        </div>
    </main>

    <script>
        (function() {
            var textarea = document.getElementById("contact-message");
            var counter = document.getElementById("char-num");
            if (textarea && counter) {
                counter.textContent = textarea.value.length;
                textarea.addEventListener("input", function() {
                    counter.textContent = this.value.length;
                });
            }
        })();
    </script>
</body>
</html>

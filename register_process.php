<?php

session_start();



require_once __DIR__ . "/db.php";



if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: register.php");

    exit;

}



$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";



if ($email === "" || $password === "") {

    header("Location: register.php?error=missing");

    exit;

}



if (strlen($password) < 6) {

    header("Location: register.php?error=short");

    exit;

}



try {

    $pdo = getPdo();



    $existingStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");

    $existingStmt->execute([":email" => $email]);



    if ($existingStmt->fetch()) {

        header("Location: register.php?error=exists");

        exit;

    }



    $insertStmt = $pdo->prepare(

        "INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, 'user')"

    );

    $insertStmt->execute([

        ":email" => $email,

        ":password_hash" => password_hash($password, PASSWORD_DEFAULT),

    ]);



    header("Location: index.php?registered=1");

    exit;

} catch (Throwable $exception) {

    header("Location: register.php?error=server");

    exit;

}

?>


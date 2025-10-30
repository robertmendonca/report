<?php

session_start();

$error_message = $_SESSION['error_message'] ?? ''; // Recupera a mensagem de erro, se houver
$success_message = $_SESSION['success_message'] ?? ''; // Recupera a mensagem de sucesso, se houver

unset($_SESSION['error_message']); // Remove a mensagem de erro após exibi-la
unset($_SESSION['success_message']); // Remove a mensagem de sucesso após exibi-la


?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>

    <script src="./assets/js/color-modes.js"></script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Robert Mendonça">
    <title>Login - ARXVIEW </title>

    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="./assets/css/sign-in.css" rel="stylesheet">

</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary">

    <?php require 'theme.php'; ?>

    <main class="form-signin w-100 m-auto">

        <form method="POST" action="validate_login.php">

        

            <img class="mb-4 p-1" src="./assets/image/s00003507.webp" alt="" width="130">
            <img class="mb-4 p-1" src="./assets/image/s00003508.webp" alt="" width="130">


            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php
                session_unset();
                session_destroy();
            endif;
            ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php
                session_unset();
                session_destroy();
            endif;
            ?>

            <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" required>
                <label for="username">Username</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>

            <div class="form-check text-start my-3">
                <input class="form-check-input" type="checkbox" value="remember-me" id="flexCheckDefault">
                <label class="form-check-label" for="flexCheckDefault">
                    Remember me
                </label>
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-body-secondary">&copy; 2024</p>
        </form>
    </main>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>

</body>

</html>
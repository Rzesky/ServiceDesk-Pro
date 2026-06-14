<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($email, $password)) {
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ServiceDesk Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="auth-page">
        <form class="auth-card" method="post">
            <h1>Staff Login</h1>

            <?php if ($error): ?>
                <p class="alert error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log in</button>
        </form>
    </main>
</body>
</html>

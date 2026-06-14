<?php
require_once __DIR__ . '/../includes/auth.php';

require_roles(['admin', 'leader']);

$user = current_user();
$allowedRoles = has_role(['admin']) ? ['staff', 'leader', 'admin'] : ['staff'];
$errors = [];
$success = '';
$form = [
    'name' => '',
    'email' => '',
    'role' => 'staff',
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $form['role'] = $_POST['role'] ?? 'staff';

    if ($form['name'] === '' || strlen($form['name']) > 100) {
        $errors[] = 'Name is required and must be 100 characters or fewer.';
    }

    if ($form['email'] === '' || strlen($form['email']) > 150 || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!in_array($form['role'], $allowedRoles, true)) {
        $errors[] = 'You do not have permission to create that role.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$form['email']]);

        if ($stmt->fetchColumn()) {
            $errors[] = 'A user with that email already exists.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['name'],
            $form['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $form['role'],
        ]);

        $success = 'User account created.';
        $form = [
            'name' => '',
            'email' => '',
            'role' => 'staff',
        ];
    }
}

$stmt = db()->prepare('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users - ServiceDesk Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>ServiceDesk Pro</strong>
            <span><?= e($user['name'] ?? $user['email']) ?></span>
        </div>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="tickets.php">Tickets</a>
            <a href="customers.php">Customers</a>
            <a class="active" href="users.php">Users</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>User Management</h1>
        </div>

        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="alert success"><?= e($success) ?></p>
        <?php endif; ?>

        <section class="detail-grid">
            <article class="panel">
                <h2>Create Staff Account</h2>

                <form method="post">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="100" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" maxlength="150" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8" required>

                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <?php foreach ($allowedRoles as $role): ?>
                            <option value="<?= e($role) ?>" <?= $form['role'] === $role ? 'selected' : '' ?>>
                                <?= e(ucfirst($role)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Create User</button>
                </form>
            </article>

            <aside class="panel">
                <h2>Role Rules</h2>
                <p class="muted">Leaders can create staff users only. Admins can create staff, leader, and admin users.</p>
            </aside>
        </section>

        <section class="panel">
            <h2>Existing Users</h2>

            <div class="table-card flush">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No users found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($users as $staffUser): ?>
                            <tr>
                                <td><?= e($staffUser['name']) ?></td>
                                <td><?= e($staffUser['email']) ?></td>
                                <td><?= e(ucfirst($staffUser['role'])) ?></td>
                                <td><?= e($staffUser['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>

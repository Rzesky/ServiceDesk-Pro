<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$statuses = ['open', 'in_progress', 'waiting', 'closed'];
$counts = array_fill_keys($statuses, 0);

$stmt = db()->query('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status');

foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - ServiceDesk Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div>
            <strong>ServiceDesk Pro</strong>
            <span><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
        </div>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="tickets.php">Tickets</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h1>ServiceDesk Pro Dashboard</h1>

        <section class="stats-grid">
            <article class="stat-card">
                <span>Open tickets</span>
                <strong><?= $counts['open'] ?></strong>
            </article>

            <article class="stat-card">
                <span>In progress tickets</span>
                <strong><?= $counts['in_progress'] ?></strong>
            </article>

            <article class="stat-card">
                <span>Waiting tickets</span>
                <strong><?= $counts['waiting'] ?></strong>
            </article>

            <article class="stat-card">
                <span>Closed tickets</span>
                <strong><?= $counts['closed'] ?></strong>
            </article>
        </section>
    </main>
</body>
</html>

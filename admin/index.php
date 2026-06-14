<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$statuses = ['open', 'in_progress', 'waiting', 'closed'];
$counts = array_fill_keys($statuses, 0);

$stmt = db()->prepare('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status');
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}

$stmt = db()->prepare(
    'SELECT id, customer_name, subject, priority, status, created_at
     FROM tickets
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute();
$latestTickets = $stmt->fetchAll();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
            <span><?= e($user['name'] ?? $user['email']) ?></span>
        </div>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="tickets.php">Tickets</a>
            <a href="customers.php">Customers</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <h1>ServiceDesk Pro Dashboard</h1>

        <section class="stats-grid">
            <article class="stat-card">
                <span>Open</span>
                <strong><?= $counts['open'] ?></strong>
            </article>

            <article class="stat-card">
                <span>In Progress</span>
                <strong><?= $counts['in_progress'] ?></strong>
            </article>

            <article class="stat-card">
                <span>Waiting</span>
                <strong><?= $counts['waiting'] ?></strong>
            </article>

            <article class="stat-card">
                <span>Closed</span>
                <strong><?= $counts['closed'] ?></strong>
            </article>
        </section>

        <section class="panel dashboard-section">
            <div class="page-header">
                <h2>Latest Tickets</h2>
                <a href="tickets.php">View all</a>
            </div>

            <div class="table-card flush">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$latestTickets): ?>
                            <tr>
                                <td colspan="5" class="empty-state">No tickets found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($latestTickets as $ticket): ?>
                            <tr>
                                <td><?= e($ticket['customer_name']) ?></td>
                                <td>
                                    <a href="ticket.php?id=<?= (int) $ticket['id'] ?>">
                                        <?= e($ticket['subject']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?= e($ticket['priority']) ?>">
                                        <?= e(ucfirst($ticket['priority'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge"><?= e(str_replace('_', ' ', $ticket['status'])) ?></span>
                                </td>
                                <td><?= e($ticket['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>

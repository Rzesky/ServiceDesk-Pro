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
    'SELECT COUNT(*) AS total
     FROM tickets
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)'
);
$stmt->execute();
$ticketsLastSevenDays = (int) $stmt->fetchColumn();

$priorities = ['low', 'medium', 'high', 'urgent'];
$priorityCounts = array_fill_keys($priorities, 0);

$stmt = db()->prepare('SELECT priority, COUNT(*) AS total FROM tickets GROUP BY priority');
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $priorityCounts[$row['priority']] = (int) $row['total'];
}

$stmt = db()->prepare(
    'SELECT c.id, c.name, c.email, COUNT(t.id) AS total_tickets
     FROM customers c
     INNER JOIN tickets t ON t.customer_id = c.id
     GROUP BY c.id, c.name, c.email
     ORDER BY total_tickets DESC, c.name ASC
     LIMIT 5'
);
$stmt->execute();
$topCustomers = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT COUNT(*) / 30 AS average_per_day
     FROM tickets
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)'
);
$stmt->execute();
$averageTicketsPerDay = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT id, customer_name, subject, priority, status, created_at
     FROM tickets
     ORDER BY created_at DESC
     LIMIT 5'
);
$stmt->execute();
$latestTickets = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT al.*, t.subject, u.name AS user_name, u.email AS user_email
     FROM activity_logs al
     LEFT JOIN tickets t ON al.ticket_id = t.id
     LEFT JOIN users u ON al.user_id = u.id
     ORDER BY al.created_at DESC
     LIMIT 10'
);
$stmt->execute();
$latestActivity = $stmt->fetchAll();

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
            <a class="active" href="index.php">Dashboard</a>
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

        <section class="analytics-grid">
            <article class="panel analytics-card">
                <span class="analytics-label">Tickets created last 7 days</span>
                <strong><?= $ticketsLastSevenDays ?></strong>
                <p class="muted">Includes today and the previous 6 days.</p>
            </article>

            <article class="panel analytics-card">
                <span class="analytics-label">Average tickets per day</span>
                <strong><?= e(number_format($averageTicketsPerDay, 1)) ?></strong>
                <p class="muted">Based on tickets created in the last 30 days.</p>
            </article>

            <article class="panel dashboard-section">
                <h2>Tickets by Priority</h2>

                <div class="analytics-list">
                    <?php foreach ($priorityCounts as $priority => $total): ?>
                        <div class="analytics-row">
                            <span class="priority-badge priority-<?= e($priority) ?>">
                                <?= e(ucfirst($priority)) ?>
                            </span>
                            <strong><?= (int) $total ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel dashboard-section">
                <h2>Top 5 Customers</h2>

                <div class="table-card flush">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$topCustomers): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">No customer ticket data yet.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($topCustomers as $customer): ?>
                                <tr>
                                    <td>
                                        <a href="customer.php?id=<?= (int) $customer['id'] ?>">
                                            <?= e($customer['name']) ?>
                                        </a>
                                        <span class="table-subtext"><?= e($customer['email']) ?></span>
                                    </td>
                                    <td><?= (int) $customer['total_tickets'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
                                    <span class="status-badge status-<?= e($ticket['status']) ?>"><?= e(str_replace('_', ' ', $ticket['status'])) ?></span>
                                </td>
                                <td><?= e($ticket['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel dashboard-section">
            <h2>Latest Activity</h2>

            <div class="activity-list">
                <?php if (!$latestActivity): ?>
                    <p class="muted">No activity yet.</p>
                <?php endif; ?>

                <?php foreach ($latestActivity as $activity): ?>
                    <article class="activity-item">
                        <div>
                            <strong><?= e(str_replace('_', ' ', $activity['action'])) ?></strong>
                            <p><?= e($activity['description']) ?></p>
                            <?php if ($activity['ticket_id']): ?>
                                <a href="ticket.php?id=<?= (int) $activity['ticket_id'] ?>">
                                    View ticket
                                </a>
                            <?php endif; ?>
                        </div>
                        <span><?= e($activity['created_at']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>

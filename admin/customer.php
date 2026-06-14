<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$customerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!$customerId) {
    $customer = null;
} else {
    $stmt = db()->prepare('SELECT id, name, email, phone, created_at FROM customers WHERE id = ? LIMIT 1');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
}

$tickets = [];

if ($customer) {
    $stmt = db()->prepare(
        'SELECT id, subject, status, priority, created_at
         FROM tickets
         WHERE customer_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$customerId]);
    $tickets = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Details - ServiceDesk Pro</title>
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
            <a class="active" href="customers.php">Customers</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <?php if (!$customer): ?>
            <section class="panel">
                <h1>Customer not found</h1>
                <p class="muted">The customer you are looking for does not exist.</p>
                <a href="customers.php">Back to customers</a>
            </section>
        <?php else: ?>
            <div class="page-header">
                <h1>Customer Details</h1>
                <a href="customers.php">Back to customers</a>
            </div>

            <section class="panel">
                <h2><?= e($customer['name']) ?></h2>

                <dl class="details-list">
                    <div>
                        <dt>Name</dt>
                        <dd><?= e($customer['name']) ?></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><?= e($customer['email']) ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><?= e($customer['phone'] ?: 'Not provided') ?></dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd><?= e($customer['created_at']) ?></dd>
                    </div>
                </dl>
            </section>

            <section class="panel">
                <h2>Customer Tickets</h2>

                <div class="table-card flush">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$tickets): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">No tickets found for this customer.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td>#<?= (int) $ticket['id'] ?></td>
                                    <td>
                                        <a href="ticket.php?id=<?= (int) $ticket['id'] ?>">
                                            <?= e($ticket['subject']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= e($ticket['status']) ?>"><?= e(str_replace('_', ' ', $ticket['status'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?= e($ticket['priority']) ?>">
                                            <?= e(ucfirst($ticket['priority'])) ?>
                                        </span>
                                    </td>
                                    <td><?= e($ticket['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

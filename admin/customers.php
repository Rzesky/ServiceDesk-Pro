<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();

$stmt = db()->prepare(
    'SELECT c.id, c.name, c.email, c.phone, COUNT(t.id) AS total_tickets, MAX(t.created_at) AS last_ticket_date
     FROM customers c
     LEFT JOIN tickets t ON t.customer_id = c.id AND t.deleted_at IS NULL
     GROUP BY c.id, c.name, c.email, c.phone
     ORDER BY last_ticket_date DESC, c.created_at DESC'
);
$stmt->execute();
$customers = $stmt->fetchAll();

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
    <title>Customers - ServiceDesk Pro</title>
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
            <?php if (can_manage_users()): ?>
                <a href="users.php">Users</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Customers</h1>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Total Tickets</th>
                        <th>Last Ticket</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$customers): ?>
                        <tr>
                            <td colspan="6" class="empty-state">No customers found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= e($customer['name']) ?></td>
                            <td><?= e($customer['email']) ?></td>
                            <td><?= e($customer['phone'] ?: 'Not provided') ?></td>
                            <td><?= (int) $customer['total_tickets'] ?></td>
                            <td><?= e($customer['last_ticket_date'] ?: 'No tickets') ?></td>
                            <td><a href="customer.php?id=<?= (int) $customer['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

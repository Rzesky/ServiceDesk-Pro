<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();

$user = current_user();
$allowedStatuses = ['open', 'in_progress', 'waiting', 'closed'];
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$where = [];
$params = [];

if (!in_array($status, array_merge(['all'], $allowedStatuses), true)) {
    $status = 'all';
}

if ($status !== 'all') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(customer_email LIKE ? OR subject LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql = 'SELECT id, customer_name, customer_email, subject, priority, status, created_at FROM tickets';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

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
    <title>Tickets - ServiceDesk Pro</title>
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
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Tickets</h1>
        </div>

        <form class="filter-bar" method="get">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                    <?php foreach ($allowedStatuses as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                            <?= e(ucwords(str_replace('_', ' ', $option))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="search">Search email or subject</label>
                <input type="search" id="search" name="search" value="<?= e($search) ?>">
            </div>

            <button type="submit">Filter</button>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$tickets): ?>
                        <tr>
                            <td colspan="8" class="empty-state">No tickets found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>#<?= (int) $ticket['id'] ?></td>
                            <td><?= e($ticket['customer_name']) ?></td>
                            <td><?= e($ticket['customer_email']) ?></td>
                            <td><?= e($ticket['subject']) ?></td>
                            <td>
                                <span class="priority-badge priority-<?= e($ticket['priority']) ?>">
                                    <?= e(ucfirst($ticket['priority'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge"><?= e(str_replace('_', ' ', $ticket['status'])) ?></span>
                            </td>
                            <td><?= e($ticket['created_at']) ?></td>
                            <td><a href="ticket.php?id=<?= (int) $ticket['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

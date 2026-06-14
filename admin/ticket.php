<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = current_user();
$allowedStatuses = ['open', 'in_progress', 'waiting', 'closed'];
$ticketId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$errors = [];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!$ticketId) {
    $ticket = null;
} else {
    $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ? LIMIT 1');
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
}

if ($ticket && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';

        if (in_array($status, $allowedStatuses, true)) {
            $stmt = db()->prepare('UPDATE tickets SET status = ? WHERE id = ?');
            $stmt->execute([$status, $ticketId]);

            log_activity(
                'status_changed',
                'Status changed from ' . $ticket['status'] . ' to ' . $status . '.',
                $ticketId,
                $ticket['customer_id'] ? (int) $ticket['customer_id'] : null,
                (int) $user['id']
            );

            header('Location: ticket.php?id=' . $ticketId);
            exit;
        }

        $errors[] = 'Please choose a valid status.';
    }

    if ($action === 'add_message') {
        $message = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['is_internal']) ? 1 : 0;

        if ($message === '') {
            $errors[] = 'Message is required.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$ticketId, (int) $user['id'], $message, $isInternal]);

            log_activity(
                'message_added',
                $isInternal ? 'Internal note added.' : 'Reply added.',
                $ticketId,
                $ticket['customer_id'] ? (int) $ticket['customer_id'] : null,
                (int) $user['id']
            );

            header('Location: ticket.php?id=' . $ticketId);
            exit;
        }
    }
}

$messages = [];
$activityLogs = [];

if ($ticket) {
    $stmt = db()->prepare(
        'SELECT tm.*, u.name AS user_name, u.email AS user_email
         FROM ticket_messages tm
         LEFT JOIN users u ON tm.user_id = u.id
         WHERE tm.ticket_id = ?
         ORDER BY tm.created_at ASC'
    );
    $stmt->execute([$ticketId]);
    $messages = $stmt->fetchAll();

    $stmt = db()->prepare(
        'SELECT al.*, u.name AS user_name, u.email AS user_email
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         WHERE al.ticket_id = ?
         ORDER BY al.created_at DESC'
    );
    $stmt->execute([$ticketId]);
    $activityLogs = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket Details - ServiceDesk Pro</title>
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
        <?php if (!$ticket): ?>
            <section class="panel">
                <h1>Ticket not found</h1>
                <p class="muted">The ticket you are looking for does not exist.</p>
                <a href="tickets.php">Back to tickets</a>
            </section>
        <?php else: ?>
            <div class="page-header">
                <h1>Ticket #<?= (int) $ticket['id'] ?></h1>
                <a href="tickets.php">Back to tickets</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section class="detail-grid">
                <article class="panel">
                    <h2>Ticket Details</h2>

                    <dl class="details-list">
                        <div>
                            <dt>ID</dt>
                            <dd>#<?= (int) $ticket['id'] ?></dd>
                        </div>
                        <div>
                            <dt>Customer</dt>
                            <dd><?= e($ticket['customer_name']) ?></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><?= e($ticket['customer_email']) ?></dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd><?= e($ticket['customer_phone'] ?: 'Not provided') ?></dd>
                        </div>
                        <div>
                            <dt>Subject</dt>
                            <dd><?= e($ticket['subject']) ?></dd>
                        </div>
                        <div>
                            <dt>Priority</dt>
                            <dd>
                                <span class="priority-badge priority-<?= e($ticket['priority']) ?>">
                                    <?= e(ucfirst($ticket['priority'])) ?>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><span class="status-badge"><?= e(str_replace('_', ' ', $ticket['status'])) ?></span></dd>
                        </div>
                        <div>
                            <dt>Created</dt>
                            <dd><?= e($ticket['created_at']) ?></dd>
                        </div>
                    </dl>

                    <h2>Original Message</h2>
                    <p class="message-body"><?= nl2br(e($ticket['message'])) ?></p>
                </article>

                <aside class="panel">
                    <h2>Update Status</h2>
                    <form method="post">
                        <input type="hidden" name="action" value="update_status">

                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?= e($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>>
                                    <?= e(ucwords(str_replace('_', ' ', $status))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Update Status</button>
                    </form>
                </aside>
            </section>

            <section class="panel">
                <h2>Message History</h2>

                <div class="thread">
                    <?php if (!$messages): ?>
                        <p class="muted">No messages yet.</p>
                    <?php endif; ?>

                    <?php foreach ($messages as $message): ?>
                        <article class="thread-message">
                            <div class="thread-meta">
                                <strong>
                                    <?= $message['user_id'] ? e($message['user_name'] ?: $message['user_email']) : 'Customer' ?>
                                </strong>
                                <span><?= e($message['created_at']) ?></span>
                                <span><?= $message['is_internal'] ? 'Internal note' : 'Reply' ?></span>
                            </div>
                            <p><?= nl2br(e($message['message'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <h2>Activity History</h2>

                <div class="activity-list">
                    <?php if (!$activityLogs): ?>
                        <p class="muted">No activity yet.</p>
                    <?php endif; ?>

                    <?php foreach ($activityLogs as $activity): ?>
                        <article class="activity-item">
                            <div>
                                <strong><?= e(str_replace('_', ' ', $activity['action'])) ?></strong>
                                <p><?= e($activity['description']) ?></p>
                                <?php if ($activity['user_id']): ?>
                                    <small><?= e($activity['user_name'] ?: $activity['user_email']) ?></small>
                                <?php endif; ?>
                            </div>
                            <span><?= e($activity['created_at']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <h2>Add Reply / Internal Note</h2>

                <form method="post">
                    <input type="hidden" name="action" value="add_message">

                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>

                    <label class="checkbox-label">
                        <input type="checkbox" name="is_internal" checked>
                        Internal note
                    </label>

                    <button type="submit">Add Message</button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

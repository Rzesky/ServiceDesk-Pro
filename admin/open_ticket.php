<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = current_user();
$ticketId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$ticketId) {
    header('Location: tickets.php');
    exit;
}

$stmt = db()->prepare(
    'SELECT id, status, customer_id
     FROM tickets
     WHERE id = ? AND deleted_at IS NULL
     LIMIT 1'
);
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: tickets.php');
    exit;
}

if ($ticket['status'] === 'open') {
    $stmt = db()->prepare(
        'UPDATE tickets
         SET status = ?, assigned_to = ?
         WHERE id = ? AND status = ? AND deleted_at IS NULL'
    );
    $stmt->execute(['in_progress', (int) $user['id'], $ticketId, 'open']);

    if ($stmt->rowCount() > 0) {
        log_activity(
            'ticket_opened',
            'Ticket opened and assigned to ' . ($user['name'] ?? $user['email']) . '.',
            $ticketId,
            $ticket['customer_id'] ? (int) $ticket['customer_id'] : null,
            (int) $user['id']
        );
    }
}

header('Location: ticket.php?id=' . $ticketId);
exit;

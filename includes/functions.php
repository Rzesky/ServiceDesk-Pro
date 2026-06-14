<?php
require_once __DIR__ . '/db.php';

function log_activity(
    string $action,
    string $description,
    ?int $ticketId = null,
    ?int $customerId = null,
    ?int $userId = null
): void {
    $stmt = db()->prepare(
        'INSERT INTO activity_logs (ticket_id, customer_id, user_id, action, description)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$ticketId, $customerId, $userId, $action, $description]);
}

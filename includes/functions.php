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

function validate_ticket_attachment(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['attachment' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['attachment' => null, 'error' => 'Attachment upload failed. Please try again.'];
    }

    if (($file['size'] ?? 0) <= 0) {
        return ['attachment' => null, 'error' => 'Attachment cannot be empty.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['attachment' => null, 'error' => 'Attachment must be 5MB or smaller.'];
    }

    $originalName = basename((string) ($file['name'] ?? ''));

    if ($originalName === '' || strlen($originalName) > 255) {
        return ['attachment' => null, 'error' => 'Attachment file name is invalid.'];
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
    ];

    if (!isset($allowedExtensions[$extension])) {
        return ['attachment' => null, 'error' => 'Attachment must be a JPG, PNG, or PDF file.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['attachment' => null, 'error' => 'Attachment upload could not be verified.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedExtensions[$extension], true)) {
        return ['attachment' => null, 'error' => 'Attachment file type does not match the uploaded file.'];
    }

    if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && !getimagesize($file['tmp_name'])) {
        return ['attachment' => null, 'error' => 'Attachment image could not be validated.'];
    }

    return [
        'attachment' => [
            'tmp_name' => $file['tmp_name'],
            'original_name' => $originalName,
            'stored_name' => bin2hex(random_bytes(16)) . '.' . $extension,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
        ],
        'error' => null,
    ];
}

function save_ticket_attachment(PDO $pdo, int $ticketId, array $attachment): void
{
    $uploadDir = dirname(__DIR__) . '/uploads';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . '/' . $attachment['stored_name'];

    if (!move_uploaded_file($attachment['tmp_name'], $destination)) {
        throw new RuntimeException('Attachment could not be saved.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO ticket_attachments (ticket_id, original_name, stored_name, mime_type, file_size)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $ticketId,
        $attachment['original_name'],
        $attachment['stored_name'],
        $attachment['mime_type'],
        $attachment['file_size'],
    ]);
}

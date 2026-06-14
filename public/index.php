<?php
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = false;
$allowedPriorities = ['low', 'medium', 'high', 'urgent'];
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'priority' => 'medium',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $value) {
        $form[$field] = trim($_POST[$field] ?? '');
    }

    if ($form['name'] === '') {
        $errors[] = 'Name is required.';
    }

    if ($form['email'] === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($form['subject'] === '') {
        $errors[] = 'Subject is required.';
    }

    if (!in_array($form['priority'], $allowedPriorities, true)) {
        $errors[] = 'Please choose a valid priority.';
    }

    if ($form['message'] === '') {
        $errors[] = 'Message is required.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
            $stmt->execute([$form['email']]);
            $customerId = $stmt->fetchColumn();

            if (!$customerId) {
                $stmt = $pdo->prepare('INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)');
                $stmt->execute([
                    $form['name'],
                    $form['email'],
                    $form['phone'] ?: null,
                ]);
                $customerId = (int) $pdo->lastInsertId();

                log_activity(
                    'customer_created',
                    'Customer created: ' . $form['email'],
                    null,
                    $customerId
                );
            }

            $stmt = $pdo->prepare(
                'INSERT INTO tickets (customer_id, customer_name, customer_email, customer_phone, subject, priority, message)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $customerId,
                $form['name'],
                $form['email'],
                $form['phone'] ?: null,
                $form['subject'],
                $form['priority'],
                $form['message'],
            ]);

            $ticketId = (int) $pdo->lastInsertId();

            log_activity(
                'ticket_created',
                'Ticket created: ' . $form['subject'],
                $ticketId,
                (int) $customerId
            );

            $stmt = $pdo->prepare(
                'INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal)
                 VALUES (?, NULL, ?, 0)'
            );
            $stmt->execute([$ticketId, $form['message']]);

            log_activity(
                'message_added',
                'Initial customer message added.',
                $ticketId,
                (int) $customerId
            );

            $pdo->commit();
            $success = true;
            $form = array_fill_keys(array_keys($form), '');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Could not submit your ticket. Please try again.';
        }
    }
}

function old(string $field, array $form): string
{
    return htmlspecialchars($form[$field] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit a Ticket - ServiceDesk Pro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="public-page">
        <section class="ticket-form-card">
            <?php if ($success): ?>
                <div class="success-panel">
                    <h1>Your ticket has been submitted successfully.</h1>
                    <p>Our support team will review your request and contact you soon.</p>
                    <a class="button-link" href="index.php">Submit another ticket</a>
                </div>
            <?php else: ?>
                <h1>Submit a Support Ticket</h1>
                <p>Tell us what happened and our support team will review your request.</p>

                <?php if ($errors): ?>
                    <div class="alert error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= old('name', $form) ?>" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= old('email', $form) ?>" required>

                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= old('phone', $form) ?>">

                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?= old('subject', $form) ?>" required>

                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <?php foreach ($allowedPriorities as $priority): ?>
                            <option value="<?= old('priority', ['priority' => $priority]) ?>" <?= $form['priority'] === $priority ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($priority), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required><?= old('message', $form) ?></textarea>

                    <button type="submit">Submit Ticket</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

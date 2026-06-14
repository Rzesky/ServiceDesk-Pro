USE servicedesk_pro;

ALTER TABLE users
  MODIFY role ENUM('staff', 'leader', 'admin') NOT NULL DEFAULT 'staff';

UPDATE users
SET role = 'admin'
WHERE email = 'admin@servicedesk.local';

ALTER TABLE tickets
  ADD COLUMN assigned_to INT UNSIGNED NULL AFTER status,
  ADD COLUMN deleted_at TIMESTAMP NULL AFTER assigned_to,
  ADD INDEX idx_assigned_to (assigned_to),
  ADD INDEX idx_deleted_at (deleted_at),
  ADD CONSTRAINT fk_tickets_assigned_user
    FOREIGN KEY (assigned_to) REFERENCES users(id)
    ON DELETE SET NULL;

ALTER TABLE activity_logs
  MODIFY action ENUM(
    'ticket_created',
    'customer_created',
    'status_changed',
    'message_added',
    'ticket_opened',
    'ticket_deleted'
  ) NOT NULL;

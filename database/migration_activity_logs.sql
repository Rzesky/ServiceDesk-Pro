CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  action ENUM('ticket_created', 'customer_created', 'status_changed', 'message_added') NOT NULL,
  description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ticket_id (ticket_id),
  INDEX idx_customer_id (customer_id),
  INDEX idx_created_at (created_at),
  CONSTRAINT fk_activity_logs_ticket
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_activity_logs_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_activity_logs_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

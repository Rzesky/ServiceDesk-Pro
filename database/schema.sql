CREATE DATABASE IF NOT EXISTS servicedesk_pro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE servicedesk_pro;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('staff', 'admin') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NULL,
  customer_name VARCHAR(100) NOT NULL,
  customer_email VARCHAR(150) NOT NULL,
  customer_phone VARCHAR(30) NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  status ENUM('open', 'in_progress', 'waiting', 'closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_priority (priority),
  INDEX idx_status (status),
  INDEX idx_customer_id (customer_id),
  INDEX idx_customer_email (customer_email),
  INDEX idx_subject (subject),
  CONSTRAINT fk_tickets_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  message TEXT NOT NULL,
  is_internal TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_messages_ticket
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_ticket_messages_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

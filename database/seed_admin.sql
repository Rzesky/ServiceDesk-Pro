USE servicedesk_pro;

INSERT INTO users (name, email, password_hash, role)
VALUES (
  'Admin',
  'admin@servicedesk.local',
  '$2y$10$e9JKCU7ovzCYmxuAhfQrnu0ziXi3swLuE4fi1LRRRi57SdTfxHFyO',
  'admin'
);

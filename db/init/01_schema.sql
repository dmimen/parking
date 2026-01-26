CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL UNIQUE,
  tg_id BIGINT NULL UNIQUE,
  role ENUM('admin','manager','guard') NOT NULL DEFAULT 'guard',
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  car_model VARCHAR(255) NOT NULL,
  car_number VARCHAR(32) NOT NULL,
  comment VARCHAR(255) NULL,
  who_added INT NULL,
  date_added DATETIME NOT NULL,
  INDEX idx_cars_number (car_number),
  CONSTRAINT fk_cars_user FOREIGN KEY (who_added) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS remote_cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  car_model VARCHAR(255) NOT NULL,
  car_number VARCHAR(32) NOT NULL,
  comment VARCHAR(255) NULL,
  who_added INT NULL,
  date_added DATETIME NOT NULL,
  who_deleted INT NULL,
  date_deleted DATETIME NOT NULL,
  INDEX idx_remote_number (car_number),
  CONSTRAINT fk_remote_added FOREIGN KEY (who_added) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_remote_deleted FOREIGN KEY (who_deleted) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS otp_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  blocked_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_user (user_id),
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS otp_outbox (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  status ENUM('pending','sent','error') NOT NULL DEFAULT 'pending',
  error_message VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  INDEX idx_outbox_status (status),
  CONSTRAINT fk_outbox_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

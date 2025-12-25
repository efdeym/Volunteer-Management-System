CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('volunteer','admin') NOT NULL DEFAULT 'volunteer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  event_date DATE NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_registration (user_id, event_id),
  CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role)
VALUES ('Project Admin', 'admin@vms.local', '$2y$10$gWS9Vyuk3F7S3w7Dnk3aHuJpN96CBrum1BgqsYqS2rCA0nVddOZXS', 'admin')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO events (title, event_date, description) VALUES
('Community Food Drive', '2025-12-05', 'Help sort and deliver food donations to local shelters.'),
('STEM Mentorship Night', '2025-12-12', 'Mentor students through science challenges.'),
('Parks Clean-Up Blitz', '2026-01-08', 'Beautify four city parks in one day.');

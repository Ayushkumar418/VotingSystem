CREATE DATABASE IF NOT EXISTS voting_system;
USE voting_system;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    voter_id VARCHAR(50) UNIQUE NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    photo_url VARCHAR(255),
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    election_id INT,
    candidate_id INT,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    UNIQUE KEY unique_vote (user_id, election_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, full_name, voter_id, is_admin) 
VALUES (
    'admin@system.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'System Administrator', 
    'ADMIN001', 
    1
) ON DUPLICATE KEY UPDATE password = VALUES(password), is_admin = 1;

-- Update admin user with fresh password hash for 'admin123'
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@system.com';

ALTER TABLE elections
MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active;

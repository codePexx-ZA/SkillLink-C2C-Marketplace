ALTER TABLE users
    ADD COLUMN account_status ENUM('active', 'promoted', 'restricted', 'deleted') NOT NULL DEFAULT 'active'
    AFTER role;

CREATE TABLE IF NOT EXISTS user_account_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    action ENUM('promote', 'restrict', 'delete', 'ignore') NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_user_account_actions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_user_account_actions_admin
        FOREIGN KEY (admin_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

ALTER TABLE user_account_actions
    MODIFY action ENUM('promote', 'restrict', 'delete', 'ignore') NOT NULL;

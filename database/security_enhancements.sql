-- Security Enhancement Tables
-- Additional tables for enhanced security features

-- Security logs table
CREATE TABLE IF NOT EXISTS security_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two-factor authentication table
CREATE TABLE IF NOT EXISTS two_factor_auth (
    tfa_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    secret_key VARCHAR(32) NOT NULL,
    backup_codes JSON,
    is_enabled BOOLEAN DEFAULT FALSE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_tfa (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API access tokens table
CREATE TABLE IF NOT EXISTS api_access_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    token_hash VARCHAR(255) NOT NULL,
    token_name VARCHAR(100),
    permissions JSON,
    expires_at TIMESTAMP NULL,
    last_used TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_staff_id (staff_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password history table (prevent password reuse)
CREATE TABLE IF NOT EXISTS password_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    staff_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    session_data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File upload logs table
CREATE TABLE IF NOT EXISTS file_upload_logs (
    upload_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_path VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    is_safe BOOLEAN DEFAULT TRUE,
    scan_result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL,
    INDEX idx_staff_id (staff_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_safe (is_safe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add security-related columns to existing tables
ALTER TABLE staff_users 
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS require_password_change BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE;

-- Add indexes for security queries
ALTER TABLE audit_logs ADD INDEX IF NOT EXISTS idx_ip_address (ip_address);
ALTER TABLE audit_logs ADD INDEX IF NOT EXISTS idx_action_timestamp (action_timestamp);

-- Create view for security dashboard
CREATE OR REPLACE VIEW security_dashboard AS
SELECT 
    DATE(created_at) as date,
    event_type,
    COUNT(*) as event_count,
    COUNT(DISTINCT ip_address) as unique_ips
FROM security_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), event_type
ORDER BY date DESC, event_count DESC;

-- Insert default security settings
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('security_password_min_length', '8', 'Minimum password length'),
('security_max_login_attempts', '5', 'Maximum failed login attempts before lockout'),
('security_lockout_duration', '900', 'Account lockout duration in seconds'),
('security_session_timeout', '3600', 'Session timeout in seconds'),
('security_require_2fa', 'false', 'Require two-factor authentication'),
('security_password_history', '5', 'Number of previous passwords to remember'),
('security_force_https', 'false', 'Force HTTPS connections'),
('security_rate_limit_enabled', 'true', 'Enable rate limiting'),
('security_rate_limit_requests', '100', 'Maximum requests per hour'),
('security_file_scan_enabled', 'false', 'Enable file malware scanning');

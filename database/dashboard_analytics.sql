CREATE TABLE IF NOT EXISTS dashboard_widgets (
    widget_id INT PRIMARY KEY AUTO_INCREMENT,
    widget_name VARCHAR(100) NOT NULL,
    widget_type ENUM('chart', 'counter', 'table', 'gauge', 'map') NOT NULL,
    widget_config JSON,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dashboard_user_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT,
    widget_layout JSON,
    refresh_interval INT DEFAULT 30,
    theme VARCHAR(20) DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS real_time_metrics (
    metric_id INT PRIMARY KEY AUTO_INCREMENT,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    metric_data JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name_time (metric_name, recorded_at)
);

CREATE TABLE IF NOT EXISTS dashboard_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    alert_type ENUM('warning', 'error', 'info', 'success') NOT NULL,
    alert_title VARCHAR(200) NOT NULL,
    alert_message TEXT,
    alert_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_active_alerts (is_active, created_at)
);

-- Insert default widgets
INSERT INTO dashboard_widgets (widget_name, widget_type, widget_config, display_order) VALUES
('คิวรอทั้งหมด', 'counter', '{"color": "primary", "icon": "fas fa-users", "query": "waiting_queues"}', 1),
('คิวที่เสร็จสิ้นวันนี้', 'counter', '{"color": "success", "icon": "fas fa-check-circle", "query": "completed_today"}', 2),
('เวลารอเฉลี่ย', 'gauge', '{"color": "warning", "icon": "fas fa-clock", "query": "avg_wait_time", "max": 60}', 3),
('จุดบริการที่ใช้งาน', 'counter', '{"color": "info", "icon": "fas fa-map-marker-alt", "query": "active_service_points"}', 4),
('กราฟคิวรายชั่วโมง', 'chart', '{"type": "line", "query": "hourly_queues", "height": 300}', 5),
('สถานะจุดบริการ', 'table', '{"query": "service_point_status", "height": 400}', 6),
('การกระจายประเภทคิว', 'chart', '{"type": "doughnut", "query": "queue_type_distribution", "height": 300}', 7),
('คิวล่าสุด', 'table', '{"query": "recent_queues", "height": 400}', 8);

-- Reporting System Tables
-- Create tables for advanced reporting and analytics

-- Report Templates
CREATE TABLE IF NOT EXISTS report_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_description TEXT,
    report_type ENUM('queue_performance', 'service_point_analysis', 'staff_productivity', 'patient_flow', 'custom') NOT NULL,
    template_config JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Scheduled Reports
CREATE TABLE IF NOT EXISTS scheduled_reports (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    schedule_name VARCHAR(100) NOT NULL,
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_day_of_week INT NULL, -- 0=Sunday, 1=Monday, etc.
    schedule_day_of_month INT NULL, -- 1-31
    recipients JSON, -- Email addresses
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(template_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Report Execution Log
CREATE TABLE IF NOT EXISTS report_execution_log (
    execution_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NULL,
    template_id INT NOT NULL,
    execution_type ENUM('manual', 'scheduled') NOT NULL,
    parameters JSON,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    file_path VARCHAR(255) NULL,
    file_size INT NULL,
    execution_time_seconds DECIMAL(10,2) NULL,
    error_message TEXT NULL,
    executed_by INT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (schedule_id) REFERENCES scheduled_reports(schedule_id),
    FOREIGN KEY (template_id) REFERENCES report_templates(template_id),
    FOREIGN KEY (executed_by) REFERENCES users(user_id)
);

-- Report Cache for performance
CREATE TABLE IF NOT EXISTS report_cache (
    cache_id INT PRIMARY KEY AUTO_INCREMENT,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    report_data LONGTEXT,
    parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires_at (expires_at)
);

-- Performance Metrics Summary (for faster reporting)
CREATE TABLE IF NOT EXISTS daily_performance_summary (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    summary_date DATE NOT NULL,
    queue_type_id INT,
    service_point_id INT,
    total_queues INT DEFAULT 0,
    completed_queues INT DEFAULT 0,
    cancelled_queues INT DEFAULT 0,
    avg_wait_time_minutes DECIMAL(10,2) DEFAULT 0,
    avg_service_time_minutes DECIMAL(10,2) DEFAULT 0,
    max_wait_time_minutes DECIMAL(10,2) DEFAULT 0,
    peak_hour_start TIME,
    peak_hour_end TIME,
    peak_hour_queue_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_summary (summary_date, queue_type_id, service_point_id),
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id),
    FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id),
    INDEX idx_summary_date (summary_date),
    INDEX idx_queue_type (queue_type_id),
    INDEX idx_service_point (service_point_id)
);

-- Insert default report templates
INSERT INTO report_templates (template_name, template_description, report_type, template_config) VALUES
('รายงานประสิทธิภาพคิวรายวัน', 'รายงานสรุปประสิทธิภาพการให้บริการรายวัน', 'queue_performance', 
 '{"period": "daily", "metrics": ["total_queues", "avg_wait_time", "completion_rate"], "groupBy": "queue_type"}'),

('รายงานการใช้งานจุดบริการ', 'วิเคราะห์การใช้งานจุดบริการต่างๆ', 'service_point_analysis', 
 '{"period": "weekly", "metrics": ["utilization_rate", "avg_service_time", "peak_hours"], "groupBy": "service_point"}'),

('รายงานผลิตภาพเจ้าหน้าที่', 'ประเมินผลิตภาพการทำงานของเจ้าหน้าที่', 'staff_productivity', 
 '{"period": "monthly", "metrics": ["queues_served", "avg_service_time", "efficiency_score"], "groupBy": "staff"}'),

('รายงานการไหลของผู้ป่วย', 'วิเคราะห์เส้นทางการให้บริการผู้ป่วย', 'patient_flow', 
 '{"period": "weekly", "metrics": ["flow_completion_rate", "bottlenecks", "avg_flow_time"], "groupBy": "service_flow"}'),

('รายงานสรุปรายเดือน', 'รายงานสรุปภาพรวมประจำเดือน', 'queue_performance', 
 '{"period": "monthly", "metrics": ["all"], "groupBy": "month", "includeCharts": true}');

-- Create indexes for better performance
CREATE INDEX idx_queues_created_date ON queues(DATE(created_at));
CREATE INDEX idx_queues_status_date ON queues(status, DATE(created_at));
CREATE INDEX idx_queue_history_date ON queue_history(DATE(created_at));
CREATE INDEX idx_audit_logs_date ON audit_logs(DATE(created_at));

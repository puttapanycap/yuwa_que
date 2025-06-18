-- =====================================================
-- Yuwaprasart Queue System - Complete Database Schema
-- Version: 2.0.0
-- Created: 2024-01-15
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- 1. CORE SYSTEM TABLES
-- =====================================================

-- Users table
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` text,
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service types table
CREATE TABLE `service_types` (
  `service_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `service_code` varchar(10) NOT NULL,
  `description` text,
  `estimated_time` int(11) DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT 'fas fa-user-md',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_type_id`),
  UNIQUE KEY `service_code` (`service_code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service points table
CREATE TABLE `service_points` (
  `service_point_id` int(11) NOT NULL AUTO_INCREMENT,
  `point_name` varchar(100) NOT NULL,
  `point_code` varchar(10) NOT NULL,
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_point_id`),
  UNIQUE KEY `point_code` (`point_code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service flows table
CREATE TABLE `service_flows` (
  `flow_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_type_id` int(11) NOT NULL,
  `service_point_id` int(11) NOT NULL,
  `flow_order` int(11) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `estimated_time` int(11) DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`flow_id`),
  KEY `idx_service_type` (`service_type_id`),
  KEY `idx_service_point` (`service_point_id`),
  KEY `idx_flow_order` (`flow_order`),
  CONSTRAINT `fk_service_flows_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`service_type_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_service_flows_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User service points table
CREATE TABLE `user_service_points` (
  `user_id` int(11) NOT NULL,
  `service_point_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `service_point_id`),
  KEY `idx_service_point` (`service_point_id`),
  CONSTRAINT `fk_user_service_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_service_points_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. QUEUE MANAGEMENT TABLES
-- =====================================================

-- Queues table
CREATE TABLE `queues` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_number` varchar(20) NOT NULL,
  `service_type_id` int(11) NOT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `citizen_id` varchar(13) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('waiting','called','serving','completed','cancelled','no_show') NOT NULL DEFAULT 'waiting',
  `current_service_point_id` int(11) DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `notes` text,
  `estimated_wait_time` int(11) DEFAULT NULL,
  `actual_wait_time` int(11) DEFAULT NULL,
  `service_duration` int(11) DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `feedback` text,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`),
  UNIQUE KEY `queue_number_date` (`queue_number`, `created_at`),
  KEY `idx_service_type` (`service_type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_current_service_point` (`current_service_point_id`),
  KEY `idx_phone` (`phone`),
  CONSTRAINT `fk_queues_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`service_type_id`),
  CONSTRAINT `fk_queues_service_point` FOREIGN KEY (`current_service_point_id`) REFERENCES `service_points` (`service_point_id`),
  CONSTRAINT `fk_queues_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_queues_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue history table
CREATE TABLE `queue_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `service_point_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_queue_id` (`queue_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_queue_history_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_queue_history_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`),
  CONSTRAINT `fk_queue_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Queue flow tracking table
CREATE TABLE `queue_flow_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `service_point_id` int(11) NOT NULL,
  `flow_order` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','skipped') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  KEY `idx_queue_id` (`queue_id`),
  KEY `idx_service_point` (`service_point_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_queue_flow_tracking_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_queue_flow_tracking_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. AUDIO SYSTEM TABLES
-- =====================================================

-- Audio settings table
CREATE TABLE `audio_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('boolean','integer','string','json') NOT NULL DEFAULT 'string',
  `description` text,
  `category` varchar(50) DEFAULT 'general',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audio files table
CREATE TABLE `audio_files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `duration` decimal(10,2) DEFAULT NULL,
  `file_type` enum('system','custom','tts') NOT NULL DEFAULT 'custom',
  `category` varchar(50) DEFAULT 'general',
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`file_id`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_audio_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audio call history table
CREATE TABLE `audio_call_history` (
  `call_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) DEFAULT NULL,
  `service_point_id` int(11) DEFAULT NULL,
  `audio_type` enum('queue_call','announcement','system') NOT NULL DEFAULT 'queue_call',
  `message_text` text,
  `audio_file_id` int(11) DEFAULT NULL,
  `tts_used` tinyint(1) NOT NULL DEFAULT 0,
  `voice_settings` json DEFAULT NULL,
  `play_status` enum('pending','playing','completed','failed') NOT NULL DEFAULT 'pending',
  `play_duration` decimal(10,2) DEFAULT NULL,
  `error_message` text,
  `called_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `played_at` datetime DEFAULT NULL,
  PRIMARY KEY (`call_id`),
  KEY `idx_queue_id` (`queue_id`),
  KEY `idx_service_point` (`service_point_id`),
  KEY `idx_audio_type` (`audio_type`),
  KEY `idx_play_status` (`play_status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audio_call_history_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`),
  CONSTRAINT `fk_audio_call_history_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`),
  CONSTRAINT `fk_audio_call_history_audio_file` FOREIGN KEY (`audio_file_id`) REFERENCES `audio_files` (`file_id`),
  CONSTRAINT `fk_audio_call_history_called_by` FOREIGN KEY (`called_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TTS cache table
CREATE TABLE `tts_cache` (
  `cache_id` int(11) NOT NULL AUTO_INCREMENT,
  `text_hash` varchar(64) NOT NULL,
  `original_text` text NOT NULL,
  `processed_text` text NOT NULL,
  `voice_settings` json DEFAULT NULL,
  `audio_file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `duration` decimal(10,2) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 1,
  `last_used` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `text_hash` (`text_hash`),
  KEY `idx_last_used` (`last_used`),
  KEY `idx_usage_count` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. NOTIFICATION SYSTEM TABLES
-- =====================================================

-- Notification types table
CREATE TABLE `notification_types` (
  `type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 'fas fa-bell',
  `color` varchar(7) DEFAULT '#007bff',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `type_code` (`type_code`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `auto_dismiss_after` int(11) DEFAULT 5000,
  `service_point_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_is_public` (`is_public`, `is_active`, `created_at`),
  KEY `idx_service_point` (`service_point_id`, `created_at`),
  KEY `idx_user_id` (`user_id`, `read_at`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_notifications_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_notifications_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`),
  CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences table
CREATE TABLE `notification_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `telegram_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `line_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_notification_type` (`user_id`, `notification_type`),
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification deliveries table
CREATE TABLE `notification_deliveries` (
  `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `delivery_method` enum('email','sms','push','telegram','line','system') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `status` enum('pending','sent','delivered','failed','bounced') NOT NULL DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `error_message` text,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`delivery_id`),
  KEY `idx_notification_id` (`notification_id`),
  KEY `idx_delivery_method` (`delivery_method`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `fk_notification_deliveries_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. AUTO RESET SYSTEM TABLES
-- =====================================================

-- Auto reset schedules table
CREATE TABLE `auto_reset_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_name` varchar(100) NOT NULL,
  `reset_type` enum('daily','weekly','monthly','custom') NOT NULL DEFAULT 'daily',
  `reset_time` time NOT NULL DEFAULT '00:00:00',
  `reset_days` json DEFAULT NULL,
  `service_type_ids` json DEFAULT NULL,
  `backup_before_reset` tinyint(1) NOT NULL DEFAULT 1,
  `send_notification` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `idx_reset_type` (`reset_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_next_run` (`next_run`),
  CONSTRAINT `fk_auto_reset_schedules_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto reset logs table
CREATE TABLE `auto_reset_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) DEFAULT NULL,
  `reset_type` varchar(50) NOT NULL,
  `service_type_ids` json DEFAULT NULL,
  `queues_reset` int(11) NOT NULL DEFAULT 0,
  `backup_created` tinyint(1) NOT NULL DEFAULT 0,
  `backup_file` varchar(255) DEFAULT NULL,
  `status` enum('success','failed','partial') NOT NULL DEFAULT 'success',
  `error_message` text,
  `execution_time` decimal(10,3) DEFAULT NULL,
  `triggered_by` enum('schedule','manual','api') NOT NULL DEFAULT 'schedule',
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_triggered_by` (`triggered_by`),
  CONSTRAINT `fk_auto_reset_logs_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `auto_reset_schedules` (`schedule_id`),
  CONSTRAINT `fk_auto_reset_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. REPORTING SYSTEM TABLES
-- =====================================================

-- Report templates table
CREATE TABLE `report_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `template_code` varchar(50) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT 'general',
  `sql_query` text NOT NULL,
  `parameters` json DEFAULT NULL,
  `output_formats` json DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `template_code` (`template_code`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_report_templates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled reports table
CREATE TABLE `scheduled_reports` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `schedule_name` varchar(100) NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'daily',
  `schedule_time` time NOT NULL DEFAULT '08:00:00',
  `schedule_days` json DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `output_format` varchar(20) NOT NULL DEFAULT 'pdf',
  `recipients` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_frequency` (`frequency`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_next_run` (`next_run`),
  CONSTRAINT `fk_scheduled_reports_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scheduled_reports_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report execution logs table
CREATE TABLE `report_execution_logs` (
  `execution_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `report_name` varchar(100) NOT NULL,
  `parameters` json DEFAULT NULL,
  `output_format` varchar(20) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `execution_time` decimal(10,3) DEFAULT NULL,
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` text,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`execution_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_report_execution_logs_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`),
  CONSTRAINT `fk_report_execution_logs_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `scheduled_reports` (`schedule_id`),
  CONSTRAINT `fk_report_execution_logs_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily performance summary table
CREATE TABLE `daily_performance_summary` (
  `summary_id` int(11) NOT NULL AUTO_INCREMENT,
  `summary_date` date NOT NULL,
  `service_type_id` int(11) DEFAULT NULL,
  `service_point_id` int(11) DEFAULT NULL,
  `total_queues` int(11) NOT NULL DEFAULT 0,
  `completed_queues` int(11) NOT NULL DEFAULT 0,
  `cancelled_queues` int(11) NOT NULL DEFAULT 0,
  `no_show_queues` int(11) NOT NULL DEFAULT 0,
  `average_wait_time` decimal(10,2) DEFAULT NULL,
  `average_service_time` decimal(10,2) DEFAULT NULL,
  `peak_hour` varchar(5) DEFAULT NULL,
  `efficiency_rate` decimal(5,2) DEFAULT NULL,
  `satisfaction_score` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`summary_id`),
  UNIQUE KEY `unique_daily_summary` (`summary_date`, `service_type_id`, `service_point_id`),
  KEY `idx_summary_date` (`summary_date`),
  KEY `idx_service_type` (`service_type_id`),
  KEY `idx_service_point` (`service_point_id`),
  CONSTRAINT `fk_daily_performance_summary_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`service_type_id`),
  CONSTRAINT `fk_daily_performance_summary_service_point` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. DASHBOARD ANALYTICS TABLES
-- =====================================================

-- Dashboard widgets table
CREATE TABLE `dashboard_widgets` (
  `widget_id` int(11) NOT NULL AUTO_INCREMENT,
  `widget_code` varchar(50) NOT NULL,
  `widget_name` varchar(100) NOT NULL,
  `widget_type` enum('chart','metric','table','custom') NOT NULL DEFAULT 'metric',
  `description` text,
  `data_source` varchar(100) NOT NULL,
  `configuration` json DEFAULT NULL,
  `refresh_interval` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`widget_id`),
  UNIQUE KEY `widget_code` (`widget_code`),
  KEY `idx_widget_type` (`widget_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard layouts table
CREATE TABLE `dashboard_layouts` (
  `layout_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dashboard_type` enum('admin','staff','monitor') NOT NULL DEFAULT 'admin',
  `layout_name` varchar(100) NOT NULL,
  `layout_config` json NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`layout_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_dashboard_type` (`dashboard_type`),
  CONSTRAINT `fk_dashboard_layouts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard preferences table
CREATE TABLE `dashboard_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text,
  `preference_type` enum('boolean','integer','string','json') NOT NULL DEFAULT 'string',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_preference` (`user_id`, `preference_key`),
  CONSTRAINT `fk_dashboard_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard alerts table
CREATE TABLE `dashboard_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(50) NOT NULL,
  `alert_title` varchar(255) NOT NULL,
  `alert_message` text NOT NULL,
  `severity` enum('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `conditions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `triggered_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `auto_resolve` tinyint(1) NOT NULL DEFAULT 0,
  `resolve_after` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`alert_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_triggered_at` (`triggered_at`),
  CONSTRAINT `fk_dashboard_alerts_acknowledged_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. MOBILE API TABLES
-- =====================================================

-- API access tokens table
CREATE TABLE `api_access_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `token_name` varchar(100) DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_api_access_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mobile app sessions table
CREATE TABLE `mobile_app_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `device_type` enum('ios','android','web') NOT NULL DEFAULT 'web',
  `app_version` varchar(20) DEFAULT NULL,
  `fcm_token` varchar(255) DEFAULT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_mobile_app_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API request logs table
CREATE TABLE `api_request_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_data` json DEFAULT NULL,
  `response_code` int(11) NOT NULL,
  `response_time` decimal(10,3) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_response_code` (`response_code`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_api_request_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. SECURITY TABLES
-- =====================================================

-- Security logs table
CREATE TABLE `security_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `event_data` json DEFAULT NULL,
  `description` text,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_resolved` (`is_resolved`),
  CONSTRAINT `fk_security_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_security_logs_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two factor auth table
CREATE TABLE `two_factor_auth` (
  `tfa_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `secret_key` varchar(255) NOT NULL,
  `backup_codes` json DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tfa_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_two_factor_auth_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password history table
CREATE TABLE `password_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table
CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File upload logs table
CREATE TABLE `file_upload_logs` (
  `upload_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `upload_type` varchar(50) DEFAULT 'general',
  `scan_status` enum('pending','clean','infected','error') DEFAULT 'pending',
  `scan_result` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`upload_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_upload_type` (`upload_type`),
  KEY `idx_scan_status` (`scan_status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_file_upload_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. AUDIT LOGS TABLE
-- =====================================================

-- Audit logs table
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. SYSTEM SETTINGS TABLE
-- =====================================================

-- System settings table
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext,
  `setting_type` enum('boolean','integer','string','json','text') NOT NULL DEFAULT 'string',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` text,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_editable` tinyint(1) NOT NULL DEFAULT 1,
  `validation_rules` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_category` (`category`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional composite indexes for better performance
CREATE INDEX idx_queues_status_created ON queues(status, created_at);
CREATE INDEX idx_queues_service_type_status ON queues(service_type_id, status);
CREATE INDEX idx_queue_history_queue_created ON queue_history(queue_id, created_at);
CREATE INDEX idx_notifications_public_active ON notifications(is_public, is_active, created_at);
CREATE INDEX idx_audio_call_history_created ON audio_call_history(created_at, audio_type);
CREATE INDEX idx_daily_summary_date_service ON daily_performance_summary(summary_date, service_type_id);

-- =====================================================
-- TRIGGERS FOR AUDIT LOGGING
-- =====================================================

DELIMITER $$

-- Trigger for users table
CREATE TRIGGER tr_users_audit_insert AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (NEW.user_id, 'INSERT', 'users', NEW.user_id, 
            JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'full_name', NEW.full_name, 'role_id', NEW.role_id),
            @user_ip);
END$$

CREATE TRIGGER tr_users_audit_update AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.user_id, 'UPDATE', 'users', NEW.user_id,
            JSON_OBJECT('username', OLD.username, 'email', OLD.email, 'full_name', OLD.full_name, 'role_id', OLD.role_id),
            JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'full_name', NEW.full_name, 'role_id', NEW.role_id),
            @user_ip);
END$$

-- Trigger for queues table
CREATE TRIGGER tr_queues_audit_insert AFTER INSERT ON queues
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (NEW.created_by, 'INSERT', 'queues', NEW.queue_id,
            JSON_OBJECT('queue_number', NEW.queue_number, 'service_type_id', NEW.service_type_id, 'status', NEW.status),
            @user_ip);
END$$

CREATE TRIGGER tr_queues_audit_update AFTER UPDATE ON queues
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.updated_by, 'UPDATE', 'queues', NEW.queue_id,
            JSON_OBJECT('status', OLD.status, 'current_service_point_id', OLD.current_service_point_id),
            JSON_OBJECT('status', NEW.status, 'current_service_point_id', NEW.current_service_point_id),
            @user_ip);
END$$

DELIMITER ;

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for queue statistics
CREATE VIEW v_queue_statistics AS
SELECT 
    DATE(created_at) as queue_date,
    service_type_id,
    COUNT(*) as total_queues,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_queues,
    AVG(CASE WHEN actual_wait_time IS NOT NULL THEN actual_wait_time END) as avg_wait_time,
    AVG(CASE WHEN service_duration IS NOT NULL THEN service_duration END) as avg_service_time
FROM queues 
GROUP BY DATE(created_at), service_type_id;

-- View for current queue status
CREATE VIEW v_current_queue_status AS
SELECT 
    q.queue_id,
    q.queue_number,
    q.service_type_id,
    st.service_name,
    q.patient_name,
    q.status,
    q.priority,
    q.current_service_point_id,
    sp.point_name as service_point_name,
    q.created_at,
    q.called_at,
    q.estimated_wait_time
FROM queues q
LEFT JOIN service_types st ON q.service_type_id = st.service_type_id
LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
WHERE DATE(q.created_at) = CURDATE()
AND q.status IN ('waiting', 'called', 'serving');

-- View for service point performance
CREATE VIEW v_service_point_performance AS
SELECT 
    sp.service_point_id,
    sp.point_name,
    DATE(q.created_at) as performance_date,
    COUNT(q.queue_id) as total_served,
    AVG(q.service_duration) as avg_service_time,
    SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    (SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) / COUNT(q.queue_id) * 100) as completion_rate
FROM service_points sp
LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id
WHERE q.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY sp.service_point_id, DATE(q.created_at);

COMMIT;

-- =====================================================
-- STARTER DATA INSERTION
-- =====================================================

-- Insert default roles
INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `permissions`, `is_active`) VALUES
(1, 'admin', 'ผู้ดูแลระบบ', '{"all": true}', 1),
(2, 'manager', 'ผู้จัดการ', '{"users": {"view": true, "create": true, "edit": true}, "queues": {"view": true, "manage": true}, "reports": {"view": true, "create": true}, "settings": {"view": true}}', 1),
(3, 'staff', 'เจ้าหน้าที่', '{"queues": {"view": true, "manage": true}, "reports": {"view": true}}', 1),
(4, 'viewer', 'ผู้ดูข้อมูล', '{"queues": {"view": true}, "reports": {"view": true}}', 1);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role_id`, `is_active`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yuwaprasart.com', 'ผู้ดูแลระบบ', 1, 1),
(2, 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@yuwaprasart.com', 'ผู้จัดการ', 2, 1),
(3, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff1@yuwaprasart.com', 'เจ้าหน้าที่ 1', 3, 1),
(4, 'staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff2@yuwaprasart.com', 'เจ้าหน้าที่ 2', 3, 1);

-- Insert default service types
INSERT INTO `service_types` (`service_type_id`, `service_name`, `service_code`, `description`, `estimated_time`, `is_active`, `display_order`, `color`, `icon`) VALUES
(1, 'แพทย์ทั่วไป', 'A', 'บริการตรวจรักษาโดยแพทย์ทั่วไป', 15, 1, 1, '#007bff', 'fas fa-user-md'),
(2, 'แพทย์เฉพาะทาง', 'B', 'บริการตรวจรักษาโดยแพทย์เฉพาะทาง', 20, 1, 2, '#28a745', 'fas fa-stethoscope'),
(3, 'ตรวจเลือด', 'C', 'บริการตรวจเลือดและตรวจสอบสุขภาพ', 10, 1, 3, '#dc3545', 'fas fa-vial'),
(4, 'เอ็กซเรย์', 'D', 'บริการถ่ายภาพรังสี', 15, 1, 4, '#ffc107', 'fas fa-x-ray'),
(5, 'ยา', 'E', 'บริการจ่ายยาและให้คำปรึกษา', 5, 1, 5, '#17a2b8', 'fas fa-pills'),
(6, 'การเงิน', 'F', 'บริการชำระเงินและการเงิน', 10, 1, 6, '#6f42c1', 'fas fa-credit-card');

-- Insert default service points
INSERT INTO `service_points` (`service_point_id`, `point_name`, `point_code`, `description`, `is_active`, `display_order`, `location`) VALUES
(1, 'ห้องตรวจ 1', 'R001', 'ห้องตรวจแพทย์ทั่วไป ห้องที่ 1', 1, 1, 'ชั้น 1'),
(2, 'ห้องตรวจ 2', 'R002', 'ห้องตรวจแพทย์ทั่วไป ห้องที่ 2', 1, 2, 'ชั้น 1'),
(3, 'ห้องตรวจ 3', 'R003', 'ห้องตรวจแพทย์เฉพาะทาง', 1, 3, 'ชั้น 2'),
(4, 'ห้องเจาะเลือด', 'LAB1', 'ห้องเจาะเลือดและตรวจสอบ', 1, 4, 'ชั้น 1'),
(5, 'ห้องเอ็กซเรย์', 'XRAY', 'ห้องถ่ายภาพรังสี', 1, 5, 'ชั้น 1'),
(6, 'เคาน์เตอร์ยา', 'PHAR', 'เคาน์เตอร์จ่ายยา', 1, 6, 'ชั้น 1'),
(7, 'เคาน์เตอร์การเงิน', 'CASH', 'เคาน์เตอร์ชำระเงิน', 1, 7, 'ชั้น 1');

-- Insert service flows (mapping service types to service points)
INSERT INTO `service_flows` (`service_type_id`, `service_point_id`, `flow_order`, `is_required`, `estimated_time`, `is_active`) VALUES
-- แพทย์ทั่วไป -> ห้องตรวจ 1,2
(1, 1, 1, 1, 15, 1),
(1, 2, 1, 1, 15, 1),
-- แพทย์เฉพาะทาง -> ห้องตรวจ 3
(2, 3, 1, 1, 20, 1),
-- ตรวจเลือด -> ห้องเจาะเลือด
(3, 4, 1, 1, 10, 1),
-- เอ็กซเรย์ -> ห้องเอ็กซเรย์
(4, 5, 1, 1, 15, 1),
-- ยา -> เคาน์เตอร์ยา
(5, 6, 1, 1, 5, 1),
-- การเงิน -> เคาน์เตอร์การเงิน
(6, 7, 1, 1, 10, 1);

-- Insert user service point assignments
INSERT INTO `user_service_points` (`user_id`, `service_point_id`) VALUES
-- Admin can access all service points
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7),
-- Manager can access all service points
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7),
-- Staff1 assigned to examination rooms
(3, 1), (3, 2), (3, 3),
-- Staff2 assigned to lab and pharmacy
(4, 4), (4, 6), (4, 7);

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `is_editable`) VALUES
-- Application settings
('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'string', 'application', 'ชื่อแอปพลิเคชัน', 1, 1),
('app_description', 'ระบบจัดการคิวโรงพยาบาล', 'string', 'application', 'คำอธิบายแอปพลิเคชัน', 1, 1),
('app_version', '2.0.0', 'string', 'application', 'เวอร์ชันแอปพลิเคชัน', 1, 0),
('app_timezone', 'Asia/Bangkok', 'string', 'application', 'เขตเวลา', 1, 1),
('app_language', 'th', 'string', 'application', 'ภาษาเริ่มต้น', 1, 1),

-- Queue settings
('queue_prefix_length', '1', 'integer', 'queue', 'ความยาว Prefix คิว', 1, 1),
('queue_number_length', '3', 'integer', 'queue', 'ความยาวหมายเลขคิว', 1, 1),
('max_queue_per_day', '999', 'integer', 'queue', 'จำนวนคิวสูงสุดต่อวัน', 1, 1),
('queue_timeout_minutes', '30', 'integer', 'queue', 'เวลาหมดอายุคิว (นาที)', 1, 1),
('display_refresh_interval', '3', 'integer', 'queue', 'ความถี่ในการรีเฟรชหน้าจอ (วินาที)', 1, 1),
('enable_priority_queue', 'true', 'boolean', 'queue', 'เปิดใช้งานคิวพิเศษ', 1, 1),
('auto_forward_enabled', 'false', 'boolean', 'queue', 'ส่งต่อคิวอัตโนมัติ', 1, 1),

-- Working hours
('working_hours_start', '08:00', 'string', 'schedule', 'เวลาเปิดทำการ', 1, 1),
('working_hours_end', '16:00', 'string', 'schedule', 'เวลาปิดทำการ', 1, 1),

-- Audio/TTS settings
('tts_enabled', 'true', 'boolean', 'audio', 'เปิดใช้งานระบบเสียงเรียกคิว', 1, 1),
('tts_provider', 'browser', 'string', 'audio', 'ผู้ให้บริการ TTS', 1, 1),
('tts_language', 'th-TH', 'string', 'audio', 'ภาษา TTS', 1, 1),
('tts_speed', '1.0', 'string', 'audio', 'ความเร็วเสียง', 1, 1),
('audio_volume', '1.0', 'string', 'audio', 'ระดับเสียง', 1, 1),
('audio_repeat_count', '2', 'integer', 'audio', 'จำนวนครั้งที่เล่นซ้ำ', 1, 1),

-- Email settings
('email_notifications', 'false', 'boolean', 'email', 'เปิดใช้งานการแจ้งเตือนทางอีเมล', 0, 1),
('mail_host', 'smtp.gmail.com', 'string', 'email', 'SMTP Host', 0, 1),
('mail_port', '587', 'integer', 'email', 'SMTP Port', 0, 1),
('mail_encryption', 'tls', 'string', 'email', 'การเข้ารหัส', 0, 1),
('mail_from_address', 'noreply@yuwaprasart.com', 'string', 'email', 'อีเมลผู้ส่ง', 0, 1),
('mail_from_name', 'Yuwaprasart Queue System', 'string', 'email', 'ชื่อผู้ส่ง', 0, 1),

-- Telegram settings
('telegram_notifications', 'false', 'boolean', 'telegram', 'เปิดใช้งานการแจ้งเตือนทาง Telegram', 0, 1),
('telegram_notify_template', 'คิว {queue_number} กรุณามาที่จุดบริการ {service_point}', 'string', 'telegram', 'เทมเพลตข้อความ', 0, 1);

-- Insert audio settings
INSERT INTO `audio_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_active`) VALUES
('enable_tts', 'true', 'boolean', 'tts', 'เปิดใช้งาน Text-to-Speech', 1),
('tts_language', 'th-TH', 'string', 'tts', 'ภาษา TTS', 1),
('tts_voice', 'Google Thai', 'string', 'tts', 'เสียง TTS', 1),
('tts_rate', '1.0', 'string', 'tts', 'ความเร็วเสียง', 1),
('tts_pitch', '1.0', 'string', 'tts', 'ระดับเสียง', 1),
('audio_volume', '1.0', 'string', 'playback', 'ระดับเสียง', 1),
('call_repeat', '2', 'integer', 'playback', 'จำนวนครั้งที่เล่นซ้ำ', 1),
('enable_sound_effects', 'true', 'boolean', 'playback', 'เปิดใช้งานเสียงเอฟเฟกต์', 1),
('read_letters_in_thai', 'true', 'boolean', 'tts', 'อ่านตัวอักษรเป็นภาษาไทย', 1),
('separate_queue_characters', 'true', 'boolean', 'tts', 'แยกตัวอักษรและตัวเลข', 1),
('pause_between_characters', '500', 'integer', 'tts', 'ช่วงหยุดระหว่างตัวอักษร (ms)', 1),
('queue_number_repeat', '2', 'integer', 'tts', 'จำนวนครั้งที่อ่านหมายเลขคิว', 1);

-- Insert notification types
INSERT INTO `notification_types` (`type_code`, `type_name`, `description`, `icon`, `color`, `is_public`, `is_active`) VALUES
('queue_called', 'เรียกคิว', 'แจ้งเตือนเมื่อมีการเรียกคิว', 'fas fa-bullhorn', '#28a745', 1, 1),
('queue_completed', 'คิวเสร็จสิ้น', 'แจ้งเตือนเมื่อคิวเสร็จสิ้น', 'fas fa-check-circle', '#17a2b8', 0, 1),
('queue_cancelled', 'ยกเลิกคิว', 'แจ้งเตือนเมื่อมีการยกเลิกคิว', 'fas fa-times-circle', '#dc3545', 0, 1),
('system_alert', 'แจ้งเตือนระบบ', 'แจ้งเตือนเหตุการณ์สำคัญของระบบ', 'fas fa-exclamation-triangle', '#ffc107', 0, 1),
('announcement', 'ประกาศ', 'ประกาศทั่วไป', 'fas fa-bell', '#007bff', 1, 1),
('maintenance', 'บำรุงรักษา', 'แจ้งเตือนการบำรุงรักษาระบบ', 'fas fa-tools', '#6c757d', 1, 1);

-- Insert dashboard widgets
INSERT INTO `dashboard_widgets` (`widget_code`, `widget_name`, `widget_type`, `description`, `data_source`, `configuration`, `refresh_interval`, `is_active`) VALUES
('today_queue_summary', 'สรุปคิววันนี้', 'metric', 'สรุปสถิติคิวของวันนี้', 'api/get_today_stats.php', '{"metrics": ["total", "waiting", "completed", "cancelled"]}', 30, 1),
('queue_by_status', 'คิวตามสถานะ', 'chart', 'แผนภูมิแสดงการกระจายคิวตามสถานะ', 'api/get_dashboard_widgets.php', '{"chart_type": "pie", "widget": "queue_by_status"}', 60, 1),
('service_point_status', 'สถานะจุดบริการ', 'table', 'สถานะปัจจุบันของจุดบริการทั้งหมด', 'api/get_service_points_status.php', '{"columns": ["point_name", "current_queue", "status", "wait_time"]}', 30, 1),
('hourly_queue_trend', 'แนวโน้มคิวรายชั่วโมง', 'chart', 'แผนภูมิแสดงแนวโน้มการสร้างคิวรายชั่วโมง', 'api/get_dashboard_widgets.php', '{"chart_type": "line", "widget": "hourly_queue_trend"}', 300, 1),
('recent_activity', 'กิจกรรมล่าสุด', 'table', 'กิจกรรมคิวล่าสุด', 'api/get_recent_activity.php', '{"limit": 10, "columns": ["time", "queue_number", "action", "service_point"]}', 30, 1),
('avg_wait_time', 'เวลารอเฉลี่ย', 'chart', 'เวลารอเฉลี่ยตามประเภทบริการ', 'api/get_dashboard_widgets.php', '{"chart_type": "bar", "widget": "avg_wait_time"}', 300, 1);

-- Insert report templates
INSERT INTO `report_templates` (`template_code`, `template_name`, `description`, `category`, `sql_query`, `parameters`, `output_formats`, `is_public`, `is_active`) VALUES
('daily_queue_summary', 'สรุปคิวรายวัน', 'รายงานสรุปสถิติคิวรายวัน', 'daily', 
'SELECT DATE(q.created_at) as date, st.service_name, COUNT(*) as total_queues, 
SUM(CASE WHEN q.status = "completed" THEN 1 ELSE 0 END) as completed,
SUM(CASE WHEN q.status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
AVG(q.actual_wait_time) as avg_wait_time
FROM queues q 
JOIN service_types st ON q.service_type_id = st.service_type_id 
WHERE DATE(q.created_at) BETWEEN :date_from AND :date_to 
GROUP BY DATE(q.created_at), st.service_name 
ORDER BY DATE(q.created_at), st.service_name',
'{"date_from": {"type": "date", "required": true, "label": "วันที่เริ่มต้น"}, "date_to": {"type": "date", "required": true, "label": "วันที่สิ้นสุด"}}',
'["csv", "excel", "pdf"]', 1, 1),

('service_point_performance', 'ประสิทธิภาพจุดบริการ', 'รายงานประสิทธิภาพการทำงานของจุดบริการ', 'performance',
'SELECT sp.point_name, COUNT(q.queue_id) as total_queues,
AVG(q.service_duration) as avg_service_time,
MAX(q.service_duration) as max_service_time,
(SUM(CASE WHEN q.status = "completed" THEN 1 ELSE 0 END) / COUNT(q.queue_id) * 100) as completion_rate
FROM service_points sp
LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id
WHERE DATE(q.created_at) BETWEEN :date_from AND :date_to
GROUP BY sp.point_name ORDER BY sp.point_name',
'{"date_from": {"type": "date", "required": true, "label": "วันที่เริ่มต้น"}, "date_to": {"type": "date", "required": true, "label": "วันที่สิ้นสุด"}}',
'["csv", "excel", "pdf"]', 0, 1),

('user_activity_report', 'รายงานกิจกรรมผู้ใช้', 'รายงานกิจกรรมการทำงานของผู้ใช้', 'activity',
'SELECT u.username, u.full_name, COUNT(q.queue_id) as queues_processed,
AVG(q.service_duration) as avg_service_time,
SUM(CASE WHEN q.status = "completed" THEN 1 ELSE 0 END) as completed_queues
FROM users u
LEFT JOIN queues q ON u.user_id = q.updated_by
WHERE q.status = "completed" AND DATE(q.completed_at) BETWEEN :date_from AND :date_to
GROUP BY u.username, u.full_name
ORDER BY COUNT(q.queue_id) DESC',
'{"date_from": {"type": "date", "required": true, "label": "วันที่เริ่มต้น"}, "date_to": {"type": "date", "required": true, "label": "วันที่สิ้นสุด"}}',
'["csv", "excel", "pdf"]', 0, 1);

-- Insert auto reset schedules
INSERT INTO `auto_reset_schedules` (`schedule_name`, `reset_type`, `reset_time`, `reset_days`, `service_type_ids`, `backup_before_reset`, `send_notification`, `is_active`, `created_by`) VALUES
('รีเซ็ตคิวรายวัน', 'daily', '00:00:00', NULL, NULL, 1, 1, 1, 1),
('รีเซ็ตคิวสัปดาห์', 'weekly', '00:00:00', '[1]', NULL, 1, 1, 0, 1),
('รีเซ็ตคิวรายเดือน', 'monthly', '00:00:00', '[1]', NULL, 1, 1, 0, 1);

-- Insert sample queues for testing (today's date)
INSERT INTO `queues` (`queue_number`, `service_type_id`, `patient_name`, `phone`, `priority`, `status`, `current_service_point_id`, `created_by`, `created_at`) VALUES
('A001', 1, 'สมชาย ใจดี', '0812345678', 'normal', 'completed', 1, 3, NOW() - INTERVAL 2 HOUR),
('A002', 1, 'สมหญิง รักดี', '0823456789', 'normal', 'completed', 2, 3, NOW() - INTERVAL 1 HOUR),
('A003', 1, 'สมศักดิ์ มีสุข', '0834567890', 'high', 'serving', 1, 3, NOW() - INTERVAL 30 MINUTE),
('B001', 2, 'สมปอง สุขใจ', '0845678901', 'normal', 'waiting', NULL, 3, NOW() - INTERVAL 15 MINUTE),
('C001', 3, 'สมใส ใสใจ', '0856789012', 'normal', 'called', 4, 4, NOW() - INTERVAL 10 MINUTE),
('E001', 5, 'สมหมาย ดีใจ', '0867890123', 'normal', 'waiting', NULL, 4, NOW() - INTERVAL 5 MINUTE);

-- Insert queue history for the sample queues
INSERT INTO `queue_history` (`queue_id`, `action`, `old_status`, `new_status`, `service_point_id`, `user_id`, `notes`) VALUES
(1, 'created', NULL, 'waiting', NULL, 3, 'สร้างคิวใหม่'),
(1, 'called', 'waiting', 'called', 1, 3, 'เรียกคิว'),
(1, 'served', 'called', 'serving', 1, 3, 'เริ่มให้บริการ'),
(1, 'completed', 'serving', 'completed', 1, 3, 'เสร็จสิ้นการให้บริการ'),
(2, 'created', NULL, 'waiting', NULL, 3, 'สร้างคิวใหม่'),
(2, 'called', 'waiting', 'called', 2, 3, 'เรียกคิว'),
(2, 'served', 'called', 'serving', 2, 3, 'เริ่มให้บริการ'),
(2, 'completed', 'serving', 'completed', 2, 3, 'เสร็จสิ้นการให้บริการ'),
(3, 'created', NULL, 'waiting', NULL, 3, 'สร้างคิวใหม่'),
(3, 'called', 'waiting', 'called', 1, 3, 'เรียกคิว'),
(3, 'served', 'called', 'serving', 1, 3, 'เริ่มให้บริการ'),
(4, 'created', NULL, 'waiting', NULL, 3, 'สร้างคิวใหม่'),
(5, 'created', NULL, 'waiting', NULL, 4, 'สร้างคิวใหม่'),
(5, 'called', 'waiting', 'called', 4, 4, 'เรียกคิว'),
(6, 'created', NULL, 'waiting', NULL, 4, 'สร้างคิวใหม่');

-- Insert sample notifications
INSERT INTO `notifications` (`notification_type`, `title`, `message`, `priority`, `is_public`, `service_point_id`, `created_by`) VALUES
('queue_called', 'เรียกคิว A003', 'หมายเลข A003 กรุณามาที่ห้องตรวจ 1', 'high', 1, 1, 3),
('queue_called', 'เรียกคิว C001', 'หมายเลข C001 กรุณามาที่ห้องเจาะเลือด', 'normal', 1, 4, 4),
('announcement', 'ประกาศ', 'ระบบจะปิดปรับปรุงในวันอาทิตย์ที่ 21 มกราคม 2567 เวลา 08:00-12:00 น.', 'normal', 1, NULL, 1);

-- Insert sample audio call history
INSERT INTO `audio_call_history` (`queue_id`, `service_point_id`, `audio_type`, `message_text`, `tts_used`, `play_status`, `called_by`) VALUES
(1, 1, 'queue_call', 'หมายเลข เอ ศูนย์ ศูนย์ หนึ่ง เชิญที่ห้องตรวจ 1', 1, 'completed', 3),
(2, 2, 'queue_call', 'หมายเลข เอ ศูนย์ ศูนย์ สอง เชิญที่ห้องตรวจ 2', 1, 'completed', 3),
(3, 1, 'queue_call', 'หมายเลข เอ ศูนย์ ศูนย์ สาม เชิญที่ห้องตรวจ 1', 1, 'completed', 3),
(5, 4, 'queue_call', 'หมายเลข ซี ศูนย์ ศูนย์ หนึ่ง เชิญที่ห้องเจาะเลือด', 1, 'completed', 4);

-- Insert sample daily performance summary
INSERT INTO `daily_performance_summary` (`summary_date`, `service_type_id`, `service_point_id`, `total_queues`, `completed_queues`, `cancelled_queues`, `no_show_queues`, `average_wait_time`, `average_service_time`, `efficiency_rate`, `satisfaction_score`) VALUES
(CURDATE(), 1, 1, 15, 12, 2, 1, 12.5, 8.3, 80.0, 4.2),
(CURDATE(), 1, 2, 18, 15, 2, 1, 10.2, 7.8, 83.3, 4.5),
(CURDATE(), 2, 3, 8, 7, 1, 0, 15.8, 12.5, 87.5, 4.3),
(CURDATE(), 3, 4, 25, 23, 1, 1, 8.5, 5.2, 92.0, 4.6),
(CURDATE(), 4, 5, 12, 11, 0, 1, 18.3, 15.2, 91.7, 4.1),
(CURDATE(), 5, 6, 35, 33, 1, 1, 5.2, 3.8, 94.3, 4.7),
(CURDATE(), 6, 7, 22, 20, 1, 1, 7.8, 6.2, 90.9, 4.4);

-- Insert sample dashboard preferences for admin user
INSERT INTO `dashboard_preferences` (`user_id`, `preference_key`, `preference_value`, `preference_type`) VALUES
(1, 'default_dashboard', 'admin', 'string'),
(1, 'refresh_interval', '30', 'integer'),
(1, 'show_notifications', 'true', 'boolean'),
(1, 'theme', 'light', 'string'),
(1, 'language', 'th', 'string');

-- Insert sample security log
INSERT INTO `security_logs` (`event_type`, `severity`, `user_id`, `ip_address`, `description`) VALUES
('login_success', 'low', 1, '127.0.0.1', 'ผู้ดูแลระบบเข้าสู่ระบบสำเร็จ'),
('login_success', 'low', 3, '127.0.0.1', 'เจ้าหน้าที่เข้าสู่ระบบสำเร็จ'),
('settings_changed', 'medium', 1, '127.0.0.1', 'มีการเปลี่ยนแปลงการตั้งค่าระบบ');

-- Update queue numbers with actual wait and service times for completed queues
UPDATE `queues` SET 
    `actual_wait_time` = 15,
    `service_duration` = 8,
    `called_at` = `created_at` + INTERVAL 15 MINUTE,
    `served_at` = `created_at` + INTERVAL 20 MINUTE,
    `completed_at` = `created_at` + INTERVAL 28 MINUTE
WHERE `queue_id` = 1;

UPDATE `queues` SET 
    `actual_wait_time` = 10,
    `service_duration` = 12,
    `called_at` = `created_at` + INTERVAL 10 MINUTE,
    `served_at` = `created_at` + INTERVAL 15 MINUTE,
    `completed_at` = `created_at` + INTERVAL 27 MINUTE
WHERE `queue_id` = 2;

UPDATE `queues` SET 
    `actual_wait_time` = 25,
    `called_at` = `created_at` + INTERVAL 25 MINUTE,
    `served_at` = `created_at` + INTERVAL 30 MINUTE
WHERE `queue_id` = 3;

UPDATE `queues` SET 
    `called_at` = `created_at` + INTERVAL 5 MINUTE
WHERE `queue_id` = 5;

-- Set next run times for auto reset schedules
UPDATE `auto_reset_schedules` SET 
    `next_run` = DATE_ADD(CURDATE() + INTERVAL 1 DAY, INTERVAL 0 HOUR)
WHERE `reset_type` = 'daily';

UPDATE `auto_reset_schedules` SET 
    `next_run` = DATE_ADD(DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY), INTERVAL 0 HOUR)
WHERE `reset_type` = 'weekly';

UPDATE `auto_reset_schedules` SET 
    `next_run` = DATE_ADD(LAST_DAY(CURDATE()) + INTERVAL 1 DAY, INTERVAL 0 HOUR)
WHERE `reset_type` = 'monthly';

-- =====================================================
-- END OF SCHEMA
-- =====================================================

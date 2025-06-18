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
-- END OF SCHEMA
-- =====================================================

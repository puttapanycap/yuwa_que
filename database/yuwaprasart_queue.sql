/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80030 (8.0.30)
 Source Host           : localhost:3306
 Source Schema         : yuwaprasart_queue

 Target Server Type    : MySQL
 Target Server Version : 80030 (8.0.30)
 File Encoding         : 65001

 Date: 01/10/2025 10:17:05
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for api_settings
-- ----------------------------
DROP TABLE IF EXISTS `api_settings`;
CREATE TABLE `api_settings`  (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_settings
-- ----------------------------

-- ----------------------------
-- Table structure for api_usage_logs
-- ----------------------------
DROP TABLE IF EXISTS `api_usage_logs`;
CREATE TABLE `api_usage_logs`  (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int NULL DEFAULT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `method` enum('GET','POST','PUT','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `request_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `response_code` int NULL DEFAULT NULL,
  `response_time_ms` int NULL DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_endpoint`(`endpoint` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE,
  INDEX `idx_registration_id`(`registration_id` ASC) USING BTREE,
  CONSTRAINT `fk_aul_reg` FOREIGN KEY (`registration_id`) REFERENCES `mobile_app_registrations` (`registration_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of api_usage_logs
-- ----------------------------

-- ----------------------------
-- Table structure for audio_call_history
-- ----------------------------
DROP TABLE IF EXISTS `audio_call_history`;
CREATE TABLE `audio_call_history`  (
  `call_id` int NOT NULL AUTO_INCREMENT,
  `queue_id` int NULL DEFAULT NULL,
  `service_point_id` int NULL DEFAULT NULL,
  `staff_id` int NULL DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `call_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tts_used` tinyint(1) NULL DEFAULT 0,
  `audio_status` enum('pending','played','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending',
  PRIMARY KEY (`call_id`) USING BTREE,
  INDEX `fk_ach_queue`(`queue_id` ASC) USING BTREE,
  INDEX `fk_ach_sp`(`service_point_id` ASC) USING BTREE,
  INDEX `fk_ach_staff`(`staff_id` ASC) USING BTREE,
  CONSTRAINT `fk_ach_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_ach_sp` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_ach_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 247 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of audio_call_history
-- ----------------------------
INSERT INTO `audio_call_history` VALUES (1, 5, 1, 1, 'หมายเลข B 0 0 1 เชิญที่ จุดคัดกรอง', '2025-07-29 15:20:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (2, 5, 1, 1, 'หมายเลข B 0 0 1 เชิญที่ จุดคัดกรอง', '2025-07-29 15:20:59', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (3, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:23:40', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (4, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:38:15', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (5, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:41:49', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (6, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:52:15', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (7, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:52:16', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (8, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:55:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (9, 6, 1, NULL, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:55:33', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (10, 6, 1, NULL, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:56:37', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (11, 6, 1, NULL, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:57:56', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (12, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 15:58:40', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (13, 7, 1, 1, 'หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '2025-07-29 16:00:26', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (14, 7, 1, 1, 'หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '2025-07-29 16:25:12', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (15, 7, 1, 1, 'หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '2025-07-29 16:25:42', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (16, 7, 1, 1, 'หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '2025-07-29 16:26:10', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (17, 7, 1, 1, 'หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '2025-07-29 16:26:10', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (18, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:26:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (19, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:26:28', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (20, 7, 2, 1, 'หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '2025-07-29 16:26:37', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (21, 1, 1, NULL, 'หมายเลข A 0 0 1 เชิญที่ จุดคัดกรอง', '2025-07-29 16:46:51', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (22, 7, 2, NULL, 'หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '2025-07-29 16:52:36', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (23, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:52:43', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (24, 7, 2, 1, 'หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '2025-07-29 16:52:49', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (25, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:54:50', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (26, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:55:00', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (27, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:55:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (28, 6, 1, 1, 'หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '2025-07-29 16:55:18', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (29, 8, 1, 1, 'หมายเลข A 0 0 5 เชิญที่ จุดคัดกรอง', '2025-07-29 16:55:36', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (30, 8, 1, 1, 'หมายเลข A 0 0 5 เชิญที่ จุดคัดกรอง', '2025-07-29 16:55:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (31, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:02:32', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (32, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:02:44', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (33, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:02:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (34, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:03:00', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (35, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:03:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (36, 7, 2, 1, 'ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-07-29 17:03:18', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (37, 8, 1, 1, 'ขอเชิญหมายเลข A 0 0 5 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:03:30', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (38, 8, 1, 1, 'ขอเชิญหมายเลข A 0 0 5 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:03:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (39, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:04:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (40, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:04:36', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (41, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:05:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (42, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:05:12', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (43, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:05:15', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (44, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-29 17:05:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (45, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-30 09:29:39', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (46, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-31 16:10:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (47, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-31 16:20:05', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (48, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-07-31 16:20:34', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (49, 9, 1, 1, 'ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '2025-08-13 16:39:21', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (50, 10, 1, 1, 'ขอเชิญหมายเลข T 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-13 16:40:12', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (51, 11, 1, 1, 'ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-13 16:40:18', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (52, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-13 16:40:24', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (53, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-21 10:31:23', 1, 'played');
INSERT INTO `audio_call_history` VALUES (54, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-21 10:31:28', 1, 'played');
INSERT INTO `audio_call_history` VALUES (55, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-21 10:45:55', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (56, 12, 1, 1, 'หมายเลข A002 เชิญที่ จุดคัดกรอง', '2025-08-22 14:18:13', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (57, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-26 11:10:08', 1, 'played');
INSERT INTO `audio_call_history` VALUES (58, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-26 11:10:12', 1, 'played');
INSERT INTO `audio_call_history` VALUES (59, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-27 09:21:20', 1, 'played');
INSERT INTO `audio_call_history` VALUES (60, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-27 09:21:30', 1, 'played');
INSERT INTO `audio_call_history` VALUES (61, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-27 10:13:00', 1, 'played');
INSERT INTO `audio_call_history` VALUES (62, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-27 10:13:40', 1, 'played');
INSERT INTO `audio_call_history` VALUES (63, NULL, NULL, 1, 'ทดสอบระบบเสียง: หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-08-29 09:54:21', 1, 'played');
INSERT INTO `audio_call_history` VALUES (64, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:22:37', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (65, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-08-29 10:22:44', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (66, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-08-29 10:22:46', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (67, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:28:55', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (68, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:29:34', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (69, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-08-29 10:29:39', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (70, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:31:39', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (71, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-08-29 10:31:44', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (72, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:33:46', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (73, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:34:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (74, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:34:34', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (75, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:34:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (76, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:36:56', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (77, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 10:36:59', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (78, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:07:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (79, 12, 1, 1, 'ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:07:30', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (80, 13, 1, 1, 'ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:07:45', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (81, 13, 1, 1, 'ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:08:21', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (82, 13, 1, 1, 'ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:08:35', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (83, 13, 1, 1, 'ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:08:38', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (84, 13, 1, 1, 'ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:08:50', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (85, 14, 1, 1, 'ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:09:32', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (86, 14, 1, 1, 'ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '2025-08-29 11:09:53', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (87, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุดคัดกรอง ครับ', '2025-09-02 10:31:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (88, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:31:26', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (89, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:31:35', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (90, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:31:53', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (91, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-02 10:32:06', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (92, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:32:11', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (93, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:32:47', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (94, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:33:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (95, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:33:51', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (96, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:34:17', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (97, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:35:24', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (98, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:35:33', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (99, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:35:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (100, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:36:06', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (101, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:36:10', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (102, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:36:22', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (103, 16, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '2025-09-02 10:36:37', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (104, 16, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:07:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (105, 16, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:08:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (106, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:08:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (107, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-03 15:09:22', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (108, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-03 15:09:25', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (109, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:19:38', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (110, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:19:54', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (111, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '2025-09-03 15:20:03', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (112, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ จุด ห้องตรวจ 1 ครับ', '2025-09-03 15:20:12', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (113, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:20:26', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (114, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:20:32', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (115, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:20:47', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (116, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:20:59', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (117, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:30:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (118, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:37:53', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (119, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:38:02', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (120, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-03 15:38:16', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (121, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:38:21', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (122, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:38:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (123, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:38:42', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (124, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:44:00', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (125, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 15:44:59', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (126, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 15:45:05', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (127, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 15:45:14', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (128, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 15:45:23', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (129, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 15:49:13', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (130, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 15:49:15', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (131, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:13:16', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (132, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 16:13:17', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (133, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:13:28', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (134, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:13:40', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (135, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-03 16:21:49', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (136, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:24:30', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (137, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:24:52', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (138, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-03 16:25:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (139, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-08 15:40:13', 0, 'played');
INSERT INTO `audio_call_history` VALUES (140, NULL, 1, 1, 'ขอเชิญ {คัดกรอง}', '2025-09-09 14:05:34', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (141, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '2025-09-09 14:05:39', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (142, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:06:01', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (143, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:06:40', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (144, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:07:05', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (145, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:07:07', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (146, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:08:04', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (147, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:08:07', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (148, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:08:40', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (149, 19, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:09:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (150, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:10:24', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (151, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:17:08', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (152, 18, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:18:22', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (153, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-09 14:18:52', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (154, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:50:00', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (155, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '2025-09-11 09:50:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (156, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:50:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (157, 17, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:50:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (158, 16, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:33', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (159, 15, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:39', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (160, 14, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:45', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (161, 13, 1, 1, 'ขอเชิญ หมายเลข B 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (162, 12, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:54', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (163, 11, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 09:51:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (164, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '2025-09-11 11:26:44', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (165, 11, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 11:52:14', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (166, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-11 11:52:17', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (167, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-11 11:52:27', 0, 'played');
INSERT INTO `audio_call_history` VALUES (168, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-11 11:52:27', 0, 'played');
INSERT INTO `audio_call_history` VALUES (169, 7, 2, 1, 'ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '2025-09-11 11:52:43', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (170, 11, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 12:15:16', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (171, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '2025-09-11 13:33:46', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (172, NULL, 1, 1, 'ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '2025-09-11 13:33:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (173, 11, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-11 14:03:48', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (174, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-11 14:41:23', 0, 'played');
INSERT INTO `audio_call_history` VALUES (175, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:22:57', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (176, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:23:06', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (177, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:23:14', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (178, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:23:33', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (179, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:23:35', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (180, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:24:28', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (181, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:24:42', 0, 'played');
INSERT INTO `audio_call_history` VALUES (182, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:24:50', 0, 'played');
INSERT INTO `audio_call_history` VALUES (183, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:29:19', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (184, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:29:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (185, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:31:11', 0, 'played');
INSERT INTO `audio_call_history` VALUES (186, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:31:27', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (187, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:31:36', 0, 'played');
INSERT INTO `audio_call_history` VALUES (188, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:31:41', 0, 'played');
INSERT INTO `audio_call_history` VALUES (189, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:33:04', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (190, 8, 1, NULL, 'ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '2025-09-22 16:33:23', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (191, NULL, 1, NULL, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-22 16:33:31', 0, 'played');
INSERT INTO `audio_call_history` VALUES (192, 6, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:47:58', 0, 'played');
INSERT INTO `audio_call_history` VALUES (193, 6, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:48:25', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (194, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:54:10', 0, 'played');
INSERT INTO `audio_call_history` VALUES (195, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:55:02', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (196, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:55:09', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (197, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-24 15:55:21', 0, 'played');
INSERT INTO `audio_call_history` VALUES (198, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:56:51', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (199, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:56:59', 0, 'pending');
INSERT INTO `audio_call_history` VALUES (200, 1, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-24 15:57:18', 0, 'played');
INSERT INTO `audio_call_history` VALUES (201, 1, 2, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '2025-09-29 10:00:03', 0, 'played');
INSERT INTO `audio_call_history` VALUES (202, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-29 10:01:16', 0, 'played');
INSERT INTO `audio_call_history` VALUES (203, 1, 2, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '2025-09-29 10:01:25', 0, 'played');
INSERT INTO `audio_call_history` VALUES (204, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-29 10:02:30', 0, 'played');
INSERT INTO `audio_call_history` VALUES (205, 1, 2, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '2025-09-29 10:02:39', 0, 'played');
INSERT INTO `audio_call_history` VALUES (206, NULL, NULL, 1, 'ทดสอบระบบเสียง: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-09-30 15:43:22', 1, 'played');
INSERT INTO `audio_call_history` VALUES (207, NULL, NULL, 1, 'ทดสอบระบบเสียง: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-09-30 15:44:44', 1, 'played');
INSERT INTO `audio_call_history` VALUES (208, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-30 15:45:09', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (209, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-30 15:45:18', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (210, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-30 15:45:27', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (211, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-30 15:45:30', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (212, NULL, NULL, 1, 'ทดสอบระบบเสียง: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-09-30 15:45:55', 1, 'played');
INSERT INTO `audio_call_history` VALUES (213, NULL, NULL, 1, 'ทดสอบระบบเสียง: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1', '2025-09-30 15:46:44', 1, 'played');
INSERT INTO `audio_call_history` VALUES (214, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-30 16:08:28', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (215, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-09-30 16:08:34', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (216, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-09-30 16:08:37', 1, 'pending');
INSERT INTO `audio_call_history` VALUES (217, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:37:30', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (218, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:37:39', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (219, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:37:41', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (220, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:37:58', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (221, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:42:43', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (222, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:43:07', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (223, 2, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:43:11', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (224, 1, 2, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '2025-10-01 08:43:29', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (225, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:43:32', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (226, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:43:48', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (227, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:43:50', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (228, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:48:25', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (229, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:49:38', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (230, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:50:26', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (231, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:50:28', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (232, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:51:04', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (233, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:51:09', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (234, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 08:51:30', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (235, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:51:33', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (236, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:51:36', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (237, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:51:37', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (238, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 08:51:43', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (239, 4, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-10-01 09:32:14', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (240, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 09:32:21', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (241, 4, 1, 1, 'ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '2025-10-01 09:32:22', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (242, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 09:32:24', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (243, 3, 1, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '2025-10-01 09:33:04', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (244, 1, 2, 1, 'ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '2025-10-01 09:33:07', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (245, 5, 1, 1, 'ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '2025-10-01 10:16:10', 1, 'failed');
INSERT INTO `audio_call_history` VALUES (246, NULL, 1, 1, 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '2025-10-01 10:16:19', 1, 'failed');

-- ----------------------------
-- Table structure for audio_files
-- ----------------------------
DROP TABLE IF EXISTS `audio_files`;
CREATE TABLE `audio_files`  (
  `audio_id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `audio_type` enum('queue_number','service_point','message','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`audio_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 64 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of audio_files
-- ----------------------------
INSERT INTO `audio_files` VALUES (1, 'audio_68ae6c3f17033.wav', 'A', '/uploads/audio/audio_68ae6c3f17033.wav', 'queue_number', 1, '2025-08-27 09:23:59', '2025-08-27 09:23:59');
INSERT INTO `audio_files` VALUES (2, 'audio_68ae6c5cb1c66.wav', 'B', '/uploads/audio/audio_68ae6c5cb1c66.wav', 'queue_number', 1, '2025-08-27 09:24:28', '2025-08-27 09:24:28');
INSERT INTO `audio_files` VALUES (3, 'audio_68ae6c7a65cf0.wav', 'C', '/uploads/audio/audio_68ae6c7a65cf0.wav', 'queue_number', 1, '2025-08-27 09:24:58', '2025-08-27 09:24:58');
INSERT INTO `audio_files` VALUES (4, 'audio_68ae6c8a1d81e.wav', 'D', '/uploads/audio/audio_68ae6c8a1d81e.wav', 'queue_number', 1, '2025-08-27 09:25:14', '2025-08-27 09:25:14');
INSERT INTO `audio_files` VALUES (5, 'audio_68ae6ca05fe0a.wav', 'E', '/uploads/audio/audio_68ae6ca05fe0a.wav', 'queue_number', 1, '2025-08-27 09:25:36', '2025-08-27 09:25:36');
INSERT INTO `audio_files` VALUES (6, 'audio_68ae6d038b53d.wav', 'F', '/uploads/audio/audio_68ae6d038b53d.wav', 'queue_number', 1, '2025-08-27 09:27:15', '2025-08-27 09:27:15');
INSERT INTO `audio_files` VALUES (7, 'audio_68ae77aa4ba35.wav', 'G', '/uploads/audio/audio_68ae77aa4ba35.wav', 'queue_number', 1, '2025-08-27 10:12:42', '2025-08-27 10:12:42');
INSERT INTO `audio_files` VALUES (8, 'audio_68ae77bb099dd.wav', 'H', '/uploads/audio/audio_68ae77bb099dd.wav', 'queue_number', 1, '2025-08-27 10:12:59', '2025-08-27 10:12:59');
INSERT INTO `audio_files` VALUES (9, 'audio_68ae77d322334.wav', 'I', '/uploads/audio/audio_68ae77d322334.wav', 'queue_number', 1, '2025-08-27 10:13:23', '2025-08-27 10:13:23');
INSERT INTO `audio_files` VALUES (10, 'audio_68ae77e1370ad.wav', 'J', '/uploads/audio/audio_68ae77e1370ad.wav', 'queue_number', 1, '2025-08-27 10:13:37', '2025-08-27 10:13:37');
INSERT INTO `audio_files` VALUES (11, 'audio_68ae77f8cac93.wav', 'K', '/uploads/audio/audio_68ae77f8cac93.wav', 'queue_number', 1, '2025-08-27 10:14:00', '2025-08-27 10:14:00');
INSERT INTO `audio_files` VALUES (12, 'audio_68ae7802ce284.wav', 'L', '/uploads/audio/audio_68ae7802ce284.wav', 'queue_number', 1, '2025-08-27 10:14:10', '2025-08-27 10:14:10');
INSERT INTO `audio_files` VALUES (13, 'audio_68ae781a57625.wav', 'M', '/uploads/audio/audio_68ae781a57625.wav', 'queue_number', 1, '2025-08-27 10:14:34', '2025-08-27 10:14:34');
INSERT INTO `audio_files` VALUES (14, 'audio_68ae78243dc72.wav', 'N', '/uploads/audio/audio_68ae78243dc72.wav', 'queue_number', 1, '2025-08-27 10:14:44', '2025-08-27 10:14:44');
INSERT INTO `audio_files` VALUES (15, 'audio_68ae7839292a2.wav', 'O', '/uploads/audio/audio_68ae7839292a2.wav', 'queue_number', 1, '2025-08-27 10:15:05', '2025-08-27 10:15:05');
INSERT INTO `audio_files` VALUES (16, 'audio_68ae7847813c4.wav', 'P', '/uploads/audio/audio_68ae7847813c4.wav', 'queue_number', 1, '2025-08-27 10:15:19', '2025-08-27 10:15:19');
INSERT INTO `audio_files` VALUES (17, 'audio_68ae785f89da2.wav', 'R', '/uploads/audio/audio_68ae785f89da2.wav', 'queue_number', 1, '2025-08-27 10:15:43', '2025-08-27 10:15:43');
INSERT INTO `audio_files` VALUES (18, 'audio_68ae787d3ef10.wav', 'Q', '/uploads/audio/audio_68ae787d3ef10.wav', 'queue_number', 1, '2025-08-27 10:16:13', '2025-08-27 10:16:13');
INSERT INTO `audio_files` VALUES (19, 'audio_68ae7888ba231.wav', 'S', '/uploads/audio/audio_68ae7888ba231.wav', 'queue_number', 1, '2025-08-27 10:16:24', '2025-08-27 10:16:24');
INSERT INTO `audio_files` VALUES (20, 'audio_68ae7894ede09.wav', 'T', '/uploads/audio/audio_68ae7894ede09.wav', 'queue_number', 1, '2025-08-27 10:16:36', '2025-08-27 10:16:36');
INSERT INTO `audio_files` VALUES (21, 'audio_68aeb218b7059.wav', 'U', '/uploads/audio/audio_68aeb218b7059.wav', 'queue_number', 1, '2025-08-27 14:22:00', '2025-08-27 14:22:00');
INSERT INTO `audio_files` VALUES (22, 'audio_68aeca1248ee3.wav', 'V', '/uploads/audio/audio_68aeca1248ee3.wav', 'queue_number', 1, '2025-08-27 16:04:18', '2025-08-27 16:04:18');
INSERT INTO `audio_files` VALUES (23, 'audio_68aeca1d1e782.wav', 'W', '/uploads/audio/audio_68aeca1d1e782.wav', 'queue_number', 1, '2025-08-27 16:04:29', '2025-08-27 16:04:29');
INSERT INTO `audio_files` VALUES (24, 'audio_68aeca2781cd4.wav', 'X', '/uploads/audio/audio_68aeca2781cd4.wav', 'queue_number', 1, '2025-08-27 16:04:39', '2025-08-27 16:04:39');
INSERT INTO `audio_files` VALUES (25, 'audio_68aeca37eb264.wav', 'Y', '/uploads/audio/audio_68aeca37eb264.wav', 'queue_number', 1, '2025-08-27 16:04:55', '2025-08-27 16:04:55');
INSERT INTO `audio_files` VALUES (26, 'audio_68aeca5ea22b3.wav', 'Z', '/uploads/audio/audio_68aeca5ea22b3.wav', 'queue_number', 1, '2025-08-27 16:05:34', '2025-08-27 16:05:34');
INSERT INTO `audio_files` VALUES (27, 'audio_68afce69709c5.wav', '0', '/uploads/audio/audio_68afce69709c5.wav', 'queue_number', 1, '2025-08-28 10:35:05', '2025-08-29 10:33:41');
INSERT INTO `audio_files` VALUES (28, 'audio_68afce7a05afe.wav', '1', '/uploads/audio/audio_68afce7a05afe.wav', 'queue_number', 1, '2025-08-28 10:35:22', '2025-08-28 10:35:22');
INSERT INTO `audio_files` VALUES (29, 'audio_68afce8ba1128.wav', '2', '/uploads/audio/audio_68afce8ba1128.wav', 'queue_number', 1, '2025-08-28 10:35:39', '2025-08-28 10:35:39');
INSERT INTO `audio_files` VALUES (30, 'audio_68afce9525e6b.wav', '3', '/uploads/audio/audio_68afce9525e6b.wav', 'queue_number', 1, '2025-08-28 10:35:49', '2025-08-28 10:35:49');
INSERT INTO `audio_files` VALUES (31, 'audio_68afce9fd1a86.wav', '4', '/uploads/audio/audio_68afce9fd1a86.wav', 'queue_number', 1, '2025-08-28 10:35:59', '2025-08-28 10:35:59');
INSERT INTO `audio_files` VALUES (32, 'audio_68afceb2a960f.wav', '5', '/uploads/audio/audio_68afceb2a960f.wav', 'queue_number', 1, '2025-08-28 10:36:18', '2025-08-28 10:36:18');
INSERT INTO `audio_files` VALUES (33, 'audio_68afcebc87bea.wav', '6', '/uploads/audio/audio_68afcebc87bea.wav', 'queue_number', 1, '2025-08-28 10:36:28', '2025-08-28 10:36:28');
INSERT INTO `audio_files` VALUES (34, 'audio_68afcf03261b0.wav', '7', '/uploads/audio/audio_68afcf03261b0.wav', 'queue_number', 1, '2025-08-28 10:37:39', '2025-08-28 10:37:39');
INSERT INTO `audio_files` VALUES (35, 'audio_68afcf1683b80.wav', '8', '/uploads/audio/audio_68afcf1683b80.wav', 'queue_number', 1, '2025-08-28 10:37:58', '2025-08-28 10:37:58');
INSERT INTO `audio_files` VALUES (36, 'audio_68afcf21b4dd7.wav', '9', '/uploads/audio/audio_68afcf21b4dd7.wav', 'queue_number', 1, '2025-08-28 10:38:09', '2025-08-28 10:38:09');
INSERT INTO `audio_files` VALUES (38, 'audio_68afcf4a3e2f4.wav', 'ครับ', '/uploads/audio/audio_68afcf4a3e2f4.wav', 'message', 1, '2025-08-28 10:38:50', '2025-08-28 10:38:50');
INSERT INTO `audio_files` VALUES (39, 'audio_68afcf662a25e.wav', 'ขอเชิญ', '/uploads/audio/audio_68afcf662a25e.wav', 'message', 1, '2025-08-28 10:39:18', '2025-08-28 10:39:18');
INSERT INTO `audio_files` VALUES (40, 'audio_68afcf7d443bf.wav', 'คลินิค', '/uploads/audio/audio_68afcf7d443bf.wav', 'service_point', 1, '2025-08-28 10:39:41', '2025-08-28 10:39:41');
INSERT INTO `audio_files` VALUES (41, 'audio_68afcfa6e9d74.wav', 'คัดกรอง', '/uploads/audio/audio_68afcfa6e9d74.wav', 'service_point', 1, '2025-08-28 10:40:22', '2025-08-28 10:40:22');
INSERT INTO `audio_files` VALUES (42, 'audio_68afcfba4aa63.wav', 'คิว', '/uploads/audio/audio_68afcfba4aa63.wav', 'message', 1, '2025-08-28 10:40:42', '2025-08-28 10:40:42');
INSERT INTO `audio_files` VALUES (43, 'audio_68afcfddcda41.wav', 'จิตวิทยา', '/uploads/audio/audio_68afcfddcda41.wav', 'service_point', 1, '2025-08-28 10:41:17', '2025-08-28 10:41:17');
INSERT INTO `audio_files` VALUES (44, 'audio_68afd00ce1ec5.wav', 'เจาะเลือด', '/uploads/audio/audio_68afd00ce1ec5.wav', 'service_point', 1, '2025-08-28 10:42:04', '2025-08-28 10:42:04');
INSERT INTO `audio_files` VALUES (45, 'audio_68afd02b19be9.wav', 'ช่อง', '/uploads/audio/audio_68afd02b19be9.wav', 'message', 1, '2025-08-28 10:42:35', '2025-08-28 10:42:35');
INSERT INTO `audio_files` VALUES (46, 'audio_68afd067d26a4.wav', 'ตรวจสอบสิทธิ์', '/uploads/audio/audio_68afd067d26a4.wav', 'service_point', 1, '2025-08-28 10:43:35', '2025-08-28 10:43:35');
INSERT INTO `audio_files` VALUES (47, 'audio_68afd07ec890e.wav', 'ทันตกรรม', '/uploads/audio/audio_68afd07ec890e.wav', 'service_point', 1, '2025-08-28 10:43:58', '2025-08-28 10:43:58');
INSERT INTO `audio_files` VALUES (48, 'audio_68afd09cedb12.wav', 'ที่จุดรับบริการ', '/uploads/audio/audio_68afd09cedb12.wav', 'message', 1, '2025-08-28 10:44:28', '2025-08-28 10:44:28');
INSERT INTO `audio_files` VALUES (49, 'audio_68afd0b805ddc.wav', 'บริเวณ', '/uploads/audio/audio_68afd0b805ddc.wav', 'message', 1, '2025-08-28 10:44:56', '2025-08-28 10:44:56');
INSERT INTO `audio_files` VALUES (50, 'audio_68afd0e63fcd1.wav', 'รับใบนัด', '/uploads/audio/audio_68afd0e63fcd1.wav', 'service_point', 1, '2025-08-28 10:45:42', '2025-08-28 10:45:42');
INSERT INTO `audio_files` VALUES (51, 'audio_68afd0f8a1291.wav', 'รับยาเดิม', '/uploads/audio/audio_68afd0f8a1291.wav', 'service_point', 1, '2025-08-28 10:46:00', '2025-08-28 10:46:00');
INSERT INTO `audio_files` VALUES (52, 'audio_68afd1169868f.wav', 'แลป', '/uploads/audio/audio_68afd1169868f.wav', 'service_point', 1, '2025-08-28 10:46:30', '2025-08-28 10:46:30');
INSERT INTO `audio_files` VALUES (53, 'audio_68afd12790cfa.wav', 'เวชระเบียน', '/uploads/audio/audio_68afd12790cfa.wav', 'service_point', 1, '2025-08-28 10:46:47', '2025-08-28 10:46:47');
INSERT INTO `audio_files` VALUES (54, 'audio_68afd134d620a.wav', 'สวัสดีครับ', '/uploads/audio/audio_68afd134d620a.wav', 'message', 1, '2025-08-28 10:47:00', '2025-08-28 10:47:00');
INSERT INTO `audio_files` VALUES (56, 'audio_68afd14916b69.wav', 'หมายเลข', '/uploads/audio/audio_68afd14916b69.wav', 'message', 1, '2025-08-28 10:47:21', '2025-08-28 10:47:21');
INSERT INTO `audio_files` VALUES (57, 'audio_68afd15c9489c.wav', 'ห้อง', '/uploads/audio/audio_68afd15c9489c.wav', 'message', 1, '2025-08-28 10:47:40', '2025-08-28 10:47:40');
INSERT INTO `audio_files` VALUES (58, 'audio_68afd16f5b6e9.wav', 'ห้องเก็บเงิน', '/uploads/audio/audio_68afd16f5b6e9.wav', 'service_point', 1, '2025-08-28 10:47:59', '2025-08-28 10:47:59');
INSERT INTO `audio_files` VALUES (59, 'audio_68afd247b1673.wav', 'ห้องจ่ายยา', '/uploads/audio/audio_68afd247b1673.wav', 'service_point', 1, '2025-08-28 10:51:35', '2025-08-28 10:51:35');
INSERT INTO `audio_files` VALUES (60, 'audio_68afd26047b98.wav', 'ห้องตรวจ', '/uploads/audio/audio_68afd26047b98.wav', 'service_point', 1, '2025-08-28 10:52:00', '2025-08-28 10:52:00');
INSERT INTO `audio_files` VALUES (61, 'audio_68b665f69af08.wav', 'ที่', '/uploads/audio/audio_68b665f69af08.wav', 'message', 1, '2025-09-02 10:35:18', '2025-09-02 10:35:18');
INSERT INTO `audio_files` VALUES (62, 'audio_68b7f78b21e5b.wav', 'จุด', '/uploads/audio/audio_68b7f78b21e5b.wav', 'message', 1, '2025-09-03 15:08:43', '2025-09-03 15:08:43');
INSERT INTO `audio_files` VALUES (63, 'audio_68bfd4bbce9b0.wav', 'คัดกรอง', '/uploads/audio/audio_68bfd4bbce9b0.wav', 'message', 1, '2025-09-09 14:18:19', '2025-09-09 14:18:19');

-- ----------------------------
-- Table structure for audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs`  (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NULL DEFAULT NULL,
  `action_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `table_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `record_id` int NULL DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `idx_audit_logs_date`(`timestamp` ASC) USING BTREE,
  INDEX `fk_al_staff`(`staff_id` ASC) USING BTREE,
  CONSTRAINT `fk_al_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 595 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of audit_logs
-- ----------------------------
INSERT INTO `audit_logs` VALUES (1, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1333****3333', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-06-19 16:33:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (2, NULL, 'Admin area access denied: Not logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-06-19 16:33:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (3, NULL, 'สร้างคิว D001 จาก Kiosk - บัตรประชาชน: 1111****1111', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-06-19 16:35:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (4, NULL, 'Admin area access denied: Not logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 10:47:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (5, NULL, 'Admin area access denied: Not logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-21 10:47:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (6, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:21:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (7, NULL, 'Staff area access denied: Not logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:26:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (8, 1, 'เข้าสู่ระบบ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:26:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (9, 1, 'ออกจากระบบ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:26:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (10, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:27:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (11, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 2132****2313', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:28:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (12, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:31:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (13, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:31:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (14, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:31:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (15, NULL, 'สร้างคิว A002 จาก Kiosk - บัตรประชาชน: 2132****2313', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:35:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (16, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:36:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (17, 1, 'เรียกคิว D001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:41:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (18, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:42:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (19, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:42:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (20, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:42:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (21, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:42:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (22, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:52:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (23, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 14:52:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (24, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:02:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (25, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:02:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (26, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:09:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (27, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:09:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (28, NULL, 'สร้างคิว B001 จาก Kiosk - บัตรประชาชน: 2132****2313', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:13:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (29, 1, 'เรียกคิว B001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:13:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (30, 1, 'เล่นเสียงเรียกคิว: หมายเลข B 0 0 1 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:20:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (31, 1, 'เล่นเสียงเรียกคิว: หมายเลข B 0 0 1 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:20:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (32, NULL, 'สร้างคิว A003 จาก Kiosk - บัตรประชาชน: 5645****6456', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:23:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (33, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:23:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (34, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:23:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (35, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:38:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (36, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:41:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (37, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:52:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (38, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:52:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (39, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:55:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (40, NULL, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', '2025-07-29 15:55:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (41, NULL, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', '2025-07-29 15:56:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (42, NULL, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', '2025-07-29 15:57:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (43, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:58:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (44, NULL, 'สร้างคิว A004 จาก Kiosk - บัตรประชาชน: 5645****6456', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 15:59:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (45, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:00:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (46, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:00:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (47, 1, 'บันทึก Service Flow สำหรับ คิวทั่วไป', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:15:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (48, 1, 'บันทึก Service Flow สำหรับ คิวทั่วไป', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:17:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (49, 1, 'บันทึก Service Flow สำหรับ คิวทั่วไป', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:18:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (50, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:25:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (51, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:25:12', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (52, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:25:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (53, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (54, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (55, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (56, 1, 'ส่งต่อคิว A004 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (57, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (58, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (59, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (60, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:26:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (61, NULL, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 1 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'curl/8.14.1', '2025-07-29 16:46:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (62, NULL, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-29 16:52:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (63, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:52:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (64, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 4 เชิญที่ ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:52:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (65, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-29 16:54:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (66, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:54:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (67, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:54:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (68, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (69, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (70, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (71, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (72, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 3 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (73, NULL, 'สร้างคิว A005 จาก Kiosk - บัตรประชาชน: 2132****2313', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (74, 1, 'เรียกคิว A005 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (75, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 5 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (76, 1, 'เรียกคิว A005 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (77, 1, 'เล่นเสียงเรียกคิว: หมายเลข A 0 0 5 เชิญที่ จุดคัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 16:55:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (78, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:02:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (79, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:02:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (80, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:02:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (81, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:02:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (82, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:02:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (83, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (84, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (85, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (86, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (87, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข อิอิ A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (88, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (89, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 5 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (90, NULL, 'สร้างคิว A006 จาก Kiosk - บัตรประชาชน: 8456****6464', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (91, 1, 'เรียกคิว A005 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (92, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 5 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:03:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (93, 1, 'เรียกคิว A006 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:04:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (94, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:04:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (95, 1, 'เรียกคิว A006 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:04:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (96, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:04:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (97, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (98, 1, 'เรียกคิว A006 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (99, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (100, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:12', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (101, 1, 'เรียกคิว A006 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (102, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (103, 1, 'เรียกคิว A006 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (104, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-29 17:05:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (105, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-30 09:29:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (106, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-30 09:29:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (107, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-31 16:10:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (108, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-31 16:10:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (109, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-31 16:20:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (110, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-31 16:20:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (111, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-07-31 16:20:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (112, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 5645****6456', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 13:51:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (113, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 13:51:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (114, 1, 'เข้าสู่ระบบ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 13:51:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (115, NULL, 'สร้างคิว A002 จาก Kiosk - บัตรประชาชน: 8456****6464', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 15:00:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (116, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 15:00:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (117, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-08-04 15:00:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (118, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:38:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (119, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 6 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:39:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (120, 1, 'เรียกคิว T001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:11', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (121, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข T 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:12', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (122, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (123, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (124, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (125, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-13 16:40:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (126, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-15 10:23:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (127, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-15 10:23:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (128, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 08:47:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (129, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 08:47:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (130, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:30:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (131, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:31:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (132, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:31:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (133, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:31:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (134, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:31:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (135, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-21 10:45:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (136, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-22 14:14:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (137, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-22 14:14:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (138, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-22 14:18:13', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (139, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:08:54', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (140, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:08:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (141, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:10:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (142, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:10:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (143, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:10:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (144, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 11:10:12', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (145, NULL, 'สร้างคิว B001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-26 13:01:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (146, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:20:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (147, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:20:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (148, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:21:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (149, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:21:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (150, 1, 'อัพโหลดไฟล์เสียง: A', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:23:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (151, 1, 'อัพโหลดไฟล์เสียง: B', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:24:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (152, 1, 'อัพโหลดไฟล์เสียง: C', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:24:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (153, 1, 'อัพโหลดไฟล์เสียง: D', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:25:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (154, 1, 'อัพโหลดไฟล์เสียง: E', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:25:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (155, 1, 'อัพโหลดไฟล์เสียง: F', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 09:27:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (156, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:12:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (157, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:12:13', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (158, 1, 'อัพโหลดไฟล์เสียง: G', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:12:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (159, 1, 'อัพโหลดไฟล์เสียง: H', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:12:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (160, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:13:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (161, 1, 'อัพโหลดไฟล์เสียง: I', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:13:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (162, 1, 'อัพโหลดไฟล์เสียง: J', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:13:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (163, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:13:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (164, 1, 'อัพโหลดไฟล์เสียง: K', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:14:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (165, 1, 'อัพโหลดไฟล์เสียง: L', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:14:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (166, 1, 'อัพโหลดไฟล์เสียง: M', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:14:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (167, 1, 'อัพโหลดไฟล์เสียง: N', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:14:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (168, 1, 'อัพโหลดไฟล์เสียง: O', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:15:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (169, 1, 'อัพโหลดไฟล์เสียง: P', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:15:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (170, 1, 'อัพโหลดไฟล์เสียง: R', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:15:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (171, 1, 'อัพโหลดไฟล์เสียง: Q', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:16:13', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (172, 1, 'อัพโหลดไฟล์เสียง: S', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:16:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (173, 1, 'อัพโหลดไฟล์เสียง: T', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 10:16:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (174, 1, 'อัพโหลดไฟล์เสียง: U', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 14:22:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (175, 1, 'อัพโหลดไฟล์เสียง: V', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 16:04:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (176, 1, 'อัพโหลดไฟล์เสียง: W', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 16:04:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (177, 1, 'อัพโหลดไฟล์เสียง: X', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 16:04:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (178, 1, 'อัพโหลดไฟล์เสียง: Y', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 16:04:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (179, 1, 'อัพโหลดไฟล์เสียง: Z', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-27 16:05:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (180, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:15:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (181, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:15:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (182, 1, 'อัพโหลดไฟล์เสียง: 0 (ศูนย์)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:35:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (183, 1, 'อัพโหลดไฟล์เสียง: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:35:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (184, 1, 'อัพโหลดไฟล์เสียง: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:35:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (185, 1, 'อัพโหลดไฟล์เสียง: 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:35:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (186, 1, 'อัพโหลดไฟล์เสียง: 4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:35:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (187, 1, 'อัพโหลดไฟล์เสียง: 5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:36:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (188, 1, 'อัพโหลดไฟล์เสียง: 6', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:36:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (189, 1, 'อัพโหลดไฟล์เสียง: 7', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:37:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (190, 1, 'อัพโหลดไฟล์เสียง: 8', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:37:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (191, 1, 'อัพโหลดไฟล์เสียง: 9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:38:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (192, 1, 'อัพโหลดไฟล์เสียง: ขอเชิญ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:38:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (193, 1, 'อัพโหลดไฟล์เสียง: ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:38:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (194, 1, 'อัพโหลดไฟล์เสียง: ขอเชิญ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:39:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (195, 1, 'อัพโหลดไฟล์เสียง: คลินิค', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:39:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (196, 1, 'อัพโหลดไฟล์เสียง: คัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:40:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (197, 1, 'อัพโหลดไฟล์เสียง: คิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:40:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (198, 1, 'อัพโหลดไฟล์เสียง: จิตวิทยา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:41:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (199, 1, 'อัพโหลดไฟล์เสียง: เจาะเลือด', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:42:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (200, 1, 'อัพโหลดไฟล์เสียง: ช่อง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:42:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (201, 1, 'อัพโหลดไฟล์เสียง: ตรวจสอบสิทธิ์', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:43:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (202, 1, 'อัพโหลดไฟล์เสียง: ทันตกรรม', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:43:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (203, 1, 'อัพโหลดไฟล์เสียง: ที่จุดรับบริการ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:44:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (204, 1, 'อัพโหลดไฟล์เสียง: บริเวณ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:44:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (205, 1, 'อัพโหลดไฟล์เสียง: รับใบนัด', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:45:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (206, 1, 'อัพโหลดไฟล์เสียง: รับยาเดิม', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:46:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (207, 1, 'อัพโหลดไฟล์เสียง: แลป', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:46:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (208, 1, 'อัพโหลดไฟล์เสียง: เวชระเบียน', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:46:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (209, 1, 'อัพโหลดไฟล์เสียง: สวัสดีครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:47:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (210, 1, 'อัพโหลดไฟล์เสียง: สวัสดีครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:47:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (211, 1, 'อัพโหลดไฟล์เสียง: หมายเลข', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:47:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (212, 1, 'อัพโหลดไฟล์เสียง: ห้อง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:47:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (213, 1, 'อัพโหลดไฟล์เสียง: ห้องเก็บเงิน', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:47:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (214, 1, 'เพิ่มรูปแบบข้อความเสียงเรียก: 5555', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:48:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (215, 1, 'แก้ไขรูปแบบข้อความเสียงเรียก ID: 4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:49:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (216, 1, 'อัพโหลดไฟล์เสียง: ห้องจ่ายยา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:51:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (217, 1, 'อัพโหลดไฟล์เสียง: ห้องตรวจ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 10:52:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (218, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 13:09:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (219, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-28 13:09:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (220, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 09:20:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (221, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 09:20:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (222, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 09:54:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (223, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 09:54:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (224, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:22:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (225, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:22:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (226, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:22:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (227, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:22:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (228, 1, 'แก้ไขรูปแบบข้อความเสียงเรียก ID: 4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:23:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (229, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:28:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (230, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:29:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (231, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:29:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (232, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:31:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (233, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:31:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (234, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:33:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (235, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (236, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (237, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (238, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (239, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (240, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (241, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:34:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (242, 1, 'แก้ไขรูปแบบข้อความเสียงเรียก ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:36:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (243, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:36:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (244, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:36:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (245, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 10:36:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (246, 1, 'ลบไฟล์เสียง: สวัสดีครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:06:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (247, 1, 'ลบไฟล์เสียง: ขอเชิญ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:06:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (248, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:07:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (249, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:07:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (250, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 2 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:07:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (251, 1, 'เรียกคิว B001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:07:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (252, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:07:45', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (253, 1, 'เรียกคิว B001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (254, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (255, 1, 'แก้ไขรูปแบบข้อความเสียงเรียก ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (256, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (257, 1, 'เรียกคิว B001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (258, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (259, 1, 'เรียกคิว B001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (260, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข B 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:08:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (261, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:09:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (262, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:09:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (263, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:09:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (264, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:09:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (265, 1, 'เล่นเสียงเรียกคิว: ขอเชิญหมายเลข A 0 0 1 ที่ จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-08-29 11:09:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (266, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:30:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (267, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:30:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (268, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุดคัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (269, 1, 'แก้ไขจุดบริการ: จุด คัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (270, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (271, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (272, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (273, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (274, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (275, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:31:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (276, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:32:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (277, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:32:11', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (278, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:32:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (279, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:32:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (280, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:33:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (281, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:33:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (282, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:33:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (283, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:33:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (284, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:33:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (285, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:34:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (286, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:34:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (287, 1, 'อัพโหลดไฟล์เสียง: ที่', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (288, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (289, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (290, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (291, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (292, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (293, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:35:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (294, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (295, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (296, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (297, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (298, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (299, NULL, 'สร้างคิว A002 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (300, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (301, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-02 10:36:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (302, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:00:03', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (303, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:00:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (304, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:06:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (305, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:07:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (306, 1, 'อัพโหลดไฟล์เสียง: จุด', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:08:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (307, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:08:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (308, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:08:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (309, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:08:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (310, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:09:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (311, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:09:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (312, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:19:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (313, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:19:54', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (314, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (315, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:03', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (316, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ จุด ห้องตรวจ 1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:12', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (317, 1, 'แก้ไขรูปแบบข้อความเสียงเรียก ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (318, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (319, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (320, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (321, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (322, NULL, 'สร้างคิว A002 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (323, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (324, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:20:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (325, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:30:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (326, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:37:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (327, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (328, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (329, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (330, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (331, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (332, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (333, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (334, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:38:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (335, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:43:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (336, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:44:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (337, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:44:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (338, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:44:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (339, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:45:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (340, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:45:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (341, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:45:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (342, 1, 'เรียกคิว A004 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:45:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (343, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:45:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (344, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:49:13', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (345, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 15:49:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (346, NULL, 'สร้างคิว A003 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (347, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (348, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (349, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (350, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (351, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (352, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:13:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (353, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:21:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (354, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:24:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (355, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:24:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (356, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:24:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (357, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:24:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (358, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:25:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (359, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:25:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (360, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-03 16:28:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (361, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 14:56:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (362, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 14:56:54', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (363, 1, 'Staff area access denied: No role assigned', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 15:37:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (364, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 15:37:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (365, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 15:40:13', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (366, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 15:44:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (367, 1, 'อัปเดตการตั้งค่าระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-08 15:52:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (368, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-09 09:39:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (369, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-09 09:39:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (370, 1, 'ออกจากระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-09 10:12:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (371, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-09-09 10:12:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (372, NULL, 'Admin area access denied: Not logged in', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:04:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (373, NULL, 'Admin area access denied: Not logged in', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:04:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (374, 1, 'เข้าสู่ระบบ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:04:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (375, 1, 'Admin area access denied: Insufficient permissions', '192.168.200.191', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:04:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (376, 1, 'เข้าสู่ระบบ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:05:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (377, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ {คัดกรอง}', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:05:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (378, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:05:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (379, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:06:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (380, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:06:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (381, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:07:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (382, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:07:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (383, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:07:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (384, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:08:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (385, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:08:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (386, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:08:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (387, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:09:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (388, NULL, 'Staff area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:09:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (389, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:10:03', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (390, 1, 'เสร็จสิ้นคิว A003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:10:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (391, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:10:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (392, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:17:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (393, 1, 'อัพโหลดไฟล์เสียง: คัดกรอง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:18:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (394, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:18:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (395, 1, 'ยกเลิกคิว A002 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 14:18:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (396, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.191', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-09 14:18:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (397, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:47:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (398, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:47:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (399, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:47:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (400, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:50:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (401, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:50:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (402, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:50:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (403, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:50:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (404, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:50:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (405, 1, 'ส่งต่อคิว A001 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (406, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (407, 1, 'ยกเลิกคิว A002 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (408, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (409, 1, 'ยกเลิกคิว A001 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (410, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:45', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (411, 1, 'ยกเลิกคิว A001 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (412, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข B 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (413, 1, 'ยกเลิกคิว B001 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (414, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:54', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (415, 1, 'ยกเลิกคิว A002 - ผู้ป่วยไม่มา', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 09:51:56', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (416, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 09:51:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (417, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:18:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (418, 1, 'อัพเดทการตั้งค่าระบบเสียง', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 11:21:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (419, 1, 'Staff area access denied: No role assigned', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:25:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (420, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:25:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (421, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:26:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (422, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:26:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (423, 1, 'อัพเดทการตั้งค่าระบบเสียง', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 11:26:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (424, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 11:26:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (425, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:28:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (426, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:28:20', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (427, 1, 'อัพเดทการตั้งค่าระบบเสียง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:28:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (428, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:52:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (429, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:52:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (430, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:52:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (431, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 11:52:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (432, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 4 ที่ ห้องตรวจ  1 ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 11:52:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (433, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 12:15:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (434, 1, 'อัปเดตการตั้งค่าระบบ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 13:33:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (435, 1, 'อัพเดทการตั้งค่าระบบเสียง', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 13:33:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (436, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 13:33:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (437, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A001 ที่ ห้องตรวจ 1 ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 13:33:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (438, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '192.168.200.192', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Safari/537.36', '2025-09-11 14:03:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (439, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 14:41:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (440, 1, 'เริ่มให้บริการคิว A001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 15:58:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (441, 1, 'ส่งต่อคิว A001 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 15:58:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (442, 1, 'เริ่มให้บริการคิว T001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 15:58:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (443, 1, 'ส่งต่อคิว T001 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 15:58:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (444, 1, 'ส่งต่อคิว A006 ไปยังจุดบริการ ID: 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-11 15:58:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (445, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-16 15:48:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (446, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-16 16:16:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (447, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-16 16:27:08', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (448, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-16 16:28:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (449, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 09:17:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (450, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 09:19:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (451, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 09:19:53', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (452, 1, 'เรียกคิว A005 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:20:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (453, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:22:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (454, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:22:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (455, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:24:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (456, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:24:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (457, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 11:25:01', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (458, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 13:20:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (459, 1, 'Admin area access denied: Insufficient permissions', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 13:20:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (460, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 13:20:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (461, NULL, 'Staff area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 15:32:49', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (462, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-17 15:32:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (463, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 09:51:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (464, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 09:51:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (465, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:18:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (466, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:19:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (467, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:21:00', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (468, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:21:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (469, 1, 'Staff area access denied: No role assigned', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:43:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (470, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:43:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (471, 1, 'Staff area access denied: No role assigned', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 10:43:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (472, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 11:07:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (473, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-18 11:07:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (474, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-19 10:06:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (475, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-19 10:06:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (476, 1, 'Staff area access denied: No role assigned', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-19 11:48:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (477, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-19 11:48:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (478, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 09:20:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (479, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 09:20:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (480, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:22:57', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (481, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:23:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (482, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:23:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (483, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:23:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (484, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:23:35', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (485, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:24:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (486, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:24:42', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (487, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:24:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (488, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:29:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (489, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:29:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (490, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:31:11', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (491, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:31:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (492, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:31:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (493, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:31:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (494, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:33:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (495, NULL, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 5 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:33:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (496, NULL, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-22 16:33:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (497, NULL, 'Staff area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-23 14:08:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (498, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-23 14:08:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (499, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-23 14:09:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (500, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:28:47', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (501, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:28:52', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (502, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:47:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (503, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:47:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (504, 1, 'เสร็จสิ้นคิว A005', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:47:46', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (505, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:47:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (506, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:48:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (507, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:53:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (508, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:54:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (509, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:54:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (510, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:55:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (511, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:55:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (512, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:55:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (513, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:56:51', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (514, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:56:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (515, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-24 15:57:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (516, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 09:59:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (517, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 09:59:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (518, 1, 'ส่งต่อคิว A001 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 09:59:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (519, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:00:03', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (520, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:00:03', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (521, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:01:16', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (522, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:01:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (523, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:01:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (524, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:02:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (525, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:02:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (526, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-29 10:02:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (527, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:37:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (528, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:37:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (529, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:38:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (530, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:38:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (531, 1, 'เพิ่ม TTS API Service: GIN', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:40:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (532, 1, 'แก้ไข TTS API Service ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:41:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (533, 1, 'แก้ไข TTS API Service ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:43:19', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (534, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:43:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (535, 1, 'ตั้งค่า TTS API Service ที่ใช้งาน: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:44:40', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (536, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:44:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (537, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (538, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:18', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (539, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (540, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:27', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (541, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (542, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:45:55', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (543, 1, 'ตั้งค่า TTS API Service ที่ใช้งาน: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:46:23', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (544, 1, 'ทดสอบระบบเสียงเรียกคิว', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 15:46:44', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (545, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 16:08:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (546, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 16:08:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (547, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 16:08:34', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (548, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-09-30 16:08:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (549, NULL, 'Admin area access denied: Not logged in', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:36:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (550, 1, 'เข้าสู่ระบบ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (551, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (552, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (553, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:39', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (554, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:41', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (555, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:37:58', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (556, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:42:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (557, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (558, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (559, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:11', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (560, NULL, 'สร้างคิว A001 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:15', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (561, 1, 'ยกเลิกคิว A001 - ข้อมูลไม่ถูกต้อง', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (562, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:29', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (563, 1, 'เรียกคิว A001 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:31', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (564, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:32', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (565, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:48', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (566, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:43:50', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (567, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:48:25', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (568, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:49:38', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (569, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:50:26', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (570, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:50:28', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (571, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (572, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:09', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (573, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:30', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (574, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:33', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (575, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:36', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (576, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:37', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (577, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:51:43', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (578, 1, 'เริ่มให้บริการคิว A001', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:55:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (579, NULL, 'สร้างคิว A002 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 08:59:17', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (580, 1, 'reset_queue_numbers', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:26:09', 'queue_types', NULL, '{\"reset_type\":\"all\",\"affected_types\":[\"\\u0e04\\u0e34\\u0e27\\u0e17\\u0e31\\u0e48\\u0e27\\u0e44\\u0e1b\",\"\\u0e04\\u0e34\\u0e27\\u0e19\\u0e31\\u0e14\\u0e2b\\u0e21\\u0e32\\u0e22\",\"\\u0e04\\u0e34\\u0e27\\u0e40\\u0e23\\u0e48\\u0e07\\u0e14\\u0e48\\u0e27\\u0e19\",\"\\u0e04\\u0e34\\u0e27\\u0e1c\\u0e39\\u0e49\\u0e2a\\u0e39\\u0e07\\u0e2d\\u0e32\\u0e22\\u0e38\\/\\u0e1e\\u0e34\\u0e01\\u0e32\\u0e23\"]}', '{\"current_number\":0,\"reset_date\":\"2025-10-01 09:26:09\"}');
INSERT INTO `audit_logs` VALUES (581, 1, 'เรียกคิว A002 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:32:05', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (582, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:32:14', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (583, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:32:21', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (584, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 2 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:32:22', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (585, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:32:24', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (586, 1, 'เสร็จสิ้นคิว A002', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:33:02', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (587, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:33:04', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (588, 1, 'ส่งต่อคิว A001 ไปยังจุดบริการ ID: 2', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:33:06', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (589, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 1 ที่ ห้องตรวจ  1 ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 09:33:07', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (590, 1, 'reset_queue_numbers', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 10:15:15', 'queue_types', NULL, '{\"reset_type\":\"all\",\"affected_types\":[\"\\u0e04\\u0e34\\u0e27\\u0e17\\u0e31\\u0e48\\u0e27\\u0e44\\u0e1b\",\"\\u0e04\\u0e34\\u0e27\\u0e19\\u0e31\\u0e14\\u0e2b\\u0e21\\u0e32\\u0e22\",\"\\u0e04\\u0e34\\u0e27\\u0e40\\u0e23\\u0e48\\u0e07\\u0e14\\u0e48\\u0e27\\u0e19\",\"\\u0e04\\u0e34\\u0e27\\u0e1c\\u0e39\\u0e49\\u0e2a\\u0e39\\u0e07\\u0e2d\\u0e32\\u0e22\\u0e38\\/\\u0e1e\\u0e34\\u0e01\\u0e32\\u0e23\"]}', '{\"current_number\":0,\"reset_date\":\"2025-10-01 10:15:15\"}');
INSERT INTO `audit_logs` VALUES (591, NULL, 'สร้างคิว A003 จาก Kiosk - บัตรประชาชน: 1111****1111', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 10:15:59', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (592, 1, 'เรียกคิว A003 ที่จุดบริการ ID: 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 10:16:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (593, 1, 'เล่นเสียงเรียกคิว: ขอเชิญ หมายเลข A 0 0 3 ที่ จุด คัดกรอง ครับ', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 10:16:10', NULL, NULL, NULL, NULL);
INSERT INTO `audit_logs` VALUES (594, 1, 'เล่นเสียงเรียกคิว: ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-10-01 10:16:19', NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for auto_reset_logs
-- ----------------------------
DROP TABLE IF EXISTS `auto_reset_logs`;
CREATE TABLE `auto_reset_logs`  (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int NULL DEFAULT NULL,
  `reset_type` enum('all','by_type','by_service_point') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_id` int NULL DEFAULT NULL,
  `reset_count` int NULL DEFAULT 0,
  `affected_types` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` enum('success','failed','skipped') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `execution_time` decimal(5, 3) NULL DEFAULT 0.000,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`) USING BTREE,
  INDEX `fk_arl_schedule`(`schedule_id` ASC) USING BTREE,
  CONSTRAINT `fk_arl_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `auto_reset_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auto_reset_logs
-- ----------------------------

-- ----------------------------
-- Table structure for auto_reset_schedules
-- ----------------------------
DROP TABLE IF EXISTS `auto_reset_schedules`;
CREATE TABLE `auto_reset_schedules`  (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `schedule_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reset_type` enum('all','by_type','by_service_point') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `target_id` int NULL DEFAULT NULL,
  `schedule_time` time NOT NULL,
  `schedule_days` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1,2,3,4,5,6,7',
  `is_active` tinyint(1) NULL DEFAULT 1,
  `last_run_date` date NULL DEFAULT NULL,
  `last_run_status` enum('success','failed','skipped') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`) USING BTREE,
  INDEX `fk_ars_created_by`(`created_by` ASC) USING BTREE,
  CONSTRAINT `fk_ars_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of auto_reset_schedules
-- ----------------------------
INSERT INTO `auto_reset_schedules` VALUES (2, 'Reset คิวทั่วไป - เช้า', 'by_type', NULL, '06:00:00', '1,2,3,4,5', 1, NULL, NULL, 1, '2025-06-19 16:30:13', '2025-08-22 14:17:07');

-- ----------------------------
-- Table structure for backup_logs
-- ----------------------------
DROP TABLE IF EXISTS `backup_logs`;
CREATE TABLE `backup_logs`  (
  `backup_id` int NOT NULL AUTO_INCREMENT,
  `backup_type` enum('manual','auto_reset','scheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `backup_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` bigint NULL DEFAULT 0,
  `reset_type` enum('all','by_type','by_service_point') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `target_id` int NULL DEFAULT NULL,
  `backup_data` json NULL,
  `created_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`backup_id`) USING BTREE,
  INDEX `created_by`(`created_by` ASC) USING BTREE,
  CONSTRAINT `backup_logs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of backup_logs
-- ----------------------------

-- ----------------------------
-- Table structure for daily_performance_summary
-- ----------------------------
DROP TABLE IF EXISTS `daily_performance_summary`;
CREATE TABLE `daily_performance_summary`  (
  `summary_id` int NOT NULL AUTO_INCREMENT,
  `summary_date` date NOT NULL,
  `queue_type_id` int NULL DEFAULT NULL,
  `service_point_id` int NULL DEFAULT NULL,
  `total_queues` int NULL DEFAULT 0,
  `completed_queues` int NULL DEFAULT 0,
  `cancelled_queues` int NULL DEFAULT 0,
  `avg_wait_time_minutes` decimal(10, 2) NULL DEFAULT 0.00,
  `avg_service_time_minutes` decimal(10, 2) NULL DEFAULT 0.00,
  `max_wait_time_minutes` decimal(10, 2) NULL DEFAULT 0.00,
  `peak_hour_start` time NULL DEFAULT NULL,
  `peak_hour_end` time NULL DEFAULT NULL,
  `peak_hour_queue_count` int NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`summary_id`) USING BTREE,
  UNIQUE INDEX `unique_daily_summary`(`summary_date` ASC, `queue_type_id` ASC, `service_point_id` ASC) USING BTREE,
  INDEX `idx_summary_date`(`summary_date` ASC) USING BTREE,
  INDEX `fk_dps_queue_type`(`queue_type_id` ASC) USING BTREE,
  INDEX `fk_dps_sp`(`service_point_id` ASC) USING BTREE,
  CONSTRAINT `fk_dps_queue_type` FOREIGN KEY (`queue_type_id`) REFERENCES `queue_types` (`queue_type_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_dps_sp` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of daily_performance_summary
-- ----------------------------

-- ----------------------------
-- Table structure for dashboard_alerts
-- ----------------------------
DROP TABLE IF EXISTS `dashboard_alerts`;
CREATE TABLE `dashboard_alerts`  (
  `alert_id` int NOT NULL AUTO_INCREMENT,
  `alert_type` enum('warning','error','info','success') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alert_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alert_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `alert_data` json NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`alert_id`) USING BTREE,
  INDEX `idx_active_alerts`(`is_active` ASC, `created_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of dashboard_alerts
-- ----------------------------

-- ----------------------------
-- Table structure for dashboard_user_preferences
-- ----------------------------
DROP TABLE IF EXISTS `dashboard_user_preferences`;
CREATE TABLE `dashboard_user_preferences`  (
  `preference_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NULL DEFAULT NULL,
  `widget_layout` json NULL,
  `refresh_interval` int NULL DEFAULT 30,
  `theme` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'light',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`) USING BTREE,
  INDEX `fk_dup_staff`(`staff_id` ASC) USING BTREE,
  CONSTRAINT `fk_dup_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of dashboard_user_preferences
-- ----------------------------

-- ----------------------------
-- Table structure for dashboard_widgets
-- ----------------------------
DROP TABLE IF EXISTS `dashboard_widgets`;
CREATE TABLE `dashboard_widgets`  (
  `widget_id` int NOT NULL AUTO_INCREMENT,
  `widget_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `widget_type` enum('chart','counter','table','gauge','map') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `widget_config` json NULL,
  `display_order` int NULL DEFAULT 0,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`widget_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of dashboard_widgets
-- ----------------------------
INSERT INTO `dashboard_widgets` VALUES (1, 'คิวรอทั้งหมด', 'counter', '{\"icon\": \"fas fa-users\", \"color\": \"primary\", \"query\": \"waiting_queues\"}', 1, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (2, 'คิวที่เสร็จสิ้นวันนี้', 'counter', '{\"icon\": \"fas fa-check-circle\", \"color\": \"success\", \"query\": \"completed_today\"}', 2, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (3, 'เวลารอเฉลี่ย', 'gauge', '{\"max\": 60, \"icon\": \"fas fa-clock\", \"color\": \"warning\", \"query\": \"avg_wait_time\"}', 3, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (4, 'จุดบริการที่ใช้งาน', 'counter', '{\"icon\": \"fas fa-map-marker-alt\", \"color\": \"info\", \"query\": \"active_service_points\"}', 4, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (5, 'กราฟคิวรายชั่วโมง', 'chart', '{\"type\": \"line\", \"query\": \"hourly_queues\", \"height\": 300}', 5, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (6, 'สถานะจุดบริการ', 'table', '{\"query\": \"service_point_status\", \"height\": 400}', 6, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (7, 'การกระจายประเภทคิว', 'chart', '{\"type\": \"doughnut\", \"query\": \"queue_type_distribution\", \"height\": 300}', 7, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `dashboard_widgets` VALUES (8, 'คิวล่าสุด', 'table', '{\"query\": \"recent_queues\", \"height\": 400}', 8, 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for menus_admin
-- ----------------------------
DROP TABLE IF EXISTS `menus_admin`;
CREATE TABLE `menus_admin`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `icon_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sort` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of menus_admin
-- ----------------------------
INSERT INTO `menus_admin` VALUES (1, 'แดชบอร์ด', 'fa-light fa-tachometer-alt', 'dashboard.php', NULL);
INSERT INTO `menus_admin` VALUES (2, 'จัดการผู้ใช้', 'fa-light fa-users', 'users.php', NULL);
INSERT INTO `menus_admin` VALUES (3, 'บทบาทและสิทธิ์', 'fa-light fa-user-tag', 'roles.php', NULL);
INSERT INTO `menus_admin` VALUES (4, 'จุดบริการ', 'fa-light fa-map-marker-alt', 'service_points.php', NULL);
INSERT INTO `menus_admin` VALUES (5, 'ประเภทคิว', 'fa-light fa-list', 'queue_types.php', NULL);
INSERT INTO `menus_admin` VALUES (6, 'Service Flows', 'fa-light fa-route', 'service_flows.php', NULL);
INSERT INTO `menus_admin` VALUES (7, 'จัดการคิว', 'fa-light fa-tasks', 'queue_management.php', NULL);
INSERT INTO `menus_admin` VALUES (8, 'การตั้งค่า', 'fa-light fa-cog', 'settings.php', NULL);
INSERT INTO `menus_admin` VALUES (9, 'Environment', 'fa-light fa-server', 'environment_settings.php', NULL);
INSERT INTO `menus_admin` VALUES (10, 'ระบบเสียงเรียกคิว', 'fa-light fa-volume-up', 'audio_settings.php', NULL);
INSERT INTO `menus_admin` VALUES (11, 'Auto Reset', 'fa-light fa-clock', 'auto_reset_settings.php', NULL);
INSERT INTO `menus_admin` VALUES (12, 'ประวัติ Auto Reset', 'fa-light fa-history', 'auto_reset_logs.php', NULL);
INSERT INTO `menus_admin` VALUES (13, 'จัดการ Backup', 'fa-light fa-database', 'backup_management.php', NULL);
INSERT INTO `menus_admin` VALUES (14, 'รายงาน', 'fa-light fa-chart-bar', 'reports.php', NULL);
INSERT INTO `menus_admin` VALUES (15, 'บันทึกการใช้งาน', 'fa-light fa-history', 'audit_logs.php', NULL);

-- ----------------------------
-- Table structure for mobile_app_registrations
-- ----------------------------
DROP TABLE IF EXISTS `mobile_app_registrations`;
CREATE TABLE `mobile_app_registrations`  (
  `registration_id` int NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `api_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `app_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `platform` enum('ios','android','web','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'other',
  `bundle_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `rate_limit_per_minute` int NULL DEFAULT 60,
  `allowed_endpoints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`registration_id`) USING BTREE,
  UNIQUE INDEX `api_key`(`api_key` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of mobile_app_registrations
-- ----------------------------
INSERT INTO `mobile_app_registrations` VALUES (1, 'Hospital Queue Mobile App', 'test_api_key_12345', '052c910d5f58aa128609e5f9fe141fff1fb8386c561ff653226cf32bbb42dc87', NULL, 'android', NULL, 1, 60, '[\"queue\", \"status\", \"types\", \"notifications\"]', '2025-06-19 16:30:13', NULL, NULL);

-- ----------------------------
-- Table structure for mobile_sessions
-- ----------------------------
DROP TABLE IF EXISTS `mobile_sessions`;
CREATE TABLE `mobile_sessions`  (
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mobile_user_id` int NULL DEFAULT NULL,
  `registration_id` int NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`) USING BTREE,
  INDEX `idx_expires_at`(`expires_at` ASC) USING BTREE,
  INDEX `idx_mobile_user_id`(`mobile_user_id` ASC) USING BTREE,
  INDEX `fk_ms_reg`(`registration_id` ASC) USING BTREE,
  CONSTRAINT `fk_ms_reg` FOREIGN KEY (`registration_id`) REFERENCES `mobile_app_registrations` (`registration_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_ms_user` FOREIGN KEY (`mobile_user_id`) REFERENCES `mobile_users` (`mobile_user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of mobile_sessions
-- ----------------------------

-- ----------------------------
-- Table structure for mobile_users
-- ----------------------------
DROP TABLE IF EXISTS `mobile_users`;
CREATE TABLE `mobile_users`  (
  `mobile_user_id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int NULL DEFAULT NULL,
  `device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `device_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `platform` enum('ios','android','web') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `app_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `os_version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `device_model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mobile_user_id`) USING BTREE,
  UNIQUE INDEX `device_id`(`device_id` ASC) USING BTREE,
  INDEX `idx_device_id`(`device_id` ASC) USING BTREE,
  INDEX `fk_mu_reg`(`registration_id` ASC) USING BTREE,
  CONSTRAINT `fk_mu_reg` FOREIGN KEY (`registration_id`) REFERENCES `mobile_app_registrations` (`registration_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of mobile_users
-- ----------------------------

-- ----------------------------
-- Table structure for notification_deliveries
-- ----------------------------
DROP TABLE IF EXISTS `notification_deliveries`;
CREATE TABLE `notification_deliveries`  (
  `delivery_id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `channel` enum('browser','email','telegram','sms') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','sent','failed','delivered','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`delivery_id`) USING BTREE,
  INDEX `idx_delivery_status`(`status` ASC) USING BTREE,
  INDEX `idx_delivery_channel`(`channel` ASC) USING BTREE,
  INDEX `fk_nd_notification`(`notification_id` ASC) USING BTREE,
  CONSTRAINT `fk_nd_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 75 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of notification_deliveries
-- ----------------------------
INSERT INTO `notification_deliveries` VALUES (1, 2, 'browser', 'sent', NULL, '2025-09-03 16:13:17', NULL, NULL, '2025-09-03 16:13:17');
INSERT INTO `notification_deliveries` VALUES (2, 3, 'browser', 'sent', NULL, '2025-09-03 16:13:28', NULL, NULL, '2025-09-03 16:13:28');
INSERT INTO `notification_deliveries` VALUES (3, 4, 'browser', 'sent', NULL, '2025-09-03 16:13:40', NULL, NULL, '2025-09-03 16:13:40');
INSERT INTO `notification_deliveries` VALUES (4, 5, 'browser', 'sent', NULL, '2025-09-03 16:21:49', NULL, NULL, '2025-09-03 16:21:49');
INSERT INTO `notification_deliveries` VALUES (5, 6, 'browser', 'sent', NULL, '2025-09-03 16:24:30', NULL, NULL, '2025-09-03 16:24:30');
INSERT INTO `notification_deliveries` VALUES (6, 7, 'browser', 'sent', NULL, '2025-09-03 16:24:52', NULL, NULL, '2025-09-03 16:24:52');
INSERT INTO `notification_deliveries` VALUES (7, 8, 'browser', 'sent', NULL, '2025-09-03 16:25:09', NULL, NULL, '2025-09-03 16:25:09');
INSERT INTO `notification_deliveries` VALUES (8, 9, 'browser', 'sent', NULL, '2025-09-08 15:40:13', NULL, NULL, '2025-09-08 15:40:13');
INSERT INTO `notification_deliveries` VALUES (9, 10, 'browser', 'sent', NULL, '2025-09-09 14:06:01', NULL, NULL, '2025-09-09 14:06:01');
INSERT INTO `notification_deliveries` VALUES (10, 11, 'browser', 'sent', NULL, '2025-09-09 14:06:40', NULL, NULL, '2025-09-09 14:06:40');
INSERT INTO `notification_deliveries` VALUES (11, 12, 'browser', 'sent', NULL, '2025-09-09 14:07:05', NULL, NULL, '2025-09-09 14:07:05');
INSERT INTO `notification_deliveries` VALUES (12, 13, 'browser', 'sent', NULL, '2025-09-09 14:07:07', NULL, NULL, '2025-09-09 14:07:07');
INSERT INTO `notification_deliveries` VALUES (13, 14, 'browser', 'sent', NULL, '2025-09-09 14:08:04', NULL, NULL, '2025-09-09 14:08:04');
INSERT INTO `notification_deliveries` VALUES (14, 15, 'browser', 'sent', NULL, '2025-09-09 14:08:07', NULL, NULL, '2025-09-09 14:08:07');
INSERT INTO `notification_deliveries` VALUES (15, 16, 'browser', 'sent', NULL, '2025-09-09 14:08:41', NULL, NULL, '2025-09-09 14:08:41');
INSERT INTO `notification_deliveries` VALUES (16, 17, 'browser', 'sent', NULL, '2025-09-09 14:09:09', NULL, NULL, '2025-09-09 14:09:09');
INSERT INTO `notification_deliveries` VALUES (17, 18, 'browser', 'sent', NULL, '2025-09-09 14:10:24', NULL, NULL, '2025-09-09 14:10:24');
INSERT INTO `notification_deliveries` VALUES (18, 19, 'browser', 'sent', NULL, '2025-09-09 14:17:08', NULL, NULL, '2025-09-09 14:17:08');
INSERT INTO `notification_deliveries` VALUES (19, 21, 'browser', 'sent', NULL, '2025-09-11 11:52:17', NULL, NULL, '2025-09-11 11:52:17');
INSERT INTO `notification_deliveries` VALUES (20, 22, 'browser', 'sent', NULL, '2025-09-11 11:52:43', NULL, NULL, '2025-09-11 11:52:43');
INSERT INTO `notification_deliveries` VALUES (21, 23, 'browser', 'sent', NULL, '2025-09-17 11:20:05', NULL, NULL, '2025-09-17 11:20:05');
INSERT INTO `notification_deliveries` VALUES (22, 24, 'browser', 'sent', NULL, '2025-09-22 16:23:33', NULL, NULL, '2025-09-22 16:23:33');
INSERT INTO `notification_deliveries` VALUES (23, 25, 'browser', 'sent', NULL, '2025-09-22 16:23:35', NULL, NULL, '2025-09-22 16:23:35');
INSERT INTO `notification_deliveries` VALUES (24, 26, 'browser', 'sent', NULL, '2025-09-24 15:54:09', NULL, NULL, '2025-09-24 15:54:09');
INSERT INTO `notification_deliveries` VALUES (25, 27, 'browser', 'sent', NULL, '2025-09-29 10:00:03', NULL, NULL, '2025-09-29 10:00:03');
INSERT INTO `notification_deliveries` VALUES (26, 28, 'browser', 'sent', NULL, '2025-09-29 10:00:03', NULL, NULL, '2025-09-29 10:00:03');
INSERT INTO `notification_deliveries` VALUES (27, 29, 'browser', 'sent', NULL, '2025-09-29 10:01:25', NULL, NULL, '2025-09-29 10:01:25');
INSERT INTO `notification_deliveries` VALUES (28, 30, 'browser', 'sent', NULL, '2025-09-29 10:01:25', NULL, NULL, '2025-09-29 10:01:25');
INSERT INTO `notification_deliveries` VALUES (29, 31, 'browser', 'sent', NULL, '2025-09-29 10:02:38', NULL, NULL, '2025-09-29 10:02:38');
INSERT INTO `notification_deliveries` VALUES (30, 32, 'browser', 'sent', NULL, '2025-09-29 10:02:39', NULL, NULL, '2025-09-29 10:02:39');
INSERT INTO `notification_deliveries` VALUES (31, 33, 'browser', 'sent', NULL, '2025-09-30 15:38:32', NULL, NULL, '2025-09-30 15:38:32');
INSERT INTO `notification_deliveries` VALUES (32, 34, 'browser', 'sent', NULL, '2025-09-30 15:45:09', NULL, NULL, '2025-09-30 15:45:09');
INSERT INTO `notification_deliveries` VALUES (33, 35, 'browser', 'sent', NULL, '2025-09-30 15:45:18', NULL, NULL, '2025-09-30 15:45:18');
INSERT INTO `notification_deliveries` VALUES (34, 36, 'browser', 'sent', NULL, '2025-09-30 15:45:24', NULL, NULL, '2025-09-30 15:45:24');
INSERT INTO `notification_deliveries` VALUES (35, 37, 'browser', 'sent', NULL, '2025-09-30 15:45:27', NULL, NULL, '2025-09-30 15:45:27');
INSERT INTO `notification_deliveries` VALUES (36, 38, 'browser', 'sent', NULL, '2025-09-30 15:45:30', NULL, NULL, '2025-09-30 15:45:30');
INSERT INTO `notification_deliveries` VALUES (37, 39, 'browser', 'sent', NULL, '2025-09-30 16:08:28', NULL, NULL, '2025-09-30 16:08:28');
INSERT INTO `notification_deliveries` VALUES (38, 40, 'browser', 'sent', NULL, '2025-09-30 16:08:32', NULL, NULL, '2025-09-30 16:08:32');
INSERT INTO `notification_deliveries` VALUES (39, 41, 'browser', 'sent', NULL, '2025-09-30 16:08:34', NULL, NULL, '2025-09-30 16:08:34');
INSERT INTO `notification_deliveries` VALUES (40, 42, 'browser', 'sent', NULL, '2025-09-30 16:08:37', NULL, NULL, '2025-09-30 16:08:37');
INSERT INTO `notification_deliveries` VALUES (41, 43, 'browser', 'sent', NULL, '2025-10-01 08:37:30', NULL, NULL, '2025-10-01 08:37:30');
INSERT INTO `notification_deliveries` VALUES (42, 44, 'browser', 'sent', NULL, '2025-10-01 08:37:36', NULL, NULL, '2025-10-01 08:37:36');
INSERT INTO `notification_deliveries` VALUES (43, 45, 'browser', 'sent', NULL, '2025-10-01 08:37:39', NULL, NULL, '2025-10-01 08:37:39');
INSERT INTO `notification_deliveries` VALUES (44, 46, 'browser', 'sent', NULL, '2025-10-01 08:37:41', NULL, NULL, '2025-10-01 08:37:41');
INSERT INTO `notification_deliveries` VALUES (45, 47, 'browser', 'sent', NULL, '2025-10-01 08:37:58', NULL, NULL, '2025-10-01 08:37:58');
INSERT INTO `notification_deliveries` VALUES (46, 48, 'browser', 'sent', NULL, '2025-10-01 08:42:43', NULL, NULL, '2025-10-01 08:42:43');
INSERT INTO `notification_deliveries` VALUES (47, 49, 'browser', 'sent', NULL, '2025-10-01 08:43:04', NULL, NULL, '2025-10-01 08:43:04');
INSERT INTO `notification_deliveries` VALUES (48, 50, 'browser', 'sent', NULL, '2025-10-01 08:43:07', NULL, NULL, '2025-10-01 08:43:07');
INSERT INTO `notification_deliveries` VALUES (49, 51, 'browser', 'sent', NULL, '2025-10-01 08:43:11', NULL, NULL, '2025-10-01 08:43:11');
INSERT INTO `notification_deliveries` VALUES (50, 52, 'browser', 'sent', NULL, '2025-10-01 08:43:29', NULL, NULL, '2025-10-01 08:43:29');
INSERT INTO `notification_deliveries` VALUES (51, 53, 'browser', 'sent', NULL, '2025-10-01 08:43:31', NULL, NULL, '2025-10-01 08:43:31');
INSERT INTO `notification_deliveries` VALUES (52, 54, 'browser', 'sent', NULL, '2025-10-01 08:43:32', NULL, NULL, '2025-10-01 08:43:32');
INSERT INTO `notification_deliveries` VALUES (53, 55, 'browser', 'sent', NULL, '2025-10-01 08:43:49', NULL, NULL, '2025-10-01 08:43:49');
INSERT INTO `notification_deliveries` VALUES (54, 56, 'browser', 'sent', NULL, '2025-10-01 08:43:50', NULL, NULL, '2025-10-01 08:43:50');
INSERT INTO `notification_deliveries` VALUES (55, 57, 'browser', 'sent', NULL, '2025-10-01 08:49:38', NULL, NULL, '2025-10-01 08:49:38');
INSERT INTO `notification_deliveries` VALUES (56, 58, 'browser', 'sent', NULL, '2025-10-01 08:50:26', NULL, NULL, '2025-10-01 08:50:26');
INSERT INTO `notification_deliveries` VALUES (57, 59, 'browser', 'sent', NULL, '2025-10-01 08:50:28', NULL, NULL, '2025-10-01 08:50:28');
INSERT INTO `notification_deliveries` VALUES (58, 60, 'browser', 'sent', NULL, '2025-10-01 08:51:04', NULL, NULL, '2025-10-01 08:51:04');
INSERT INTO `notification_deliveries` VALUES (59, 61, 'browser', 'sent', NULL, '2025-10-01 08:51:09', NULL, NULL, '2025-10-01 08:51:09');
INSERT INTO `notification_deliveries` VALUES (60, 62, 'browser', 'sent', NULL, '2025-10-01 08:51:30', NULL, NULL, '2025-10-01 08:51:30');
INSERT INTO `notification_deliveries` VALUES (61, 63, 'browser', 'sent', NULL, '2025-10-01 08:51:33', NULL, NULL, '2025-10-01 08:51:33');
INSERT INTO `notification_deliveries` VALUES (62, 64, 'browser', 'sent', NULL, '2025-10-01 08:51:36', NULL, NULL, '2025-10-01 08:51:36');
INSERT INTO `notification_deliveries` VALUES (63, 65, 'browser', 'sent', NULL, '2025-10-01 08:51:37', NULL, NULL, '2025-10-01 08:51:37');
INSERT INTO `notification_deliveries` VALUES (64, 66, 'browser', 'sent', NULL, '2025-10-01 08:51:43', NULL, NULL, '2025-10-01 08:51:43');
INSERT INTO `notification_deliveries` VALUES (65, 67, 'browser', 'sent', NULL, '2025-10-01 09:32:05', NULL, NULL, '2025-10-01 09:32:05');
INSERT INTO `notification_deliveries` VALUES (66, 68, 'browser', 'sent', NULL, '2025-10-01 09:32:14', NULL, NULL, '2025-10-01 09:32:14');
INSERT INTO `notification_deliveries` VALUES (67, 69, 'browser', 'sent', NULL, '2025-10-01 09:32:21', NULL, NULL, '2025-10-01 09:32:21');
INSERT INTO `notification_deliveries` VALUES (68, 70, 'browser', 'sent', NULL, '2025-10-01 09:32:22', NULL, NULL, '2025-10-01 09:32:22');
INSERT INTO `notification_deliveries` VALUES (69, 71, 'browser', 'sent', NULL, '2025-10-01 09:32:24', NULL, NULL, '2025-10-01 09:32:24');
INSERT INTO `notification_deliveries` VALUES (70, 72, 'browser', 'sent', NULL, '2025-10-01 09:33:04', NULL, NULL, '2025-10-01 09:33:04');
INSERT INTO `notification_deliveries` VALUES (71, 73, 'browser', 'sent', NULL, '2025-10-01 09:33:07', NULL, NULL, '2025-10-01 09:33:07');
INSERT INTO `notification_deliveries` VALUES (72, 74, 'browser', 'sent', NULL, '2025-10-01 10:16:10', NULL, NULL, '2025-10-01 10:16:10');
INSERT INTO `notification_deliveries` VALUES (73, 75, 'browser', 'sent', NULL, '2025-10-01 10:16:10', NULL, NULL, '2025-10-01 10:16:10');
INSERT INTO `notification_deliveries` VALUES (74, 76, 'browser', 'sent', NULL, '2025-10-01 10:16:19', NULL, NULL, '2025-10-01 10:16:19');

-- ----------------------------
-- Table structure for notification_preferences
-- ----------------------------
DROP TABLE IF EXISTS `notification_preferences`;
CREATE TABLE `notification_preferences`  (
  `preference_id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email_enabled` tinyint(1) NULL DEFAULT 0,
  `browser_enabled` tinyint(1) NULL DEFAULT 1,
  `telegram_enabled` tinyint(1) NULL DEFAULT 0,
  `sound_enabled` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`) USING BTREE,
  UNIQUE INDEX `unique_staff_notification_type`(`staff_id` ASC, `notification_type` ASC) USING BTREE,
  CONSTRAINT `fk_np_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of notification_preferences
-- ----------------------------
INSERT INTO `notification_preferences` VALUES (1, 1, 'system_alert', 0, 1, 0, 1, '2025-09-03 16:13:16', '2025-09-03 16:13:16');
INSERT INTO `notification_preferences` VALUES (2, 1, 'queue_called', 0, 1, 0, 1, '2025-09-11 09:50:56', '2025-09-11 09:50:56');

-- ----------------------------
-- Table structure for notification_types
-- ----------------------------
DROP TABLE IF EXISTS `notification_types`;
CREATE TABLE `notification_types`  (
  `type_id` int NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'bell',
  `default_priority` enum('low','normal','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'normal',
  `default_sound` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'notification.mp3',
  `is_system` tinyint(1) NULL DEFAULT 0,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`type_id`) USING BTREE,
  UNIQUE INDEX `type_code`(`type_code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of notification_types
-- ----------------------------
INSERT INTO `notification_types` VALUES (1, 'queue_called', 'เรียกคิว', 'แจ้งเตือนเมื่อมีการเรียกคิว', 'bullhorn', 'high', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (2, 'queue_completed', 'คิวเสร็จสิ้น', 'แจ้งเตือนเมื่อคิวเสร็จสิ้น', 'check-circle', 'normal', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (3, 'queue_forwarded', 'คิวถูกส่งต่อ', 'แจ้งเตือนเมื่อคิวถูกส่งต่อไปยังจุดบริการอื่น', 'arrow-right', 'normal', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (4, 'queue_waiting_long', 'คิวรอนาน', 'แจ้งเตือนเมื่อมีคิวรอนานเกินกำหนด', 'clock', 'high', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (5, 'system_alert', 'การแจ้งเตือนระบบ', 'แจ้งเตือนจากระบบ', 'exclamation-triangle', 'high', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (6, 'auto_reset', 'Auto Reset', 'แจ้งเตือนเกี่ยวกับการ Reset คิวอัตโนมัติ', 'sync', 'normal', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (7, 'staff_message', 'ข้อความจากเจ้าหน้าที่', 'ข้อความจากเจ้าหน้าที่คนอื่น', 'comment', 'normal', 'notification.mp3', 0, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (8, 'system_update', 'อัปเดตระบบ', 'แจ้งเตือนเมื่อมีการอัปเดตระบบ', 'download', 'normal', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (9, 'backup_complete', 'สำรองข้อมูลเสร็จสิ้น', 'แจ้งเตือนเมื่อการสำรองข้อมูลเสร็จสิ้น', 'database', 'low', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');
INSERT INTO `notification_types` VALUES (10, 'user_login', 'การเข้าสู่ระบบ', 'แจ้งเตือนเมื่อมีการเข้าสู่ระบบ', 'sign-in-alt', 'low', 'notification.mp3', 1, 1, '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications`  (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'info-circle',
  `priority` enum('low','normal','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'normal',
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_system` tinyint(1) NULL DEFAULT 0,
  `is_read` tinyint(1) NULL DEFAULT 0,
  `is_dismissed` tinyint(1) NULL DEFAULT 0,
  `recipient_id` int NULL DEFAULT NULL,
  `recipient_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sender_id` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `metadata` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `service_point_id` int NULL DEFAULT NULL,
  `is_public` int NULL DEFAULT NULL,
  `is_active` int NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`) USING BTREE,
  INDEX `idx_notification_recipient`(`recipient_id` ASC) USING BTREE,
  INDEX `idx_notification_type`(`notification_type` ASC) USING BTREE,
  INDEX `idx_notification_created`(`created_at` ASC) USING BTREE,
  INDEX `fk_n_sender`(`sender_id` ASC) USING BTREE,
  CONSTRAINT `fk_n_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_n_sender` FOREIGN KEY (`sender_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 77 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of notifications
-- ----------------------------
INSERT INTO `notifications` VALUES (1, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 18) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:13:16', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (2, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 7) (Service Point: ห้องตรวจ 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:13:17', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (3, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:13:28', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (4, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:13:40', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (5, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 7) (Service Point: ห้องตรวจ 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:21:49', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (6, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:24:30', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (7, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:24:52', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (8, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-03 16:25:09', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (9, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-08 15:40:13', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (10, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:06:01', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (11, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:06:40', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (12, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:07:05', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (13, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:07:07', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (14, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:08:04', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (15, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:08:07', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (16, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:08:41', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (17, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 19) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:09:09', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (18, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 18) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:10:24', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (19, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: คัดกรอง (Queue ID: 18) (Service Point: จุด คัดกรอง)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-09 14:17:08', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (20, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-11 09:50:56', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (21, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 7)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-11 11:52:17', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (22, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 7)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-11 11:52:43', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (23, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A005 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-17 11:20:05', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (24, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: /uploads/audio/audio_68afd14916b69.wav', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, NULL, '2025-09-22 16:23:33', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (25, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: /uploads/audio/audio_68afd14916b69.wav', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, NULL, '2025-09-22 16:23:35', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (26, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-24 15:54:09', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (27, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ ห้องตรวจ 1', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:00:03', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (28, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:00:03', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (29, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ ห้องตรวจ 1', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:01:25', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (30, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:01:25', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (31, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ ห้องตรวจ 1', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:02:38', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (32, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ห้องตรวจ (Queue ID: 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-29 10:02:39', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (33, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:38:32', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (34, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:45:09', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (35, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:45:18', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (36, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:45:24', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (37, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:45:27', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (38, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 15:45:30', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (39, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 16:08:28', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (40, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 16:08:32', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (41, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 16:08:34', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (42, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-09-30 16:08:37', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (43, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:37:30', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (44, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:37:36', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (45, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:37:39', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (46, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:37:41', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (47, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:37:58', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (48, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:42:43', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (49, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:04', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (50, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:07', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (51, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 2)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:11', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (52, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_a8d5345e311f87cdd21a8d2fc46e5014.mp3 (Queue ID: 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:29', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (53, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A001 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:31', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (54, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:32', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (55, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:49', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (56, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:43:50', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (57, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:49:38', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (58, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:50:26', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (59, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:50:28', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (60, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:04', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (61, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:09', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (62, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:30', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (63, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:33', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (64, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:36', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (65, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:37', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (66, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 08:51:43', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (67, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A002 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:32:05', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (68, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_d8cf53f206f10e4aec7b67be5fbabff2.mp3 (Queue ID: 4)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:32:14', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (69, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:32:21', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (70, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_d8cf53f206f10e4aec7b67be5fbabff2.mp3 (Queue ID: 4)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:32:22', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (71, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:32:24', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (72, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_ffff1d523a4b491ddff2e2c75ad321d1.mp3 (Queue ID: 3)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:33:04', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (73, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_a8d5345e311f87cdd21a8d2fc46e5014.mp3 (Queue ID: 1)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 09:33:07', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (74, 'queue_called', 'เรียกคิวแล้ว', 'หมายเลข A003 เชิญที่ จุด คัดกรอง', 'bullhorn', 'high', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 10:16:10', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (75, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_49fb2598a9634f19f866a4cdeba1b96f.mp3 (Queue ID: 5)', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 10:16:10', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `notifications` VALUES (76, 'system_alert', 'ปัญหาเสียงเรียกคิว', 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: storage/tts/tts_service_1_072cb6656b4de99b5088c10e478f2c97.mp3', 'exclamation-triangle', 'urgent', NULL, 1, 0, 0, 1, NULL, 1, '2025-10-01 10:16:19', NULL, NULL, NULL, NULL, NULL);

-- ----------------------------
-- Table structure for patients
-- ----------------------------
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients`  (
  `patient_id` int NOT NULL AUTO_INCREMENT,
  `id_card_number` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`patient_id`) USING BTREE,
  UNIQUE INDEX `id_card_number`(`id_card_number` ASC) USING BTREE,
  INDEX `idx_patients_id_card`(`id_card_number` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of patients
-- ----------------------------
INSERT INTO `patients` VALUES (1, '1333333333333', NULL, NULL, '2025-06-19 16:33:14');
INSERT INTO `patients` VALUES (2, '1111111111111', NULL, NULL, '2025-06-19 16:35:19');
INSERT INTO `patients` VALUES (3, '2132213212313', NULL, NULL, '2025-07-29 14:28:41');
INSERT INTO `patients` VALUES (6, '5645456456456', NULL, NULL, '2025-07-29 15:23:29');
INSERT INTO `patients` VALUES (9, '8456545446464', NULL, NULL, '2025-07-29 17:03:47');

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permission_id`) USING BTREE,
  UNIQUE INDEX `permission_name`(`permission_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of permissions
-- ----------------------------
INSERT INTO `permissions` VALUES (1, 'manage_users', 'จัดการบัญชีผู้ใช้', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (2, 'manage_settings', 'จัดการการตั้งค่าระบบ', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (3, 'manage_queues', 'จัดการคิว', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (4, 'call_queue', 'เรียกคิว', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (5, 'forward_queue', 'ส่งต่อคิว', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (6, 'cancel_queue', 'ยกเลิกคิว', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (7, 'view_reports', 'ดูรายงาน', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (8, 'manage_service_points', 'จัดการจุดบริการ', '2025-06-19 16:30:13');
INSERT INTO `permissions` VALUES (9, 'manage_audio_system', 'จัดการระบบเสียงเรียกคิว', '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for push_notifications
-- ----------------------------
DROP TABLE IF EXISTS `push_notifications`;
CREATE TABLE `push_notifications`  (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `mobile_user_id` int NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `data` json NULL,
  `status` enum('pending','sent','delivered','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE,
  INDEX `idx_mobile_user_id`(`mobile_user_id` ASC) USING BTREE,
  CONSTRAINT `fk_pn_user` FOREIGN KEY (`mobile_user_id`) REFERENCES `mobile_users` (`mobile_user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of push_notifications
-- ----------------------------

-- ----------------------------
-- Table structure for queue_types
-- ----------------------------
DROP TABLE IF EXISTS `queue_types`;
CREATE TABLE `queue_types`  (
  `queue_type_id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `prefix_char` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'A',
  `is_active` tinyint(1) NULL DEFAULT 1,
  `current_number` int NULL DEFAULT 0,
  `last_reset_date` timestamp NULL DEFAULT NULL,
  `last_reset_by` int NULL DEFAULT NULL,
  `last_reset_type` enum('manual','auto') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_type_id`) USING BTREE,
  UNIQUE INDEX `type_name`(`type_name` ASC) USING BTREE,
  INDEX `idx_queue_types_active`(`is_active` ASC) USING BTREE,
  INDEX `idx_queue_types_prefix`(`prefix_char` ASC) USING BTREE,
  INDEX `fk_qt_reset_by`(`last_reset_by` ASC) USING BTREE,
  CONSTRAINT `fk_qt_reset_by` FOREIGN KEY (`last_reset_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of queue_types
-- ----------------------------
INSERT INTO `queue_types` VALUES (1, 'คิวทั่วไป', 'คิวสำหรับผู้ป่วยทั่วไป', 'A', 1, 0, '2025-10-01 10:15:15', 1, 'manual', '2025-06-19 16:30:13');
INSERT INTO `queue_types` VALUES (2, 'คิวนัดหมาย', 'คิวสำหรับผู้ป่วยที่มีการนัดหมาย', 'B', 1, 0, '2025-10-01 10:15:15', 1, 'manual', '2025-06-19 16:30:13');
INSERT INTO `queue_types` VALUES (3, 'คิวเร่งด่วน', 'คิวสำหรับผู้ป่วยเร่งด่วน', 'C', 1, 0, '2025-10-01 10:15:15', 1, 'manual', '2025-06-19 16:30:13');
INSERT INTO `queue_types` VALUES (4, 'คิวผู้สูงอายุ/พิการ', 'คิวสำหรับผู้สูงอายุและผู้พิการ', 'D', 1, 0, '2025-10-01 10:15:15', 1, 'manual', '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for queues
-- ----------------------------
DROP TABLE IF EXISTS `queues`;
CREATE TABLE `queues`  (
  `queue_id` int NOT NULL AUTO_INCREMENT,
  `queue_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `queue_type_id` int NULL DEFAULT NULL,
  `patient_id_card_number` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `kiosk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `creation_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `current_status` enum('waiting','called','processing','forwarded','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'waiting',
  `current_service_point_id` int NULL DEFAULT NULL,
  `last_called_time` timestamp NULL DEFAULT NULL,
  `called_count` int NULL DEFAULT 0,
  `priority_level` int NULL DEFAULT 0,
  `estimated_wait_time` int NULL DEFAULT 0,
  `updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`) USING BTREE,
  INDEX `idx_queue_status`(`current_status` ASC) USING BTREE,
  INDEX `idx_queue_service_point`(`current_service_point_id` ASC) USING BTREE,
  INDEX `idx_creation_time`(`creation_time` ASC) USING BTREE,
  INDEX `idx_queues_created_date`(`creation_time` ASC) USING BTREE,
  INDEX `idx_queues_status_date`(`current_status` ASC, `creation_time` ASC) USING BTREE,
  INDEX `fk_q_queue_type`(`queue_type_id` ASC) USING BTREE,
  CONSTRAINT `fk_q_queue_type` FOREIGN KEY (`queue_type_id`) REFERENCES `queue_types` (`queue_type_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_q_service_point` FOREIGN KEY (`current_service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of queues
-- ----------------------------
INSERT INTO `queues` VALUES (1, 'A001', 1, '1111111111111', 'KIOSK_01', '2025-09-24 15:53:40', 'called', 2, '2025-09-29 10:02:38', 4, 0, 0, '2025-09-24 15:53:40');
INSERT INTO `queues` VALUES (2, 'A001', 1, '1111111111111', 'KIOSK_01', '2025-09-30 15:38:28', 'cancelled', 1, '2025-10-01 08:43:04', 5, 0, 0, '2025-09-30 15:38:28');
INSERT INTO `queues` VALUES (3, 'A001', 1, '1111111111111', 'KIOSK_01', '2025-10-01 08:43:15', 'waiting', 2, '2025-10-01 08:43:31', 1, 0, 0, '2025-10-01 08:43:15');
INSERT INTO `queues` VALUES (4, 'A002', 1, '1111111111111', 'KIOSK_01', '2025-10-01 08:59:17', 'completed', 1, '2025-10-01 09:32:05', 1, 0, 0, '2025-10-01 08:59:17');
INSERT INTO `queues` VALUES (5, 'A003', 1, '1111111111111', 'KIOSK_01', '2025-10-01 10:15:59', 'called', 1, '2025-10-01 10:16:10', 1, 0, 0, '2025-10-01 10:15:59');

-- ----------------------------
-- Table structure for real_time_metrics
-- ----------------------------
DROP TABLE IF EXISTS `real_time_metrics`;
CREATE TABLE `real_time_metrics`  (
  `metric_id` int NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `metric_value` decimal(10, 2) NULL DEFAULT NULL,
  `metric_data` json NULL,
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`metric_id`) USING BTREE,
  INDEX `idx_metric_name_time`(`metric_name` ASC, `recorded_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of real_time_metrics
-- ----------------------------

-- ----------------------------
-- Table structure for report_cache
-- ----------------------------
DROP TABLE IF EXISTS `report_cache`;
CREATE TABLE `report_cache`  (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `parameters` json NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`cache_id`) USING BTREE,
  UNIQUE INDEX `cache_key`(`cache_key` ASC) USING BTREE,
  INDEX `idx_cache_key`(`cache_key` ASC) USING BTREE,
  INDEX `idx_expires_at`(`expires_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of report_cache
-- ----------------------------

-- ----------------------------
-- Table structure for report_execution_log
-- ----------------------------
DROP TABLE IF EXISTS `report_execution_log`;
CREATE TABLE `report_execution_log`  (
  `execution_id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int NULL DEFAULT NULL,
  `template_id` int NOT NULL,
  `execution_type` enum('manual','scheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `parameters` json NULL,
  `status` enum('running','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `file_size` int NULL DEFAULT NULL,
  `execution_time_seconds` decimal(10, 2) NULL DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `executed_by` int NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`execution_id`) USING BTREE,
  INDEX `fk_rel_schedule`(`schedule_id` ASC) USING BTREE,
  INDEX `fk_rel_template`(`template_id` ASC) USING BTREE,
  INDEX `fk_rel_executed_by`(`executed_by` ASC) USING BTREE,
  CONSTRAINT `fk_rel_executed_by` FOREIGN KEY (`executed_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_rel_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `scheduled_reports` (`schedule_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_rel_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of report_execution_log
-- ----------------------------

-- ----------------------------
-- Table structure for report_templates
-- ----------------------------
DROP TABLE IF EXISTS `report_templates`;
CREATE TABLE `report_templates`  (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `template_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `report_type` enum('queue_performance','service_point_analysis','staff_productivity','patient_flow','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `template_config` json NULL,
  `created_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`template_id`) USING BTREE,
  INDEX `fk_rt_created_by`(`created_by` ASC) USING BTREE,
  CONSTRAINT `fk_rt_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of report_templates
-- ----------------------------
INSERT INTO `report_templates` VALUES (1, 'รายงานประสิทธิภาพคิวรายวัน', 'รายงานสรุปประสิทธิภาพการให้บริการรายวัน', 'queue_performance', '{\"period\": \"daily\", \"groupBy\": \"queue_type\", \"metrics\": [\"total_queues\", \"avg_wait_time\", \"completion_rate\"]}', 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13', 1);
INSERT INTO `report_templates` VALUES (2, 'รายงานการใช้งานจุดบริการ', 'วิเคราะห์การใช้งานจุดบริการต่างๆ', 'service_point_analysis', '{\"period\": \"weekly\", \"groupBy\": \"service_point\", \"metrics\": [\"utilization_rate\", \"avg_service_time\", \"peak_hours\"]}', 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13', 1);
INSERT INTO `report_templates` VALUES (3, 'รายงานผลิตภาพเจ้าหน้าที่', 'ประเมินผลิตภาพการทำงานของเจ้าหน้าที่', 'staff_productivity', '{\"period\": \"monthly\", \"groupBy\": \"staff\", \"metrics\": [\"queues_served\", \"avg_service_time\", \"efficiency_score\"]}', 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13', 1);
INSERT INTO `report_templates` VALUES (4, 'รายงานการไหลของผู้ป่วย', 'วิเคราะห์เส้นทางการให้บริการผู้ป่วย', 'patient_flow', '{\"period\": \"weekly\", \"groupBy\": \"service_flow\", \"metrics\": [\"flow_completion_rate\", \"bottlenecks\", \"avg_flow_time\"]}', 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13', 1);
INSERT INTO `report_templates` VALUES (5, 'รายงานสรุปรายเดือน', 'รายงานสรุปภาพรวมประจำเดือน', 'queue_performance', '{\"period\": \"monthly\", \"groupBy\": \"month\", \"metrics\": [\"all\"], \"includeCharts\": true}', 1, '2025-06-19 16:30:13', '2025-06-19 16:30:13', 1);

-- ----------------------------
-- Table structure for role_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions`  (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`) USING BTREE,
  INDEX `fk_rp_permission`(`permission_id` ASC) USING BTREE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of role_permissions
-- ----------------------------
INSERT INTO `role_permissions` VALUES (1, 1);
INSERT INTO `role_permissions` VALUES (1, 2);
INSERT INTO `role_permissions` VALUES (1, 3);
INSERT INTO `role_permissions` VALUES (2, 3);
INSERT INTO `role_permissions` VALUES (3, 3);
INSERT INTO `role_permissions` VALUES (4, 3);
INSERT INTO `role_permissions` VALUES (5, 3);
INSERT INTO `role_permissions` VALUES (6, 3);
INSERT INTO `role_permissions` VALUES (1, 4);
INSERT INTO `role_permissions` VALUES (2, 4);
INSERT INTO `role_permissions` VALUES (3, 4);
INSERT INTO `role_permissions` VALUES (4, 4);
INSERT INTO `role_permissions` VALUES (5, 4);
INSERT INTO `role_permissions` VALUES (6, 4);
INSERT INTO `role_permissions` VALUES (1, 5);
INSERT INTO `role_permissions` VALUES (2, 5);
INSERT INTO `role_permissions` VALUES (3, 5);
INSERT INTO `role_permissions` VALUES (4, 5);
INSERT INTO `role_permissions` VALUES (5, 5);
INSERT INTO `role_permissions` VALUES (6, 5);
INSERT INTO `role_permissions` VALUES (1, 6);
INSERT INTO `role_permissions` VALUES (1, 7);
INSERT INTO `role_permissions` VALUES (1, 8);
INSERT INTO `role_permissions` VALUES (1, 9);

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`) USING BTREE,
  UNIQUE INDEX `role_name`(`role_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'Admin', 'ผู้ดูแลระบบ', '2025-06-19 16:30:13');
INSERT INTO `roles` VALUES (2, 'Staff-Screening', 'เจ้าหน้าที่จุดคัดกรอง', '2025-06-19 16:30:13');
INSERT INTO `roles` VALUES (3, 'Staff-Doctor', 'เจ้าหน้าที่ห้องตรวจแพทย์', '2025-06-19 16:30:13');
INSERT INTO `roles` VALUES (4, 'Staff-Pharmacy', 'เจ้าหน้าที่เภสัช', '2025-06-19 16:30:13');
INSERT INTO `roles` VALUES (5, 'Staff-Cashier', 'เจ้าหน้าที่การเงิน', '2025-06-19 16:30:13');
INSERT INTO `roles` VALUES (6, 'Staff-Records', 'เจ้าหน้าที่เวชระเบียน', '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for scheduled_reports
-- ----------------------------
DROP TABLE IF EXISTS `scheduled_reports`;
CREATE TABLE `scheduled_reports`  (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `schedule_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `schedule_frequency` enum('daily','weekly','monthly','quarterly') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `schedule_time` time NOT NULL,
  `schedule_day_of_week` int NULL DEFAULT NULL,
  `schedule_day_of_month` int NULL DEFAULT NULL,
  `recipients` json NULL,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `created_by` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`) USING BTREE,
  INDEX `fk_sr_template`(`template_id` ASC) USING BTREE,
  INDEX `fk_sr_created_by`(`created_by` ASC) USING BTREE,
  CONSTRAINT `fk_sr_created_by` FOREIGN KEY (`created_by`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_sr_template` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`template_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of scheduled_reports
-- ----------------------------

-- ----------------------------
-- Table structure for service_flow_history
-- ----------------------------
DROP TABLE IF EXISTS `service_flow_history`;
CREATE TABLE `service_flow_history`  (
  `flow_id` int NOT NULL AUTO_INCREMENT,
  `queue_id` int NULL DEFAULT NULL,
  `from_service_point_id` int NULL DEFAULT NULL,
  `to_service_point_id` int NULL DEFAULT NULL,
  `staff_id` int NULL DEFAULT NULL,
  `action` enum('created','called','forwarded','completed','recalled','skipped','cancelled','hold') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`flow_id`) USING BTREE,
  INDEX `idx_service_flow_queue_id`(`queue_id` ASC) USING BTREE,
  INDEX `idx_service_flow_timestamp`(`timestamp` ASC) USING BTREE,
  INDEX `idx_service_flow_action`(`action` ASC) USING BTREE,
  INDEX `idx_queue_history_date`(`timestamp` ASC) USING BTREE,
  INDEX `fk_sfh_from_sp`(`from_service_point_id` ASC) USING BTREE,
  INDEX `fk_sfh_to_sp`(`to_service_point_id` ASC) USING BTREE,
  INDEX `fk_sfh_staff`(`staff_id` ASC) USING BTREE,
  CONSTRAINT `fk_sfh_from_sp` FOREIGN KEY (`from_service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_sfh_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`queue_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_sfh_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_sfh_to_sp` FOREIGN KEY (`to_service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 131 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of service_flow_history
-- ----------------------------
INSERT INTO `service_flow_history` VALUES (1, 1, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-06-19 16:33:14');
INSERT INTO `service_flow_history` VALUES (2, 2, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-06-19 16:35:19');
INSERT INTO `service_flow_history` VALUES (3, 3, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 14:28:41');
INSERT INTO `service_flow_history` VALUES (4, 4, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 14:35:22');
INSERT INTO `service_flow_history` VALUES (5, 1, 1, 1, 1, 'called', NULL, '2025-07-29 14:36:22');
INSERT INTO `service_flow_history` VALUES (6, 2, 1, 1, 1, 'called', NULL, '2025-07-29 14:41:58');
INSERT INTO `service_flow_history` VALUES (7, 3, 1, 1, 1, 'called', NULL, '2025-07-29 14:42:29');
INSERT INTO `service_flow_history` VALUES (8, 4, 1, 1, 1, 'called', NULL, '2025-07-29 14:42:30');
INSERT INTO `service_flow_history` VALUES (9, 4, 1, 1, 1, 'called', NULL, '2025-07-29 14:42:33');
INSERT INTO `service_flow_history` VALUES (10, 4, 1, 1, 1, 'called', NULL, '2025-07-29 14:42:57');
INSERT INTO `service_flow_history` VALUES (11, 5, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 15:13:43');
INSERT INTO `service_flow_history` VALUES (12, 5, 1, 1, 1, 'called', NULL, '2025-07-29 15:13:48');
INSERT INTO `service_flow_history` VALUES (13, 6, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 15:23:29');
INSERT INTO `service_flow_history` VALUES (14, 6, 1, 1, 1, 'called', NULL, '2025-07-29 15:23:40');
INSERT INTO `service_flow_history` VALUES (15, 7, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 15:59:07');
INSERT INTO `service_flow_history` VALUES (16, 7, 1, 1, 1, 'called', NULL, '2025-07-29 16:00:24');
INSERT INTO `service_flow_history` VALUES (17, 7, 1, 1, 1, 'called', NULL, '2025-07-29 16:25:00');
INSERT INTO `service_flow_history` VALUES (18, 7, 1, 1, 1, 'called', NULL, '2025-07-29 16:26:09');
INSERT INTO `service_flow_history` VALUES (19, 7, 1, 2, 1, 'forwarded', '', '2025-07-29 16:26:25');
INSERT INTO `service_flow_history` VALUES (20, 7, 2, 2, 1, 'called', NULL, '2025-07-29 16:26:33');
INSERT INTO `service_flow_history` VALUES (21, 6, 1, 1, 1, 'called', NULL, '2025-07-29 16:54:49');
INSERT INTO `service_flow_history` VALUES (22, 6, 1, 1, 1, 'called', NULL, '2025-07-29 16:55:07');
INSERT INTO `service_flow_history` VALUES (23, 6, 1, 1, 1, 'called', NULL, '2025-07-29 16:55:16');
INSERT INTO `service_flow_history` VALUES (24, 8, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 16:55:28');
INSERT INTO `service_flow_history` VALUES (25, 8, 1, 1, 1, 'called', NULL, '2025-07-29 16:55:34');
INSERT INTO `service_flow_history` VALUES (26, 8, 1, 1, 1, 'called', NULL, '2025-07-29 16:55:46');
INSERT INTO `service_flow_history` VALUES (27, 7, 2, 2, 1, 'called', NULL, '2025-07-29 17:02:58');
INSERT INTO `service_flow_history` VALUES (28, 7, 2, 2, 1, 'called', NULL, '2025-07-29 17:03:06');
INSERT INTO `service_flow_history` VALUES (29, 7, 2, 2, 1, 'called', NULL, '2025-07-29 17:03:15');
INSERT INTO `service_flow_history` VALUES (30, 9, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-07-29 17:03:47');
INSERT INTO `service_flow_history` VALUES (31, 8, 1, 1, 1, 'called', NULL, '2025-07-29 17:03:57');
INSERT INTO `service_flow_history` VALUES (32, 9, 1, 1, 1, 'called', NULL, '2025-07-29 17:04:06');
INSERT INTO `service_flow_history` VALUES (33, 9, 1, 1, 1, 'called', NULL, '2025-07-29 17:04:36');
INSERT INTO `service_flow_history` VALUES (34, 9, 1, 1, 1, 'called', NULL, '2025-07-29 17:05:07');
INSERT INTO `service_flow_history` VALUES (35, 9, 1, 1, 1, 'called', NULL, '2025-07-29 17:05:14');
INSERT INTO `service_flow_history` VALUES (36, 9, 1, 1, 1, 'called', NULL, '2025-07-29 17:05:27');
INSERT INTO `service_flow_history` VALUES (37, 11, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-08-04 13:51:23');
INSERT INTO `service_flow_history` VALUES (38, 12, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-08-04 15:00:00');
INSERT INTO `service_flow_history` VALUES (39, 10, 1, 1, 1, 'called', NULL, '2025-08-13 16:40:11');
INSERT INTO `service_flow_history` VALUES (40, 11, 1, 1, 1, 'called', NULL, '2025-08-13 16:40:16');
INSERT INTO `service_flow_history` VALUES (41, 12, 1, 1, 1, 'called', NULL, '2025-08-13 16:40:22');
INSERT INTO `service_flow_history` VALUES (42, 12, 1, 1, 1, 'called', NULL, '2025-08-21 08:47:34');
INSERT INTO `service_flow_history` VALUES (43, 12, 1, 1, 1, 'called', NULL, '2025-08-22 14:18:13');
INSERT INTO `service_flow_history` VALUES (44, 13, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-08-26 13:01:41');
INSERT INTO `service_flow_history` VALUES (45, 12, 1, 1, 1, 'called', NULL, '2025-08-29 10:34:08');
INSERT INTO `service_flow_history` VALUES (46, 12, 1, 1, 1, 'called', NULL, '2025-08-29 10:34:33');
INSERT INTO `service_flow_history` VALUES (47, 12, 1, 1, 1, 'called', NULL, '2025-08-29 10:36:57');
INSERT INTO `service_flow_history` VALUES (48, 12, 1, 1, 1, 'called', NULL, '2025-08-29 11:07:28');
INSERT INTO `service_flow_history` VALUES (49, 13, 1, 1, 1, 'called', NULL, '2025-08-29 11:07:44');
INSERT INTO `service_flow_history` VALUES (50, 13, 1, 1, 1, 'called', NULL, '2025-08-29 11:08:20');
INSERT INTO `service_flow_history` VALUES (51, 13, 1, 1, 1, 'called', NULL, '2025-08-29 11:08:37');
INSERT INTO `service_flow_history` VALUES (52, 13, 1, 1, 1, 'called', NULL, '2025-08-29 11:08:47');
INSERT INTO `service_flow_history` VALUES (53, 14, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-08-29 11:09:22');
INSERT INTO `service_flow_history` VALUES (54, 14, 1, 1, 1, 'called', NULL, '2025-08-29 11:09:30');
INSERT INTO `service_flow_history` VALUES (55, 14, 1, 1, 1, 'called', NULL, '2025-08-29 11:09:51');
INSERT INTO `service_flow_history` VALUES (56, 14, 1, 1, 1, 'called', NULL, '2025-09-02 10:31:34');
INSERT INTO `service_flow_history` VALUES (57, 14, 1, 1, 1, 'called', NULL, '2025-09-02 10:31:34');
INSERT INTO `service_flow_history` VALUES (58, 14, 1, 1, 1, 'called', NULL, '2025-09-02 10:31:51');
INSERT INTO `service_flow_history` VALUES (59, 14, 1, 1, 1, 'called', NULL, '2025-09-02 10:32:44');
INSERT INTO `service_flow_history` VALUES (60, 15, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-02 10:33:01');
INSERT INTO `service_flow_history` VALUES (61, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:33:05');
INSERT INTO `service_flow_history` VALUES (62, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:33:50');
INSERT INTO `service_flow_history` VALUES (63, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:34:16');
INSERT INTO `service_flow_history` VALUES (64, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:35:31');
INSERT INTO `service_flow_history` VALUES (65, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:35:47');
INSERT INTO `service_flow_history` VALUES (66, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:35:47');
INSERT INTO `service_flow_history` VALUES (67, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:36:06');
INSERT INTO `service_flow_history` VALUES (68, 15, 1, 1, 1, 'called', NULL, '2025-09-02 10:36:21');
INSERT INTO `service_flow_history` VALUES (69, 16, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-02 10:36:30');
INSERT INTO `service_flow_history` VALUES (70, 16, 1, 1, 1, 'called', NULL, '2025-09-02 10:36:36');
INSERT INTO `service_flow_history` VALUES (71, 17, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-03 15:00:03');
INSERT INTO `service_flow_history` VALUES (72, 17, 1, 1, 1, 'called', NULL, '2025-09-03 15:08:57');
INSERT INTO `service_flow_history` VALUES (73, 17, 1, 1, 1, 'called', NULL, '2025-09-03 15:20:00');
INSERT INTO `service_flow_history` VALUES (74, 17, 1, 1, 1, 'called', NULL, '2025-09-03 15:20:31');
INSERT INTO `service_flow_history` VALUES (75, 18, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-03 15:20:53');
INSERT INTO `service_flow_history` VALUES (76, 18, 1, 1, 1, 'called', NULL, '2025-09-03 15:20:59');
INSERT INTO `service_flow_history` VALUES (77, 18, 1, 1, 1, 'called', NULL, '2025-09-03 15:38:02');
INSERT INTO `service_flow_history` VALUES (78, 18, 1, 1, 1, 'called', NULL, '2025-09-03 15:38:25');
INSERT INTO `service_flow_history` VALUES (79, 18, 1, 1, 1, 'called', NULL, '2025-09-03 15:38:39');
INSERT INTO `service_flow_history` VALUES (80, 18, 1, 1, 1, 'called', NULL, '2025-09-03 15:43:59');
INSERT INTO `service_flow_history` VALUES (81, 7, 2, 2, 1, 'called', NULL, '2025-09-03 15:44:52');
INSERT INTO `service_flow_history` VALUES (82, 7, 2, 2, 1, 'called', NULL, '2025-09-03 15:45:02');
INSERT INTO `service_flow_history` VALUES (83, 7, 2, 2, 1, 'called', NULL, '2025-09-03 15:45:21');
INSERT INTO `service_flow_history` VALUES (84, 19, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-03 16:13:14');
INSERT INTO `service_flow_history` VALUES (85, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:13:26');
INSERT INTO `service_flow_history` VALUES (86, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:13:39');
INSERT INTO `service_flow_history` VALUES (87, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:24:28');
INSERT INTO `service_flow_history` VALUES (88, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:24:51');
INSERT INTO `service_flow_history` VALUES (89, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:25:08');
INSERT INTO `service_flow_history` VALUES (90, 19, 1, 1, 1, 'called', NULL, '2025-09-03 16:28:41');
INSERT INTO `service_flow_history` VALUES (91, 20, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-08 15:44:02');
INSERT INTO `service_flow_history` VALUES (92, 21, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-09 14:07:56');
INSERT INTO `service_flow_history` VALUES (93, 19, 1, NULL, 1, 'completed', '', '2025-09-09 14:10:22');
INSERT INTO `service_flow_history` VALUES (94, 18, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-09 14:18:50');
INSERT INTO `service_flow_history` VALUES (95, 17, 1, 1, 1, 'recalled', NULL, '2025-09-11 09:50:56');
INSERT INTO `service_flow_history` VALUES (96, 17, 1, 2, 1, 'forwarded', '', '2025-09-11 09:51:31');
INSERT INTO `service_flow_history` VALUES (97, 16, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-11 09:51:38');
INSERT INTO `service_flow_history` VALUES (98, 15, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-11 09:51:43');
INSERT INTO `service_flow_history` VALUES (99, 14, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-11 09:51:47');
INSERT INTO `service_flow_history` VALUES (100, 13, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-11 09:51:51');
INSERT INTO `service_flow_history` VALUES (101, 12, 1, NULL, 1, 'cancelled', 'ผู้ป่วยไม่มา', '2025-09-11 09:51:56');
INSERT INTO `service_flow_history` VALUES (102, 11, 1, 1, 1, 'called', 'เริ่มให้บริการ', '2025-09-11 15:58:27');
INSERT INTO `service_flow_history` VALUES (103, 11, 1, 2, 1, 'forwarded', '', '2025-09-11 15:58:39');
INSERT INTO `service_flow_history` VALUES (104, 10, 1, 1, 1, 'called', 'เริ่มให้บริการ', '2025-09-11 15:58:41');
INSERT INTO `service_flow_history` VALUES (105, 10, 1, 2, 1, 'forwarded', '', '2025-09-11 15:58:46');
INSERT INTO `service_flow_history` VALUES (106, 9, 1, 3, 1, 'forwarded', '', '2025-09-11 15:58:50');
INSERT INTO `service_flow_history` VALUES (107, 8, 1, 1, 1, 'recalled', NULL, '2025-09-17 11:20:05');
INSERT INTO `service_flow_history` VALUES (108, 8, 1, NULL, 1, 'completed', '', '2025-09-24 15:47:46');
INSERT INTO `service_flow_history` VALUES (109, 1, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-24 15:53:40');
INSERT INTO `service_flow_history` VALUES (110, 1, 1, 1, 1, 'called', NULL, '2025-09-24 15:54:09');
INSERT INTO `service_flow_history` VALUES (111, 1, 1, 2, 1, 'forwarded', '', '2025-09-29 09:59:33');
INSERT INTO `service_flow_history` VALUES (112, 1, 2, 2, 1, 'called', NULL, '2025-09-29 10:00:03');
INSERT INTO `service_flow_history` VALUES (113, 1, 2, 2, 1, 'recalled', NULL, '2025-09-29 10:01:25');
INSERT INTO `service_flow_history` VALUES (114, 1, 2, 2, 1, 'recalled', NULL, '2025-09-29 10:02:38');
INSERT INTO `service_flow_history` VALUES (115, 2, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-09-30 15:38:28');
INSERT INTO `service_flow_history` VALUES (116, 2, 1, 1, 1, 'called', NULL, '2025-09-30 15:38:32');
INSERT INTO `service_flow_history` VALUES (117, 2, 1, 1, 1, 'recalled', NULL, '2025-09-30 15:45:24');
INSERT INTO `service_flow_history` VALUES (118, 2, 1, 1, 1, 'recalled', NULL, '2025-09-30 16:08:32');
INSERT INTO `service_flow_history` VALUES (119, 2, 1, 1, 1, 'recalled', NULL, '2025-10-01 08:37:36');
INSERT INTO `service_flow_history` VALUES (120, 2, 1, 1, 1, 'recalled', NULL, '2025-10-01 08:43:04');
INSERT INTO `service_flow_history` VALUES (121, 3, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-10-01 08:43:15');
INSERT INTO `service_flow_history` VALUES (122, 2, 1, NULL, 1, 'cancelled', 'ข้อมูลไม่ถูกต้อง', '2025-10-01 08:43:29');
INSERT INTO `service_flow_history` VALUES (123, 3, 1, 1, 1, 'called', NULL, '2025-10-01 08:43:31');
INSERT INTO `service_flow_history` VALUES (124, 3, 1, 1, 1, 'called', 'เริ่มให้บริการ', '2025-10-01 08:55:17');
INSERT INTO `service_flow_history` VALUES (125, 4, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-10-01 08:59:17');
INSERT INTO `service_flow_history` VALUES (126, 4, 1, 1, 1, 'called', NULL, '2025-10-01 09:32:05');
INSERT INTO `service_flow_history` VALUES (127, 4, 1, NULL, 1, 'completed', '', '2025-10-01 09:33:02');
INSERT INTO `service_flow_history` VALUES (128, 3, 1, 2, 1, 'forwarded', '', '2025-10-01 09:33:06');
INSERT INTO `service_flow_history` VALUES (129, 5, NULL, 1, NULL, 'created', 'สร้างคิวจาก Kiosk', '2025-10-01 10:15:59');
INSERT INTO `service_flow_history` VALUES (130, 5, 1, 1, 1, 'called', NULL, '2025-10-01 10:16:10');

-- ----------------------------
-- Table structure for service_flows
-- ----------------------------
DROP TABLE IF EXISTS `service_flows`;
CREATE TABLE `service_flows`  (
  `flow_id` int NOT NULL AUTO_INCREMENT,
  `queue_type_id` int NULL DEFAULT NULL,
  `from_service_point_id` int NULL DEFAULT NULL,
  `to_service_point_id` int NULL DEFAULT NULL,
  `sequence_order` int NULL DEFAULT 0,
  `is_optional` tinyint(1) NULL DEFAULT 0,
  `is_active` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`flow_id`) USING BTREE,
  INDEX `idx_service_flows_queue_type`(`queue_type_id` ASC) USING BTREE,
  INDEX `idx_service_flows_active`(`is_active` ASC) USING BTREE,
  INDEX `fk_sf_from_sp`(`from_service_point_id` ASC) USING BTREE,
  INDEX `fk_sf_to_sp`(`to_service_point_id` ASC) USING BTREE,
  CONSTRAINT `fk_sf_from_sp` FOREIGN KEY (`from_service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_sf_queue_type` FOREIGN KEY (`queue_type_id`) REFERENCES `queue_types` (`queue_type_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_sf_to_sp` FOREIGN KEY (`to_service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of service_flows
-- ----------------------------
INSERT INTO `service_flows` VALUES (2, 1, 1, 2, 1, 0, 1);
INSERT INTO `service_flows` VALUES (3, 1, 1, 3, 2, 0, 1);
INSERT INTO `service_flows` VALUES (4, 1, 2, 5, 3, 0, 1);

-- ----------------------------
-- Table structure for service_points
-- ----------------------------
DROP TABLE IF EXISTS `service_points`;
CREATE TABLE `service_points`  (
  `service_point_id` int NOT NULL AUTO_INCREMENT,
  `point_label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `point_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `point_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `position_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `display_order` int NULL DEFAULT 0,
  `queue_type_id` int NULL DEFAULT NULL,
  `voice_template_id` int NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_point_id`) USING BTREE,
  UNIQUE INDEX `position_key`(`position_key` ASC) USING BTREE,
  INDEX `idx_service_points_active`(`is_active` ASC) USING BTREE,
  INDEX `idx_service_points_display_order`(`display_order` ASC) USING BTREE,
  INDEX `idx_service_points_queue_type`(`queue_type_id` ASC) USING BTREE,
  INDEX `fk_sp_voice_template`(`voice_template_id` ASC) USING BTREE,
  CONSTRAINT `fk_sp_queue_type` FOREIGN KEY (`queue_type_id`) REFERENCES `queue_types` (`queue_type_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_sp_voice_template` FOREIGN KEY (`voice_template_id`) REFERENCES `voice_templates` (`template_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of service_points
-- ----------------------------
INSERT INTO `service_points` VALUES (1, 'จุด', 'คัดกรอง', 'จุดคัดกรองผู้ป่วยเบื้องต้น', 'SCREENING_01', 1, 1, NULL, NULL, '2025-06-19 16:30:13');
INSERT INTO `service_points` VALUES (2, NULL, 'ห้องตรวจ 1', 'ห้องตรวจแพทย์ห้องที่ 1', 'DOCTOR_01', 1, 2, NULL, NULL, '2025-06-19 16:30:13');
INSERT INTO `service_points` VALUES (3, NULL, 'ห้องตรวจ 2', 'ห้องตรวจแพทย์ห้องที่ 2', 'DOCTOR_02', 1, 3, NULL, NULL, '2025-06-19 16:30:13');
INSERT INTO `service_points` VALUES (4, NULL, 'ห้องเภสัช', 'จุดรับยา', 'PHARMACY_01', 1, 4, NULL, NULL, '2025-06-19 16:30:13');
INSERT INTO `service_points` VALUES (5, NULL, 'การเงิน', 'จุดชำระเงิน', 'CASHIER_01', 1, 5, NULL, NULL, '2025-06-19 16:30:13');
INSERT INTO `service_points` VALUES (6, NULL, 'เวชระเบียน', 'จุดบริการเวชระเบียน', 'RECORDS_01', 1, 6, NULL, NULL, '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of settings
-- ----------------------------
INSERT INTO `settings` VALUES ('api_documentation_url', '/api/docs', 'URL เอกสาร API', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('api_enabled', '1', 'เปิดใช้งาน Mobile API', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('api_support_email', 'support@hospital.com', 'อีเมลสำหรับการสนับสนุน API', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('api_version', '1.0', 'เวอร์ชัน API ปัจจุบัน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('app_description', 'ระบบจัดการคิวโรงพยาบาล', 'คำอธิบายแอปพลิเคชัน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('app_language', 'th', 'ภาษาของแอปพลิเคชัน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('app_logo', '', 'โลโก้แอปพลิเคชัน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'ชื่อแอปพลิเคชัน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('app_timezone', 'Asia/Bangkok', 'เขตเวลาของแอปพลิเคชัน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('audio_provider', 'files', '', '2025-09-11 13:33:40');
INSERT INTO `settings` VALUES ('audio_repeat_count', '1', 'จำนวนครั้งที่เล่นซ้ำ', '2025-09-11 13:33:40');
INSERT INTO `settings` VALUES ('audio_volume', '1', 'ระดับเสียง', '2025-09-11 13:33:40');
INSERT INTO `settings` VALUES ('auto_backup_enabled', 'false', 'เปิดใช้งานการสำรองอัตโนมัติ', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('auto_backup_time', '02:00', 'เวลาสำรองข้อมูลอัตโนมัติ', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('auto_forward_enabled', 'false', 'เปิดใช้งานการส่งต่ออัตโนมัติ', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('auto_reset_backup_before', '0', 'สำรองข้อมูลก่อนรีเซ็ต', '2025-10-01 09:00:41');
INSERT INTO `settings` VALUES ('auto_reset_enabled', '1', 'เปิดใช้งานการรีเซ็ตอัตโนมัติ', '2025-10-01 09:00:41');
INSERT INTO `settings` VALUES ('auto_reset_max_retries', '3', 'จำนวนครั้งสูงสุดในการลองใหม่', '2025-10-01 09:00:41');
INSERT INTO `settings` VALUES ('auto_reset_notification', '0', 'แจ้งเตือนเมื่อรีเซ็ต', '2025-10-01 09:00:41');
INSERT INTO `settings` VALUES ('aws_access_key_id', '', 'AWS Access Key ID', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('aws_region', '', 'AWS Region', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('aws_secret_access_key', '', 'AWS Secret Access Key', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('azure_speech_key', '', 'Azure Speech Service Key', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('azure_speech_region', '', 'Azure Speech Service Region', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('backup_enabled', 'true', 'เปิดใช้งานการสำรองข้อมูล', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('backup_retention_days', '30', 'จำนวนวันเก็บข้อมูลสำรอง', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('daily_summary_enabled', 'true', 'เปิดใช้งานสรุปรายวัน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('daily_summary_time', '23:30', 'เวลาสร้างสรุปรายวัน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('default_rate_limit', '60', 'จำนวนการเรียกใช้สูงสุดต่อนาที', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('display_refresh_interval', '3', 'ช่วงเวลาการรีเฟรชหน้าจอ (วินาที)', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('email_notifications', 'false', 'เปิดใช้งานการแจ้งเตือนทางอีเมล', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('enable_priority_queue', 'true', 'เปิดใช้งานคิวพิเศษ', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('google_cloud_key_file', '', 'Google Cloud Key File', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('google_cloud_project_id', '', 'Google Cloud Project ID', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('mail_encryption', 'tls', 'SMTP Encryption', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_from_address', 'noreply@hospital.com', 'ที่อยู่อีเมลผู้ส่ง', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_from_name', 'Queue System', 'ชื่อผู้ส่งอีเมล', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_host', 'smtp.gmail.com', 'SMTP Host', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_password', '', 'SMTP Password', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_port', '587', 'SMTP Port', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('mail_username', '', 'SMTP Username', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('notification_enabled', 'true', 'เปิดใช้งานระบบแจ้งเตือน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('push_notification_enabled', '1', 'เปิดใช้งาน Push Notification', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('queue_number_length', '3', 'ความยาวของหมายเลขคิว', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('queue_prefix_length', '1', 'ความยาวของ prefix คิว', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('queue_timeout_minutes', '30', 'เวลา timeout ของคิว (นาที)', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('rate_limit_enabled', '1', 'เปิดใช้งานการจำกัดอัตราการเรียกใช้', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('report_cache_enabled', 'true', 'เปิดใช้งาน cache สำหรับรายงาน', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('report_cache_ttl', '1800', 'เวลา cache รายงาน (วินาที)', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('service_point_label', 'จุดบริการ', '', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('session_timeout', '3600', 'เวลาหมดอายุเซสชัน (วินาที)', '2025-06-19 16:30:13');
INSERT INTO `settings` VALUES ('sound_notification_before', '1', 'เล่นเสียงแจ้งเตือนก่อน', '2025-09-11 13:33:40');
INSERT INTO `settings` VALUES ('telegram_admin_chat_id', '', 'Telegram Admin Chat ID', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('telegram_bot_token', '', 'Telegram Bot Token', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('telegram_chat_id', '', 'Telegram Chat ID (ทั่วไป)', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('telegram_group_chat_id', '', 'Telegram Group Chat ID', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('telegram_notifications', 'false', 'เปิดใช้งาน Telegram Notifications', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('telegram_notify_template', '<br /><b>Warning</b>:  Undefined array key ', 'เทมเพลตข้อความ Telegram', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('tts_api_url', '', 'URL API ของ TTS', '2025-08-26 11:10:06');
INSERT INTO `settings` VALUES ('tts_call_format', 'ขอเชิญหมายเลข 5555 {queue_number} ที่ {service_point} ครับ', '', '2025-09-08 15:52:50');
INSERT INTO `settings` VALUES ('tts_enabled', '0', 'เปิดใช้งาน TTS', '2025-09-09 14:18:19');
INSERT INTO `settings` VALUES ('tts_language', 'th-TH', 'ภาษาของ TTS', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('tts_pitch', '0', 'ระดับเสียงของ TTS', '2025-08-26 11:10:06');
INSERT INTO `settings` VALUES ('tts_provider', 'google_free', 'ผู้ให้บริการ TTS', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('tts_speed', '0.6', 'ความเร็วของ TTS', '2025-08-29 09:54:28');
INSERT INTO `settings` VALUES ('tts_voice', 'th-TH-Wavenet-B', 'เสียงของ TTS', '2025-08-26 11:10:06');
INSERT INTO `settings` VALUES ('working_hours_end', '16:00', 'เวลาสิ้นสุดการทำงาน', '2025-09-11 13:33:34');
INSERT INTO `settings` VALUES ('working_hours_start', '08:00', 'เวลาเริ่มทำงาน', '2025-09-11 13:33:34');

-- ----------------------------
-- Table structure for staff_service_point_access
-- ----------------------------
DROP TABLE IF EXISTS `staff_service_point_access`;
CREATE TABLE `staff_service_point_access`  (
  `staff_id` int NOT NULL,
  `service_point_id` int NOT NULL,
  PRIMARY KEY (`staff_id`, `service_point_id`) USING BTREE,
  INDEX `fk_sspa_sp`(`service_point_id` ASC) USING BTREE,
  CONSTRAINT `fk_sspa_sp` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`service_point_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_sspa_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`staff_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of staff_service_point_access
-- ----------------------------
INSERT INTO `staff_service_point_access` VALUES (1, 1);
INSERT INTO `staff_service_point_access` VALUES (1, 2);
INSERT INTO `staff_service_point_access` VALUES (1, 3);
INSERT INTO `staff_service_point_access` VALUES (1, 4);
INSERT INTO `staff_service_point_access` VALUES (1, 5);
INSERT INTO `staff_service_point_access` VALUES (1, 6);

-- ----------------------------
-- Table structure for staff_users
-- ----------------------------
DROP TABLE IF EXISTS `staff_users`;
CREATE TABLE `staff_users`  (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int NULL DEFAULT NULL,
  `is_active` tinyint(1) NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`) USING BTREE,
  UNIQUE INDEX `username`(`username` ASC) USING BTREE,
  INDEX `idx_staff_users_username`(`username` ASC) USING BTREE,
  INDEX `idx_staff_users_active`(`is_active` ASC) USING BTREE,
  INDEX `fk_staff_role`(`role_id` ASC) USING BTREE,
  CONSTRAINT `fk_staff_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of staff_users
-- ----------------------------
INSERT INTO `staff_users` VALUES (1, 'admin', '$2y$10$YnYLz/CHULA9Cpl4Kqmnke.FMzw9AjzQHC07C955UKo58R4M5sOyO', 'ผู้ดูแลระบบ', 1, 1, '2025-10-01 08:37:04', 'pass:123456', '2025-06-19 16:30:13');

-- ----------------------------
-- Table structure for tts_api_services
-- ----------------------------
DROP TABLE IF EXISTS `tts_api_services`;
CREATE TABLE `tts_api_services`  (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `curl_command` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tts_api_services
-- ----------------------------
INSERT INTO `tts_api_services` VALUES (1, 'GIN', 'curl --location http://gin.ycap.go.th:5000/tts\' \\\r\n--header \'Content-Type: application/json\' \\\r\n--data \'{\r\n    \"text\": \"{{_TEXT_TO_SPECH_}}\",\r\n    \"lang\": \"th\"\r\n}\'', 1, '2025-09-30 15:40:59', '2025-09-30 15:46:23');

-- ----------------------------
-- Table structure for voice_templates
-- ----------------------------
DROP TABLE IF EXISTS `voice_templates`;
CREATE TABLE `voice_templates`  (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `template_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_default` tinyint(1) NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`) USING BTREE,
  UNIQUE INDEX `template_name`(`template_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of voice_templates
-- ----------------------------
INSERT INTO `voice_templates` VALUES (1, 'เรียกคิวมาตรฐาน', 'ขอเชิญ หมายเลข {queue_number} ที่ {service_point_name} ครับ', 1, '2025-06-19 16:30:13', '2025-09-03 15:20:24');
INSERT INTO `voice_templates` VALUES (2, 'เรียกคิวแบบสั้น', 'คิว {queue_number} ที่ {service_point_name}', 0, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `voice_templates` VALUES (3, 'เรียกคิวแบบมีชื่อ', 'คุณ {patient_name} หมายเลข {queue_number} เชิญที่ {service_point_name}', 0, '2025-06-19 16:30:13', '2025-06-19 16:30:13');
INSERT INTO `voice_templates` VALUES (4, '5555', 'ขอเชิญ {คัดกรอง}', 0, '2025-08-28 10:48:55', '2025-08-29 10:23:30');

-- ----------------------------
-- View structure for v_current_queue_status
-- ----------------------------
DROP VIEW IF EXISTS `v_current_queue_status`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_current_queue_status` AS select `q`.`queue_id` AS `queue_id`,`q`.`queue_number` AS `queue_number`,`q`.`queue_type_id` AS `queue_type_id`,`qt`.`type_name` AS `queue_type_name`,`q`.`current_status` AS `status`,`q`.`priority_level` AS `priority`,`q`.`current_service_point_id` AS `current_service_point_id`,`sp`.`point_name` AS `service_point_name`,`q`.`creation_time` AS `created_at`,`q`.`last_called_time` AS `called_at`,`q`.`estimated_wait_time` AS `estimated_wait_time` from ((`queues` `q` left join `queue_types` `qt` on((`q`.`queue_type_id` = `qt`.`queue_type_id`))) left join `service_points` `sp` on((`q`.`current_service_point_id` = `sp`.`service_point_id`))) where ((cast(`q`.`creation_time` as date) = curdate()) and (`q`.`current_status` in ('waiting','called','processing')));

-- ----------------------------
-- View structure for v_queue_statistics
-- ----------------------------
DROP VIEW IF EXISTS `v_queue_statistics`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_queue_statistics` AS select cast(`q`.`creation_time` as date) AS `queue_date`,`q`.`queue_type_id` AS `queue_type_id`,count(0) AS `total_queues`,sum((case when (`q`.`current_status` = 'completed') then 1 else 0 end)) AS `completed_queues`,sum((case when (`q`.`current_status` = 'cancelled') then 1 else 0 end)) AS `cancelled_queues`,sum((case when (`q`.`current_status` = 'no_show') then 1 else 0 end)) AS `no_show_queues`,NULL AS `avg_wait_time`,NULL AS `avg_service_time` from `queues` `q` group by cast(`q`.`creation_time` as date),`q`.`queue_type_id`;

-- ----------------------------
-- View structure for v_service_point_performance
-- ----------------------------
DROP VIEW IF EXISTS `v_service_point_performance`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `v_service_point_performance` AS select `sp`.`service_point_id` AS `service_point_id`,`sp`.`point_name` AS `point_name`,cast(`q`.`creation_time` as date) AS `performance_date`,count(`q`.`queue_id`) AS `total_served`,NULL AS `avg_service_time`,sum((case when (`q`.`current_status` = 'completed') then 1 else 0 end)) AS `completed_count`,((sum((case when (`q`.`current_status` = 'completed') then 1 else 0 end)) / count(`q`.`queue_id`)) * 100) AS `completion_rate` from (`service_points` `sp` left join `queues` `q` on((`sp`.`service_point_id` = `q`.`current_service_point_id`))) where (`q`.`creation_time` >= (curdate() - interval 30 day)) group by `sp`.`service_point_id`,cast(`q`.`creation_time` as date);

SET FOREIGN_KEY_CHECKS = 1;

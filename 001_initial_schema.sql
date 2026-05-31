-- ═══════════════════════════════════════════════════════════
-- YEB Platform — Initial Schema Migration
-- Run this in your Hostinger MySQL database (phpMyAdmin)
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── USERS + AUTH ───
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','trainer','rep') NOT NULL DEFAULT 'rep',
  `unlocked_level` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TRAIN CONTENT: MANUALS (top-level grouping) ───
CREATE TABLE IF NOT EXISTS `manuals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── FOLDERS (infinite nesting via parent_id) ───
CREATE TABLE IF NOT EXISTS `folders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `manual_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_folder_manual` (`manual_id`),
  KEY `fk_folder_parent` (`parent_id`),
  CONSTRAINT `fk_folder_manual` FOREIGN KEY (`manual_id`) REFERENCES `manuals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_folder_parent` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── MODULES (a "note" / lesson / quiz / passoff / checkpoint) ───
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `module_type` ENUM('lesson','quiz','passoff','checkpoint') NOT NULL DEFAULT 'lesson',
  `level_required` INT NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `next_step_guidance` TEXT NULL,
  `pass_threshold` INT NOT NULL DEFAULT 80,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_module_folder` (`folder_id`),
  CONSTRAINT `fk_module_folder` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SECTIONS (quote/response/tip blocks) ───
CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `section_type` ENUM('quote_response','text','heading') NOT NULL DEFAULT 'quote_response',
  `customer_quote` TEXT NULL,
  `rep_response` TEXT NULL,
  `tip` TEXT NULL,
  `heading_text` TEXT NULL,
  `body_text` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_section_module` (`module_id`),
  CONSTRAINT `fk_section_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── VIDEOS (embed URLs per module) ───
CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL,
  `embed_url` TEXT NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_video_module` (`module_id`),
  CONSTRAINT `fk_video_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── QUIZ QUESTIONS ───
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice','short_answer') NOT NULL DEFAULT 'multiple_choice',
  `explanation` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_question_module` (`module_id`),
  CONSTRAINT `fk_question_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── QUIZ CHOICES ───
CREATE TABLE IF NOT EXISTS `quiz_choices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `choice_text` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_choice_question` (`question_id`),
  CONSTRAINT `fk_choice_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── QUIZ ATTEMPTS ───
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `score` INT NOT NULL,
  `passed` TINYINT(1) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_attempt_user` (`user_id`),
  KEY `fk_attempt_module` (`module_id`),
  CONSTRAINT `fk_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── USER MODULE PROGRESS ───
CREATE TABLE IF NOT EXISTS `user_module_progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `module_id` INT UNSIGNED NOT NULL,
  `status` ENUM('not_started','in_progress','completed','pending_passoff','passed_off') NOT NULL DEFAULT 'not_started',
  `completed_at` DATETIME NULL,
  `passed_off_by` INT UNSIGNED NULL,
  `passed_off_at` DATETIME NULL,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_module_unique` (`user_id`, `module_id`),
  KEY `fk_progress_user` (`user_id`),
  KEY `fk_progress_module` (`module_id`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── LEVEL CHECKPOINTS ───
CREATE TABLE IF NOT EXISTS `level_checkpoints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `manual_id` INT UNSIGNED NOT NULL,
  `level_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `instructions` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `manual_level_unique` (`manual_id`, `level_number`),
  CONSTRAINT `fk_checkpoint_manual` FOREIGN KEY (`manual_id`) REFERENCES `manuals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CHECKPOINT REQUIREMENTS ───
CREATE TABLE IF NOT EXISTS `checkpoint_requirements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `checkpoint_id` INT UNSIGNED NOT NULL,
  `required_module_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cp_req_unique` (`checkpoint_id`, `required_module_id`),
  CONSTRAINT `fk_cpreq_checkpoint` FOREIGN KEY (`checkpoint_id`) REFERENCES `level_checkpoints` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpreq_module` FOREIGN KEY (`required_module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CHECKPOINT REQUESTS (rep asks leader to unlock level) ───
CREATE TABLE IF NOT EXISTS `checkpoint_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `checkpoint_id` INT UNSIGNED NOT NULL,
  `status` ENUM('not_requested','requested','approved','rejected') NOT NULL DEFAULT 'not_requested',
  `requested_at` DATETIME NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `reviewer_notes` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cprequest_user` (`user_id`),
  KEY `fk_cprequest_checkpoint` (`checkpoint_id`),
  CONSTRAINT `fk_cprequest_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cprequest_checkpoint` FOREIGN KEY (`checkpoint_id`) REFERENCES `level_checkpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── HOME SECTIONS (editable from admin) ───
CREATE TABLE IF NOT EXISTS `home_sections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NULL,
  `subtitle` TEXT NULL,
  `body_json` JSON NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key_unique` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SERVICES SECTIONS (editable from admin) ───
CREATE TABLE IF NOT EXISTS `services_sections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `price` VARCHAR(64) NULL,
  `price_period` VARCHAR(64) NULL,
  `description` TEXT NULL,
  `features_json` JSON NULL,
  `cta_label` VARCHAR(64) NULL,
  `cta_url` VARCHAR(255) NULL,
  `icon` VARCHAR(32) NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_key_unique` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════════

-- Default admin user (password: — change after first login!)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `unlocked_level`) VALUES
('David West', 'info@yourenergybest.com', '$2y$10$placeholder_hash_replace_me', 'admin', 90);

-- Builder Manual + initial levels
INSERT INTO `manuals` (`title`, `slug`, `description`, `display_order`) VALUES
('Builder Manual', 'builder', 'The complete door-to-door solar sales training path.', 1),
('Engineer Manual', 'engineer', 'Technical solar knowledge and system design.', 2),
('Leader Manual', 'leader', 'Team management, recruiting, and leadership development.', 3);

-- Level checkpoints for Builder Manual (levels stored as int x10: 1.5 = 15)
INSERT INTO `level_checkpoints` (`manual_id`, `level_number`, `title`, `instructions`, `display_order`) VALUES
(1, 0,  'Level 0: Rookie Rep', 'Welcome to the team. Complete all Level 0 modules to begin your journey.', 1),
(1, 10, 'Level 1.0: Question Adventurer', 'Master the art of asking great questions. Complete all L1 modules and pass the quiz.', 2),
(1, 15, 'Level 1.5', 'Intermediate questioning skills. Practice in the field and get trainer approval.', 3),
(1, 20, 'Level 2.0: Verbal Jiujitsu', 'Learn to redirect conversations naturally. Complete modules and passoff with trainer.', 4),
(1, 25, 'Level 2.5', 'Advanced redirection practice. Field validation required.', 5),
(1, 30, 'Level 3.0: Knocking Existing Installs', 'Master the approach for homes that already have solar.', 6),
(1, 40, 'Level 4.0: Increase Your Booking Ratio', 'Advanced questions, status quo disruption. Requires checkpoint approval.', 7),
(1, 50, 'Level 5.0: Opening the Sandbox', 'Advanced questioning — opening possibilities with homeowners.', 8),
(1, 60, 'Level 6.0: Solidifying Appointments', 'Lock in appointments and reduce cancellations.', 9),
(1, 70, 'Level 7.0: Solution Presentation', 'Master the in-home or virtual presentation.', 10),
(1, 80, 'Level 8.0: Overcoming Objections Playset', 'Handle every objection with confidence.', 11),
(1, 90, 'Level 9.0: Training Others', 'Become a trainer. Teach what you know.', 12);

-- Seed "What Makes Us Different" home section
INSERT INTO `home_sections` (`section_key`, `title`, `subtitle`, `body_json`) VALUES
('what_makes_us_different', 'What Makes Us Different', 'We''re not a national call center. We''re your San Diego neighbors.', '[
  {"icon":"🏠","title":"Personalized Design","description":"Custom-engineered for your roof, your usage, your goals — not a cookie-cutter template."},
  {"icon":"💰","title":"$0 Down Options","description":"Multiple financing paths so you start saving from day one — no upfront cost required."},
  {"icon":"📱","title":"Transparent Process","description":"Know exactly where your project stands at every step. No surprises, no runaround."},
  {"icon":"🤝","title":"Local San Diego Team","description":"Real people who live here, work here, and show up. Not a 1-800 number."}
]');

-- Seed services
INSERT INTO `services_sections` (`section_key`, `title`, `price`, `price_period`, `description`, `features_json`, `icon`, `display_order`) VALUES
('self_audit', 'Self-Audit', '$49', 'one-time', 'Unlock your solar audit to determine system health and performance.', '["System performance analysis","Production vs. consumption review","Inverter & panel health check","Savings verification report","Delivered within 48 hours"]', '📊', 1),
('full_inspection', 'Full Inspection & Audit', '$389', 'one-time', 'Complete on-site inspection and audit report of your entire system.', '["Everything in Self-Audit","On-site roof & electrical inspection","Panel-level diagnostics","Detailed audit report with photos","Repair recommendations & quote","60-min expert consultation"]', '🔍', 2),
('yearly_plan', 'Yearly Service Plan', '$109', '/year', 'Upfront audit and yearly inspections of your system.', '["Initial full system audit","Annual on-site inspection","Performance monitoring review","Priority scheduling for repairs","10% discount on all services","Yearly report with recommendations"]', '🛡️', 3);

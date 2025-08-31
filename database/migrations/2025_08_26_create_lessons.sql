-- Migration: create lessons table
-- Note: Using updated_on (no space) and created_at for consistency

CREATE TABLE IF NOT EXISTS `lessons` (
  `lesson_id` INT NOT NULL AUTO_INCREMENT,
  `subject_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `lesson_number` VARCHAR(255) DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `vocabulary` TEXT NULL,
  `content` TEXT NOT NULL,
  `thumbnail` TEXT NULL,
  `video` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lesson_id`),
  KEY `idx_lessons_class_subject` (`class_id`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

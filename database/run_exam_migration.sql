-- Run this script to create the new exam_results table
-- This adds result storage for exam history purposes

-- Create the exam_results table
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` int(11) NOT NULL,
  `exam_assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `obtained_marks` double NOT NULL DEFAULT 0,
  `total_marks` double NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grade` varchar(2) DEFAULT NULL,
  `grading_type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','graded','published') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add primary key and indexes
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_assessment_id` (`exam_assessment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_grading_type` (`grading_type`),
  ADD KEY `idx_status` (`status`);

-- Set auto-increment
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Optional: Add foreign key constraints (uncomment if you want referential integrity)
-- ALTER TABLE `exam_results`
--   ADD CONSTRAINT `fk_exam_results_assessment` FOREIGN KEY (`exam_assessment_id`) REFERENCES `exam_assessments` (`exam_assessment_id`) ON DELETE CASCADE;

-- Success message
SELECT 'Exam results table created successfully!' as message;

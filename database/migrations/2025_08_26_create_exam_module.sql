-- Exam module tables (exact structures provided, plus primary keys and auto-increments for operability)

-- --------------------------------------------------------
-- Table structure for table `exam_assessments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_assessments` (
  `exam_assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `timespan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `exam_assessments`
  ADD PRIMARY KEY (`exam_assessment_id`);
ALTER TABLE `exam_assessments`
  MODIFY `exam_assessment_id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `exam_assessments_data`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_assessments_data` (
  `data_id` int(11) NOT NULL,
  `exam_assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `correct_answer` varchar(500) NOT NULL,
  `student_answer` varchar(500) DEFAULT NULL,
  `createdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `exam_assessments_data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `idx_exam_assessment_id` (`exam_assessment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_question_id` (`question_id`);
ALTER TABLE `exam_assessments_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `exam_questions`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_questions` (
  `question_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `question` text NOT NULL,
  `option1` text DEFAULT NULL,
  `option2` text DEFAULT NULL,
  `option3` text DEFAULT NULL,
  `option4` text DEFAULT NULL,
  `option5` text DEFAULT NULL,
  `feedback1` text DEFAULT NULL,
  `feedback2` text DEFAULT NULL,
  `feedback3` text DEFAULT NULL,
  `feedback4` text DEFAULT NULL,
  `feedback5` text DEFAULT NULL,
  `answer` text NOT NULL,
  `marks` double NOT NULL,
  `createdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_subject_id` (`subject_id`);
ALTER TABLE `exam_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `exam_results`
-- --------------------------------------------------------
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

ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_assessment_id` (`exam_assessment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_class_id` (`class_id`),
  ADD KEY `idx_grading_type` (`grading_type`),
  ADD KEY `idx_status` (`status`);
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Optional: basic FK integrity (commented to honor exact provided structures without FKs)
-- ALTER TABLE `exam_assessments_data`
--   ADD CONSTRAINT `fk_exam_assessments_data_assessment` FOREIGN KEY (`exam_assessment_id`) REFERENCES `exam_assessments` (`exam_assessment_id`) ON DELETE CASCADE;

-- ALTER TABLE `exam_results`
--   ADD CONSTRAINT `fk_exam_results_assessment` FOREIGN KEY (`exam_assessment_id`) REFERENCES `exam_assessments` (`exam_assessment_id`) ON DELETE CASCADE;

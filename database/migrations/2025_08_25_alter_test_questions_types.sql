-- Migration: Extend test_questions.question_type to support richer types
USE rinda_school;

-- Normalize existing data before changing ENUM
UPDATE test_questions SET question_type='objective_single' WHERE question_type='multiple_choice';

ALTER TABLE test_questions
  MODIFY COLUMN question_type ENUM('objective_single','objective_multiple','true_false','short_answer','essay','practical') NOT NULL;

-- Note: For objective_multiple questions, store multiple correct answers as JSON array in correct_answer.
-- For objective_single/true_false, store scalar string in correct_answer.

-- Migration: Add content_pages and test_answers
USE rinda_school;

CREATE TABLE IF NOT EXISTS content_pages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  content_html MEDIUMTEXT NOT NULL,
  class_id INT,
  subject_id INT,
  teacher_id INT NOT NULL,
  is_published BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
  FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS test_answers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  test_id INT NOT NULL,
  question_id INT NOT NULL,
  student_id INT NOT NULL,
  answer_text TEXT,
  is_auto_graded BOOLEAN DEFAULT FALSE,
  marks_awarded INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_test_student (test_id, student_id)
);

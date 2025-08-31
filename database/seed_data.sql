-- Rinda School Management System - Seed Data
-- Insert default data for testing and initial setup

USE rinda_school;

-- Insert default SuperAdmin user
-- Password: Admin123! (bcrypt hash)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, address, is_active, email_verified) VALUES
('admin', 'admin@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'superadmin', '+1 (555) 123-4567', '123 Rinda Street, Education City, USA', TRUE, TRUE);

-- Insert sample teachers
INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, address, is_active, email_verified) VALUES
('teacher1', 'john.smith@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'teacher', '+1 (555) 111-1111', '456 Teacher Ave, Education City, USA', TRUE, TRUE),
('teacher2', 'sarah.johnson@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', 'teacher', '+1 (555) 222-2222', '789 Educator St, Education City, USA', TRUE, TRUE),
('teacher3', 'michael.brown@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Brown', 'teacher', '+1 (555) 333-3333', '321 Learning Blvd, Education City, USA', TRUE, TRUE);

-- Insert sample students
INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, address, is_active, email_verified) VALUES
('student1', 'emma.wilson@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma', 'Wilson', 'student', '+1 (555) 444-4444', '654 Student Way, Education City, USA', TRUE, TRUE),
('student2', 'james.davis@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Davis', 'student', '+1 (555) 555-5555', '987 Scholar Rd, Education City, USA', TRUE, TRUE),
('student3', 'olivia.miller@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Olivia', 'Miller', 'student', '+1 (555) 666-6666', '147 Academy Ln, Education City, USA', TRUE, TRUE);

-- Insert sample supervisors
INSERT INTO users (username, email, password_hash, first_name, last_name, role, phone, address, is_active, email_verified) VALUES
('supervisor1', 'robert.garcia@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert', 'Garcia', 'supervisor', '+1 (555) 777-7777', '258 Supervisor Ct, Education City, USA', TRUE, TRUE),
('supervisor2', 'lisa.martinez@rinda.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Martinez', 'supervisor', '+1 (555) 888-8888', '369 Monitor Dr, Education City, USA', TRUE, TRUE);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES
('app_name', 'Rinda School Management System', 'string', TRUE),
('app_description', 'Comprehensive school management system for modern education', 'string', TRUE),
('app_address', '123 Rinda Street, Education City, USA', 'string', TRUE),
('app_phone', '+1 (555) 123-4567', 'string', TRUE),
('app_email', 'info@rinda.edu', 'string', TRUE),
('smtp_host', 'smtp.mailtrap.io', 'string', FALSE),
('smtp_port', '2525', 'number', FALSE),
('smtp_username', 'demo_user', 'string', FALSE),
('smtp_password', 'demo_pass', 'string', FALSE),
('smtp_encryption', 'tls', 'string', FALSE),
('system_language', 'en', 'string', FALSE),
('system_timezone', 'America/New_York', 'string', FALSE),
('system_currency', 'USD', 'string', FALSE),
('theme_primary_color', '#2563eb', 'string', TRUE),
('theme_secondary_color', '#1e40af', 'string', TRUE),
('theme_accent_color', '#3b82f6', 'string', TRUE),
('theme_mode', 'light', 'string', TRUE),
('email_verification_enabled', '1', 'boolean', FALSE),
('two_factor_enabled', '0', 'boolean', FALSE);

-- Insert sample classes
INSERT INTO classes (class_name, class_code, grade_level, academic_year, max_students, description) VALUES
('Grade 9A', 'G9A', 9, '2024-2025', 25, 'Advanced Mathematics and Science Class'),
('Grade 9B', 'G9B', 9, '2024-2025', 28, 'Standard Curriculum Class'),
('Grade 10A', 'G10A', 10, '2024-2025', 26, 'College Preparatory Class'),
('Grade 10B', 'G10B', 10, '2024-2025', 24, 'General Studies Class'),
('Grade 11A', 'G11A', 11, '2024-2025', 22, 'Advanced Placement Class'),
('Grade 11B', 'G11B', 11, '2024-2025', 25, 'Standard Curriculum Class');

-- Insert sample subjects
INSERT INTO subjects (subject_name, subject_code, description, credits) VALUES
('Mathematics', 'MATH101', 'Advanced Mathematics including Algebra, Geometry, and Calculus', 4),
('English Literature', 'ENG101', 'English Language Arts and Literature Studies', 3),
('Physics', 'PHY101', 'Fundamental Physics Concepts and Laboratory Work', 4),
('Chemistry', 'CHEM101', 'General Chemistry with Laboratory Experiments', 4),
('History', 'HIST101', 'World History and Social Studies', 3),
('Biology', 'BIO101', 'Life Sciences and Biological Concepts', 4),
('Computer Science', 'CS101', 'Programming and Computer Fundamentals', 3),
('Art', 'ART101', 'Creative Arts and Design Principles', 2);

-- Insert class-subject relationships
INSERT INTO class_subjects (class_id, subject_id) VALUES
(1, 1), (1, 3), (1, 5), (1, 7), -- Grade 9A: Math, Physics, History, CS
(1, 2), (1, 4), (1, 6), (1, 8), -- Grade 9A: English, Chemistry, Biology, Art
(2, 1), (2, 2), (2, 3), (2, 5), -- Grade 9B: Math, English, Physics, History
(2, 4), (2, 6), (2, 7), (2, 8), -- Grade 9B: Chemistry, Biology, CS, Art
(3, 1), (3, 2), (3, 3), (3, 4), -- Grade 10A: Math, English, Physics, Chemistry
(3, 5), (3, 6), (3, 7), (3, 8), -- Grade 10A: History, Biology, CS, Art
(4, 1), (4, 2), (4, 3), (4, 5), -- Grade 10B: Math, English, Physics, History
(4, 4), (4, 6), (4, 7), (4, 8), -- Grade 10B: Chemistry, Biology, CS, Art
(5, 1), (5, 2), (5, 3), (5, 4), -- Grade 11A: Math, English, Physics, Chemistry
(5, 5), (5, 6), (5, 7), (5, 8), -- Grade 11A: History, Biology, CS, Art
(6, 1), (6, 2), (6, 3), (6, 5), -- Grade 11B: Math, English, Physics, History
(6, 4), (6, 6), (6, 7), (6, 8); -- Grade 11B: Chemistry, Biology, CS, Art

-- Insert teacher assignments
INSERT INTO teacher_assignments (teacher_id, class_id, subject_id, academic_year) VALUES
(2, 1, 1, '2024-2025'), -- John Smith - Grade 9A Math
(2, 2, 1, '2024-2025'), -- John Smith - Grade 9B Math
(2, 3, 1, '2024-2025'), -- John Smith - Grade 10A Math
(3, 1, 2, '2024-2025'), -- Sarah Johnson - Grade 9A English
(3, 2, 2, '2024-2025'), -- Sarah Johnson - Grade 9B English
(3, 3, 2, '2024-2025'), -- Sarah Johnson - Grade 10A English
(4, 1, 3, '2024-2025'), -- Michael Brown - Grade 9A Physics
(4, 2, 3, '2024-2025'), -- Michael Brown - Grade 9B Physics
(4, 3, 3, '2024-2025'); -- Michael Brown - Grade 10A Physics

-- Insert student enrollments
INSERT INTO student_enrollments (student_id, class_id, academic_year, enrollment_date, status) VALUES
(5, 1, '2024-2025', '2024-09-01', 'active'), -- Emma Wilson - Grade 9A
(6, 1, '2024-2025', '2024-09-01', 'active'), -- James Davis - Grade 9A
(7, 2, '2024-2025', '2024-09-01', 'active'); -- Olivia Miller - Grade 9B

-- Insert supervisor assignments
INSERT INTO supervisor_assignments (supervisor_id, class_id, school_level, academic_year) VALUES
(8, 1, 'high', '2024-2025'), -- Robert Garcia - Grade 9A
(8, 2, 'high', '2024-2025'), -- Robert Garcia - Grade 9B
(9, 3, 'high', '2024-2025'), -- Lisa Martinez - Grade 10A
(9, 4, 'high', '2024-2025'); -- Lisa Martinez - Grade 10B

-- Insert sample timetable
INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room_number, academic_year) VALUES
(1, 1, 2, 'monday', '08:00:00', '09:30:00', 'Room 101', '2024-2025'),
(1, 2, 3, 'monday', '09:45:00', '11:15:00', 'Room 102', '2024-2025'),
(1, 3, 4, 'monday', '11:30:00', '13:00:00', 'Lab 201', '2024-2025'),
(1, 1, 2, 'tuesday', '08:00:00', '09:30:00', 'Room 101', '2024-2025'),
(1, 4, 4, 'tuesday', '09:45:00', '11:15:00', 'Lab 202', '2024-2025'),
(1, 2, 3, 'wednesday', '08:00:00', '09:30:00', 'Room 102', '2024-2025');

-- Insert sample learning resources
INSERT INTO learning_resources (title, description, resource_type, file_path, file_size, file_type, class_id, subject_id, uploaded_by, is_public) VALUES
('Algebra Fundamentals', 'Introduction to algebraic concepts and problem solving', 'document', '/uploads/documents/algebra_fundamentals.pdf', 2048576, 'pdf', 1, 1, 2, TRUE),
('Physics Lab Manual', 'Laboratory exercises for physics experiments', 'document', '/uploads/documents/physics_lab_manual.pdf', 1536000, 'pdf', 1, 3, 4, TRUE),
('English Grammar Rules', 'Comprehensive guide to English grammar', 'document', '/uploads/documents/english_grammar.pdf', 1024000, 'pdf', 1, 2, 3, TRUE);

-- Insert sample virtual classes
INSERT INTO virtual_classes (class_id, subject_id, teacher_id, title, description, meeting_link, meeting_code, scheduled_date, start_time, end_time, max_participants) VALUES
(1, 1, 2, 'Algebra Review Session', 'Weekly review of algebra concepts', 'https://meet.google.com/abc-defg-hij', 'ALG001', '2024-12-20', '14:00:00', '15:30:00', 25),
(1, 3, 4, 'Physics Virtual Lab', 'Virtual laboratory session for physics experiments', 'https://meet.google.com/xyz-uvw-rst', 'PHY001', '2024-12-21', '15:00:00', '16:30:00', 25);

-- Insert sample tests
INSERT INTO tests (title, description, class_id, subject_id, teacher_id, test_type, total_marks, duration_minutes, scheduled_date, start_time, end_time) VALUES
('Algebra Midterm', 'Midterm examination covering algebra fundamentals', 1, 1, 2, 'monthly', 100, 90, '2024-12-25', '09:00:00', '10:30:00'),
('Physics Quiz 1', 'Weekly quiz on physics concepts', 1, 3, 4, 'weekly', 50, 45, '2024-12-22', '14:00:00', '14:45:00');

-- Insert sample test questions
INSERT INTO test_questions (test_id, question_text, question_type, marks, options, correct_answer) VALUES
(1, 'What is the value of x in the equation 2x + 5 = 13?', 'multiple_choice', 10, '["3", "4", "5", "6"]', '4'),
(1, 'Solve the quadratic equation: x² - 4x + 4 = 0', 'short_answer', 20, NULL, 'x = 2'),
(2, 'What is the SI unit of force?', 'multiple_choice', 10, '["Newton", "Joule", "Watt", "Pascal"]', 'Newton'),
(2, 'State Newton\'s First Law of Motion', 'essay', 20, NULL, 'An object at rest stays at rest and an object in motion stays in motion unless acted upon by an external force');

-- Insert sample assignments
INSERT INTO assignments (title, description, class_id, subject_id, teacher_id, due_date, due_time, total_marks) VALUES
('Algebra Problem Set 1', 'Complete problems 1-20 in Chapter 2', 1, 1, 2, '2024-12-30', '23:59:00', 50),
('Physics Lab Report', 'Write a report on the pendulum experiment', 1, 3, 4, '2024-12-28', '23:59:00', 75);

-- Insert sample announcements
INSERT INTO announcements (title, content, author_id, target_audience, is_active) VALUES
('Welcome to New Academic Year', 'Welcome all students and teachers to the 2024-2025 academic year. We have exciting new programs and improvements planned.', 1, 'all', TRUE),
('Mathematics Competition', 'Annual mathematics competition will be held next month. All interested students should register with their math teachers.', 2, 'students', TRUE),
('Teacher Professional Development', 'Mandatory professional development session for all teachers this Friday at 3 PM in the auditorium.', 1, 'teachers', TRUE);

-- Insert default theme
INSERT INTO themes (theme_name, primary_color, secondary_color, accent_color, is_dark_mode, is_active) VALUES
('Default Blue', '#2563eb', '#1e40af', '#3b82f6', FALSE, TRUE),
('Dark Mode', '#1f2937', '#111827', '#3b82f6', TRUE, FALSE);

-- Insert sample system logs
INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES
(1, 'login', 'SuperAdmin logged in successfully', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'user_created', 'Created new teacher account: john.smith@rinda.edu', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'setting_updated', 'Updated system theme to blue', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); 
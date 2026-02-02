-- ============================================
-- DROP TABLES (for re-run safety)
-- ============================================
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS instructors;

-- ============================================
-- INSTRUCTORS TABLE
-- ============================================
CREATE TABLE instructors (
    instructor_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(100),
    bio TEXT,
    hire_date DATE DEFAULT (CURRENT_DATE),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- ============================================
-- STUDENTS TABLE
-- ============================================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- ============================================
-- COURSES TABLE
-- ============================================
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    instructor_id INT,
    category VARCHAR(50),
    level ENUM('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
    max_students INT DEFAULT 30,
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id)
        REFERENCES instructors(instructor_id)
        ON DELETE SET NULL,
    INDEX idx_code (course_code),
    INDEX idx_instructor (instructor_id),
    INDEX idx_active (is_active)
);

-- ============================================
-- ENROLLMENTS TABLE
-- ============================================
CREATE TABLE enrollments (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('Active', 'Completed', 'Dropped', 'Pending') DEFAULT 'Active',
    grade VARCHAR(5),
    enrolled_by_instructor_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE,
    FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by_instructor_id)
        REFERENCES instructors(instructor_id)
        ON DELETE SET NULL,
    UNIQUE KEY unique_enrollment (student_id, course_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
);

-- ============================================
-- SAMPLE DATA
-- Password = "password123"
-- ============================================

-- Instructors (IDs: 1, 2, 3)
INSERT INTO instructors 
(first_name, last_name, email, password, phone, department, bio)
VALUES
('Sanjay', 'Shrestha', 'sanjay.shrestha@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9841000001', 'Computer Science', 'Senior lecturer in software engineering'),

('Pratima', 'Karki', 'pratima.karki@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9841000002', 'Data Science', 'Researcher in data analytics and AI'),

('Ramesh', 'Adhikari', 'ramesh.adhikari@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9841000003', 'Web Development', 'Full-stack web development expert');

-- Students (IDs: 1–4)
INSERT INTO students 
(first_name, last_name, email, password, phone, date_of_birth, address)
VALUES
('Nima', 'Sherpa', 'nima.sherpa@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9800000001', '2004-02-10', 'Kathmandu, Nepal'),

('Anusha', 'Poudel', 'anusha.poudel@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9800000002', '2003-06-25', 'Pokhara, Nepal'),

('Suman', 'Gurung', 'suman.gurung@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9800000003', '2002-09-14', 'Lamjung, Nepal'),

('Bishal', 'Thapa', 'bishal.thapa@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '9800000004', '2001-12-01', 'Chitwan, Nepal');

-- Courses (FIXED instructor_id → 1,2,3 only)
INSERT INTO courses 
(course_code, course_name, description, credits, instructor_id, category, level, max_students, start_date, end_date)
VALUES
('CS105', 'Object-Oriented Programming',
 'Core OOP concepts using Java', 4, 1, 'Programming', 'Intermediate', 30, '2024-02-01', '2024-06-01'),

('DS205', 'Machine Learning Fundamentals',
 'Supervised and unsupervised learning techniques', 4, 2, 'Data Science', 'Advanced', 25, '2024-02-10', '2024-06-10'),

('WEB110', 'Web Technologies',
 'HTML, CSS, and JavaScript fundamentals', 3, 3, 'Web Development', 'Beginner', 35, '2024-02-15', '2024-06-15'),

('DB210', 'Database Systems',
 'Relational databases, SQL, and normalization', 4, 1, 'Database', 'Intermediate', 30, '2024-03-01', '2024-07-01');

-- Enrollments (all IDs valid)
INSERT INTO enrollments 
(student_id, course_id, status, enrolled_by_instructor_id)
VALUES
(1, 1, 'Active', 1),
(1, 3, 'Active', 2),
(2, 1, 'Active', 1),
(3, 3, 'Active', 2);

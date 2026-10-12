
-- =========================================
-- ONLINE COURSE REGISTRATION SYSTEM
-- MySQL / XAMPP
-- =========================================

DROP DATABASE IF EXISTS online_course_registration;

CREATE DATABASE online_course_registration
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE online_course_registration;

-- 1. DEPARTMENTS
CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. ACADEMIC TERMS
CREATE TABLE academic_terms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    registration_starts_at DATETIME NOT NULL,
    registration_ends_at DATETIME NOT NULL,
    credit_limit TINYINT UNSIGNED NOT NULL DEFAULT 24,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_term (academic_year, semester),
    INDEX idx_term_current
        (is_current, registration_starts_at, registration_ends_at)
) ENGINE=InnoDB;

-- 3. USERS
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','admin') NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. STUDENTS
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    register_number VARCHAR(40) NOT NULL UNIQUE,
    full_name VARCHAR(160) NOT NULL,
    department_id BIGINT UNSIGNED NOT NULL,
    semester TINYINT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_student_department
        FOREIGN KEY (department_id) REFERENCES departments(id),

    INDEX idx_student_department (department_id, semester)
) ENGINE=InnoDB;

-- 5. COURSES
CREATE TABLE courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    credits TINYINT UNSIGNED NOT NULL,
    faculty_name VARCHAR(160) NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    semester TINYINT UNSIGNED NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_course_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON DELETE SET NULL,

    INDEX idx_courses_browse
        (active, department_id, semester, title)
) ENGINE=InnoDB;

-- 6. COURSE PREREQUISITES
CREATE TABLE course_prerequisites (
    course_id BIGINT UNSIGNED NOT NULL,
    prerequisite_course_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (course_id, prerequisite_course_id),

    CONSTRAINT fk_prereq_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_prereq_required
        FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_prerequisite_not_self
        CHECK (course_id <> prerequisite_course_id)
) ENGINE=InnoDB;

-- 7. COURSE SCHEDULES
CREATE TABLE course_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL
        COMMENT '1=Monday, 7=Sunday',
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    room VARCHAR(80) NOT NULL DEFAULT '',

    CONSTRAINT fk_schedule_course
        FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_schedule_day
        CHECK (day_of_week BETWEEN 1 AND 7),

    CONSTRAINT chk_schedule_time
        CHECK (starts_at < ends_at),

    INDEX idx_schedule_day_time
        (day_of_week, starts_at, ends_at)
) ENGINE=InnoDB;

-- 8. ACADEMIC RECORDS
CREATE TABLE academic_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    term_id BIGINT UNSIGNED NOT NULL,
    grade VARCHAR(8) NOT NULL,
    completed_at DATE NOT NULL,

    UNIQUE KEY uq_academic_record
        (student_id, course_id, term_id),

    CONSTRAINT fk_record_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_record_course
        FOREIGN KEY (course_id) REFERENCES courses(id),

    CONSTRAINT fk_record_term
        FOREIGN KEY (term_id) REFERENCES academic_terms(id),

    INDEX idx_record_completion
        (student_id, course_id, grade)
) ENGINE=InnoDB;

-- 9. REGISTRATIONS
CREATE TABLE registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    term_id BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'pending','approved','rejected','cancelled'
    ) NOT NULL DEFAULT 'pending',
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at DATETIME NULL,
    decided_by BIGINT UNSIGNED NULL,
    decision_note VARCHAR(500) NULL,

    UNIQUE KEY uq_registration
        (student_id, course_id, term_id),

    CONSTRAINT fk_registration_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_registration_course
        FOREIGN KEY (course_id) REFERENCES courses(id),

    CONSTRAINT fk_registration_term
        FOREIGN KEY (term_id) REFERENCES academic_terms(id),

    CONSTRAINT fk_registration_admin
        FOREIGN KEY (decided_by) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_registration_course_status
        (course_id, term_id, status),

    INDEX idx_registration_student_term
        (student_id, term_id, status),

    INDEX idx_registration_requested (requested_at)
) ENGINE=InnoDB;

-- 10. NOTIFICATIONS
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message VARCHAR(500) NOT NULL,
    url VARCHAR(255) NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_notification_user_read
        (user_id, read_at, created_at)
) ENGINE=InnoDB;

-- 11. AUDIT LOGS
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_audit_entity
        (entity_type, entity_id, created_at),

    INDEX idx_audit_actor
        (actor_user_id, created_at)
) ENGINE=InnoDB;

-- =========================================
-- SAMPLE DATA
-- =========================================

-- DEPARTMENTS
INSERT INTO departments (code, name) VALUES
('CSE', 'Computer Science and Engineering'),
('ECE', 'Electronics and Communication Engineering'),
('ME', 'Mechanical Engineering');

-- CURRENT ACADEMIC TERM
INSERT INTO academic_terms (
    name, academic_year, semester,
    starts_on, ends_on,
    registration_starts_at, registration_ends_at,
    credit_limit, is_current
) VALUES (
    'Odd Semester 2026',
    '2026-2027',
    1,
    '2026-07-01',
    '2026-12-31',
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_ADD(NOW(), INTERVAL 90 DAY),
    24,
    1
);

-- COURSES
INSERT INTO courses (
    code, title, description, credits,
    faculty_name, department_id, semester, capacity
) VALUES
(
    'CS101',
    'Programming Fundamentals',
    'Problem solving and programming with a modern language.',
    4, 'Dr. A. Kumar', 1, 1, 60
),
(
    'CS102',
    'Discrete Mathematics',
    'Logic, sets, relations, graphs, and combinatorics.',
    3, 'Prof. S. Rao', 1, 1, 45
),
(
    'EC101',
    'Digital Systems',
    'Number systems, combinational logic, and sequential circuits.',
    4, 'Dr. M. Iyer', 2, 1, 40
);

-- COURSE TIMETABLE
INSERT INTO course_schedules (
    course_id, day_of_week, starts_at, ends_at, room
) VALUES
(1, 1, '09:00:00', '10:30:00', 'Block A 201'),
(1, 3, '09:00:00', '10:30:00', 'Block A 201'),
(2, 2, '11:00:00', '12:30:00', 'Block B 105'),
(2, 4, '11:00:00', '12:30:00', 'Block B 105'),
(3, 1, '13:00:00', '14:30:00', 'Block C 301'),
(3, 5, '13:00:00', '14:30:00', 'Block C 301');

-- ADDITIONAL DEMO COURSES
INSERT INTO courses (
    code, title, description, credits, faculty_name,
    department_id, semester, capacity
)
SELECT demo.code, demo.title, demo.description, demo.credits, demo.faculty_name,
       d.id, demo.semester, demo.capacity
FROM (
    SELECT 'CS103' code, 'Data Structures and Algorithms' title,
           'Core data structures, algorithm design, and complexity analysis.' description,
           4 credits, 'Dr. R. Nair' faculty_name, 'CSE' department_code, 1 semester, 50 capacity
    UNION ALL SELECT 'CS104', 'Database Systems',
           'Relational database design, SQL, normalization, and transactions.',
           3, 'Prof. P. Shah', 'CSE', 1, 45
    UNION ALL SELECT 'CS105', 'Web Development Fundamentals',
           'Build accessible web pages with HTML, CSS, JavaScript, and server concepts.',
           3, 'Ms. K. Thomas', 'CSE', 1, 40
    UNION ALL SELECT 'EC102', 'Circuit Analysis',
           'Fundamental electrical circuit laws, network analysis, and applications.',
           4, 'Dr. V. Menon', 'ECE', 1, 45
    UNION ALL SELECT 'EC103', 'Electronic Devices',
           'Semiconductor fundamentals and the operation of electronic devices.',
           3, 'Prof. N. Das', 'ECE', 1, 40
    UNION ALL SELECT 'ME101', 'Engineering Mechanics',
           'Statics, dynamics, force systems, and engineering problem solving.',
           4, 'Dr. S. Kulkarni', 'ME', 1, 50
    UNION ALL SELECT 'ME102', 'Manufacturing Processes',
           'An introduction to forming, machining, casting, and production methods.',
           3, 'Prof. A. George', 'ME', 1, 40
    UNION ALL SELECT 'MA101', 'Applied Mathematics I',
           'Calculus, linear algebra, and mathematical methods for engineering.',
           4, 'Dr. L. Iyer', NULL, 1, 80
    UNION ALL SELECT 'HS101', 'Technical Communication',
           'Written, oral, and visual communication for academic and technical work.',
           2, 'Ms. R. Joseph', NULL, 1, 80
    UNION ALL SELECT 'CS201', 'Object-Oriented Programming',
           'Classes, objects, inheritance, polymorphism, and object-oriented design.',
           4, 'Dr. A. Kumar', 'CSE', 2, 45
    UNION ALL SELECT 'EC201', 'Microprocessors',
           'Processor architecture, assembly concepts, interfacing, and applications.',
           4, 'Dr. M. Iyer', 'ECE', 2, 35
    UNION ALL SELECT 'ME201', 'Thermodynamics',
           'Properties of pure substances, energy, and the first and second laws.',
           4, 'Dr. S. Kulkarni', 'ME', 2, 40
    UNION ALL SELECT 'CS401', 'Operating Systems',
           'Processes, concurrency, memory management, and file systems.',
           4, 'Dr. P. Sharma', 'CSE', 4, 45
    UNION ALL SELECT 'CS402', 'Software Engineering',
           'Requirements, design, testing, and software project practices.',
           3, 'Prof. N. Verma', 'CSE', 4, 45
    UNION ALL SELECT 'CS403', 'Computer Networks',
           'Network architectures, protocols, routing, and transport services.',
           4, 'Dr. K. Mehta', 'CSE', 4, 45
    UNION ALL SELECT 'CS404', 'Theory of Computation',
           'Automata, formal languages, computability, and complexity.',
           3, 'Prof. V. Rao', 'CSE', 4, 40
) AS demo
LEFT JOIN departments d ON d.code = demo.department_code
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    credits = VALUES(credits),
    faculty_name = VALUES(faculty_name),
    department_id = VALUES(department_id),
    semester = VALUES(semester),
    capacity = VALUES(capacity);

DELETE FROM course_schedules
WHERE course_id IN (
    SELECT id FROM courses
    WHERE code IN ('CS103','CS104','CS105','EC102','EC103','ME101','ME102',
                   'MA101','HS101','CS201','EC201','ME201','CS401','CS402','CS403','CS404')
);

INSERT INTO course_schedules (course_id, day_of_week, starts_at, ends_at, room)
SELECT c.id, meeting.day_of_week, meeting.starts_at, meeting.ends_at, meeting.room
FROM (
    SELECT 'CS103' code, 1 day_of_week, '11:00:00' starts_at, '12:30:00' ends_at, 'Block A 202' room
    UNION ALL SELECT 'CS103', 3, '11:00:00', '12:30:00', 'Block A 202'
    UNION ALL SELECT 'CS104', 2, '09:00:00', '10:30:00', 'Block A 203'
    UNION ALL SELECT 'CS104', 4, '09:00:00', '10:30:00', 'Block A 203'
    UNION ALL SELECT 'CS105', 2, '14:30:00', '16:00:00', 'Lab A 101'
    UNION ALL SELECT 'CS105', 4, '14:30:00', '16:00:00', 'Lab A 101'
    UNION ALL SELECT 'EC102', 1, '09:00:00', '10:30:00', 'Block C 302'
    UNION ALL SELECT 'EC102', 3, '09:00:00', '10:30:00', 'Block C 302'
    UNION ALL SELECT 'EC103', 2, '11:00:00', '12:30:00', 'Block C 303'
    UNION ALL SELECT 'EC103', 4, '11:00:00', '12:30:00', 'Block C 303'
    UNION ALL SELECT 'ME101', 1, '11:00:00', '12:30:00', 'Block D 101'
    UNION ALL SELECT 'ME101', 3, '11:00:00', '12:30:00', 'Block D 101'
    UNION ALL SELECT 'ME102', 2, '09:00:00', '10:30:00', 'Workshop D 1'
    UNION ALL SELECT 'ME102', 4, '09:00:00', '10:30:00', 'Workshop D 1'
    UNION ALL SELECT 'MA101', 1, '14:30:00', '16:00:00', 'Block B 201'
    UNION ALL SELECT 'MA101', 3, '14:30:00', '16:00:00', 'Block B 201'
    UNION ALL SELECT 'HS101', 5, '11:00:00', '12:30:00', 'Block B 202'
    UNION ALL SELECT 'CS201', 1, '09:00:00', '10:30:00', 'Block A 204'
    UNION ALL SELECT 'CS201', 3, '09:00:00', '10:30:00', 'Block A 204'
    UNION ALL SELECT 'EC201', 2, '14:30:00', '16:00:00', 'Lab C 201'
    UNION ALL SELECT 'EC201', 4, '14:30:00', '16:00:00', 'Lab C 201'
    UNION ALL SELECT 'ME201', 2, '13:00:00', '14:30:00', 'Block D 102'
    UNION ALL SELECT 'ME201', 4, '13:00:00', '14:30:00', 'Block D 102'
    UNION ALL SELECT 'CS401', 1, '09:00:00', '10:30:00', 'Block A 301'
    UNION ALL SELECT 'CS401', 3, '09:00:00', '10:30:00', 'Block A 301'
    UNION ALL SELECT 'CS402', 2, '09:00:00', '10:30:00', 'Block A 302'
    UNION ALL SELECT 'CS402', 4, '09:00:00', '10:30:00', 'Block A 302'
    UNION ALL SELECT 'CS403', 1, '11:00:00', '12:30:00', 'Block A 303'
    UNION ALL SELECT 'CS403', 3, '11:00:00', '12:30:00', 'Block A 303'
    UNION ALL SELECT 'CS404', 2, '11:00:00', '12:30:00', 'Block A 304'
    UNION ALL SELECT 'CS404', 4, '11:00:00', '12:30:00', 'Block A 304'
) AS meeting
JOIN courses c ON c.code = meeting.code;

DELETE cp
FROM course_prerequisites cp
JOIN courses c ON c.id = cp.course_id
WHERE c.code IN ('CS201','EC201','ME201');

INSERT INTO course_prerequisites (course_id, prerequisite_course_id)
SELECT course.id, prerequisite.id
FROM (
    SELECT 'CS201' course_code, 'CS101' prerequisite_code
    UNION ALL SELECT 'EC201', 'EC101'
    UNION ALL SELECT 'ME201', 'ME101'
) AS relation
JOIN courses course ON course.code = relation.course_code
JOIN courses prerequisite ON prerequisite.code = relation.prerequisite_code;

-- VERIFY TABLES
SHOW TABLES;

-- VERIFY SAMPLE COURSES
SELECT * FROM courses;

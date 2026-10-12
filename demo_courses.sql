-- Add or refresh the sample catalog without resetting student or administrator data.
USE online_course_registration;

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

SELECT code, title, semester FROM courses ORDER BY department_id, semester, code;

USE `naevis_inter_high_db`;

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `password` varchar(255) DEFAULT 'student123';

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `address` varchar(255) DEFAULT '';

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `guardian` varchar(255) DEFAULT '';

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `contact_no` varchar(50) DEFAULT '';

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `email` varchar(255) DEFAULT '';

ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `photo` varchar(255) DEFAULT '';

UPDATE `students`
SET
    password = 'student123'
WHERE
    password IS NULL
    OR password = '';

CREATE TABLE
    IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        audience ENUM (
            'All',
            'JHS',
            'SHS',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12'
        ) DEFAULT 'All',
        posted_by VARCHAR(255) DEFAULT 'Admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn BIGINT (12) NOT NULL,
        school_date DATE NOT NULL,
        status ENUM ('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
        remarks VARCHAR(255) DEFAULT '',
        UNIQUE KEY uniq_attendance (lrn, school_date)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subject VARCHAR(120) NOT NULL,
        description TEXT,
        due_date DATE NOT NULL,
        grade_level INT (2) DEFAULT NULL,
        section VARCHAR(30) DEFAULT '',
        strand VARCHAR(50) DEFAULT '',
        status ENUM ('Pending', 'Submitted', 'Checked') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_title VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        event_type VARCHAR(80) DEFAULT 'School Event',
        description TEXT
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn BIGINT (12) DEFAULT NULL,
        message VARCHAR(255) NOT NULL,
        link VARCHAR(255) DEFAULT '',
        is_read TINYINT (1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE
    IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        role VARCHAR(50) NOT NULL,
        action VARCHAR(120) NOT NULL,
        target_lrn BIGINT (12) DEFAULT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO
    announcements (title, body, audience, posted_by)
SELECT
    'Quarterly Examination Week',
    'Please check your subject requirements and review your dashboard regularly for grade updates.',
    'All',
    'Admin'
WHERE
    NOT EXISTS (
        SELECT
            1
        FROM
            announcements
        LIMIT
            1
    );

INSERT INTO
    calendar_events (event_title, event_date, event_type, description)
SELECT
    'First Quarter Examination',
    DATE_ADD (CURDATE (), INTERVAL 7 DAY),
    'Exam',
    'Prepare for the first quarterly assessment.'
WHERE
    NOT EXISTS (
        SELECT
            1
        FROM
            calendar_events
        LIMIT
            1
    );

INSERT INTO
    activities (
        title,
        subject,
        description,
        due_date,
        grade_level,
        section,
        strand
    )
SELECT
    'Mathematics Problem Set',
    'Mathematics',
    'Complete the assigned practice problems.',
    DATE_ADD (CURDATE (), INTERVAL 3 DAY),
    7,
    '',
    ''
WHERE
    NOT EXISTS (
        SELECT
            1
        FROM
            activities
        LIMIT
            1
    );
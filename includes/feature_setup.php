<?php
function ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
	$tableEsc = $conn->real_escape_string($table);
	$colEsc = $conn->real_escape_string($column);
	$check = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$colEsc}'");
	if ($check && $check->num_rows === 0) {
		@$conn->query("ALTER TABLE `{$tableEsc}` ADD COLUMN `{$colEsc}` {$definition}");
	}
}

function ensure_feature_tables(mysqli $conn): void
{
	ensure_column($conn, 'students', 'password', "varchar(255) DEFAULT 'student123'");
	ensure_column($conn, 'students', 'address', "varchar(255) DEFAULT ''");
	ensure_column($conn, 'students', 'guardian', "varchar(255) DEFAULT ''");
	ensure_column($conn, 'students', 'contact_no', "varchar(50) DEFAULT ''");
	ensure_column($conn, 'students', 'email', "varchar(255) DEFAULT ''");
	ensure_column($conn, 'students', 'photo', "varchar(255) DEFAULT ''");

	$conn->query("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        audience ENUM('All','JHS','SHS','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') DEFAULT 'All',
        posted_by VARCHAR(255) DEFAULT 'Admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$conn->query("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn BIGINT(12) NOT NULL,
        school_date DATE NOT NULL,
        status ENUM('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Present',
        remarks VARCHAR(255) DEFAULT '',
        UNIQUE KEY uniq_attendance (lrn, school_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$conn->query("CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subject VARCHAR(120) NOT NULL,
        description TEXT,
        due_date DATE NOT NULL,
        grade_level INT(2) DEFAULT NULL,
        section VARCHAR(30) DEFAULT '',
        strand VARCHAR(50) DEFAULT '',
        status ENUM('Pending','Submitted','Checked') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$conn->query("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_title VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        event_type VARCHAR(80) DEFAULT 'School Event',
        description TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn BIGINT(12) DEFAULT NULL,
        message VARCHAR(255) NOT NULL,
        link VARCHAR(255) DEFAULT '',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        role VARCHAR(50) NOT NULL,
        action VARCHAR(120) NOT NULL,
        target_lrn BIGINT(12) DEFAULT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$count = $conn->query("SELECT COUNT(*) AS total FROM announcements")->fetch_assoc()['total'] ?? 0;
	if ((int)$count === 0) {
		$conn->query("INSERT INTO announcements (title, body, audience, posted_by) VALUES
            ('Quarterly Examination Week', 'Please check your subject requirements and review your dashboard regularly for grade updates.', 'All', 'Admin'),
            ('Recognition Day Preparation', 'Honor list candidates will be validated after final grade encoding.', 'All', 'Admin'),
            ('Senior High Career Guidance', 'Grade 11 and 12 students are invited to join the strand-based career orientation.', 'SHS', 'Admin')");
	}

	$count = $conn->query("SELECT COUNT(*) AS total FROM calendar_events")->fetch_assoc()['total'] ?? 0;
	if ((int)$count === 0) {
		$conn->query("INSERT INTO calendar_events (event_title, event_date, event_type, description) VALUES
            ('First Quarter Examination', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Exam', 'Prepare for the first quarterly assessment.'),
            ('School Intramurals', DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'Event', 'Sports and academic activities for all grade levels.'),
            ('Recognition Day', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'Recognition', 'Awarding of honor students and special awards.')");
	}

	$count = $conn->query("SELECT COUNT(*) AS total FROM activities")->fetch_assoc()['total'] ?? 0;
	if ((int)$count === 0) {
		$conn->query("INSERT INTO activities (title, subject, description, due_date, grade_level, section, strand) VALUES
            ('Mathematics Problem Set', 'Mathematics', 'Complete the assigned practice problems.', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 7, '', ''),
            ('Science Reflection Journal', 'Science', 'Write a short reflection about the current science lesson.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 7, '', ''),
            ('Research Draft Submission', 'Research', 'Submit your initial research topic and outline.', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 11, '', 'STEM'),
            ('Oral Communication Performance', 'Oral Communication', 'Prepare a 3-minute speech presentation.', DATE_ADD(CURDATE(), INTERVAL 9 DAY), 11, '', '')");
	}

	$studentResult = $conn->query("SELECT lrn FROM students LIMIT 80");
	if ($studentResult) {
		while ($s = $studentResult->fetch_assoc()) {
			$lrn = (int)$s['lrn'];
			for ($i = 1; $i <= 20; $i++) {
				$status = ($i % 17 === 0) ? 'Absent' : (($i % 11 === 0) ? 'Late' : 'Present');
				$dateExpr = "DATE_SUB(CURDATE(), INTERVAL " . (20 - $i) . " DAY)";
				$conn->query("INSERT IGNORE INTO attendance (lrn, school_date, status) VALUES ({$lrn}, {$dateExpr}, '{$status}')");
			}
		}
	}
}

-- Import this file in phpMyAdmin. Existing records are not removed.
-- Select your separate hosting database before importing.


CREATE TABLE IF NOT EXISTS applicants (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    birth_date DATE NOT NULL,
    address VARCHAR(250) NOT NULL,
    program ENUM('TUPAD', '4Ps') NOT NULL,
    method ENUM('none', 'sms', 'email') NOT NULL DEFAULT 'none',
    contact VARCHAR(254) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Received',
    qualification_answers TEXT NOT NULL,
    submission_token CHAR(32) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_applicants_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS application_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id VARCHAR(32) NOT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_applicant FOREIGN KEY (applicant_id)
      REFERENCES applicants(id) ON DELETE CASCADE,
    INDEX idx_history_applicant (applicant_id, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS application_details (
    applicant_id VARCHAR(32) PRIMARY KEY,
    details MEDIUMTEXT NOT NULL,
    CONSTRAINT fk_details_applicant FOREIGN KEY (applicant_id)
      REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tupad_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    applicant_id VARCHAR(32) NOT NULL,
    work_date DATE NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_attendance_per_day (applicant_id, work_date),
    INDEX attendance_date (work_date),
    CONSTRAINT fk_attendance_applicant FOREIGN KEY (applicant_id)
      REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS registrant_profiles (
    applicant_id VARCHAR(32) PRIMARY KEY,
    worker_id VARCHAR(64) NULL UNIQUE,
    work_start DATE NULL,
    work_end DATE NULL,
    work_time VARCHAR(100) NOT NULL DEFAULT '',
    work_venue VARCHAR(250) NOT NULL DEFAULT '',
    work_notes VARCHAR(1000) NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_applicant FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS public_updates (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('event','announcement') NOT NULL,
 program ENUM('All','TUPAD','4Ps') NOT NULL DEFAULT 'All',
 title VARCHAR(160) NOT NULL,
 body TEXT NOT NULL,
 event_date DATE NULL,
 event_time VARCHAR(100) NOT NULL DEFAULT '',
 venue VARCHAR(250) NOT NULL DEFAULT '',
 published TINYINT(1) NOT NULL DEFAULT 0,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_roster (
 work_date DATE NOT NULL,
 applicant_id VARCHAR(32) NOT NULL,
 added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (work_date, applicant_id),
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Account upgrade: shared applicants holds identity/status; program-specific tables hold intake details.
CREATE TABLE IF NOT EXISTS tupad_applicants (
 applicant_id VARCHAR(32) PRIMARY KEY, intake_details MEDIUMTEXT NOT NULL,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS fourps_applicants (
 applicant_id VARCHAR(32) PRIMARY KEY, intake_details MEDIUMTEXT NOT NULL,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS tupad_members (
 applicant_id VARCHAR(32) PRIMARY KEY, admitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (applicant_id) REFERENCES tupad_applicants(applicant_id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS fourps_members (
 applicant_id VARCHAR(32) PRIMARY KEY, admitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (applicant_id) REFERENCES fourps_applicants(applicant_id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS applicant_accounts (
 applicant_id VARCHAR(32) PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, email VARCHAR(254) NOT NULL,
 must_change_password TINYINT(1) NOT NULL DEFAULT 1, auth_version INT NOT NULL DEFAULT 1,
 mail_status VARCHAR(30) NOT NULL DEFAULT 'not_sent',
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS admins (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
INSERT IGNORE INTO tupad_applicants (applicant_id,intake_details)
 SELECT a.id,COALESCE(d.details,'{}') FROM applicants a LEFT JOIN application_details d ON d.applicant_id=a.id WHERE a.program='TUPAD';
INSERT IGNORE INTO fourps_applicants (applicant_id,intake_details)
 SELECT a.id,COALESCE(d.details,'{}') FROM applicants a LEFT JOIN application_details d ON d.applicant_id=a.id WHERE a.program='4Ps';
CREATE TABLE IF NOT EXISTS applicant_documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 applicant_id VARCHAR(32) NOT NULL,
 category VARCHAR(32) NOT NULL,
 original_name VARCHAR(200) NOT NULL,
 storage_name CHAR(64) NOT NULL UNIQUE,
 mime_type VARCHAR(80) NOT NULL,
 file_size INT UNSIGNED NOT NULL,
 uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
 INDEX idx_documents_applicant (applicant_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS resident_registry (
 resident_id VARCHAR(64) PRIMARY KEY,
 household_id VARCHAR(64) NOT NULL,
 first_name VARCHAR(100) NOT NULL,
 middle_name VARCHAR(100) NOT NULL DEFAULT '',
 last_name VARCHAR(100) NOT NULL,
 birth_date DATE NOT NULL,
 barangay VARCHAR(100) NOT NULL,
 city VARCHAR(100) NOT NULL,
 province VARCHAR(100) NOT NULL,
 details MEDIUMTEXT NOT NULL,
 is_sample TINYINT(1) NOT NULL DEFAULT 0,
 imported_by VARCHAR(80) NOT NULL,
 imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_resident_name(last_name, first_name)
) ENGINE=InnoDB;

-- Additive upgrade. Back up the database first; safe to import again.
CREATE TABLE IF NOT EXISTS applicant_identity (
 applicant_id VARCHAR(32) PRIMARY KEY,
 first_name VARCHAR(60) NOT NULL, middle_name VARCHAR(60) NOT NULL DEFAULT '', last_name VARCHAR(60) NOT NULL,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS residency_reviews (
 applicant_id VARCHAR(32) PRIMARY KEY, resident_id VARCHAR(64) NULL,
 decision VARCHAR(30) NOT NULL DEFAULT 'Needs clarification', reason TEXT NOT NULL,
 reviewed_by VARCHAR(80) NOT NULL, reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
 FOREIGN KEY (resident_id) REFERENCES resident_registry(resident_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS document_reviews (
 document_id BIGINT UNSIGNED PRIMARY KEY, decision VARCHAR(30) NOT NULL DEFAULT 'Under review',
 instructions TEXT NOT NULL, reviewed_by VARCHAR(80) NOT NULL,
 reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (document_id) REFERENCES applicant_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS duplicate_reviews (
 applicant_id VARCHAR(32) NOT NULL, other_id VARCHAR(32) NOT NULL,
 decision VARCHAR(40) NOT NULL, reason TEXT NOT NULL, reviewed_by VARCHAR(80) NOT NULL,
 reviewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(applicant_id, other_id),
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
 FOREIGN KEY (other_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS workflow_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, applicant_id VARCHAR(32) NULL,
 action VARCHAR(80) NOT NULL, details TEXT NOT NULL, staff VARCHAR(80) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(applicant_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notification_log (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, applicant_id VARCHAR(32) NOT NULL,
 subject VARCHAR(200) NOT NULL, message TEXT NOT NULL, delivery VARCHAR(30) NOT NULL DEFAULT 'pending',
 attempts INT NOT NULL DEFAULT 0, attempted_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE, INDEX(delivery)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS work_activities (
 work_date DATE PRIMARY KEY, work_time VARCHAR(100) NOT NULL, venue VARCHAR(250) NOT NULL,
 instructions VARCHAR(1000) NOT NULL, updated_by VARCHAR(80) NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

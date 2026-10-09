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

CREATE TABLE IF NOT EXISTS security_limits (
 bucket_key CHAR(64) NOT NULL, bucket_number BIGINT NOT NULL, hits INT UNSIGNED NOT NULL,
 expires_at BIGINT NOT NULL, PRIMARY KEY(bucket_key,bucket_number), INDEX(expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS account_recovery_expiry (
 applicant_id VARCHAR(32) PRIMARY KEY, expires_at DATETIME NOT NULL,
 FOREIGN KEY(applicant_id) REFERENCES applicants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS staff_security (
 username VARCHAR(80) PRIMARY KEY, email VARCHAR(254) NOT NULL, auth_version INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

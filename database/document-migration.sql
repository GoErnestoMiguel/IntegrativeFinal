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

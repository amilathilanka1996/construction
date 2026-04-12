CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_companies_name (name)
);

SET @has_users_company := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'construction_manager' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'company_id');
SET @sql := IF(@has_users_company = 0, 'ALTER TABLE users ADD COLUMN company_id INT UNSIGNED NULL AFTER role', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_projects_company := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'construction_manager' AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'company_id');
SET @sql := IF(@has_projects_company = 0, 'ALTER TABLE projects ADD COLUMN company_id INT UNSIGNED NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_tenders_company := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'construction_manager' AND TABLE_NAME = 'tenders' AND COLUMN_NAME = 'company_id');
SET @sql := IF(@has_tenders_company = 0, 'ALTER TABLE tenders ADD COLUMN company_id INT UNSIGNED NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS user_companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  company_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_companies_user_company (user_id, company_id),
  KEY idx_user_companies_user_id (user_id),
  KEY idx_user_companies_company_id (company_id),
  CONSTRAINT fk_user_companies_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_user_companies_company_id FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
);

INSERT IGNORE INTO user_companies (user_id, company_id)
SELECT id, company_id
FROM users
WHERE company_id IS NOT NULL;

UPDATE projects p
INNER JOIN users u ON u.id = p.user_id
SET p.company_id = u.company_id
WHERE p.company_id IS NULL AND u.company_id IS NOT NULL;

UPDATE tenders t
INNER JOIN users u ON u.id = t.user_id
SET t.company_id = u.company_id
WHERE t.company_id IS NULL AND u.company_id IS NOT NULL;

SELECT id, name FROM companies;
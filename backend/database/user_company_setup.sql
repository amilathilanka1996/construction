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

SELECT COUNT(*) AS membership_count FROM user_companies;
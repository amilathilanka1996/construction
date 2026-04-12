# Construction Management Project

This workspace contains:

- `backend`: Yii2 API backend
- `frontend`: Vue 3 + Vite frontend

## Main features

- User signup and login
- First registered account becomes `superadmin`
- `superadmin` can see all project, income, expense and user details
- Normal users only see their own projects and finance entries
- Project create form with:
  - project name
  - project description
  - created date (automatic)
  - start date
  - closing date
  - estimate amount
  - valuation amount
  - status
- Closing date, start date, estimate amount, and valuation amount can be changed from the project detail page
- Project status flow:
  - `running`
  - `retention`
  - `closed`
- Project expense entry with quantity, unit price and auto total calculation
- Project income entry with details
- Dashboard cards for totals and current project counts

## Database

1. Create MySQL database and tables using:
   - `backend/database/schema.sql`
2. If you already created the tables before these updates, run this SQL:

```sql
ALTER TABLE project_expenses
  ADD COLUMN quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER reference_no,
  ADD COLUMN unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER quantity;

ALTER TABLE projects
  ADD COLUMN start_date DATE NOT NULL AFTER created_date,
  ADD COLUMN final_date DATE NOT NULL AFTER start_date,
  ADD COLUMN estimate_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER final_date,
  ADD COLUMN valuation_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER estimate_amount;
```

3. Backend DB config file:
   - `backend/config/db.php`

Default local settings are:

- database: `construction_manager`
- username: `root`
- password: empty

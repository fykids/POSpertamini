-- Cloudflare D1: jalankan `wrangler d1 execute <DB_NAME> --file migrations/0002_financial_management.sql`
ALTER TABLE products ADD COLUMN cost_per_liter REAL NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'cashier' CHECK (role IN ('admin','cashier'));
CREATE UNIQUE INDEX IF NOT EXISTS users_name_unique ON users(name);
CREATE TABLE IF NOT EXISTS expenses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  category TEXT NOT NULL,
  amount REAL NOT NULL CHECK(amount > 0),
  note TEXT,
  date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS expenses_date_index ON expenses(date);

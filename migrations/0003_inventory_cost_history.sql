-- Jalankan setelah migrations/0002_financial_management.sql.
ALTER TABLE products ADD COLUMN buying_price_per_liter REAL NOT NULL DEFAULT 0;
UPDATE products SET buying_price_per_liter = COALESCE(cost_per_liter, 0);
ALTER TABLE stock_refills ADD COLUMN total_cost REAL NOT NULL DEFAULT 0;
UPDATE stock_refills SET total_cost = liter_added * buying_price_per_liter;

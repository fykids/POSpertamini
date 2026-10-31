INSERT INTO users (name, password, created_at, updated_at) 
VALUES ('kasir', '$2y$12$K.zG8q7pLHQjV1J6x1iM1eE4XgH5vY8W8u9l0m1n2o3p4q5r6s7t8', datetime('now'), datetime('now'));

INSERT INTO products (name, price_per_liter, current_stock, capacity, created_at, updated_at) 
VALUES 
('Pertalite', 10000, 500, 1000, datetime('now'), datetime('now')),
('Pertamax', 13000, 300, 1000, datetime('now'), datetime('now'));
ALTER TABLE departments ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active';
ALTER TABLE positions ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active';
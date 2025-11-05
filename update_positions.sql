ALTER TABLE positions ADD COLUMN IF NOT EXISTS base_salary decimal(12,2) NOT NULL DEFAULT 0.00;

UPDATE positions SET base_salary = 
    CASE name
        WHEN 'HR Manager' THEN 50000.00
        WHEN 'IT Manager' THEN 60000.00
        WHEN 'Software Developer' THEN 45000.00
        WHEN 'Accountant' THEN 40000.00
        WHEN 'Operations Manager' THEN 55000.00
        ELSE 0.00
    END;

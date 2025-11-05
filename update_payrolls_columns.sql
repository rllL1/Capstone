-- Update table structure
ALTER TABLE payrolls
    MODIFY COLUMN net_pay DECIMAL(10,2),
    MODIFY COLUMN pay_date DATE,
    ADD COLUMN hours_worked DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN absent_hours DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN late_minutes DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN overtime_hours DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN gross_pay DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN deductions DECIMAL(10,2) DEFAULT 0.00;

-- Add performance indexes (if they don't exist)
CREATE INDEX IF NOT EXISTS idx_emp_date ON payrolls (emp_id, pay_date);
CREATE INDEX IF NOT EXISTS idx_pay_date ON payrolls (pay_date);
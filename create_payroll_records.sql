-- Create payroll_records table if it doesn't exist
CREATE TABLE IF NOT EXISTS payroll_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    days_worked DECIMAL(5,2) NOT NULL,
    hours_per_day INT DEFAULT 8,
    total_hours DECIMAL(7,2) NOT NULL,
    absent_hours DECIMAL(5,2) DEFAULT 0,
    actual_hours DECIMAL(7,2) NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    late_minutes INT DEFAULT 0,
    tardiness_deduction DECIMAL(10,2) DEFAULT 0,
    gross_pay DECIMAL(12,2) NOT NULL,
    tax_deduction DECIMAL(10,2) NOT NULL,
    sss_deduction DECIMAL(10,2) NOT NULL,
    philhealth_deduction DECIMAL(10,2) NOT NULL,
    pagibig_deduction DECIMAL(10,2) NOT NULL,
    total_deductions DECIMAL(12,2) NOT NULL,
    net_pay DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    INDEX idx_employee_id (employee_id),
    INDEX idx_pay_period (pay_period_start, pay_period_end),
    INDEX idx_status (status)
);

-- Create trigger to update created_at on record update
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_payroll_records_timestamp
BEFORE UPDATE ON payroll_records
FOR EACH ROW
BEGIN
    SET NEW.created_at = CURRENT_TIMESTAMP;
END;//
DELIMITER ;
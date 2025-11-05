-- Add new columns for tracking actual hours and rates
ALTER TABLE `payrolls`
    -- Add monthly salary base and actual working hours tracking
    ADD COLUMN `monthly_salary` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Base monthly salary' AFTER basic_pay,
    ADD COLUMN `standard_monthly_hours` INT NOT NULL DEFAULT 176 COMMENT 'Standard monthly hours (22 days * 8 hours)',
    ADD COLUMN `actual_days_worked` INT NOT NULL DEFAULT 0 COMMENT 'Actual number of days worked',
    ADD COLUMN `actual_hours_worked` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Total actual hours worked',
    
    -- Earnings breakdown
    ADD COLUMN `regular_hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Regular hourly rate based on monthly salary',
    ADD COLUMN `overtime_hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Overtime hourly rate (125% of regular rate)',
    ADD COLUMN `regular_hours_pay` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Pay for regular hours worked',
    ADD COLUMN `overtime_hours_pay` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Pay for overtime hours',
    
    -- Pay period tracking
    ADD COLUMN `pay_period_start` DATE NOT NULL AFTER pay_date,
    ADD COLUMN `pay_period_end` DATE NOT NULL AFTER pay_period_start;

-- Create a trigger to automatically calculate rates and earnings
DELIMITER //

CREATE TRIGGER before_payroll_insert 
BEFORE INSERT ON payrolls
FOR EACH ROW 
BEGIN
    -- Set monthly salary if not provided
    IF NEW.monthly_salary = 0 THEN
        SET NEW.monthly_salary = NEW.basic_pay;
    END IF;

    -- Calculate hourly rate based on standard monthly hours (22 days * 8 hours = 176 hours)
    SET NEW.regular_hourly_rate = NEW.monthly_salary / NEW.standard_monthly_hours;
    SET NEW.overtime_hourly_rate = NEW.regular_hourly_rate * 1.25;
    
    -- Calculate regular hours pay (proportional to actual hours worked)
    SET NEW.regular_hours_pay = NEW.actual_hours_worked * NEW.regular_hourly_rate;
    
    -- Calculate overtime pay
    SET NEW.overtime_hours_pay = NEW.overtime_hours * NEW.overtime_hourly_rate;
    
    -- Set gross pay as total of regular and overtime pay
    SET NEW.gross_pay = NEW.regular_hours_pay + NEW.overtime_hours_pay;
END//

CREATE TRIGGER before_payroll_update
BEFORE UPDATE ON payrolls
FOR EACH ROW
BEGIN
    -- Recalculate rates if monthly salary changes
    IF NEW.monthly_salary != OLD.monthly_salary THEN
        SET NEW.regular_hourly_rate = NEW.monthly_salary / NEW.standard_monthly_hours;
        SET NEW.overtime_hourly_rate = NEW.regular_hourly_rate * 1.25;
    END IF;
    
    -- Recalculate pay if hours or rates change
    IF NEW.actual_hours_worked != OLD.actual_hours_worked OR 
       NEW.overtime_hours != OLD.overtime_hours OR
       NEW.regular_hourly_rate != OLD.regular_hourly_rate THEN
        
        SET NEW.regular_hours_pay = NEW.actual_hours_worked * NEW.regular_hourly_rate;
        SET NEW.overtime_hours_pay = NEW.overtime_hours * NEW.overtime_hourly_rate;
        SET NEW.gross_pay = NEW.regular_hours_pay + NEW.overtime_hours_pay;
    END IF;
END//

DELIMITER ;

-- Create a view for earnings calculation details
CREATE OR REPLACE VIEW payroll_hours_detail AS
SELECT 
    p.id,
    p.employee_id,
    e.emp_name,
    p.pay_date,
    p.pay_period_start,
    p.pay_period_end,
    p.monthly_salary,
    p.standard_monthly_hours,
    p.actual_days_worked,
    p.actual_hours_worked,
    p.regular_hourly_rate,
    p.regular_hours_pay,
    p.overtime_hours,
    p.overtime_hourly_rate,
    p.overtime_hours_pay,
    p.gross_pay,
    pos.name AS position,
    d.name AS department
FROM payrolls p
JOIN employees e ON p.employee_id = e.id
JOIN positions pos ON e.position_id = pos.id
JOIN departments d ON e.department_id = d.id
ORDER BY p.pay_date DESC;
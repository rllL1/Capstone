-- Create payroll_records table if not exists
CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `days_worked` decimal(5,2) NOT NULL,
  `hours_per_day` int(11) DEFAULT 8,
  `total_hours` decimal(7,2) NOT NULL,
  `absent_hours` decimal(5,2) DEFAULT 0.00,
  `actual_hours` decimal(7,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `tardiness_deduction` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL,
  `tax_deduction` decimal(10,2) NOT NULL,
  `sss_deduction` decimal(10,2) NOT NULL,
  `philhealth_deduction` decimal(10,2) NOT NULL,
  `pagibig_deduction` decimal(10,2) NOT NULL,
  `total_deductions` decimal(12,2) NOT NULL,
  `net_pay` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `pay_period` (`pay_period_start`,`pay_period_end`),
  KEY `status` (`status`),
  CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update trigger to update created_at/updated_at timestamps
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `payroll_records_before_update` 
BEFORE UPDATE ON `payroll_records`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- Add indexes to improve query performance
ALTER TABLE `payroll_records`
  ADD INDEX `idx_created_at` (`created_at`),
  ADD INDEX `idx_employee_status` (`employee_id`, `status`),
  ADD INDEX `idx_period_employee` (`pay_period_start`, `employee_id`);

-- Create payroll_settings table if not exists
CREATE TABLE IF NOT EXISTS `payroll_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings if not exists
INSERT IGNORE INTO `payroll_settings` (`setting_key`, `setting_value`, `description`) VALUES
('working_days_per_month', '26', 'Standard number of working days per month'),
('hours_per_day', '8', 'Standard number of working hours per day'),
('overtime_rate', '1.25', 'Overtime rate multiplier (1.25 = 25% additional)'),
('sss_rate', '0.045', 'SSS contribution rate (4.5%)'),
('philhealth_rate', '0.025', 'PhilHealth contribution rate (2.5%)'),
('pagibig_rate', '0.02', 'Pag-IBIG contribution rate (2%)'),
('pagibig_max', '100', 'Maximum Pag-IBIG contribution'),
('tax_threshold', '20000', 'Monthly salary threshold for tax calculation'),
('tax_rate', '0.10', 'Tax rate for income above threshold (10%)');

-- Create a view for payroll summary
CREATE OR REPLACE VIEW `payroll_summary` AS
SELECT 
  pr.pay_period_start,
  pr.pay_period_end,
  COUNT(DISTINCT pr.employee_id) as total_employees,
  SUM(pr.gross_pay) as total_gross,
  SUM(pr.total_deductions) as total_deductions,
  SUM(pr.net_pay) as total_net,
  AVG(pr.net_pay) as average_net,
  pr.status,
  COUNT(CASE WHEN pr.status = 'approved' THEN 1 END) as approved_count,
  COUNT(CASE WHEN pr.status = 'pending' THEN 1 END) as pending_count,
  COUNT(CASE WHEN pr.status = 'rejected' THEN 1 END) as rejected_count
FROM payroll_records pr
GROUP BY pr.pay_period_start, pr.pay_period_end, pr.status;
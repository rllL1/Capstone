-- Create holidays table for the payroll system
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('regular','special') DEFAULT 'regular',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert some sample holidays for 2025
INSERT INTO `holidays` (`date`, `name`, `type`) VALUES
('2025-01-01', 'New Year\'s Day', 'regular'),
('2025-04-09', 'Araw ng Kagitingan', 'regular'),
('2025-04-17', 'Maundy Thursday', 'regular'),
('2025-04-18', 'Good Friday', 'regular'),
('2025-05-01', 'Labor Day', 'regular'),
('2025-06-12', 'Independence Day', 'regular'),
('2025-08-21', 'Ninoy Aquino Day', 'special'),
('2025-08-25', 'National Heroes Day', 'regular'),
('2025-11-01', 'All Saints\' Day', 'special'),
('2025-11-30', 'Bonifacio Day', 'regular'),
('2025-12-25', 'Christmas Day', 'regular'),
('2025-12-30', 'Rizal Day', 'regular'),
('2025-12-31', 'New Year\'s Eve', 'special');
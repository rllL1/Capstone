-- First, drop all foreign key constraints
ALTER TABLE payrolls
DROP FOREIGN KEY IF EXISTS payrolls_ibfk_1;

ALTER TABLE employees
DROP FOREIGN KEY IF EXISTS employees_department_fk,
DROP FOREIGN KEY IF EXISTS employees_position_fk;

ALTER TABLE positions
DROP FOREIGN KEY IF EXISTS positions_ibfk_1;

-- Now truncate the tables in the correct order (child tables first)
TRUNCATE TABLE payrolls;
TRUNCATE TABLE employees;
TRUNCATE TABLE positions;
TRUNCATE TABLE departments;

-- Reset auto-increment values
ALTER TABLE departments AUTO_INCREMENT = 1;
ALTER TABLE positions AUTO_INCREMENT = 1;
ALTER TABLE employees AUTO_INCREMENT = 1;
ALTER TABLE payrolls AUTO_INCREMENT = 1;

-- Recreate the foreign key constraints
ALTER TABLE positions
ADD CONSTRAINT positions_ibfk_1 
FOREIGN KEY (department_id) REFERENCES departments(id);

ALTER TABLE employees
ADD CONSTRAINT employees_department_fk 
FOREIGN KEY (department) REFERENCES departments(id),
ADD CONSTRAINT employees_position_fk 
FOREIGN KEY (position) REFERENCES positions(id);

ALTER TABLE payrolls
ADD CONSTRAINT payrolls_ibfk_1 
FOREIGN KEY (emp_id) REFERENCES employees(id);

-- Add one sample department and position to get started
INSERT INTO departments (name) VALUES ('Sample Department');
INSERT INTO positions (name, salary) VALUES ('Sample Position', 25000.00);
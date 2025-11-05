// employee_selection.js - Handles employee selection and salary population

function initializePayrollForm() {
    const deptSelect = document.getElementById('dept_select');
    const empSelect = document.getElementById('emp_select');
    const employeeGroup = document.getElementById('employee_group');
    if (!deptSelect || !empSelect || !employeeGroup) {
        console.error('Required form elements not found');
        return;
    }

    // Function to update employee dropdown
    async function updateEmployeeDropdown(departmentId) {
        try {
            empSelect.innerHTML = '<option value="">Loading...</option>';
            empSelect.disabled = true;
            employeeGroup.style.opacity = '0.7';

            if (!departmentId) {
                empSelect.innerHTML = '<option value="">Select Department First</option>';
                return;
            }

            const response = await fetch(`get_dept_pos_new.php?department_id=${departmentId}`);
            if (!response.ok) throw new Error('Failed to fetch employees');
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            empSelect.innerHTML = '<option value="">Select Employee</option>';
            
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(emp => {
                    if (emp && emp.id && emp.name) {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = `${emp.name} (#${emp.employee_id})`;
                        option.dataset.position = emp.position || '';
                        option.dataset.rawPosition = emp.raw_position || '';
                        option.dataset.salary = emp.salary || '0';
                        option.dataset.department = emp.department_name || '';
                        option.dataset.empName = emp.name;
                        option.dataset.employeeId = emp.employee_id;
                        empSelect.appendChild(option);
                    }
                });
                empSelect.disabled = false;
                employeeGroup.style.opacity = '1';
            } else {
                empSelect.innerHTML = '<option value="">No employees found in this department</option>';
            }
        } catch (error) {
            console.error('Error loading employees:', error);
            empSelect.innerHTML = '<option value="">Error loading employees. Please try again.</option>';
        }
    }

    // Event Listeners
    deptSelect.addEventListener('change', function() {
        updateEmployeeDropdown(this.value);
        // Clear employee details
        document.getElementById('display_salary')?.value = '';
        document.getElementById('salary_details')?.style.display = 'none';
    });

    empSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const displaySalary = document.getElementById('display_salary');
        const salaryDetails = document.getElementById('salary_details');
        const messageDiv = document.getElementById('submitMessage');
        const positionInput = document.getElementById('position');
        
        if (selected && selected.value) {
            const salary = selected.dataset.salary || '0';
            console.log('Selected employee salary:', salary); // Debug log
            
            // Update position display
            if (positionInput) {
                positionInput.value = selected.dataset.position || '';
            }
            
            // Update salary display
            if (displaySalary) {
                const formattedSalary = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(parseFloat(salary));
                console.log('Formatted salary:', formattedSalary); // Debug log
                displaySalary.value = formattedSalary;
                salaryDetails.style.display = 'block';
            }
            
            // Update all inputs
            document.getElementById('basic_pay_input').value = salary;
            document.getElementById('emp_name_input').value = selected.dataset.empName || '';
            document.getElementById('department_input').value = selected.dataset.department || '';
            
            // Update position input
            const positionInput = document.getElementById('position');
            if (positionInput) {
                positionInput.value = selected.dataset.position || '';
                positionInput.readOnly = true;
            }
            
            // Store employee ID if the input exists
            const employeeIdInput = document.getElementById('employee_id_input');
            if (employeeIdInput) {
                employeeIdInput.value = selected.dataset.employeeId || 'N/A';
            }
            
            submitBtn.disabled = false;
            if (messageDiv) messageDiv.style.display = 'none';
        } else {
            if (displaySalary) displaySalary.value = '';
            if (salaryDetails) salaryDetails.style.display = 'none';
            submitBtn.disabled = true;
        }
    });

    // Initialize if department is pre-selected
    if (deptSelect.value) {
        updateEmployeeDropdown(deptSelect.value);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializePayrollForm);
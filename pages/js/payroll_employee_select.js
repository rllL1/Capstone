document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing employee selection...');
    
    const deptSelect = document.getElementById('dept_select');
    const empSelect = document.getElementById('emp_select');
    
    if (!deptSelect || !empSelect) {
        console.error('Could not find department or employee select elements');
        return;
    }
    
    // Initialize employee select
    empSelect.disabled = true;
    empSelect.innerHTML = '<option value="">Select Department First</option>';
    
    function updateEmployeeDropdown(departmentName) {
        console.log('Updating employees for department:', departmentName);
        
        if (!departmentName) {
            empSelect.innerHTML = '<option value="">Select Department First</option>';
            empSelect.disabled = true;
            return;
        }
        
        // Show loading state
        empSelect.innerHTML = '<option value="">Loading...</option>';
        empSelect.disabled = true;
        
        // Fetch employees from server
        fetch(`get_dept_pos.php?department=${encodeURIComponent(departmentName)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Received data:', data);
                
                empSelect.innerHTML = '<option value="">Select Employee</option>';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = emp.name;
                        option.dataset.position = emp.position_name;
                        option.dataset.salary = emp.salary;
                        empSelect.appendChild(option);
                    });
                    
                    empSelect.disabled = false;
                } else {
                    empSelect.innerHTML = '<option value="">No employees found</option>';
                    empSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error fetching employees:', error);
                empSelect.innerHTML = '<option value="">Error loading employees</option>';
                empSelect.disabled = true;
            });
    }
    
    // Handle department selection
    deptSelect.addEventListener('change', function() {
        const department = this.value;
        console.log('Department selected:', department);
        updateEmployeeDropdown(department);
    });
    
    // Handle employee selection
    empSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        console.log('Employee selected:', selectedOption ? selectedOption.textContent : 'none');
        
        if (!selectedOption || !selectedOption.value) {
            // Clear all employee-related fields
            if (document.getElementById('emp_name')) document.getElementById('emp_name').value = '';
            if (document.getElementById('position')) document.getElementById('position').value = '';
            if (document.getElementById('monthly_base_salary')) document.getElementById('monthly_base_salary').value = '';
            return;
        }
        
        // Update employee info fields
        if (document.getElementById('emp_name')) document.getElementById('emp_name').value = selectedOption.textContent;
        if (document.getElementById('position')) document.getElementById('position').value = selectedOption.dataset.position;
        if (document.getElementById('monthly_base_salary')) {
            document.getElementById('monthly_base_salary').value = selectedOption.dataset.salary;
            // Trigger input event to recalculate if needed
            document.getElementById('monthly_base_salary').dispatchEvent(new Event('input'));
        }
        
        console.log('Updated employee fields:', {
            name: document.getElementById('emp_name')?.value,
            position: document.getElementById('position')?.value,
            salary: document.getElementById('monthly_base_salary')?.value
        });
    });
    
    // Initial load if department is pre-selected
    if (deptSelect.value) {
        updateEmployeeDropdown(deptSelect.value);
    }
});
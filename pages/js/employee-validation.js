// Employee Selection Handling
document.addEventListener('DOMContentLoaded', function() {
    const empSelect = document.getElementById('emp_select');
    const empIdInput = document.getElementById('emp_id');
    const form = document.getElementById('payrollForm');

    if (!empSelect || !empIdInput || !form) {
        console.error('Required elements not found');
        return;
    }

    // Add required validation
    empSelect.setAttribute('required', '');
    
    // Add validation styling
    empSelect.addEventListener('change', function() {
        const selectedValue = this.value;
        
        // Update hidden emp_id input
        if (empIdInput) {
            empIdInput.value = selectedValue;
        }
        
        // Handle validation styling
        if (!selectedValue) {
            this.classList.add('is-invalid');
            if (empIdInput) empIdInput.value = '';
        } else {
            this.classList.remove('is-invalid');
        }
    });

    // Add form submit handler
    form.addEventListener('submit', function(e) {
        // Check if employee is selected
        if (!empSelect.value) {
            e.preventDefault();
            empSelect.classList.add('is-invalid');
            
            Swal.fire({
                icon: 'error',
                title: 'Required Fields Missing',
                text: 'Please select an employee',
                confirmButtonColor: '#2e7d32'
            });
            return false;
        }
        
        // Update hidden input before submit
        if (empIdInput) {
            empIdInput.value = empSelect.value;
        }
    });

    // Clear validation on department change
    const deptSelect = document.getElementById('dept_select');
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            empSelect.classList.remove('is-invalid');
        });
    }
});
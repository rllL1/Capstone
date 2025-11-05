// Form validation script
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payrollForm');
    if (!form) return;

    // Add required field indicators
    const requiredFields = [
        { selector: '[name="emp_id"]', label: 'Employee', type: 'hidden' },
        { selector: '[name="department"]', label: 'Department', type: 'select' },
        { selector: '[name="pay_date"]', label: 'Pay Date', type: 'date' },
        { selector: '[name="work_hours"]', label: 'Work Hours', type: 'number' },
        { selector: '[name="basic_pay"]', label: 'Basic Pay', type: 'hidden' }
    ];

    // Add required field indicators to labels
    requiredFields.forEach(field => {
        const element = form.querySelector(field.selector);
        if (!element) return;

        // Skip hidden fields
        if (field.type === 'hidden') return;

        const label = element.closest('.form-group')?.querySelector('label');
        if (label && !label.innerHTML.includes('*')) {
            label.innerHTML += ' <span class="text-danger">*</span>';
        }

        // Add required attribute
        element.setAttribute('required', '');
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        let missingFields = [];

        // Clear previous validation states
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Validate each required field
        requiredFields.forEach(field => {
            const element = form.querySelector(field.selector);
            if (!element) return;

            let value = element.value.trim();
            
            // Special handling for hidden fields
            if (field.type === 'hidden') {
                if (!value || value === '0') {
                    isValid = false;
                    missingFields.push(field.label);
                    
                    // Find associated visible element
                    const visibleElement = document.querySelector(`[data-target="${element.name}"]`) ||
                                        document.getElementById(`${element.name}_select`);
                    if (visibleElement) {
                        visibleElement.classList.add('is-invalid');
                    }
                }
            }
            // Regular field validation
            else if (!value) {
                isValid = false;
                missingFields.push(field.label);
                element.classList.add('is-invalid');
            }
        });

        // Show validation message if there are errors
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Required Fields Missing',
                html: `Please fill in the following fields:<br><br>${missingFields.map(field => `• ${field}`).join('<br>')}`,
                confirmButtonColor: '#2e7d32'
            });
            return;
        }

        // If validation passes, submit the form using the submitPayroll function
        if (typeof submitPayroll === 'function') {
            submitPayroll();
        }
    });

    // Add event listeners to clear validation state on input
    requiredFields.forEach(field => {
        const element = form.querySelector(field.selector);
        if (!element || field.type === 'hidden') return;

        element.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });

        element.addEventListener('change', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});
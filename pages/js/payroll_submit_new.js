// Function to submit payroll
function submitPayroll() {
    // Get form data
    const form = document.getElementById('payrollForm');
    if (!form) {
        console.error('Payroll form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    // Debug log all form values
    console.log('Form Data:');
    formData.forEach((value, key) => {
        console.log(`${key}: ${value}`);
    });
    
    // Validate required fields with default values
    const requiredFields = [
        { name: 'emp_id', label: 'Employee', type: 'hidden', defaultValue: '' },
        { name: 'pay_date', label: 'Pay Date', type: 'date', defaultValue: '' },
        { name: 'basic_pay', label: 'Basic Pay', type: 'hidden', defaultValue: '0' },
        { name: 'work_hours', label: 'Work Hours', type: 'number', defaultValue: '0' }
    ];
    
    console.log('Checking required fields...');
    let isValid = true;
    let missingFields = [];
    
    requiredFields.forEach(field => {
        const input = form.querySelector(`[name="${field.name}"]`);
        let value = input?.value?.trim();
        
        console.log(`Checking ${field.name}: ${value}`);
        
        // Special check for hidden fields (like emp_id and basic_pay)
        if (field.type === 'hidden') {
            if (!value || value === field.defaultValue || value === '') {
                isValid = false;
                missingFields.push(field.label);
                // Find associated visible element (like select dropdown)
                const visibleElement = document.querySelector(`#${field.name.replace('_id', '')}_select`) || 
                                     document.querySelector(`[data-target="${field.name}"]`);
                if (visibleElement) {
                    visibleElement.classList.add('is-invalid');
                    console.log(`Added invalid class to ${field.name} visible element`);
                    // Focus on the first invalid field
                    if (missingFields.length === 1) {
                        visibleElement.focus();
                    }
                } else {
                    console.log(`No visible element found for ${field.name}`);
                }
            }
        } 
        // Check for regular input fields
        else if (!input || !value || value === '') {
            isValid = false;
            missingFields.push(field.label);
            if (input) {
                input.classList.add('is-invalid');
                console.log(`Added invalid class to ${field.name} input`);
            } else {
                console.log(`Input not found for ${field.name}`);
            }
        } else {
            input.classList.remove('is-invalid');
            console.log(`Removed invalid class from ${field.name}`);
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Required Fields Missing',
            html: `Please fill in the following required fields:<br><br>${missingFields.map(field => `• ${field}`).join('<br>')}`,
            confirmButtonColor: '#2e7d32'
        });
        return;
    }
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    formData.append('csrf_token', csrfToken);
    formData.append('add_payroll', '1'); // Add flag to indicate payroll submission

    // Double check all required values are set
    console.log('Final form data check:');
    let missingData = [];
    requiredFields.forEach(field => {
        const value = formData.get(field.name);
        console.log(`${field.name}: ${value}`);
        if (!value || value === '0' || value === '') {
            missingData.push(field.label);
        }
    });

    if (missingData.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Data',
            html: `The following fields are missing or invalid:<br><br>${missingData.map(field => `• ${field}`).join('<br>')}`,
            confirmButtonColor: '#2e7d32'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we process the payroll',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Submit the form using fetch
    fetch('save_payroll.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Close loading state
        Swal.close();

        // Check response and handle success/error
        console.log('Server response:', data); // Debug log
        
        if (data.includes('success')) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Payroll has been processed successfully',
                confirmButtonColor: '#2e7d32'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reload the page to show updated data
                    window.location.reload();
                }
            });
        } else {
            // Extract error message if available
            const errorMatch = data.match(/error:\s*(.*?)(?:\n|$)/);
            const errorMessage = errorMatch ? errorMatch[1] : 'There was an error processing the payroll. Please try again.';
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
                confirmButtonColor: '#2e7d32'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'There was an error processing the payroll. Please try again.',
            confirmButtonColor: '#2e7d32'
        });
    });
}

// Function to open records modal
function openRecordsModal() {
    const modal = document.getElementById('recordsModal');
    if (modal) {
        modal.style.display = 'block';
        // Add class to handle animation if needed
        modal.classList.add('active');
    }
}

// Function to close records modal
function closeRecordsModal() {
    const modal = document.getElementById('recordsModal');
    if (modal) {
        modal.style.display = 'none';
        // Remove active class if using animations
        modal.classList.remove('active');
    }
}

// Function to handle clicking outside modal to close it
window.onclick = function(event) {
    const modal = document.getElementById('recordsModal');
    if (event.target === modal) {
        closeRecordsModal();
    }
}

// Function to delete a payroll record
function deleteRecord(payrollId) {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the record',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send delete request
            fetch(`payroll.php?delete=${payrollId}&csrf_token=${csrfToken}`)
            .then(response => response.text())
            .then(data => {
                Swal.close();

                if (data.includes('success_msg')) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Payroll record has been deleted.',
                        confirmButtonColor: '#2e7d32'
                    }).then(() => {
                        // Reload the page to show updated data
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'There was an error deleting the record.',
                        confirmButtonColor: '#2e7d32'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was an error deleting the record.',
                    confirmButtonColor: '#2e7d32'
                });
            });
        }
    });
}

// Add event listeners once DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for submit button if it exists
    const submitBtn = document.querySelector('[data-action="submit-payroll"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitPayroll();
        });
    }

    // Add event listener for view records button if it exists
    const viewRecordsBtn = document.querySelector('[data-action="view-records"]');
    if (viewRecordsBtn) {
        viewRecordsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openRecordsModal();
        });
    }
});
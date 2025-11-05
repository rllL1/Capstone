// Utility function to show messages
function showMessage(message, isError = false) {
    const successMsg = document.getElementById('successMessage');
    const errorMsg = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    if (isError) {
        errorText.textContent = message;
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
        setTimeout(() => {
            errorMsg.style.display = 'none';
        }, 5000);
    } else {
        successMsg.style.display = 'block';
        errorMsg.style.display = 'none';
    }
}

// Function to fetch and display employee records
function viewEmployeeRecords() {
    fetch('get_employee_records.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#recordsTable tbody');
            tableBody.innerHTML = '';
            
            data.forEach(record => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${record.ref_no}</td>
                    <td>${record.emp_name}</td>
                    <td>${record.department}</td>
                    <td>${record.position}</td>
                    <td>${record.pay_date}</td>
                    <td>₱${parseFloat(record.net_pay).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td>
                        <button onclick="viewPayslip(${record.id})" class="btn btn-info btn-sm">
                            <i class="fas fa-file-alt"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
            
            // Show the records modal
            $('#recordsModal').modal('show');
        })
        .catch(error => {
            showMessage('Error fetching employee records', true);
            console.error('Error:', error);
        });
}

// Function to view individual payslip
function viewPayslip(id) {
    fetch(`get_payslip.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showMessage(data.error, true);
                return;
            }
            
            // Populate payslip modal
            document.getElementById('payslip_ref_no').textContent = data.ref_no;
            document.getElementById('payslip_date').textContent = data.pay_date;
            document.getElementById('payslip_name').textContent = data.emp_name;
            document.getElementById('payslip_department').textContent = data.department;
            document.getElementById('payslip_position').textContent = data.position;
            document.getElementById('payslip_basic').textContent = '₱' + parseFloat(data.basic_pay).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_hours').textContent = data.hours_worked;
            document.getElementById('payslip_ot').textContent = data.overtime_hours;
            document.getElementById('payslip_gross').textContent = '₱' + parseFloat(data.gross_pay).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_sss').textContent = '₱' + parseFloat(data.sss).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_philhealth').textContent = '₱' + parseFloat(data.philhealth).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_pagibig').textContent = '₱' + parseFloat(data.pagibig).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_tax').textContent = '₱' + parseFloat(data.tax).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_deductions').textContent = '₱' + parseFloat(data.deductions).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('payslip_net').textContent = '₱' + parseFloat(data.net_pay).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Hide records modal and show payslip modal
            $('#recordsModal').modal('hide');
            $('#payslipModal').modal('show');
        })
        .catch(error => {
            showMessage('Error fetching payslip details', true);
            console.error('Error:', error);
        });
}

// Event handler for when payslip modal is hidden
$('#payslipModal').on('hidden.bs.modal', function () {
    // Show records modal again
    $('#recordsModal').modal('show');
});

// Validate form data
function validateForm(formData) {
    const required = ['emp_select', 'pay_date', 'basic_pay', 'work_hours', 'gross_pay'];
    for (const field of required) {
        if (!formData.get(field)) {
            throw new Error(`Please fill in all required fields (${field})`);
        }
    }
}

// Handle form submission
document.getElementById('payrollForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Create FormData object
    const formData = new FormData(this);
    formData.append('add_payroll', '1');
    
    try {
        // Validate form
        validateForm(formData);
        
        // Submit form via fetch
        fetch('save_payroll.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message);
                // Show the records modal after successful submission
                viewEmployeeRecords();
            } else {
                showMessage(data.message, true);
            }
        })
        .catch(error => {
            showMessage('Error occurred while submitting payroll', true);
            console.error('Error:', error);
        });
    } catch (error) {
        showMessage(error.message, true);
    }
});
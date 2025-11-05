document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const viewRecordsBtn = document.getElementById('viewRecordsBtn');
    const recordsModal = document.getElementById('recordsModal');
    const payslipModal = document.getElementById('payslipModal');
    const closeButtons = document.querySelectorAll('.close-modal');

    // Utility functions
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount).replace('PHP', '₱');
    }

    function showLoadingSpinner() {
        return `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading records...</p>
            </div>
        `;
    }

    function showError(message) {
        return `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            </div>
        `;
    }

    function showNoRecords(employeeName) {
        return `
            <div class="no-records">
                <i class="fas fa-info-circle"></i>
                <p>No payroll records found for ${employeeName}</p>
            </div>
        `;
    }

    // View Payslip Function
    async function viewPayslip(payrollId) {
        try {
            recordsModal.style.display = 'none';
            payslipModal.style.display = 'block';
            document.getElementById('payslipContent').innerHTML = showLoadingSpinner();

            const response = await fetch(`get_payslip.php?id=${payrollId}`);
            if (!response.ok) throw new Error('Failed to fetch payslip');
            
            const data = await response.json();
            
            // Generate payslip HTML
            const payslipHtml = `
                <div class="payslip">
                    <div class="payslip-header">
                        <h3>Payroll Details</h3>
                        <p>Pay Period: ${data.pay_date}</p>
                    </div>
                    <div class="employee-info">
                        <p><strong>Employee:</strong> ${data.emp_name}</p>
                        <p><strong>Department:</strong> ${data.department}</p>
                        <p><strong>Position:</strong> ${data.position}</p>
                    </div>
                    <div class="salary-details">
                        <div class="detail-row">
                            <span>Basic Pay</span>
                            <span>${formatCurrency(data.basic_pay)}</span>
                        </div>
                        <div class="detail-row">
                            <span>Work Hours</span>
                            <span>${data.hours_worked} hrs</span>
                        </div>
                        <div class="detail-row">
                            <span>Overtime Hours</span>
                            <span>${data.overtime_hours} hrs</span>
                        </div>
                        <div class="detail-row total">
                            <span>Gross Pay</span>
                            <span>${formatCurrency(data.gross_pay)}</span>
                        </div>
                    </div>
                    <div class="deductions">
                        <h4>Deductions</h4>
                        <div class="detail-row">
                            <span>SSS</span>
                            <span>${formatCurrency(data.sss)}</span>
                        </div>
                        <div class="detail-row">
                            <span>PhilHealth</span>
                            <span>${formatCurrency(data.philhealth)}</span>
                        </div>
                        <div class="detail-row">
                            <span>Pag-IBIG</span>
                            <span>${formatCurrency(data.pagibig)}</span>
                        </div>
                        <div class="detail-row">
                            <span>Tax</span>
                            <span>${formatCurrency(data.tax)}</span>
                        </div>
                        <div class="detail-row total">
                            <span>Total Deductions</span>
                            <span>${formatCurrency(data.deductions)}</span>
                        </div>
                    </div>
                    <div class="net-pay">
                        <div class="detail-row">
                            <span>Net Pay</span>
                            <span>${formatCurrency(data.net_pay)}</span>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('payslipContent').innerHTML = payslipHtml;

            // Handle export button
            document.getElementById('exportPayslipBtn').onclick = () => {
                window.location.href = `export_pdf.php?id=${payrollId}`;
            };
        } catch (error) {
            console.error('Error fetching payslip:', error);
            document.getElementById('payslipContent').innerHTML = showError('Failed to load payslip');
        }
    }

    // View Records Button Click Handler
    if (viewRecordsBtn) {
        viewRecordsBtn.addEventListener('click', async function() {
            const empSelect = document.getElementById('emp_select');
            const employeeId = empSelect.value;
            const employeeName = empSelect.options[empSelect.selectedIndex]?.text || 'selected employee';

            if (!employeeId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select an employee first',
                    confirmButtonColor: '#2e7d32'
                });
                return;
            }

            try {
                // Show modal with loading state
                recordsModal.style.display = 'block';
                const recordsList = recordsModal.querySelector('.records-list');
                recordsList.innerHTML = showLoadingSpinner();

                // Fetch records
                const response = await fetch(`get_employee_records.php?emp_id=${employeeId}`);
                if (!response.ok) throw new Error('Failed to fetch records');
                
                const records = await response.json();

                // Display records or no records message
                if (records.length === 0) {
                    recordsList.innerHTML = showNoRecords(employeeName);
                    return;
                }

                // Generate records list
                recordsList.innerHTML = records.map(record => `
                    <div class="record-item">
                        <div class="record-info">
                            <div class="record-name">Payroll #${record.ref_no}</div>
                            <div class="record-details">
                                <span><i class="fas fa-calendar"></i> ${record.pay_date}</span>
                                <span><i class="fas fa-money-bill-wave"></i> ${formatCurrency(record.net_pay)}</span>
                            </div>
                        </div>
                        <div class="record-actions">
                            <button class="btn btn-primary view-payslip" data-id="${record.id}">
                                <i class="fas fa-file-invoice"></i> View Payslip
                            </button>
                        </div>
                    </div>
                `).join('');

                // Add click handlers for payslip buttons
                recordsList.querySelectorAll('.view-payslip').forEach(button => {
                    button.addEventListener('click', () => viewPayslip(button.dataset.id));
                });

            } catch (error) {
                console.error('Error:', error);
                recordsList.innerHTML = showError('Failed to load records. Please try again.');
            }
        });
    }

    // Close button handlers
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            recordsModal.style.display = 'none';
            payslipModal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === recordsModal) {
            recordsModal.style.display = 'none';
        }
        if (event.target === payslipModal) {
            payslipModal.style.display = 'none';
        }
    });
});
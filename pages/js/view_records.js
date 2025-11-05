// Records Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const viewRecordsBtn = document.getElementById('viewRecordsBtn');
    const recordsModal = document.getElementById('recordsModal');
    const payslipModal = document.getElementById('payslipModal');
    const closeButtons = document.querySelectorAll('.close-modal');
    
    function showLoadingSpinner() {
        return `<div class="loading-spinner" style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #2e7d32;"></i>
            <p style="margin-top: 1rem; color: #666;">Loading records...</p>
        </div>`;
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount).replace('PHP', '₱');
    }

    async function viewPayslip(payrollId) {
        try {
            recordsModal.style.display = 'none';
            payslipModal.style.display = 'block';
            document.getElementById('payslipContent').innerHTML = showLoadingSpinner();

            const response = await fetch(`get_payslip.php?id=${payrollId}`);
            if (!response.ok) throw new Error('Failed to fetch payslip');
            
            const data = await response.json();
            document.getElementById('payslipContent').innerHTML = generatePayslipHTML(data);

            // Handle export button
            document.getElementById('exportPayslipBtn').onclick = () => {
                window.location.href = `export_pdf.php?id=${payrollId}`;
            };
        } catch (error) {
            console.error('Error fetching payslip:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load payslip. Please try again.',
                confirmButtonColor: '#2e7d32'
            });
        }
    }

    // View Records Button Click
    if (viewRecordsBtn) {
        viewRecordsBtn.addEventListener('click', async function() {
            const employeeId = document.getElementById('emp_select').value;
            const employeeName = document.getElementById('emp_select').options[document.getElementById('emp_select').selectedIndex]?.text;
            
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

                // Fetch employee's payroll records
                const response = await fetch(`get_employee_records.php?emp_id=${employeeId}`);
                if (!response.ok) throw new Error('Failed to fetch records');
                
                const records = await response.json();

                // Display records
                if (records.length === 0) {
                    recordsList.innerHTML = `
                        <div class="no-records" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-info-circle" style="font-size: 2rem; color: #666;"></i>
                            <p style="margin-top: 1rem; color: #666;">No payroll records found for ${employeeName}</p>
                        </div>`;
                    return;
                }

                // Generate records list HTML
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
                            <button class="btn view-payslip" data-id="${record.id}" 
                                    style="background: #2e7d32; color: white; border: none; border-radius: 4px; padding: 8px 16px;">
                                <i class="fas fa-file-invoice"></i> View Payslip
                            </button>
                        </div>
                    </div>
                `).join('');

                // Add event listeners for payslip buttons
                recordsList.querySelectorAll('.view-payslip').forEach(button => {
                    button.addEventListener('click', function() {
                        viewPayslip(this.dataset.id);
                    });
                });

            } catch (error) {
                console.error('Error fetching records:', error);
                recordsList.innerHTML = `
                    <div class="error" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: #dc3545;"></i>
                        <p style="margin-top: 1rem; color: #dc3545;">Failed to load records. Please try again.</p>
                    </div>`;
            }
        });
    }

    // Close Modal Functionality
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
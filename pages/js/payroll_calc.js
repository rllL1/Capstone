// Constants for payroll calculations
const WORKING_DAYS_PER_MONTH = 26;  // Standard working days per month
const HOURS_PER_DAY = 8;            // Standard hours per day
const MINUTES_PER_HOUR = 60;        // Minutes in an hour
const OVERTIME_RATE = 1.25;         // 25% additional for overtime
const SSS_RATE = 0.045;             // 4.5% SSS
const PHILHEALTH_RATE = 0.025;      // 2.5% PhilHealth
const PAGIBIG_RATE = 0.02;          // 2% Pag-IBIG
const PAGIBIG_MAX = 100;            // Maximum Pag-IBIG contribution
const TAX_THRESHOLD = 20000;        // Monthly salary threshold for tax
const TAX_RATE = 0.10;              // 10% tax for salaries above threshold

// Function to check if employee is selected
function isEmployeeSelected() {
    const empIdInput = document.querySelector('[name="emp_id"]');
    return empIdInput && empIdInput.value.trim() !== '';
}

// Function to ensure pay date is set
function ensurePayDate() {
    const payDateInput = document.querySelector('[name="pay_date"]');
    if (payDateInput && !payDateInput.value) {
        const today = new Date();
        payDateInput.value = today.toISOString().split('T')[0];
    }
}

// Function to calculate all payroll values automatically
function calculatePayroll() {
    // First check if employee is selected
    if (!isEmployeeSelected()) {
        console.log('No employee selected, skipping calculations');
        return;
    }

    // Ensure pay date is set
    ensurePayDate();

    // Get all input elements
    const basicPayInput = document.querySelector('[name="basic_pay"]');
    const workHoursInput = document.querySelector('[name="work_hours"]');
    const overtimeHoursInput = document.querySelector('[name="overtime_hours"]');
    const absentHoursInput = document.querySelector('[name="absent_hours"]');
    const lateMinutesInput = document.querySelector('[name="late_minutes"]');
    const grossPayInput = document.querySelector('[name="gross_pay_computed"]');
    const sssInput = document.querySelector('[name="sss"]');
    const philhealthInput = document.querySelector('[name="philhealth"]');
    const pagibigInput = document.querySelector('[name="pagibig"]');
    const taxInput = document.querySelector('[name="tax"]');
    const totalDeductionsInput = document.querySelector('[name="total_deductions"]');
    const netPayInput = document.querySelector('[name="net_pay"]');

    // Get values from inputs (default to 0 if empty)
    const basicPay = parseFloat(basicPayInput.value) || 0;
    const workHours = parseFloat(workHoursInput.value) || 0;
    const overtimeHours = parseFloat(overtimeHoursInput.value) || 0;
    const absentHours = parseFloat(absentHoursInput.value) || 0;
    const lateMinutes = parseFloat(lateMinutesInput.value) || 0;

    // Calculate hourly rate
    const hourlyRate = basicPay / (WORKING_DAYS_PER_MONTH * HOURS_PER_DAY);
    const minuteRate = hourlyRate / MINUTES_PER_HOUR;

    // Calculate deductions for absences and late minutes
    const absentDeduction = absentHours * hourlyRate;
    const lateDeduction = lateMinutes * minuteRate;

    // Calculate overtime pay
    const overtimePay = overtimeHours * hourlyRate * OVERTIME_RATE;

    // Calculate regular pay based on work hours
    const regularPay = workHours * hourlyRate;

    // Calculate gross pay
    const grossPay = regularPay + overtimePay - absentDeduction - lateDeduction;

    // Calculate mandatory deductions
    const sssDeduction = grossPay * SSS_RATE;
    const philhealthDeduction = grossPay * PHILHEALTH_RATE;
    let pagibigDeduction = grossPay * PAGIBIG_RATE;
    
    // Cap Pag-IBIG at maximum amount
    if (pagibigDeduction > PAGIBIG_MAX) {
        pagibigDeduction = PAGIBIG_MAX;
    }

    // Calculate tax
    let taxDeduction = 0;
    if (grossPay > TAX_THRESHOLD) {
        taxDeduction = (grossPay - TAX_THRESHOLD) * TAX_RATE;
    }

    // Calculate total deductions
    const totalDeductions = sssDeduction + philhealthDeduction + pagibigDeduction + taxDeduction;

    // Calculate net pay
    const netPay = grossPay - totalDeductions;

    // Update form fields
    grossPayInput.value = grossPay.toFixed(2);
    sssInput.value = sssDeduction.toFixed(2);
    philhealthInput.value = philhealthDeduction.toFixed(2);
    pagibigInput.value = pagibigDeduction.toFixed(2);
    taxInput.value = taxDeduction.toFixed(2);
    totalDeductionsInput.value = totalDeductions.toFixed(2);
    netPayInput.value = netPay.toFixed(2);
}

// Add event listeners to inputs that should trigger recalculation
document.addEventListener('DOMContentLoaded', function() {
    const inputFields = [
        'basic_pay',
        'work_hours',
        'overtime_hours',
        'absent_hours',
        'late_minutes'
    ];

    inputFields.forEach(fieldName => {
        const element = document.querySelector(`[name="${fieldName}"]`);
        if (element) {
            element.addEventListener('input', calculatePayroll);
        }
    });

    // Initial calculation
    calculatePayroll();
});

// Export the function for use in other files
window.calculatePayroll = calculatePayroll;
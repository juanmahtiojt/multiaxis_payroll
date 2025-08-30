<?php
require_once 'vendor/autoload.php';
include_once "functions.php";
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Set up Dompdf options
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Get parameters for individual employee
$payslip_id = $_GET['id'] ?? '';
$employee_name = $_GET['employee'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$payroll_period = $_GET['payroll_period'] ?? '';

if (empty($payslip_id) && empty($employee_name)) {
    die('Missing required parameters');
}

try {
    // Fetch individual payslip data
    if (!empty($payslip_id)) {
        // Get payslip by ID
        $query = "SELECT * FROM payroll_records WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $payslip_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Get payslip by employee name and date range
        $query = "SELECT * FROM payroll_records 
                  WHERE name = ? 
                  AND start_date = ? 
                  AND end_date = ? 
                  AND pay_period = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $employee_name, $start_date, $end_date, $payroll_period);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Calculate overtime & totals
        $overtimePay = $row['overtime_pay'];
        if ((empty($overtimePay) || $overtimePay == 0) && $row['overtime_hours'] > 0 && $row['overtime_rate'] > 0) {
            $overtimePay = $row['overtime_hours'] * $row['overtime_rate'];
        }
        $totalEarnings = $row['basic_salary'] + $overtimePay;
        $totalDeductions = 
            $row['sss_premium'] + $row['sss_loan'] + $row['pagibig_premium'] + 
            $row['pagibig_loan'] + $row['philhealth'] + $row['cash_advance'] + 
            $row['late_deduction'] + $row['absent_deduction'] + $row['undertime_deduction'];
        $netPay = $totalEarnings - $totalDeductions;

        // Load and encode logo
        $logoPath = __DIR__ . "/my_project/images/MULTI-removebg-preview.png";
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoSrc = 'data:image/png;base64,' . $logoData;

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Individual Payslip - ' . htmlspecialchars($row['name']) . '</title>
<style>
@page {
    size: A4;
    margin: 10mm;
}
body {
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    font-size: 9px;
    line-height: 1.3;
    color: #333;
    background-color: #fff;
}

.payslip {
    width: 100%;
    max-width: 190mm;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    page-break-inside: avoid;
}

.payslip-header {
    text-align: center;
    margin-bottom: 10px;
    border-bottom: 1px solid #2c3e50;
    padding-bottom: 6px;
}

.company-name {
    font-size: 14px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 3px;
}
.company-logo {
    max-width: 70px; 
    max-height: 50px; 
    margin-bottom: 3px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    background-color: #fff;
}

.payslip-title {
    font-size: 12px;
    font-weight: bold;
    color: #34495e;
    margin-bottom: 8px;
}

.employee-info {
    margin-bottom: 10px;
    background: #f8f9fa;
    padding: 6px;
    border-radius: 3px;
}

.employee-info p {
    margin: 2px 0;
    font-size: 9px;
}

.employee-info strong {
    color: #2c3e50;
}

.pay-section {
    margin-bottom: 10px;
}

.pay-section h4 {
    font-size: 11px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
    border-bottom: 1px solid #ecf0f1;
    padding-bottom: 2px;
}

.pay-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5px;
}

.pay-table td {
    padding: 2px 0;
    font-size: 9px;
}

.pay-table .description {
    text-align: left;
    color: #555;
    padding-left: 5px;
}

.pay-table .amount {
    text-align: right;
    font-family: monospace;
    font-weight: 500;
    padding-right: 5px;
}

.pay-table .subtotal {
    font-weight: bold;
    color: #2c3e50;
    border-top: 1px solid #bdc3c7;
    padding-top: 2px;
}

.summary-section {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #2c3e50;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 3px;
}

.net-pay-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}

.net-pay-label {
    font-size: 11px;
    font-weight: bold;
    color: #2c3e50;
}

.net-pay-amount {
    font-size: 12px;
    font-weight: bold;
    color: #27ae60;
}

.signature-section {
    margin-top: 20px;
    text-align: center;
    font-size: 8px;
    color: #666;
}

.signature-line {
    border-top: 1px solid #333;
    width: 150px;
    margin: 3px auto;
}
</style>
</head>
<body>
    <div class="payslip">
        <div class="payslip-header">
            <img src="' . $logoSrc . '" alt="Company Logo" class="company-logo">
            <div class="company-name">Multi Axis Handlers & Tech Inc</div>
            <div class="payslip-title">PAYSLIP</div>
        </div>
        
        <div class="employee-info">
            <p><strong>Employee ID:</strong> ' . htmlspecialchars($row['employee_id']) . '</p>
            <p><strong>Name:</strong> ' . htmlspecialchars($row['name']) . '</p>
            <p><strong>Department:</strong> ' . htmlspecialchars($row['department']) . '</p>
            <p><strong>Pay Period:</strong> ' . ucfirst(htmlspecialchars($row['pay_period'])) . '</p>
            <p><strong>Date Range:</strong> ' . date('M d, Y', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date'])) . '</p>
           <p><strong>Generated Date:</strong> ' . 
                (!empty($row['created_ats']) ? date('M d, Y', strtotime($row['created_ats'])) : "N/A") . 
            '</p>

        </div>

        <div class="pay-section">
            <h4>EARNINGS</h4>
            <table class="pay-table">
                <tr>
                    <td class="description">Basic Salary</td>
                    <td class="amount">P' . number_format((float)($row['basic_salary'] ?? 0), 2) . '</td>

                </tr>';
        
        if ($row['overtime_hours'] > 0) {
            $html .= '
                <tr>
                    <td class="description">Overtime (' . $row['overtime_hours'] . ' hrs)</td>
                    <td class="amount">P' . number_format((float)($overtimePay ?? 0), 2) . '</td>

                </tr>';
        }
        
        $html .= '
                <tr>
                    <td class="description"><strong>Total Earnings</strong></td>
                    <td class="amount"><strong>P' . number_format((float)($totalEarnings ?? 0), 2) . '</strong></td>

                </tr>
            </table>
        </div>

        <div class="pay-section">
            <h4>DEDUCTIONS</h4>
            <table class="pay-table">';
        
        $deductions = [
            'SSS Premium' => $row['sss_premium'],
            'SSS Loan' => $row['sss_loan'],
            'Pag-IBIG Premium' => $row['pagibig_premium'],
            'Pag-IBIG Loan' => $row['pagibig_loan'],
            'PhilHealth' => $row['philhealth'],
            'Cash Advance' => $row['cash_advance'],
            'Late Deduction' => $row['late_deduction'],
            'Absent Deduction' => $row['absent_deduction'],
            'Undertime Deduction' => $row['undertime_deduction']
        ];
        
        foreach ($deductions as $desc => $amount) {
            if ($amount > 0) {
                $html .= '
                <tr>
                    <td class="description">' . $desc . '</td>
                    <td class="amount">P' . number_format((float)($amount ?? 0), 2) . '</td>

                </tr>';
            }
        }
        
        $html .= '
                <tr>
                    <td class="description"><strong>Total Deductions</strong></td>
                   <td class="amount"><strong>P' . number_format((float)($totalDeductions ?? 0), 2) . '</strong></td>

                </tr>
            </table>
        </div>

        <div class="summary-section">
            <div class="net-pay-row">
                <span class="net-pay-label">NET PAY:</span>
                <span class="net-pay-amount">P' . number_format((float)($netPay ?? 0), 2) . '</span>

            </div>
        </div>

        <div class="signature-section">
            <p>This is a computer-generated payslip.</p>
            <p>Generated on: ' . date('F j, Y') . '</p>
            <div class="signature-line"></div>
            <p>Employee Signature</p>
        </div>
    </div>
</body>
</html>';

        // Generate PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Generate filename with employee name and date
        $filename = 'payslip_' . str_replace(' ', '_', strtolower($row['name'])) . '_' . date('Ymd') . '.pdf';
        $filepath = 'payslips/' . $filename;

        // Save PDF to file
        file_put_contents($filepath, $dompdf->output());

        // Stream the PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $dompdf->output();
        exit();
    } else {
        echo 'No payslip found for the selected criteria.';
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Close database connection
mysqli_close($conn);
?>

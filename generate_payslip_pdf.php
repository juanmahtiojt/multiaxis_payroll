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
    $options->set('defaultCharset', 'UTF-8');
    $options->set('isFontSubsettingEnabled', false);
    
    // Add font directory for better font support
    $options->set('fontDir', __DIR__ . '/vendor/dompdf/dompdf/lib/fonts/');

$dompdf = new Dompdf($options);

// Get parameters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$payroll_period = $_GET['payroll_period'] ?? '';

if (empty($start_date) || empty($end_date) || empty($payroll_period)) {
    die('Missing required parameters');
}

try {
    $servername = "localhost";
    $username = "root";
    $password = "cvsuOJT@2025";
    $dbname = "multiaxis_payroll_system";
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Calculate date difference
    $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;

    // Auto-detect payroll period
    if ($days <= 7) {
        $selected_period = 'weekly';
    } elseif ($days >= 13 && $days <= 17) {
        $selected_period = 'semi-monthly';
    } elseif ($days >= 28 && $days <= 31) {
        $selected_period = 'fixed';
    } else {
        $selected_period = 'custom';
    }

    // Fetch payslip data
    $stmt = $conn->prepare("
        SELECT * FROM payroll_records 
        WHERE pay_period = :pay_period
        AND (
            start_date <= :end_date 
            AND end_date >= :start_date
        )
        ORDER BY start_date ASC
    ");
    $stmt->bindParam(':pay_period', $selected_period);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $payslip_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($payslip_result) > 0) {
        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Payslip Report - Multi Axis Handlers & Tech Inc</title>
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

/* Container for 2 payslips per page */
.payslip-container {
    width: 100%;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 5mm;
    page-break-after: always;
}

.payslip {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    page-break-inside: avoid;
    break-inside: avoid;
    height: 125mm;
    display: flex;
    flex-direction: column;
}

.payslip-header {
    text-align: center;
    margin-bottom: 6px;
    border-bottom: 1px solid #2c3e50;
    padding-bottom: 4px;
}

.company-name {
    font-size: 11px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 1px;
}
.company-logo {
    max-width: 90px; 
    max-height: 65px; 
    margin-bottom: 4px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
    background-color: #fff;

}

.payslip-title {
    font-size: 10px;
    font-weight: bold;
    color: #34495e;
    margin-bottom: 5px;
}

.employee-info {
    margin-bottom: 6px;
}

.employee-info p {
    margin: 1px 0;
    font-size: 8px;
}

.employee-info strong {
    color: #2c3e50;
}

.pay-section {
    margin-bottom: 5px;
}

.pay-section h4 {
    font-size: 9px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 3px;
    border-bottom: 1px solid #ecf0f1;
    padding-bottom: 2px;
}

.pay-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3px;
}

.pay-table td {
    padding: 1px 0;
    font-size: 8px;
}

.pay-table .description {
    text-align: left;
    color: #555;
}

.pay-table .amount {
    text-align: right;
    font-family: monospace;
    font-weight: 500;
}

.pay-table .subtotal {
    font-weight: bold;
    color: #2c3e50;
    border-top: 1px solid #bdc3c7;
    padding-top: 2px;
}

.summary-section {
    margin-top: auto;
    padding-top: 5px;
    border-top: 1px solid #2c3e50;
}

.net-pay-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
    background-color: #f8f9fa;
    border-radius: 3px;
    margin-top: 3px;
}

.net-pay-label {
    font-size: 9px;
    font-weight: bold;
    color: #2c3e50;
}

.net-pay-amount {
    font-size: 10px;
    font-weight: bold;
    color: #27ae60;
}

.deductions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    font-size: 7px;
}

.deduction-item {
    display: flex;
    justify-content: space-between;
    padding: 1px 0;
}

.deduction-item .deduction-name {
    color: #555;
}

.deduction-item .deduction-amount {
    font-family: monospace;
    font-weight: 500;
}
</style>
</head>
<body>
';

        $count = 0;
        $totalPayslips = count($payslip_result);
        
        // Start the payslip container
        $html .= '<div class="payslip-container">';
        
        foreach ($payslip_result as $row) {
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

            // Start a new container for every 2 payslips
            if ($count % 2 == 0 && $count > 0) {
                $html .= '</div><div class="payslip-container">';
            } elseif ($count == 0) {
                $html .= '<div class="payslip-container">';
            }
            $logoPath = __DIR__ . "/my_project/images/MULTI-removebg-preview.png";
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/png;base64,' . $logoData;

            $html .= '
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
                    <p><strong>Pay Period:</strong> ' . ucfirst(htmlspecialchars($selected_period)) . '</p>
                    <p><strong>Date Range:</strong> ' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . '</p>
                </div>

                <div class="pay-section">
                    <h4>EARNINGS</h4>
                    <table class="pay-table">
                        <tr>
                            <td>Basic Salary</td>
                            <td>P' . number_format($row['basic_salary'], 2) . '</td>
                        </tr>';
            
            if ($row['overtime_hours'] > 0) {
                $html .= '
                        <tr>
                            <td>Overtime (' . $row['overtime_hours'] . ' hrs)</td>
                            <td>P' . number_format($overtimePay, 2) . '</td>
                        </tr>';
            }
            
            $html .= '
                        <tr>
                            <td><strong>Total Earnings</strong></td>
                            <td><strong>P' . number_format($totalEarnings, 2) . '</strong></td>
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
                            <td>' . $desc . '</td>
                            <td>P' . number_format($amount, 2) . '</td>
                        </tr>';
                }
            }
            
            $html .= '
                        <tr>
                            <td><strong>Total Deductions</strong></td>
                            <td><strong>P' . number_format($totalDeductions, 2) . '</strong></td>
                        </tr>
                    </table>
                </div>

                <div class="summary-section">
                </div>
                <p><strong>NET PAY: P' . number_format($netPay, 2) . '</strong></p>
            </div>';
            
            $count++;
        }
        
        // Close the final container
        $html .= '</div></body></html>';

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

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>

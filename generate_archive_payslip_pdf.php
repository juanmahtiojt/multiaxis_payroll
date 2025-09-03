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
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Get parameters for archive generation
$generation_date = $_GET['generation_date'] ?? '';
$archived = $_GET['archived'] ?? 'all';

if (empty($generation_date)) {
    die('Missing generation date parameter');
}

try {
    if ($archived === 'all') {
        // Do not filter by archived
        $query = "SELECT * FROM payroll_records 
                  WHERE DATE(COALESCE(created_ats, created_at)) = ?
                  ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $generation_date);
    } else {
        // Filter by archived 0 or 1
        $archived = (int)$archived; // ensure integer
        $query = "SELECT * FROM payroll_records 
                  WHERE DATE(COALESCE(created_ats, created_at)) = ? 
                  AND archived = ?
                  ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $generation_date, $archived);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if (mysqli_num_rows($result) > 0) {
        $payslips = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $payslips[] = $row;
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Payslip Report - ' . date('F j, Y', strtotime($generation_date)) . '</title>
<style>
@page {
    size: A4;
    margin: 5mm;
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

.payslip-container {
    page-break-after: always;
}

table.grid {
    width: 90%;
    border-collapse: separate;
    border-spacing: 5mm 5mm; /* gap between payslips */
}

table.grid td {
    width: 50%;
    vertical-align: top;
}

.payslip {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 8px;
    box-sizing: border-box;
    height: 110mm; /* ensures 2 rows fit per page */
    page-break-inside: avoid;
    width: 65mm;
}}


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
    color:  #27ae60;;
}

.report-header {
    text-align: center;
    margin-bottom: 20px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.report-title {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.report-date {
    font-size: 14px;
    color: #666;
}

.report-count {
    font-size: 12px;
    color: #888;
}
</style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">Payslip Report</div>
        <div class="report-date">Generated on: ' . date('F j, Y', strtotime($generation_date)) . '</div>
        <div class="report-count">Total Payslips: ' . count($payslips) . '</div>
    </div>';

        // $count = 0;
        // $totalPayslips = count($payslips);

        // // Start the payslip container
        // $html .= '<div class="payslip-container">';

        // foreach ($payslips as $row) {
        //     // Calculate overtime & totals
        //     $overtimePay = $row['overtime_pay'];
        //     if ((empty($overtimePay) || $overtimePay == 0) && $row['overtime_hours'] > 0 && $row['overtime_rate'] > 0) {
        //         $overtimePay = $row['overtime_hours'] * $row['overtime_rate'];
        //     }
        //     $totalEarnings = $row['basic_salary'] + $overtimePay;
        //     $totalDeductions = 
        //         $row['sss_premium'] + $row['sss_loan'] + $row['pagibig_premium'] + 
        //         $row['pagibig_loan'] + $row['philhealth'] + $row['cash_advance'] + 
        //         $row['late_deduction'] + $row['absent_deduction'] + $row['undertime_deduction'];
        //     $netPay = $totalEarnings - $totalDeductions;

        //     // Start a new container for every 2 payslips
        //     if ($count % 2 == 0 && $count > 0) {
        //         $html .= '</div><div class="payslip-container">';
        //     } elseif ($count == 0) {
        //         $html .= '<div class="payslip-container">';
        //     }

        //     $logoPath = __DIR__ . "/my_project/images/MULTI-removebg-preview.png";
        //     if (file_exists($logoPath)) {
        //         $logoData = base64_encode(file_get_contents($logoPath));
        //         $logoSrc = 'data:image/png;base64,' . $logoData;
        //     } else {
        //         $logoSrc = '';
        //     }

        //     $html .= '
        //     <div class="payslip">
        //         <div class="payslip-header">
        //             <img src="' . $logoSrc . '" alt="Company Logo" class="company-logo">
        //             <div class="company-name">Multi Axis Handlers & Tech Inc</div>
        //             <div class="payslip-title">PAYSLIP</div>
        //         </div>

        //         <div class="employee-info">
        //             <p><strong>Employee ID:</strong> ' . htmlspecialchars($row['employee_id']) . '</p>
        //             <p><strong>Name:</strong> ' . htmlspecialchars($row['name']) . '</p>
        //             <p><strong>Department:</strong> ' . htmlspecialchars($row['department']) . '</p>
        //             <p><strong>Pay Period:</strong> ' . ucfirst(htmlspecialchars($row['pay_period'])) . '</p>
        //             <p><strong>Date Range:</strong> ' . date('M d, Y', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date'])) . '</p>
        //         </div>

        //         <div class="pay-section">
        //             <h4>EARNINGS</h4>
        //             <table class="pay-table">
        //                 <tr>
        //                     <td>Basic Salary</td>
        //                     <td>P' . number_format($row['basic_salary'], 2) . '</td>
        //                 </tr>';

        //     if ($row['overtime_hours'] > 0) {
        //         $html .= '
        //                 <tr>
        //                     <td>Overtime (' . $row['overtime_hours'] . ' hrs)</td>
        //                     <td>P' . number_format($overtimePay, 2) . '</td>
        //                 </tr>';
        //     }

        //     $html .= '
        //                 <tr>
        //                     <td><strong>Total Earnings</strong></td>
        //                     <td><strong>P' . number_format($totalEarnings, 2) . '</strong></td>
        //                 </tr>
        //             </table>
        //         </div>  

        //         <div class="pay-section">
        //             <h4>DEDUCTIONS</h4>
        //             <table class="pay-table">';

        //     $deductions = [
        //         'SSS Premium' => $row['sss_premium'],
        //         'SSS Loan' => $row['sss_loan'],
        //         'Pag-IBIG Premium' => $row['pagibig_premium'],
        //         'Pag-IBIG Loan' => $row['pagibig_loan'],
        //         'PhilHealth' => $row['philhealth'],
        //         'Cash Advance' => $row['cash_advance'],
        //         'Late Deduction' => $row['late_deduction'],
        //         'Absent Deduction' => $row['absent_deduction'],
        //         'Undertime Deduction' => $row['undertime_deduction']
        //     ];

        //     foreach ($deductions as $desc => $amount) {
        //         if ($amount > 0) {
        //             $html .= '
        //                 <tr>
        //                     <td>' . $desc . '</td>
        //                     <td>P' . number_format($amount, 2) . '</td>
        //                 </tr>';
        //         }
        //     }

        //     $html .= '
        //                 <tr>
        //                     <td><strong>Total Deductions</strong></td>
        //                     <td><strong>P' . number_format($totalDeductions, 2) . '</strong></td>
        //                 </tr>
        //             </table>
        //         </div>

        //         <div class="summary-section">
        //         </div>
        //         <p><strong>NET PAY: P' . number_format($netPay, 2) . '</strong></p>
        //     </div>';

        //     $count++;
        // }

        // // Close the final container
        // $html .= '</div></body></html>';



        $count = 0;
        $totalPayslips = count($payslips);

        $html .= '<table class="grid" style="width:100%; border-spacing:5mm;">'; // Start first page

        foreach ($payslips as $row) {

            if ($count % 2 == 0) {
                $html .= '<tr>'; // Start new row every 2 payslips
            }

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

            // Logo
            $logoPath = __DIR__ . "/my_project/images/MULTI-removebg-preview.png";
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoSrc = 'data:image/png;base64,' . $logoData;
            } else {
                $logoSrc = '';
            }

            // Payslip cell
            $html .= '<td style="width:50%; vertical-align:top;">
        <div class="payslip" style="border:1px solid #dee2e6; border-radius:6px; padding:8px; height:110mm; box-sizing:border-box;">
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
            </div>

            <div class="pay-section">
                <h4>EARNINGS</h4>
                <table class="pay-table">
                    <tr><td>Basic Salary</td><td>P' . number_format($row['basic_salary'], 2) . '</td></tr>';

            if ($row['overtime_hours'] > 0) {
                $html .= '<tr><td>Overtime (' . $row['overtime_hours'] . ' hrs)</td><td>P' . number_format($overtimePay, 2) . '</td></tr>';
            }

            $html .= '<tr><td><strong>Total Earnings</strong></td><td><strong>P' . number_format($totalEarnings, 2) . '</strong></td></tr>
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
                    $html .= '<tr><td>' . $desc . '</td><td>P' . number_format($amount, 2) . '</td></tr>';
                }
            }

            $html .= '<tr><td><strong>Total Deductions</strong></td><td><strong>P' . number_format($totalDeductions, 2) . '</strong></td></tr>
                </table>
            </div>

            <p><strong>NET PAY: P' . number_format($netPay, 2) . '</strong></p>
        </div>
    </td>';

            if ($count % 2 == 1) {
                $html .= '</tr>'; // Close row every 2 payslips
            }

            // Force page break after every 4 payslips
            if (($count + 1) % 4 == 0 && ($count + 1) < $totalPayslips) {
                $html .= '</table><div style="page-break-after: always;"></div><table style="width:100%; border-spacing:5mm;">';
            }

            $count++; // ✅ increment count here
        }

        // If the last row has only 1 payslip, close it
        if ($count % 2 != 0) {
            $html .= '<td></td></tr>';
        }

        $html .= '</table>'; // Close table



        // Generate PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Generate filename with date
        $status = $archived ? 'archived' : 'active';
        $filename = 'payslip_report_' . $status . '_' . str_replace('-', '', $generation_date) . '.pdf';
        $filepath = 'payslips/' . $filename;

        // Save PDF to file
        file_put_contents($filepath, $dompdf->output());

        // Stream the PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $dompdf->output();
        exit();
    } else {
        echo 'No payslips found for the selected date.';
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

// Close database connection
mysqli_close($conn);

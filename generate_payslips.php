<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Check if attendance data exists and form submitted
if (
    (!isset($_SESSION['attendance_data']) && !isset($_SESSION['attendance_data_monthly'])) 
    || !isset($_POST['generate_payslips'])
) {
    $_SESSION['error'] = "No attendance data available to generate payslips.";
    // Redirect to appropriate page depending on which data is missing
    if (isset($_SESSION['attendance_data_monthly'])) {
        header("Location: employee_attendance_monthly.php");
    } else {
        header("Location: employee_attendance.php");
    }
    exit();
}

$pay_period = $_POST['pay_period'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

if (empty($pay_period) || empty($start_date) || empty($end_date)) {
    $_SESSION['error'] = "Please provide pay period and date range.";
    // Redirect depending on what data is present
    if (isset($_SESSION['attendance_data_monthly'])) {
        header("Location: employee_attendance_monthly.php");
    } else {
        header("Location: employee_attendance.php");
    }
    exit();
}

// === Proceed to generate payslip here ===
// (Your existing logic for generating payslip)

        // After successful generation, add activity log
        $username = $_SESSION['user'];
        $activity = "Generated payslips for pay period: $pay_period ($start_date to $end_date)";
        $page = basename(__FILE__);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $timestamp = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
        $stmt->execute();
        $stmt->close();

// Redirect or render your payslip page
header("Location: view_payslips.php?msg=payslips_generated");
exit();

// Function to calculate wage details similar to the one in employee_attendance.php
function getDailyRate($id_no, $conn) {
    $sql = "SELECT daily_rate FROM daily_rate WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $stmt->bind_result($dailyRate);
    $stmt->fetch();
    $stmt->close();
    return (float)$dailyRate;
}

// Get all rates from the database for different scenarios
function getRates($conn) {
    // Get Sunday rates
    $sql = "SELECT regular_multiplier, overtime_multiplier FROM sunday_rates LIMIT 1";
    $result = $conn->query($sql);
    $rates = $result->fetch_assoc();
    
    // Convert to decimal
    $rates['regular_multiplier'] = (float)$rates['regular_multiplier'];
    $rates['overtime_multiplier'] = (float)$rates['overtime_multiplier'];
    
    // Get holiday rates - fetch all holidays with their rates
    $sql = "SELECT holiday_date, description, holiday_type, 
                  regular_rate, overtime_rate, 
                  restdayholiday_regular, restdayholiday_overtime, 
                  restdayholiday_special, restdayspecialholiday_overtime 
           FROM holidays";
    $result = $conn->query($sql);
    
    $holidays = [];
    $holidayRates = [
        'Regular' => [],
        'Special' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        // Store holiday information with date as key
        if (isset($row['holiday_date'])) {
            $holidayDate = $row['holiday_date'];
            $holidays[$holidayDate] = [
                'type' => $row['holiday_type'],
                'description' => $row['description'] ?? '',
                'rates' => []
            ];
            
            // Store the specific rates for this holiday date
            foreach (['regular_rate', 'overtime_rate', 'restdayholiday_regular', 
                     'restdayholiday_overtime', 'restdayholiday_special', 
                     'restdayspecialholiday_overtime'] as $rateKey) {
                if (isset($row[$rateKey]) && $row[$rateKey] !== null) {
                    $holidays[$holidayDate]['rates'][$rateKey] = (float)$row[$rateKey];
                }
            }
        }
        
        // Store general rates by holiday type (as fallback)
        $type = $row['holiday_type'];
        if (!isset($holidayRates[$type]['regular_rate']) && isset($row['regular_rate'])) {
            $holidayRates[$type]['regular_rate'] = (float)$row['regular_rate'];
        }
        if (!isset($holidayRates[$type]['overtime_rate']) && isset($row['overtime_rate'])) {
            $holidayRates[$type]['overtime_rate'] = (float)$row['overtime_rate'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_regular']) && isset($row['restdayholiday_regular'])) {
            $holidayRates[$type]['restdayholiday_regular'] = (float)$row['restdayholiday_regular'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_overtime']) && isset($row['restdayholiday_overtime'])) {
            $holidayRates[$type]['restdayholiday_overtime'] = (float)$row['restdayholiday_overtime'];
        }
        if (!isset($holidayRates[$type]['restdayholiday_special']) && isset($row['restdayholiday_special'])) {
            $holidayRates[$type]['restdayholiday_special'] = (float)$row['restdayholiday_special'];
        }
        if (!isset($holidayRates[$type]['restdayspecialholiday_overtime']) && isset($row['restdayspecialholiday_overtime'])) {
            $holidayRates[$type]['restdayspecialholiday_overtime'] = (float)$row['restdayspecialholiday_overtime'];
        }
    }
    
    // Add default rates if not found in database
    if (empty($holidayRates['Regular'])) {
        $holidayRates['Regular'] = [
            'regular_rate' => 1.0,
            'overtime_rate' => 1.3,     
            'restdayholiday_regular' => 1.6,
            'restdayholiday_overtime' => 1.95
        ];
    }
    
    if (empty($holidayRates['Special'])) {
        $holidayRates['Special'] = [
            'regular_rate' => 1.0,
            'overtime_rate' => 1.3,
            'restdayholiday_special' => 1.5,
            'restdayspecialholiday_overtime' => 1.95
        ];
    }
    
    $rates['holiday_rates'] = $holidayRates;
    return [
        'rates' => $rates,
        'holidays' => $holidays
    ];
}

// Get holiday rate multipliers for a specific date
function getHolidayMultipliers($date, $isSunday, $holidays) {
    // Default values for regular days
    $regularMultiplier = 1.0;
    $overtimeMultiplier = 1.25;
    
    if (isset($holidays[$date])) {
        $holidayInfo = $holidays[$date];
        $holidayType = $holidayInfo['type'];
        $specificRates = $holidayInfo['rates'];
        
        if ($isSunday) {
            // Rest day + holiday combination
            if ($holidayType == 'Regular') {
                // Rest day + Regular holiday
                $regularMultiplier = $specificRates['restdayholiday_regular'] ?? 2.6;
                $overtimeMultiplier = $specificRates['restdayholiday_overtime'] ?? 3.38;
            } else {
                // Rest day + Special holiday
                $regularMultiplier = $specificRates['restdayholiday_special'] ?? 1.5;
                $overtimeMultiplier = $specificRates['restdayspecialholiday_overtime'] ?? 1.95;
            }
        } else {
            // Just a holiday
            if ($holidayType == 'Regular') {
                $regularMultiplier = $specificRates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $specificRates['overtime_rate'] ?? 1.3;
            } else {
                $regularMultiplier = $specificRates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $specificRates['overtime_rate'] ?? 1.3;
            }
        }
    } elseif ($isSunday) {
        // Just a Sunday/rest day - use Sunday rates
        global $sundayRates;
        $regularMultiplier = $sundayRates['regular_multiplier'];
        $overtimeMultiplier = $sundayRates['overtime_multiplier'];
    }
    
    return [
        'regular' => $regularMultiplier,
        'overtime' => $overtimeMultiplier
    ];
}

// Precompute all Saturdays of the year
function getSaturdays($year) {
    $saturdays = [];
    $date = DateTime::createFromFormat('Y-m-d', "$year-01-01");
    while ($date->format('Y') == $year) {
        if ($date->format('N') == 6) {  // Saturday
            $saturdays[] = $date->format('Y-m-d');
        }
        $date->modify('+1 day');
    }
    return $saturdays;
}

// Get all rates and holidays
$ratesData = getRates($conn);
$allRates = $ratesData['rates'];
$holidays = $ratesData['holidays'];
$sundayRates = [
    'regular_multiplier' => $allRates['regular_multiplier'],
    'overtime_multiplier' => $allRates['overtime_multiplier']
];
$saturdays = getSaturdays(date('Y'));

// Process attendance data to generate payslips
$employeeData = $_SESSION['attendance_data'];

// Modified grouping logic to handle duplicates
$groupedByEmployee = [];
$processedDates = []; // Track processed dates for each employee

foreach ($employeeData as $employee) {
    if (isset($employee['dates'])) {
        foreach ($employee['dates'] as $i => $date) {
            $employeeKey = $employee['name'];
            $dateKey = $date;
            $uniqueKey = $employeeKey . '_' . $dateKey;
            
            // Get time values
            $amIn = $employee['am_in'][$i] ?? '';
            $amOut = $employee['am_out'][$i] ?? '';
            
            // If we haven't processed this date for this employee yet, or
            // if this entry has both am_in and am_out and the previous one didn't
            if (!isset($processedDates[$employeeKey][$dateKey]) || 
                ((!empty($amIn) && !empty($amOut)) && 
                 (empty($processedDates[$employeeKey][$dateKey]['am_in']) || 
                  empty($processedDates[$employeeKey][$dateKey]['am_out'])))) {
                
                // Store this entry's details
                $processedDates[$employeeKey][$dateKey] = [
                    'am_in' => $amIn,
                    'am_out' => $amOut
                ];
                
                // Either create or update the entry
                $entryData = [
                    'id_no'      => $employee['id_no'],
                    'department' => $employee['department'],
                    'name'       => $employee['name'],
                    'date'       => $date,
                    'am_in'      => $amIn,
                    'am_out'     => $amOut
                ];
                
                // Remove any previous entry for this date if it exists
                if (isset($groupedByEmployee[$employeeKey])) {
                    foreach ($groupedByEmployee[$employeeKey] as $key => $entry) {
                        if ($entry['date'] === $dateKey) {
                            unset($groupedByEmployee[$employeeKey][$key]);
                        }
                    }
                }
                
                // Add the new entry
                $groupedByEmployee[$employeeKey][] = $entryData;
            }
        }
    }
}

// Reindex arrays after potentially removing elements
foreach ($groupedByEmployee as $employeeKey => $entries) {
    $groupedByEmployee[$employeeKey] = array_values($entries);
}

// Process each employee and create payslip records
$successCount = 0;
$errors = [];

foreach ($groupedByEmployee as $employeeName => $entries) {
    try {
        // Get employee ID
        $id_no = $entries[0]['id_no'];
        $dailyRate = getDailyRate($id_no, $conn);

        // Sort entries by date
        usort($entries, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // Initialize payslip calculations
        $totalWage = 0;
        $totalRegularPay = 0;
        $totalUndertime = 0;
        $totalOvertime = 0;
        $totalOvertimeHours = 0;
        $totalDaysPresent = 0;
        $totalAbsences = 0;
        $totalRestDayPay = 0;
        $totalRegularHolidayPay = 0;
        $totalRegularOTPay = 0;
        $totalSpecialHolidayPay = 0;
        $lateMinutes = 0;

        // Process each attendance record
        foreach ($entries as $row) {
            $amIn = $row['am_in'];
            $amOut = $row['am_out'];
            
            $dateCheck = $row['date'];
            $dateObj = DateTime::createFromFormat('Y-m-d', $dateCheck);
            $isSunday = $dateObj && $dateObj->format('N') == 7;
            $isHoliday = isset($holidays[$dateCheck]);
            $holidayInfo = $isHoliday ? $holidays[$dateCheck] : null;
            $holidayType = $isHoliday ? $holidayInfo['type'] : '';
            $holidayDesc = $isHoliday ? $holidayInfo['description'] : '';
            $isSaturday = in_array($dateCheck, $saturdays);

            // Get multipliers based on date and day type
            $multipliers = getHolidayMultipliers($dateCheck, $isSunday, $holidays);
            $regularMultiplier = $multipliers['regular'];
            $overtimeMultiplier = $multipliers['overtime'];
            
            // Set the basic daily rate
            $baseDailyRate = $dailyRate;
            $dailyWage = $baseDailyRate * $regularMultiplier; // Apply multiplier immediately

            if (!empty($amIn) && !empty($amOut)) {
                $inTime = DateTime::createFromFormat('H:i', $amIn);
                $outTime = DateTime::createFromFormat('H:i', $amOut);

                if ($inTime && $outTime) {
                    $interval = $inTime->diff($outTime);
                    $workedHours = $interval->h + $interval->i / 60;

                    // Subtract 1 hour for lunch if not Saturday, Sunday, or holiday
                    if (!$isSaturday && !$isHoliday && !$isSunday) {
                        $workedHours -= 1;
                    }

                    // Subtract 1 hour if it's a holiday
                    if ($isHoliday) {
                        $workedHours -= 1;
                    }

                    $workedHours = max(0, $workedHours);

                    if ($workedHours > 0) {
                        // Track pay based on day type
                        if ($isSunday && $isHoliday) {
                            if ($holidayType == 'Regular') {
                                // Rest day + Regular holiday
                                $totalRegularHolidayPay += $dailyWage;
                            } else {
                                // Rest day + Special holiday
                                $totalSpecialHolidayPay += $dailyWage;
                            }
                        } elseif ($isSunday) {
                            $totalRestDayPay += $dailyWage;
                        } elseif ($isHoliday) {
                            if ($holidayType == 'Regular') {
                                $totalRegularHolidayPay += $dailyWage;
                            } else {
                                $totalSpecialHolidayPay += $dailyWage;
                            }
                        } else {
                            $totalRegularPay += $dailyWage;
                        }

                        // Calculate undertime or overtime
                        if ($workedHours < 8) {
                            // Calculate undertime in minutes
                            $undertimeMinutes = (8 - $workedHours) * 60;
                            
                            // Round undertime to 30-minute increments if at least 11 minutes
                            if ($undertimeMinutes >= 11) {
                                $roundedUndertime = ceil($undertimeMinutes / 30) * 0.5; // Convert to hours in 0.5 increments
                                $undertimeAmount = $roundedUndertime * ($baseDailyRate / 8);
                                $totalUndertime += $undertimeAmount;
                            }
                        } elseif ($workedHours > 8) {
                            $overtimeHours = floor($workedHours - 8);
                            if ($overtimeHours >= 1) {
                                $overtimeAmount = $overtimeHours * ($baseDailyRate / 8) * $overtimeMultiplier;
                                error_log("DEBUG: Overtime for {$row['name']} on {$row['date']}: baseDailyRate={$baseDailyRate}, overtimeMultiplier={$overtimeMultiplier}, overtimeHours={$overtimeHours}, overtimeAmount={$overtimeAmount}");
                                $totalOvertime += $overtimeAmount;
                                $totalOvertimeHours += $overtimeHours;
                                if ($isHoliday && $holidayType == 'Regular') {
                                    $totalRegularOTPay += $overtimeAmount;
                                }
                            }
                        }

                        $totalDaysPresent++;
                    } else {
                        // No hours worked = absent
                        if ($isSunday) {
                            // Rest day - no action needed
                        } elseif ($isHoliday) {
                            // Holiday but not worked
                            if ($holidayType == 'Regular') {
                                // Regular holiday still pays
                                $totalRegularHolidayPay += $baseDailyRate;
                                $totalWage += $baseDailyRate;
                            }
                        } else {
                            $totalAbsences++;
                        }
                    }
                } else {
                    // Invalid time format
                    if ($isSunday) {
                        // Rest day - no action needed
                    } elseif ($isHoliday && $holidayType === 'Regular') {
                        // Regular holiday still pays even if not worked
                        $totalRegularHolidayPay += $baseDailyRate;
                        $totalWage += $baseDailyRate;
                    } else {
                        // Absent on regular day
                        $totalAbsences++;
                    }
                }
            } else {
                // No time entries
                if ($isHoliday && $holidayType === 'Regular') {
                    // Regular holiday pays base rate even if absent
                    $totalRegularHolidayPay += $baseDailyRate;
                    $totalWage += $baseDailyRate;
                } elseif (!$isSunday) {
                    // Absent on regular day
                    $totalAbsences++;
                }
            }
        }

        // Get employee details from employees table
        $stmt = $conn->prepare("SELECT sss_no, pagibig_no, tin_no, philhealth_no, 
                                sss_premium, sss_loan, pagibig_premium, pagibig_loan, 
                                philhealth, cash_advance, leave_with_pay, leave_without_pay, 
                                available_leave FROM employees WHERE id_no = ?");
        $stmt->bind_param("s", $id_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $employeeDetails = $result->fetch_assoc();
        $stmt->close();

        if (!$employeeDetails) {
            throw new Exception("Employee details not found for ID: $id_no");
        }

        // Calculate total earnings and deductions
        $totalEarnings = $totalRegularPay + $totalOvertime + $totalRestDayPay + 
                         $totalRegularHolidayPay + $totalRegularOTPay + $totalSpecialHolidayPay;
        
        $totalDeductions = $employeeDetails['sss_premium'] + $employeeDetails['sss_loan'] + 
                          $employeeDetails['pagibig_premium'] + $employeeDetails['pagibig_loan'] + 
                          $employeeDetails['philhealth'] + $employeeDetails['cash_advance'] + 
                          $totalUndertime;
        
        $netPay = $totalEarnings - $totalDeductions;

        // Check if a payslip already exists for this period and employee
        $checkStmt = $conn->prepare("SELECT employee_id FROM employee_payslips WHERE employee_id = ? AND start_date = ? AND end_date = ?");
        $checkStmt->bind_param("sss", $id_no, $start_date, $end_date);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existingPayslip = $checkResult->fetch_assoc();
        $checkStmt->close();

        if ($existingPayslip) {
            // Update existing payslip
            $updateStmt = $conn->prepare("UPDATE employee_payslips SET 
                pay_period = ?, 
                basic_salary = ?, 
                overtime_pay = ?, 
                overtime_hours = ?, 
                overtime_rate = ?, 
                rest_day_pay = ?, 
                regular_holiday_pay = ?, 
                regular_ot_pay = ?, 
                special_holiday_pay = ?, 
                thirteenth_month_pay = ?, 
                sss_premium = ?, 
                sss_loan = ?, 
                pagibig_premium = ?, 
                pagibig_loan = ?, 
                philhealth = ?, 
                cash_advance = ?, 
                late = ?, 
                absent = ?, 
                undertime = ?, 
                leave_with_pay = ?, 
                leave_without_pay = ?, 
                available_leave = ?, 
                total_earnings = ?, 
                total_deductions = ?, 
                net_pay = ? 
                WHERE employee_id = ? AND start_date = ? AND end_date = ?");
            
            $thirteenthMonth = 0; // Not calculated in this period
            $avgOvertimeRate = $totalOvertimeHours > 0 ? $totalOvertime / $totalOvertimeHours : 0;
            
            $updateStmt->bind_param(
                "sdddddddddddddddddddddddsss",
                $pay_period,
                $totalRegularPay,
                $totalOvertime,
                $totalOvertimeHours,
                $avgOvertimeRate,
                $totalRestDayPay,
                $totalRegularHolidayPay,
                $totalRegularOTPay,
                $totalSpecialHolidayPay,
                $thirteenthMonth,
                $employeeDetails['sss_premium'],
                $employeeDetails['sss_loan'],
                $employeeDetails['pagibig_premium'],
                $employeeDetails['pagibig_loan'],
                $employeeDetails['philhealth'],
                $employeeDetails['cash_advance'],
                $lateMinutes,
                $totalAbsences,
                $totalUndertime,
                $employeeDetails['leave_with_pay'],
                $employeeDetails['leave_without_pay'],
                $employeeDetails['available_leave'],
                $totalEarnings,
                $totalDeductions,
                $netPay,
                $id_no,
                $start_date,
                $end_date
            );
            
            if ($updateStmt->execute()) {
                $successCount++;
            } else {
                throw new Exception("Error updating payslip for $employeeName: " . $conn->error);
            }
            $updateStmt->close();
        } else {
            // Insert new payslip
            $insertStmt = $conn->prepare("INSERT INTO employee_payslips (
                employee_id, name, sss_no, pagibig_no, tin_no, philhealth_no, 
                pay_period, start_date, end_date,
                basic_salary, overtime_pay, overtime_hours, overtime_rate,
                rest_day_pay, regular_holiday_pay, regular_ot_pay, special_holiday_pay,
                thirteenth_month_pay, sss_premium, sss_loan, pagibig_premium, pagibig_loan,
                philhealth, cash_advance, late, absent, undertime,
                leave_with_pay, leave_without_pay, available_leave,
                total_earnings, total_deductions, net_pay
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?
            )");
            
            $thirteenthMonth = 0; // Not calculated in this period
            $avgOvertimeRate = $totalOvertimeHours > 0 ? $totalOvertime / $totalOvertimeHours : 0;
            
            $insertStmt->bind_param(
                "ssssss" . // employee details
                "sss" . // pay period
                "dddd" . // salary details
                "dddd" . // holiday pay
                "ddddd" . // benefits and loans
                "ddddd" . // deductions
                "ddd" . // leave
                "ddd", // totals
                $id_no,
                $employeeName,
                $employeeDetails['sss_no'],
                $employeeDetails['pagibig_no'],
                $employeeDetails['tin_no'],
                $employeeDetails['philhealth_no'],
                $pay_period,
                $start_date,
                $end_date,
                $totalRegularPay,
                $totalOvertime,
                $totalOvertimeHours,
                $avgOvertimeRate,
                $totalRestDayPay,
                $totalRegularHolidayPay,
                $totalRegularOTPay,
                $totalSpecialHolidayPay,
                $thirteenthMonth,
                $employeeDetails['sss_premium'],
                $employeeDetails['sss_loan'],
                $employeeDetails['pagibig'],
                $employeeDetails['pagibig_premium'],
                $employeeDetails['pagibig_loan'],
                $employeeDetails['philhealth'],
                $employeeDetails['cash_advance'],
                $lateMinutes,
                $totalAbsences,
                $totalUndertime,
                $employeeDetails['leave_with_pay'],
                $employeeDetails['leave_without_pay'],
                $employeeDetails['available_leave'],
                $totalEarnings,
                $totalDeductions,
                $netPay
            );
            
            if ($insertStmt->execute()) {
                $successCount++;
            } else {
                throw new Exception("Error inserting payslip for $employeeName: " . $conn->error);
            }
            $insertStmt->close();
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Store results in session
$_SESSION['payslip_generation'] = [
    'success_count' => $successCount,
    'errors' => $errors,
    'pay_period' => $pay_period,
    'start_date' => $start_date,
    'end_date' => $end_date
];

// Clean up session data
unset($_SESSION['attendance_data']);

// Redirect to payslip page
header("Location: view_payslips.php");
exit();
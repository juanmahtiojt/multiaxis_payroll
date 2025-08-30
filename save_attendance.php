<?php
session_start();
include __DIR__ . "/config.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$sourcePage = $_POST['source'] ?? 'employee_attendance.php';

if (!isset($_POST['save_attendance']) || 
    (!isset($_SESSION['attendance_data']) && !isset($_SESSION['attendance_data_monthly']))) {
    $_SESSION['upload_error'] = "No attendance data to save.";
    header("Location: $sourcePage");
    exit();

}
// activity log
$username = $_SESSION['user'];
$activity = "Saved attendance data";
$page = basename(__FILE__);
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$timestamp = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO activity_logs (username, activity, page, ip_address, timestamp) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $username, $activity, $page, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
} else {
    // Optionally log error or handle it here
}

// Use the correct attendance data
$employeeData = $_SESSION['attendance_data_monthly'] ?? $_SESSION['attendance_data'];

// Generate a unique batch ID for this payroll run
$batchId = 'PAY-' . date('Ymd') . '-' . uniqid();

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
            'restdayholiday_regular' => 2.6,
            'restdayholiday_overtime' => 3.38
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

// Modified function to fetch daily rate and pay schedule from the database
function getEmployeeRateInfo($id_no, $conn) {
    $sql = "SELECT daily_rate, pay_schedule FROM daily_rate WHERE id_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $empInfo = $result->fetch_assoc();
    $stmt->close();
    
    // Default values if not found
    if (!$empInfo) {
        return [
            'daily_rate' => 0,
            'pay_schedule' => 'fixed'
        ];
    }
    
    return [
        'daily_rate' => (float)$empInfo['daily_rate'],
        'pay_schedule' => $empInfo['pay_schedule']
    ];
}

// New function to fetch employee details including government contributions
function getEmployeeDetails($id_no, $conn) {
    $sql = "SELECT 
                id_no, sss_no, pagibig_no, tin_no, philhealth_no, 
                sss_premium, sss_loan, pagibig_premium, pagibig_loan, 
                philhealth, cash_advance, leave_with_pay, leave_without_pay, available_leave 
            FROM employees 
            WHERE id_no = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $empDetails = $result->fetch_assoc();
    $stmt->close();
    
    // Default values if not found
    if (!$empDetails) {
        return [
            'id_no' => $id_no,
            'sss_no' => '',
            'pagibig_no' => '',
            'tin_no' => '',
            'philhealth_no' => '',
            'sss_premium' => 0,
            'sss_loan' => 0,
            'pagibig_premium' => 0,
            'pagibig_loan' => 0,
            'philhealth' => 0,
            'cash_advance' => 0,
            'leave_with_pay' => 0,
            'leave_without_pay' => 0,
            'available_leave' => 0
        ];
    }
    
    // Convert all numeric values to float
    foreach (['sss_premium', 'sss_loan', 'pagibig_premium', 'pagibig_loan', 
             'philhealth', 'cash_advance', 'leave_with_pay', 'leave_without_pay', 
             'available_leave'] as $field) {
        $empDetails[$field] = (float)($empDetails[$field] ?? 0);
    }
    
    return $empDetails;
}

// Calculate 13th month pay (prorated based on months worked)
function calculateThirteenthMonth($basicSalary, $monthsWorked = 12) {
    $monthsWorked = min(12, max(1, $monthsWorked)); // Limit to 1-12 months
    return ($basicSalary / 12) * $monthsWorked;
}

// Calculate basic salary based on daily rate and pay schedule
function calculateBasicSalary($dailyRate, $paySchedule) {
    switch (strtolower($paySchedule)) {
        case 'weekly':
            return $dailyRate * 6; // 6 days per week
        case 'semi-monthly':
            return $dailyRate * 11; // 11 days per semi-month
        case 'fixed':
        default:
            return $dailyRate * 15; // 15 days per fixed period
    }
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

// Get all rates and holidays in one function call
$ratesData = getRates($conn);
$allRates = $ratesData['rates'];
$holidays = $ratesData['holidays'];
$sundayRates = [
    'regular_multiplier' => $allRates['regular_multiplier'],
    'overtime_multiplier' => $allRates['overtime_multiplier']
];

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

$saturdays = getSaturdays(date('Y'));

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

ksort($groupedByEmployee);

$successCount = 0;
$errorCount = 0;

// Prepare the database for a transaction
$conn->begin_transaction();
try {
    foreach ($groupedByEmployee as $employeeName => $entries) {
        // Get employee daily rate and pay schedule
        $employeeInfo = getEmployeeRateInfo($entries[0]['id_no'], $conn);
        $dailyRate = $employeeInfo['daily_rate']; 
        $paySchedule = $employeeInfo['pay_schedule'];
        
        // Get employee details including government contributions
        $employeeDetails = getEmployeeDetails($entries[0]['id_no'], $conn);
        
        // Calculate basic salary based on pay schedule
        $basicSalary = calculateBasicSalary($dailyRate, $paySchedule);
        
        // Sort entries by date
        usort($entries, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        if (count($entries) === 0) {
            continue; // Skip if no entries for employee
        }
        
        // Get date range for the pay period
        $startDate = $entries[0]['date'];
        $endDate = $entries[count($entries) - 1]['date'];
        
        // Initialize totals
        $totalWage = 0;
        $totalUndertime = 0;
        $totalOvertime = 0;
        $totalDaysPresent = 0;
        $totalAbsences = 0;
        $total_ot_hours = 0;
        $rest_day_pay = 0;
        $regular_holiday_pay = 0;
        $regular_ot_pay = 0;
        $special_holiday_pay = 0;
        $late_deduction = 0; // Included in undertime
        
        // Process each entry
        foreach ($entries as $row) {
            $amIn = $row['am_in'];
            $amOut = $row['am_out'];
            $hoursWorked = 0;
            $status = '';
            $undertime = 0;
            $overtime = 0;
            $overtimeAmount = 0;
            $undertimeAmount = 0;
            
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
                    $hoursWorked = $workedHours;
                    
                    if ($workedHours > 0) {
                        // Set status based on day type
                        if ($isSunday && $isHoliday) {
                            $status = 'Rest Day + ' . ($holidayType == 'Regular' ? 'Regular Holiday' : 'Special Holiday');
                            // Track this payment under rest day + holiday pay
                            if ($holidayType == 'Regular') {
                                $regular_holiday_pay += $dailyWage;
                            } else {
                                $special_holiday_pay += $dailyWage;
                            }
                        } elseif ($isSunday) {
                            $status = 'Rest Day (Worked)';
                            $rest_day_pay += $dailyWage;
                        } elseif ($isHoliday) {
                            $status = $holidayType . ' Holiday';
                            if ($holidayType == 'Regular') {
                                $regular_holiday_pay += $dailyWage;
                            } else {
                                $special_holiday_pay += $dailyWage;
                            }
                        } else {
                            $status = 'Present';
                            $totalWage += $dailyWage;
                        }
                        
                        // Calculate undertime or overtime
                        if ($workedHours < 8) {
                            // Calculate undertime in minutes
                            $undertimeMinutes = (8 - $workedHours) * 60;
                            
                            // Round undertime to 30-minute increments if at least 11 minutes
                            if ($undertimeMinutes >= 11) {
                                $roundedUndertime = ceil($undertimeMinutes / 30) * 0.5; // Convert to hours in 0.5 increments
                                
                                // Always use base daily rate for undertime deductions
                                $undertimeAmount = $roundedUndertime * ($baseDailyRate / 8);
                            } else {
                                $undertimeAmount = 0; // No undertime charge if less than 11 minutes
                            }
                            
                            $totalUndertime += $undertimeAmount;
                        } elseif ($workedHours > 8) {
                            // Only count full hours for overtime, ignoring partial hours
                            $overtimeHours = floor($workedHours - 8); // Use floor() to only count complete hours
                            
                            if ($overtimeHours >= 1) {
                                // Calculate overtime pay only for full hours worked beyond 8
                                $overtimeAmount = $overtimeHours * ($baseDailyRate / 8) * $overtimeMultiplier;
                                $total_ot_hours += $overtimeHours;
                                
                                // Assign overtime to the appropriate category
                                if ($isSunday && $isHoliday) {
                                    if ($holidayType == 'Regular') {
                                        $regular_ot_pay += $overtimeAmount;
                                    } else {
                                        $regular_ot_pay += $overtimeAmount; // Special holiday OT
                                    }
                                } elseif ($isSunday) {
                                    $regular_ot_pay += $overtimeAmount; // Rest day overtime
                                } elseif ($isHoliday) {
                                    if ($holidayType == 'Regular') {
                                        $regular_ot_pay += $overtimeAmount;
                                    } else {
                                        $regular_ot_pay += $overtimeAmount; // Special holiday OT
                                    }
                                } else {
                                    $totalOvertime += $overtimeAmount; // Regular overtime
                                }
                            }
                        }
                        
                        // Accumulate totals for present days
                        $totalDaysPresent++;
                    } else {
                        // No hours worked = absent
                        if ($isSunday) {
                            $status = 'Rest Day';
                            $dailyWage = 0; // No pay for rest day unless worked
                        } elseif ($isHoliday) {
                            $status = $holidayType . ' Holiday';
                            
                            if ($holidayType == 'Regular') {
                                // Regular holiday still pays even if not worked - use 100% of base rate
                                $regular_holiday_pay;
                                $dailyWage = $baseDailyRate; // Regular holiday gets 100% of daily rate
                            } else {
                                $dailyWage = 0; // Special holiday doesn't pay if not worked
                            }
                        } else {
                            $status = 'Absent';
                            $dailyWage = 0; // No pay for absent days
                            $totalAbsences++;
                        }
                    }
                } else {
                    // Invalid time format
                    if ($isSunday) {
                        $status = 'Rest Day';
                        $dailyWage = 0; // No pay for rest day unless worked
                    } elseif ($isHoliday) {
                        $status = $holidayType . ' Holiday';
                        
                        if ($holidayType == 'Regular') {
                            // Regular holiday still pays even if not worked - use base rate
                            $regular_holiday_pay;
                            $dailyWage = $baseDailyRate;
                        } else {
                            $dailyWage = 0; // No pay for special holiday unless worked
                        }
                    } else {
                        $status = 'Absent';
                        $dailyWage = 0;
                        $totalAbsences++;
                    }
                }
            } else {
                // No time entries
                if ($isHoliday && $holidayType === 'Regular') {
                    $status = 'Regular Holiday';
                    
                    // Regular holiday pays base rate even if absent
                    $dailyWage = $baseDailyRate;
                    $regular_holiday_pay += $dailyWage;
                } elseif ($isHoliday) {
                    $status = 'Special Holiday';
                    $dailyWage = 0; // No pay for special holiday unless worked
                } elseif ($isSunday) {
                    $status = 'Rest Day';
                    $dailyWage = 0; // No pay for rest day unless worked
                } else {
                    $status = 'Absent';
                    $dailyWage = 0; // No pay for absent days
                    $totalAbsences++;
                }
            }
        }
        
        // Calculate absent deduction
        $absent_deduction = $totalAbsences * $dailyRate;
        
        // Get government contributions and deductions from employee details
        $sss_premium = $employeeDetails['sss_premium'];
        $sss_loan = $employeeDetails['sss_loan'];
        $pagibig_premium = $employeeDetails['pagibig_premium'];
        $pagibig_loan = $employeeDetails['pagibig_loan'];
        $philhealth = $employeeDetails['philhealth'];
        $cash_advance = $employeeDetails['cash_advance'];
        $leave_with_pay = $employeeDetails['leave_with_pay'];
        $leave_without_pay = $employeeDetails['leave_without_pay'];
        $available_leave = $employeeDetails['available_leave'];
        
        // Calculate 13th month pay (prorated for current pay period)
        $thirteenth_month_pay = 0; // For one month
        
        // Calculate total earnings and deductions
        $total_earnings = $totalWage + $totalOvertime + $rest_day_pay + $regular_holiday_pay + $special_holiday_pay + $regular_ot_pay;
        
        // Calculate total deductions including government contributions
        $total_deductions = $totalUndertime + $sss_premium + $sss_loan + 
                          $pagibig_premium + $pagibig_loan + $philhealth + $cash_advance;
        
        // Calculate net pay with the modified formula as requested
        $net_pay = $total_earnings - $totalUndertime - $sss_premium - $sss_loan - 
                 $pagibig_premium - $pagibig_loan - $philhealth - $cash_advance;
        
        // Format the pay period description based on pay schedule
        $pay_period = "";
        switch (strtolower($paySchedule)) {
            case 'weekly':
                $pay_period = "Weekly";
                break;
            case 'semi-monthly':
                $pay_period = "Semi-Monthly";
                break;
            case 'fixed':
            default:
                $pay_period = "Fixed";
                break;
        }
        
        // Insert into payroll_records table with additional fields
        $stmt = $conn->prepare("INSERT INTO payroll_records (
            batch_id, employee_id, name, department, pay_period, 
            start_date, end_date, basic_salary, overtime_pay, overtime_hours, overtime_rate,
            rest_day_pay, regular_holiday_pay, regular_ot_pay, special_holiday_pay, 
            late_deduction, absent_deduction, undertime_deduction, 
            sss_no, pagibig_no, tin_no, philhealth_no, thirteenth_month_pay,
            sss_premium, sss_loan, pagibig_premium, pagibig_loan, philhealth, cash_advance,
            leave_with_pay, leave_without_pay, available_leave,
            total_earnings, total_deductions, net_pay, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, 
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, NOW(), NOW()
        )");
        
        $overtime_rate = $overtimeMultiplier; // Using the last overtime multiplier as default
        
        $stmt->bind_param(
            "sssssssdddddddddddssssdddddddiiiddd",
            $batchId,
            $entries[0]['id_no'],
            $employeeName,
            $entries[0]['department'],
            $pay_period,
            $startDate,
            $endDate,
            $basicSalary,  // Use the calculated basic salary based on pay schedule
            $totalOvertime,
            $total_ot_hours,
            $overtime_rate,
            $rest_day_pay,
            $regular_holiday_pay,
            $regular_ot_pay,
            $special_holiday_pay,
            $late_deduction,
            $absent_deduction,
            $totalUndertime,
            $employeeDetails['sss_no'],
            $employeeDetails['pagibig_no'],
            $employeeDetails['tin_no'],
            $employeeDetails['philhealth_no'],
            $thirteenth_month_pay,
            $sss_premium,
            $sss_loan,
            $pagibig_premium,
            $pagibig_loan,
            $philhealth,
            $cash_advance,
            $leave_with_pay,
            $leave_without_pay,
            $available_leave,
            $total_earnings,
            $total_deductions,
            $net_pay
        );
        
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
            throw new Exception("Error saving data for employee: " . $employeeName . " - " . $stmt->error);
        }
        
        $stmt->close();
    }
    
    // If we get here, commit the transaction
    $conn->commit();
    
    // Clear attendance data from session
    unset($_SESSION['attendance_data_monthly']); // <-- Comment or remove this
    unset($_SESSION['attendance_data']);         // <-- Comment or remove this
    
    // Set success message
    $_SESSION['save_success'] = "Successfully saved $successCount payroll records with batch ID: $batchId";
    
    // Redirect back to employee_attendance.php
    header("Location: $sourcePage");
    exit();
    
} catch (Exception $e) {
    // Rollback the transaction if there was an error
    $conn->rollback();
    
    // Set error message
    $_SESSION['upload_error'] = "Error saving attendance records: " . $e->getMessage();
    
    // Redirect back to employee_attendance.php
    header("Location: $sourcePage");
    exit();
}

?>
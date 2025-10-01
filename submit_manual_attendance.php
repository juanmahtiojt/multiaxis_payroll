<?php
// submit_manual_attendance.php
session_start();
include 'config.php';

// ----------------- Helper functions -----------------
function getRates($conn)
{
    // minimal rates loader — expand as needed
    $out = ['rates' => ['regular_multiplier' => 1.0, 'overtime_multiplier' => 1.25], 'holidays' => []];
    // attempt to load sunday_rates and holidays if tables exist
    $r = $conn->query("SELECT regular_multiplier,overtime_multiplier FROM sunday_rates LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $out['rates']['regular_multiplier'] = (float) $row['regular_multiplier'];
        $out['rates']['overtime_multiplier'] = (float) $row['overtime_multiplier'];
    }
    $hRes = $conn->query("SELECT holiday_date, description, holiday_type,
                                  regular_rate, overtime_rate,
                                  restdayholiday_regular, restdayholiday_overtime,
                                  restdayholiday_special, restdayspecialholiday_overtime
                           FROM holidays");
    $holidays = [];
    if ($hRes) {
        while ($hr = $hRes->fetch_assoc()) {
            if (!empty($hr['holiday_date'])) {
                $holidays[$hr['holiday_date']] = [
                    'type' => $hr['holiday_type'],
                    'description' => $hr['description'],
                    'rates' => [
                        'regular_rate' => isset($hr['regular_rate']) ? (float) $hr['regular_rate'] : null,
                        'overtime_rate' => isset($hr['overtime_rate']) ? (float) $hr['overtime_rate'] : null,
                        'restdayholiday_regular' => isset($hr['restdayholiday_regular']) ? (float) $hr['restdayholiday_regular'] : null,
                        'restdayholiday_overtime' => isset($hr['restdayholiday_overtime']) ? (float) $hr['restdayholiday_overtime'] : null,
                        'restdayholiday_special' => isset($hr['restdayholiday_special']) ? (float) $hr['restdayholiday_special'] : null,
                        'restdayspecialholiday_overtime' => isset($hr['restdayspecialholiday_overtime']) ? (float) $hr['restdayspecialholiday_overtime'] : null
                    ]
                ];
            }
        }
    }
    $out['holidays'] = $holidays;
    return $out;
}

function getEmployeeRateInfo($id_no, $conn)
{
    $stmt = $conn->prepare("SELECT daily_rate, pay_schedule FROM daily_rate WHERE id_no = ? LIMIT 1");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['daily_rate' => (float) ($r['daily_rate'] ?? 0), 'pay_schedule' => $r['pay_schedule'] ?? 'Fixed'];
}

function getEmployeeDetails($id_no, $conn)
{
    $stmt = $conn->prepare("SELECT id_no, sss_no, pagibig_no, tin_no, philhealth_no,
                                   sss_premium, sss_loan, pagibig_premium, pagibig_loan, philhealth,
                                   cash_advance, leave_with_pay, leave_without_pay, available_leave
                            FROM employees WHERE id_no = ? LIMIT 1");
    $stmt->bind_param("s", $id_no);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r) {
        // sensible defaults
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
    // normalize numeric
    foreach (['sss_premium', 'sss_loan', 'pagibig_premium', 'pagibig_loan', 'philhealth', 'cash_advance', 'leave_with_pay', 'leave_without_pay', 'available_leave'] as $f) {
        $r[$f] = (float) ($r[$f] ?? 0);
    }
    return $r;
}

function getHolidayMultipliers($date, $isSunday, $holidays, $sundayRates)
{
    // default
    $regularMultiplier = 1.0;
    $overtimeMultiplier = 1.25;

    if (isset($holidays[$date])) {
        $info = $holidays[$date];
        $type = $info['type'] ?? '';
        $rates = $info['rates'] ?? [];
        if ($isSunday) {
            if ($type === 'Regular') {
                $regularMultiplier = $rates['restdayholiday_regular'] ?? 2.6;
                $overtimeMultiplier = $rates['restdayholiday_overtime'] ?? 3.38;
            } else {
                $regularMultiplier = $rates['restdayholiday_special'] ?? 1.5;
                $overtimeMultiplier = $rates['restdayspecialholiday_overtime'] ?? 1.95;
            }
        } else {
            if ($type === 'Regular') {
                $regularMultiplier = $rates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $rates['overtime_rate'] ?? 1.3;
            } else {
                $regularMultiplier = $rates['regular_rate'] ?? 1.0;
                $overtimeMultiplier = $rates['overtime_rate'] ?? 1.3;
            }
        }
    } elseif ($isSunday) {
        $regularMultiplier = $sundayRates['regular_multiplier'] ?? 1.0;
        $overtimeMultiplier = $sundayRates['overtime_multiplier'] ?? 1.25;
    }

    return ['regular' => $regularMultiplier, 'overtime' => $overtimeMultiplier];
}

// ----------------- Load global rates/holidays -----------------
$ratesData = getRates($conn);
$holidays = $ratesData['holidays'];
$sundayRates = ['regular_multiplier' => $ratesData['rates']['regular_multiplier'], 'overtime_multiplier' => $ratesData['rates']['overtime_multiplier']];

// ----------------- Utility: get all saturdays for a year -----------------
function getSaturdays($year)
{
    $saturdays = [];
    $date = DateTime::createFromFormat('Y-m-d', "$year-01-01");
    while ($date->format('Y') == $year) {
        if ($date->format('N') == 6)
            $saturdays[] = $date->format('Y-m-d');
        $date->modify('+1 day');
    }
    return $saturdays;
}
$saturdays = getSaturdays(date('Y'));

// ----------------- Begin main processing -----------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

$manualId = intval($_POST['id']);

// fetch manual_attendance row
$stmt = $conn->prepare("SELECT * FROM manual_attendance WHERE id = ?");
$stmt->bind_param("i", $manualId);
$stmt->execute();
$manualRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$manualRow) {
    http_response_code(404);
    echo "Manual attendance not found";
    exit;
}

// decode attendance_data
$attendanceData = json_decode($manualRow['attendance_data'], true);
if (!$attendanceData)
    $attendanceData = [];

// We'll support two flows:
// 1) 'grouped' full entries (employeeData like your earlier grouping): use weekly grouping & detailed processing
// 2) fallback: single employee simple days with ot/ut only => basic calculation

// flow 1 detection: presence of 'employees' or grouped structure
$transactionOk = false;
$conn->begin_transaction();
try {
    if (isset($attendanceData['employees']) && is_array($attendanceData['employees'])) {
        // ---- FLOW 1: batch with per-employee entries ----
        // attendanceData['employees'] expected structure per your grouping logic
        $employeeData = $attendanceData['employees'];

        // build groupedByEmployee similar to your snippet
        $groupedByEmployee = [];
        $processedDates = [];
        foreach ($employeeData as $emp) {
            $name = $emp['name'] ?? ($emp['id_no'] ?? 'Unknown');
            if (!isset($emp['dates']) || !is_array($emp['dates']))
                continue;
            foreach ($emp['dates'] as $i => $date) {
                $amIn = $emp['am_in'][$i] ?? '';
                $amOut = $emp['am_out'][$i] ?? '';

                // keep latest entry with data precedence for a date
                if (
                    !isset($processedDates[$name][$date]) ||
                    ((!empty($amIn) && !empty($amOut)) && (empty($processedDates[$name][$date]['am_in']) || empty($processedDates[$name][$date]['am_out'])))
                ) {
                    $processedDates[$name][$date] = ['am_in' => $amIn, 'am_out' => $amOut];
                    // ensure grouped array replaced
                    // remove old same-date entry
                    if (!isset($groupedByEmployee[$name]))
                        $groupedByEmployee[$name] = [];
                    // remove any existing with same date
                    foreach ($groupedByEmployee[$name] as $k => $entry) {
                        if ($entry['date'] === $date)
                            unset($groupedByEmployee[$name][$k]);
                    }
                    $groupedByEmployee[$name][] = [
                        'id_no' => $emp['id_no'] ?? '',
                        'department' => $emp['department'] ?? '',
                        'name' => $name,
                        'date' => $date,
                        'am_in' => $amIn,
                        'am_out' => $amOut
                    ];
                }
            }
            // reindex
            if (isset($groupedByEmployee[$name]))
                $groupedByEmployee[$name] = array_values($groupedByEmployee[$name]);
        }

        ksort($groupedByEmployee);

        // process each employee group using your weekly grouping code
        foreach ($groupedByEmployee as $employeeName => $entries) {
            if (count($entries) == 0)
                continue;

            // get employee rate & details
            $empId = $entries[0]['id_no'] ?? '';
            $empRate = getEmployeeRateInfo($empId, $conn);
            $dailyRate = $empRate['daily_rate'];
            $paySchedule = $empRate['pay_schedule'];

            $employeeDetails = getEmployeeDetails($empId, $conn);

            // calculate basic salary depending on pay schedule (implement calculateBasicSalary if needed)
            // small fallback: basicSalary = dailyRate * number of distinct dates
            $uniqueDates = array_map(function ($e) {
                return $e['date']; }, $entries);
            $uniqueDates = array_values(array_unique($uniqueDates));
            $basicSalary = $dailyRate * count($uniqueDates);

            // sort entries by date
            usort($entries, function ($a, $b) {
                return strcmp($a['date'], $b['date']); });

            // Build list of all entry dates
            $allDates = array_map(fn($r) => $r['date'], $entries);
            sort($allDates);

            // WEEK grouping: chunk by 7-day windows as in your paste
            $weekGroups = [];
            $currentGroup = [];
            $groupStartDate = new DateTime($allDates[0]);
            $groupEndDate = clone $groupStartDate;
            $groupEndDate->modify('+6 days');

            foreach ($entries as $entry) {
                $entryDate = new DateTime($entry['date']);
                if ($entryDate >= $groupStartDate && $entryDate <= $groupEndDate) {
                    $currentGroup[] = $entry;
                } else {
                    if (!empty($currentGroup))
                        $weekGroups[] = $currentGroup;
                    $currentGroup = [$entry];
                    $groupStartDate = clone $entryDate;
                    $groupEndDate = clone $groupStartDate;
                    $groupEndDate->modify('+6 days');
                }
            }
            if (!empty($currentGroup))
                $weekGroups[] = $currentGroup;

            // For each week group compute totals and insert payroll record
            foreach ($weekGroups as $weekEntries) {
                // initialize totals per-week
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
                $late_deduction = 0;

                // process each entry using your logic
                foreach ($weekEntries as $row) {
                    $amIn = $row['am_in'];
                    $amOut = $row['am_out'];
                    $dateCheck = $row['date'];
                    $dateObj = DateTime::createFromFormat('Y-m-d', $dateCheck);
                    $isSunday = $dateObj && $dateObj->format('N') == 7;
                    $isHoliday = isset($holidays[$dateCheck]);
                    $holidayInfo = $isHoliday ? $holidays[$dateCheck] : null;
                    $holidayType = $isHoliday ? $holidayInfo['type'] : '';
                    $isSaturday = in_array($dateCheck, $saturdays);

                    // multipliers
                    $multipliers = getHolidayMultipliers($dateCheck, $isSunday, $holidays, $sundayRates);
                    $regularMultiplier = $multipliers['regular'];
                    $overtimeMultiplier = $multipliers['overtime'];

                    $baseDailyRate = $dailyRate;
                    $dailyWage = $baseDailyRate * $regularMultiplier;

                    if (!empty($amIn) && !empty($amOut)) {
                        $inTime = DateTime::createFromFormat('H:i', $amIn);
                        $outTime = DateTime::createFromFormat('H:i', $amOut);
                        if ($inTime && $outTime) {
                            $interval = $inTime->diff($outTime);
                            $workedHours = $interval->h + $interval->i / 60;

                            if (!$isSaturday && !$isHoliday && !$isSunday)
                                $workedHours -= 1;
                            if ($isHoliday)
                                $workedHours -= 1;
                            $workedHours = max(0, $workedHours);

                            if ($workedHours > 0) {
                                if ($isSunday && $isHoliday) {
                                    if ($holidayType == 'Regular')
                                        $regular_holiday_pay += $dailyWage;
                                    else
                                        $special_holiday_pay += $dailyWage;
                                } elseif ($isSunday) {
                                    $rest_day_pay += $dailyWage;
                                } elseif ($isHoliday) {
                                    if ($holidayType == 'Regular')
                                        $regular_holiday_pay += $dailyWage;
                                    else
                                        $special_holiday_pay += $dailyWage;
                                } else {
                                    $totalWage += $dailyWage;
                                }

                                if ($workedHours < 8) {
                                    $undertimeMinutes = (8 - $workedHours) * 60;
                                    if ($undertimeMinutes >= 11) {
                                        $roundedUndertime = ceil($undertimeMinutes / 30) * 0.5;
                                        $undertimeAmount = $roundedUndertime * ($baseDailyRate / 8);
                                    } else
                                        $undertimeAmount = 0;
                                    $totalUndertime += $undertimeAmount;
                                } elseif ($workedHours > 8) {
                                    $overtimeHours = floor($workedHours - 8);
                                    if ($overtimeHours >= 1) {
                                        $overtimeAmount = $overtimeHours * ($baseDailyRate / 8) * $overtimeMultiplier;
                                        $total_ot_hours += $overtimeHours;
                                        if ($isSunday || $isHoliday) {
                                            $regular_ot_pay += $overtimeAmount;
                                        } else {
                                            $totalOvertime += $overtimeAmount;
                                        }
                                    }
                                }
                                $totalDaysPresent++;
                            } else {
                                if ($isSunday) {
                                    // rest day not worked: no pay
                                } elseif ($isHoliday) {
                                    if ($holidayType == 'Regular')
                                        $dailyWage = $baseDailyRate;
                                } else {
                                    $totalAbsences++;
                                }
                            }
                        } else {
                            // invalid time formats -> treat appropriately
                            if ($isHoliday && $holidayType === 'Regular')
                                $regular_holiday_pay += $baseDailyRate;
                            elseif (!$isHoliday && !$isSunday)
                                $totalAbsences++;
                        }
                    } else {
                        // no time entries
                        if ($isHoliday && $holidayType === 'Regular')
                            $regular_holiday_pay += $baseDailyRate;
                        elseif (!$isHoliday && !$isSunday)
                            $totalAbsences++;
                    }
                } // end weekEntries loop

                // finalize totals for this group
                $absent_deduction = $totalAbsences * $dailyRate;
                $total_earnings = $totalWage + $totalOvertime + $rest_day_pay + $regular_holiday_pay + $special_holiday_pay + $regular_ot_pay;
                $total_deductions = $totalUndertime + 0 + 0; // add gov contributions later per employee
                $net_pay = $total_earnings - $total_deductions;

                // employee details for insert
                $employeeId = $entries[0]['id_no'];
                $employeeDetails = getEmployeeDetails($employeeId, $conn);

                // bind and insert into payroll_records - keep type order exactly matching bind_param
                $startDate = $weekEntries[0]['date'];
                $endDate = $weekEntries[count($weekEntries) - 1]['date'];
                $batchId = uniqid('batch_', true);
                $pay_period = ucfirst($paySchedule);

                $stmtIns = $conn->prepare("INSERT INTO payroll_records (
                    batch_id, employee_id, name, department, pay_period,
                    start_date, end_date, basic_salary, overtime_pay, overtime_hours, overtime_rate,
                    rest_day_pay, regular_holiday_pay, regular_ot_pay, special_holiday_pay,
                    late_deduction, absent_deduction, undertime_deduction,
                    total_earnings, total_deductions, net_pay,
                    sss_no, pagibig_no, tin_no, philhealth_no,
                    thirteenth_month_pay, sss_premium, sss_loan, pagibig_premium, pagibig_loan,
                    philhealth, cash_advance, leave_with_pay, leave_without_pay, available_leave,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )");

                // prepare data
                $name = $employeeDetails['name'];
                $dept = $employeeDetails['department'];
                $sss_no = $employeeDetails['sss_no'] ?? '';
                $pagibig_no = $employeeDetails['pagibig_no'] ?? '';
                $tin_no = $employeeDetails['tin_no'] ?? '';
                $philhealth_no = $employeeDetails['philhealth_no'] ?? '';

                $thirteenth_month_pay = 0.0; // compute if needed
                $sss_premium = $employeeDetails['sss_premium'];
                $sss_loan = $employeeDetails['sss_loan'];
                $pagibig_premium = $employeeDetails['pagibig_premium'];
                $pagibig_loan = $employeeDetails['pagibig_loan'];
                $philhealth = $employeeDetails['philhealth'];
                $cash_advance = $employeeDetails['cash_advance'];
                $leave_with_pay = (int) $employeeDetails['leave_with_pay'];
                $leave_without_pay = (int) $employeeDetails['leave_without_pay'];
                $available_leave = (float) $employeeDetails['available_leave'];

                // types and params - must match
                $stmtIns->bind_param(
                    "ssssssssddddddddddddssssddddddddi",
                    $batchId,
                    $employeeId,
                    $name,
                    $dept,
                    $pay_period,
                    $startDate,
                    $endDate,
                    $basicSalary,
                    $totalOvertime,
                    $total_ot_hours,
                    $overtimeMultiplier,
                    $rest_day_pay,
                    $regular_holiday_pay,
                    $regular_ot_pay,
                    $special_holiday_pay,
                    $late_deduction,
                    $absent_deduction,
                    $totalUndertime,
                    $total_earnings,
                    $total_deductions,
                    $net_pay,
                    $sss_no,
                    $pagibig_no,
                    $tin_no,
                    $philhealth_no,
                    $thirteenth_month_pay,
                    $sss_premium,
                    $sss_loan,
                    $pagibig_premium,
                    $pagibig_loan,
                    $philhealth,
                    $cash_advance,
                    $leave_with_pay,
                    $leave_without_pay,
                    $available_leave
                );

                if (!$stmtIns->execute()) {
                    throw new Exception("Insert payroll failed: " . $stmtIns->error);
                }
                $stmtIns->close();
            } // end foreach weekGroups
        } // end foreach groupedByEmployee

        // finished processing groups
        // remove the manual_attendance row that triggered this
        $stmtDel = $conn->prepare("DELETE FROM manual_attendance WHERE id = ?");
        $stmtDel->bind_param("i", $manualId);
        if (!$stmtDel->execute())
            throw new Exception("Delete manual_attendance failed");
        $stmtDel->close();

        $conn->commit();
        echo "Processed grouped attendance and inserted payroll records.";
        exit;
    } else if (isset($attendanceData['days']) && isset($manualRow['id_no'])) {
        // ---- FLOW 2: single employee simple OT/UT days ----
        $employeeId = $manualRow['id_no'];
        $empRate = getEmployeeRateInfo($employeeId, $conn);
        $dailyRate = $empRate['daily_rate'];
        $paySchedule = ucfirst($empRate['pay_schedule']);
        $employeeDetails = getEmployeeDetails($employeeId, $conn);

        $basicSalary = 0;
        $overtimePay = 0;
        $overtimeHours = 0;
        $undertimeDeduction = 0;

        $hourlyRate = $dailyRate / 8;
        // use holiday multipliers when needed - simplified here
        foreach ($attendanceData['days'] as $d => $info) {
            $ot = floatval($info['ot'] ?? 0);
            $ut = floatval($info['ut'] ?? 0);
            $basicSalary += $dailyRate;
            $overtimeHours += $ot;
            $overtimePay += $ot * $hourlyRate * $ratesData['rates']['overtime_multiplier'];
            $undertimeDeduction += $ut * $hourlyRate;
        }

        $totalEarnings = $basicSalary + $overtimePay;
        $totalDeductions = $undertimeDeduction + $employeeDetails['sss_premium'] + $employeeDetails['sss_loan'] + $employeeDetails['pagibig_premium'] + $employeeDetails['pagibig_loan'] + $employeeDetails['philhealth'];
        $netPay = $totalEarnings - $totalDeductions;

        $startDate = date("Y-m-d", strtotime(array_key_first($attendanceData['days'])));
        $endDate = date("Y-m-d", strtotime(array_key_last($attendanceData['days'])));
        $batchId = uniqid('batch_', true);

        $stmtIns = $conn->prepare("INSERT INTO payroll_records (
    batch_id, employee_id, name, department, pay_period,
    start_date, end_date, basic_salary, overtime_pay, overtime_hours, overtime_rate,
    rest_day_pay, regular_holiday_pay, regular_ot_pay, special_holiday_pay,
    late_deduction, absent_deduction, undertime_deduction,
    total_earnings, total_deductions, net_pay,
    sss_no, pagibig_no, tin_no, philhealth_no,
    thirteenth_month_pay, sss_premium, sss_loan, pagibig_premium, pagibig_loan,
    philhealth, cash_advance, leave_with_pay, leave_without_pay, available_leave,
    created_at, updated_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
)");


        $name = $manualRow['employee_name'] ?? $employeeDetails['name'];
        $dept = $manualRow['department'] ?? $employeeDetails['department'];
        $sss_no = $employeeDetails['sss_no'];
        $pagibig_no = $employeeDetails['pagibig_no'];
        $tin_no = $employeeDetails['tin_no'];
        $philhealth_no = $employeeDetails['philhealth_no'];
        $thirteenth_month_pay = 0;
        $sss_premium = $employeeDetails['sss_premium'];
        $sss_loan = $employeeDetails['sss_loan'];
        $pagibig_premium = $employeeDetails['pagibig_premium'];
        $pagibig_loan = $employeeDetails['pagibig_loan'];
        $philhealth = $employeeDetails['philhealth'];
        $cash_advance = $employeeDetails['cash_advance'];
        $leave_with_pay = (int) $employeeDetails['leave_with_pay'];
        $leave_without_pay = (int) $employeeDetails['leave_without_pay'];
        $available_leave = (float) $employeeDetails['available_leave'];

        $overtimeRate = $ratesData['rates']['overtime_multiplier'];

        $stmtIns->bind_param(
    "ssssssddddddddddddddssssdddddddddii",
    $batchId, $employeeId, $name, $dept, $paySchedule,
    $startDate, $endDate,
    $basicSalary, $overtimePay, $overtimeHours, $overtimeRate,
    $rest_day_pay, $regular_holiday_pay, $regular_ot_pay, $special_holiday_pay,
    $late_deduction, $absent_deduction, $undertimeDeduction,
    $totalEarnings, $totalDeductions, $netPay,
    $sss_no, $pagibig_no, $tin_no, $philhealth_no,
    $thirteenth_month_pay, $sss_premium, $sss_loan, $pagibig_premium, $pagibig_loan,
    $philhealth, $cash_advance, $leave_with_pay, $leave_without_pay, $available_leave
);


        if (!$stmtIns->execute())
            throw new Exception("Insert failed: " . $stmtIns->error);
        $stmtIns->close();

        // delete manual_attendance row
        $stmtDel = $conn->prepare("DELETE FROM manual_attendance WHERE id = ?");
        $stmtDel->bind_param("i", $manualId);
        if (!$stmtDel->execute())
            throw new Exception("Delete manual_attendance failed");
        $stmtDel->close();

        $conn->commit();
        echo "Processed simple attendance and created payroll record.";
        exit;
    } else {
        // unknown format
        throw new Exception("Attendance data format not recognized");
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Failed: " . $e->getMessage();
    exit;
}

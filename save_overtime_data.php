<?php
include __DIR__ . "/config.php";

// Get the raw POST data (as JSON)
$data = json_decode(file_get_contents("php://input"), true);

// Overtime rates
$rates = [
    'regular_overtime' => 125,
    'rest_day' => 1040,
    'rest_day_overtime' => 169,
    'regular_holiday' => 1600,
    'regular_holiday_overtime' => 260,
    'work_on_rest_regular_holiday' => 2080,
    'special_non_working_holiday' => 1040,
    'special_holiday_overtime' => 169
];

// Check if the data is valid
if ($data) {
    foreach ($data as $row) {
        $employee_id = mysqli_real_escape_string($conn, $row['employee_id']);
        $employee_name = mysqli_real_escape_string($conn, $row['employee_name']);
        $start_date = mysqli_real_escape_string($conn, $row['start_date']);
        $end_date = mysqli_real_escape_string($conn, $row['end_date']);
        $subtotal = mysqli_real_escape_string($conn, $row['subtotal']);
        
        // Overtime data
        $overtime_data = $row['overtime_data'];

        // Get the overtime values
        $regular_overtime = mysqli_real_escape_string($conn, $overtime_data['regular_overtime']);
        $rest_day = mysqli_real_escape_string($conn, $overtime_data['rest_day']);
        $rest_day_overtime = mysqli_real_escape_string($conn, $overtime_data['rest_day_overtime']);
        $regular_holiday = mysqli_real_escape_string($conn, $overtime_data['regular_holiday']);
        $regular_holiday_overtime = mysqli_real_escape_string($conn, $overtime_data['regular_holiday_overtime']);
        $work_on_rest_regular_holiday = mysqli_real_escape_string($conn, $overtime_data['work_on_rest_regular_holiday']);
        $special_non_working_holiday = mysqli_real_escape_string($conn, $overtime_data['special_non_working_holiday']);
        $special_holiday_overtime = mysqli_real_escape_string($conn, $overtime_data['special_holiday_overtime']);

        // Calculate the overtime pay based on rates and store the computed value
        $regular_overtime_pay = $regular_overtime * $rates['regular_overtime'];
        $rest_day_pay = $rest_day * $rates['rest_day'];
        $rest_day_overtime_pay = $rest_day_overtime * $rates['rest_day_overtime'];
        $regular_holiday_pay = $regular_holiday * $rates['regular_holiday'];
        $regular_holiday_overtime_pay = $regular_holiday_overtime * $rates['regular_holiday_overtime'];
        $work_on_rest_regular_holiday_pay = $work_on_rest_regular_holiday * $rates['work_on_rest_regular_holiday'];
        $special_non_working_holiday_pay = $special_non_working_holiday * $rates['special_non_working_holiday'];
        $special_holiday_overtime_pay = $special_holiday_overtime * $rates['special_holiday_overtime'];

        // Insert data into the database with computed overtime pay values
        $query = "INSERT INTO employee_overtime_holiday
                    (employee_id, employee_name, start_date, end_date, subtotal_minus_deductions, 
                    regular_overtime, rest_day, rest_day_overtime, regular_holiday, 
                    regular_holiday_overtime, work_on_rest_regular_holiday, 
                    special_non_working_holiday, special_holiday_overtime, total_pay, date_saved)
                    VALUES
                    ('$employee_id', '$employee_name', '$start_date', '$end_date', '$subtotal', 
                    '$regular_overtime_pay', '$rest_day_pay', '$rest_day_overtime_pay', '$regular_holiday_pay', 
                    '$regular_holiday_overtime_pay', '$work_on_rest_regular_holiday_pay', 
                    '$special_non_working_holiday_pay', '$special_holiday_overtime_pay', 
                    ('$subtotal' + '$regular_overtime_pay' + '$rest_day_pay' + '$rest_day_overtime_pay' + 
                    '$regular_holiday_pay' + '$regular_holiday_overtime_pay' + '$work_on_rest_regular_holiday_pay' + 
                    '$special_non_working_holiday_pay' + '$special_holiday_overtime_pay'), NOW())";
        
        if (!mysqli_query($conn, $query)) {
            echo json_encode(['success' => false, 'message' => 'Database insert failed']);
            exit;
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>

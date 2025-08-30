<?php
// Include the necessary files for PHPSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_GET['file'])) {
    die("File not specified!");
}

$file_path = urldecode($_GET['file']);
echo "Processing file: " . $file_path . "<br>";

// Check if the file exists
if (!file_exists($file_path)) {
    die("File not found.");
}

// Load the uploaded Excel file
try {
    $spreadsheet = IOFactory::load($file_path);
} catch (Exception $e) {
    die('Error loading file: ' . $e->getMessage());
}

$sheet = $spreadsheet->getActiveSheet();

// Read data from the Crystal Reports format based on your structure
$rows = [];
$row_num = 10; // Start at row 10 (where the data starts)

while ($sheet->getCell('C' . $row_num)->getValue() !== '') {
    // Extract ID, Department, and Name based on the pattern you described
    $employee_id = $sheet->getCell('E' . $row_num)->getValue();
    $department = $sheet->getCell('L' . $row_num)->getValue();
    $name = $sheet->getCell('E' . ($row_num + 2))->getValue(); // Assuming name is on the row after ID

    // Add this data to the array
    $rows[] = [
        'employee_id' => $employee_id,
        'department' => $department,
        'name' => $name,
    ];

    // Move to the next employee block (in increments of 30 rows)
    $row_num += 30;
}

// Process the data as required
if (count($rows) > 0) {
    echo "<h1>Employee Attendance Summary</h1>";
    echo "<table border='1'>
            <tr>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Name</th>
            </tr>";
    foreach ($rows as $row) {
        echo "<tr>
                <td>{$row['employee_id']}</td>
                <td>{$row['department']}</td>
                <td>{$row['name']}</td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "No data found in the file.";
}
?>

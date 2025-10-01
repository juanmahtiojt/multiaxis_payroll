<?php
include __DIR__ . "/config.php";
header("Content-Type: application/json");

if (!isset($_GET['date'])) {
    echo json_encode(["success" => false, "message" => "No date provided"]);
    exit;
}

$date = $_GET['date']; // YYYY-MM-DD

$response = [
    "success" => true,
    "date" => $date,
    "isSunday" => false,
    "holiday_id" => null,
    "holiday" => null,
    "holiday_type" => null,
    "rates" => [
        "regular_rate" => 1.0,
        "overtime_rate" => 1.25,
        "restdayholiday_regular" => null,
        "restdayholiday_overtime" => null,
        "restdayholiday_special" => null,
        "restdayspecialholiday_overtime" => null
    ]
];

// Sunday check
$dayOfWeek = date('w', strtotime($date));
if ($dayOfWeek == 0) {
    $response["isSunday"] = true;
}

// Holiday check
$stmt = $conn->prepare("SELECT id, description, holiday_type,
                               regular_rate, overtime_rate,
                               restdayholiday_regular, restdayholiday_overtime,
                               restdayholiday_special, restdayspecialholiday_overtime
                        FROM holidays 
                        WHERE holiday_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $response["holiday_id"] = $row["id"];
    $response["holiday"] = $row["description"];
    $response["holiday_type"] = $row["holiday_type"];
    $response["rates"] = [
        "regular_rate" => (float)($row["regular_rate"] ?? 1.0),
        "overtime_rate" => (float)($row["overtime_rate"] ?? 1.25),
        "restdayholiday_regular" => (float)($row["restdayholiday_regular"] ?? 0),
        "restdayholiday_overtime" => (float)($row["restdayholiday_overtime"] ?? 0),
        "restdayholiday_special" => (float)($row["restdayholiday_special"] ?? 0),
        "restdayspecialholiday_overtime" => (float)($row["restdayspecialholiday_overtime"] ?? 0)
    ];
}

echo json_encode($response);




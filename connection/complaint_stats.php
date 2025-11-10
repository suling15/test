<?php
session_start();
require_once '../connection/config.php';

$db = new config();
$conn = $db->connectDB();

// --- Weekly (last 7 days) ---
$weekly = [];
$stmt = $conn->query("
    SELECT DATE(c.create_at) as day, COUNT(*) as total
    FROM complaint c
    WHERE c.create_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(c.create_at)
    ORDER BY day ASC
");
while ($row = $stmt->fetch_assoc()) {
    $weekly[$row['day']] = $row['total'];
}

// --- Monthly (per month current year) ---
$monthly = [];
$stmt = $conn->query("
    SELECT MONTH(c.create_at) as month, COUNT(*) as total
    FROM complaint c
    WHERE YEAR(c.create_at) = YEAR(CURDATE())
    GROUP BY MONTH(c.create_at)
    ORDER BY month ASC
");
while ($row = $stmt->fetch_assoc()) {
    $monthly[$row['month']] = $row['total'];
}

// --- Yearly (last 5 years) ---
$yearly = [];
$stmt = $conn->query("
    SELECT YEAR(c.create_at) as year, COUNT(*) as total
    FROM complaint c
    GROUP BY YEAR(c.create_at)
    ORDER BY year DESC
    LIMIT 5
");
while ($row = $stmt->fetch_assoc()) {
    $yearly[$row['year']] = $row['total'];
}

header('Content-Type: application/json');
echo json_encode([
    "weekly" => $weekly,
    "monthly" => $monthly,
    "yearly" => $yearly
]);
?>

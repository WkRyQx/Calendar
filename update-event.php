<?php
$conn = new mysqli("localhost", "root", "", "calendar");

if ($conn->connect_error) {
die("DB hiba: " . $conn->connect_error);
}

echo "POST: ";
print_r($_POST);

$id = $_POST['id'] ?? 0;
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$date = $_POST['date'] ?? '';

$startFull = $date . " " . $start . ":00";
$endFull = $date . " " . $end . ":00";

$sql = "UPDATE esemeny
SET kezdet='$startFull', vege='$endFull'
WHERE id=$id";

if ($conn->query($sql)) {
echo " | OK | rows: " . $conn->affected_rows;
} else {
echo " | ERROR: " . $conn->error;
}
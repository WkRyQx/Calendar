<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    exit("Hiba: nincs bejelentkezve");
}

$conn = new mysqli("localhost", "root", "", "calendar");

if ($conn->connect_error) {
    exit("DB hiba");
}

$id = $_POST['id'] ?? null;
$start = $_POST['start'] ?? null;
$end = $_POST['end'] ?? null;
$date = $_POST['date'] ?? null;

$userId = $_SESSION['user_id'];

if (!$id || !$start || !$end || !$date) {
    exit("Hiányzó adat");
}

$startFull = $date . " " . $start . ":00";
$endFull = $date . " " . $end . ":00";

$stmt = $conn->prepare("UPDATE esemeny SET kezdet=?, vege=? WHERE id=? AND felhasznalo_id=?");
$stmt->bind_param("ssii", $startFull, $endFull, $id, $userId);
$stmt->execute();

echo "OK";
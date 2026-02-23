<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";        
$dbname = "calendar";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Hiba a kapcsolódáskor: " . $conn->connect_error);
}

$success = "";
$error = "";



?>


<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <title>Főoldal</title>
</head>
<body>
    
<?php if ($success): ?>
    <div class="msg success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="msg error"><?= $error ?></div>
<?php endif; ?>


<button id="menuBtn" type="button"><i class="fa-solid fa-bars"></i></button>

<form method="Post" action="kijelentkezes.php" class="kijelentkezes">
    <div>
        <button type="submit">Kijelentkezés</button>
    </div>
</form>

<div id="overlay"></div>

<div id="sidebar">
    <button id="closeSidebarBtn" type="button"><i class="fa-solid fa-bars"></i></button>
    <button type="button"><i class="fa-solid fa-plus"></i>Létrehozás</button>

    <div class="calendar">
        <div class="weekdays">
            <div>H</div><div>K</div><div>Sze</div><div>Cs</div><div>P</div><div>Szo</div><div>V</div>
        </div>

        <?php

            //aktuális dátum
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

            $honapok = [
                1 => 'január',
                2 => 'február',
                3 => 'március',
                4 => 'április',
                5 => 'május',
                6 => 'június',
                7 => 'július',
                8 => 'augusztus',
                9 => 'szeptember',
                10 => 'október',
                11 => 'november',
                12 => 'december'
            ];

            $monthName = $year . '  ' . $honapok[$month];

            // Ha kilógna
            if ($month < 1)
            {
                $month = 12;
                $year--;
            }

            if ($month > 12)
            {
                $month = 1;
                $year++;
            }

            $firstDayOfMonth = strtotime("$year-$month-01");
            $daysInMonth = date('t', $firstDayOfMonth);
            $startDay = date('N', $firstDayOfMonth) - 1; //vasárnap

            // Aktuális mai dátum
            $todayYear = date('Y');
            $todayMonth = date('n');
            $todayDay = date('j');

            // Előző és következő hónap linkhez
            $prevMonth = $month - 1;
            $prevYear = $year;
            $nextMonth = $month + 1;
            $nextYear = $year;

            if ($prevMonth < 1)
            {
                $prevMonth = 12;
                $prevYear--;
            }

            if ($nextMonth > 12)
            {
                $nextMonth = 1;
                $nextYear++;
            }


            echo "<h2>
            <a href='?year=$prevYear&month=$prevMonth'>&laquo;</a>
            $monthName
            <a href='?year=$nextYear&month=$nextMonth'>&raquo;</a>
            </h2>";

            echo "<div class='days'>";

            // Előző hónap napjai
            $prevMonthDays = date('t', strtotime('-1 month', $firstDayOfMonth));
            for ($i = $startDay - 1; $i >= 0; $i--)
            {
            echo '<div class="day muted">' . ($prevMonthDays - $i) . '</div>';
            }

            // Aktuális hónap napjai
            for ($d = 1; $d <= $daysInMonth; $d++)
            {
                $class = 'day';

                // Mai nap jelölése
                if ($year == $todayYear && $month == $todayMonth && $d == $todayDay)
                {
                    $class .= ' today';
                }
                    echo "<div class='$class'>$d</div>";
            }

            // Következő hónap kitöltés
            $totalCells = $startDay + $daysInMonth;
            $remaining = 7 - ($totalCells % 7);
            if ($remaining < 7)
            {
                for ($i = 1; $i <= $remaining; $i++) {
                    echo "<div class='day muted'>$i</div>";
                }
            }
            echo "</div>";

        ?>
    </div>
</div>

<?php
date_default_timezone_set('Europe/Budapest');

$pdo = new PDO("mysql:host=localhost;dbname=calendar;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT e.nev, e.leiras, e.kezdet, e.vege , k.szin
    FROM esemeny e
    INNER JOIN esemeny_kategoria k 
        ON e.esemenykat_id = k.id
    WHERE DATE(e.kezdet) = ?
    ORDER BY e.kezdet
");


$stmt->execute([$selectedDate]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function timeToMinutes($time) {
    list($h, $m) = explode(':', $time);
    return $h * 60 + $m;
}

foreach ($events as $i => $event) {

    $start = date('H:i', strtotime($event['kezdet']));
    $end   = date('H:i', strtotime($event['vege']));

    $events[$i]['start'] = $start;
    $events[$i]['end']   = $end;

    $events[$i]['startMin'] = timeToMinutes($start);
    $events[$i]['endMin']   = timeToMinutes($end);

    $events[$i]['column'] = 0;
    $events[$i]['totalColumns'] = 1;
}


/* ======== ÜTKÖZÉSKEZELÉS ======== */
foreach ($events as $i => $event) {
    $events[$i]['startMin'] = timeToMinutes($event['start']);
    $events[$i]['endMin']   = timeToMinutes($event['end']);
    $events[$i]['column'] = 0;
    $events[$i]['totalColumns'] = 1;
}

for ($i = 0; $i < count($events); $i++) {
    $overlaps = [];
    for ($j = 0; $j < count($events); $j++) {
        if ($i == $j) continue;

        if (
            $events[$i]['startMin'] < $events[$j]['endMin'] &&
            $events[$i]['endMin'] > $events[$j]['startMin']
        ) {
            $overlaps[] = $j;
        }
    }

    if (!empty($overlaps)) {
        $events[$i]['totalColumns'] = count($overlaps) + 1;
        $events[$i]['column'] = array_search($i, array_merge([$i], $overlaps));
        if ($events[$i]['column'] === false) {
            $events[$i]['column'] = 0;
        }
    }
}

/* ======== AKTUÁLIS IDŐ ======== */
$currentTop = date('G') * 60 + date('i') + 60;
?>

<div class="day-view">

    <div class="time-column">
        <?php
        for ($h=0; $h<24; $h++) {
            echo "<div class='time-slot'>" . sprintf("%02d:00", $h) . "</div>";
        }
        ?>
    </div>

    <div class="calendar-grid">

        <?php
        for ($h=0; $h<24; $h++) {
            echo "<div class='hour-line'></div>";
        }
        ?>
        <div class='hour-line'></div>

        <!-- Aktuális idő -->
        <div class="current-time" style="top: <?= $currentTop ?>px;"></div>

        <!-- Események -->
        <?php foreach ($events as $event):

            $top = $event['startMin'];
            $height = $event['endMin'] - $event['startMin'];

            $width = 100 / $event['totalColumns'];
            $left = $event['column'] * $width;
        ?>

<div class="event"
     style="
     top:<?= $top ?>px;
     height:<?= $height ?>px;
     width:<?= $width ?>%;
     left:<?= $left ?>%;
     background: <?= $event['szin'] ?? '#4caf50' ?>;
     ">
     
    <?= htmlspecialchars($event['nev']) ?><br>
    <?= $event['start'] ?> - <?= $event['end'] ?>

</div>

        <?php endforeach; ?>

    </div>
</div>

<?php
$conn->close();
?>
    


    <script>
        const openBtn = document.getElementById("menuBtn");
        const closeBtn = document.getElementById("closeSidebarBtn");
        const sidebar = document.getElementById("sidebar");

        openBtn.addEventListener("click", () => {
          sidebar.classList.remove("close");
        });
        
        closeBtn.addEventListener("click", () => {
          sidebar.classList.add("close");
        });
        
    </script>
</body>
</html>
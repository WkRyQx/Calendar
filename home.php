<?php
/* ========================= SESSION + DB (PDO) ========================= */
session_start();
date_default_timezone_set('Europe/Budapest');

if (!isset($_SESSION['user_id'])) {
    header("Location: log-reg.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

// DB Kapcsolat (PDO)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=calendar;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Próbáljuk meg hozzáadni a felhasznalo_id-t a kategóriákhoz, hogy lehessenek saját kategóriák
    try {
        $pdo->exec("ALTER TABLE esemeny_kategoria ADD COLUMN felhasznalo_id INT NULL");
        $pdo->exec("ALTER TABLE esemeny_kategoria ADD FOREIGN KEY (felhasznalo_id) REFERENCES felhasznalo(id) ON DELETE CASCADE");
    } catch(PDOException $e) {} // Ha már létezik, elkapjuk a hibát némán
} catch (PDOException $e) {
    die("Hiba az adatbázis kapcsolódáskor: " . $e->getMessage());
}

if (isset($_GET['mod'])) {
    $valasztas = $_GET['mod']; // 'dark' vagy 'light'
    setcookie("tema", $valasztas, time() + (86400 * 30), "/"); 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stilus = $_COOKIE['tema'] ?? 'light';

/* ========================= ESÉNYEK-TEENDŐK MENTÉSE ========================= */
if (isset($_POST['save_event'])) {
    $nev = $_POST['nev'] ?? '';
    $leiras = $_POST['leiras'] ?? '';
    $kezdet = $_POST['kezdet'] ?? '';
    $vege = $_POST['vege'] ?? '';
    $esemenykat_id = !empty($_POST['esemenykat_id']) ? $_POST['esemenykat_id'] : NULL;

    // Új kategória létrehozása
    if ($esemenykat_id === 'new' && !empty($_POST['uj_kategoria_nev'])) {
        $uj_nev = $_POST['uj_kategoria_nev'];
        $uj_szin = $_POST['uj_kategoria_szin'] ?? '#1a73e8';
        
        try {
            $stmtCat = $pdo->prepare("INSERT INTO esemeny_kategoria (nev, szin, felhasznalo_id) VALUES (?, ?, ?)");
            $stmtCat->execute([$uj_nev, $uj_szin, $userId]);
            $esemenykat_id = $pdo->lastInsertId();
        } catch (PDOException $e) {
            // Ha a felhasznalo_id valamiért mégsem létezik (régi adatbázis szerkezet)
            $stmtCat = $pdo->prepare("INSERT INTO esemeny_kategoria (nev, szin) VALUES (?, ?)");
            $stmtCat->execute([$uj_nev, $uj_szin]);
            $esemenykat_id = $pdo->lastInsertId();
        }
    }

    $stmt = $pdo->prepare("INSERT INTO esemeny (nev, leiras, kezdet, vege, esemenykat_id, felhasznalo_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nev, $leiras, $kezdet, $vege, $esemenykat_id, $userId]);

    header("Location: home.php");
    exit();
}

if (isset($_POST['save_task'])) {
    $nev = $_POST['nev'] ?? '';
    $leiras = $_POST['leiras'] ?? '';
    $hatarido = $_POST['hatarido'] ?? '';
    $kesz = 0;

    $stmt = $pdo->prepare("INSERT INTO teendo (nev, leiras, hatarido, kesz, felhasznalo_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nev, $leiras, $hatarido, $kesz, $userId]);

    header("Location: home.php");
    exit();
}

/* ========================= DÁTUM KEZELÉS ========================= */
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$view = $_GET['view'] ?? 'month';

// Naptár navigáció adatai
if (isset($_GET['year']) && isset($_GET['month'])) {
    $year  = (int)$_GET['year'];
    $month = (int)$_GET['month'];
} else {
    $year  = (int)date('Y', strtotime($selectedDate));
    $month = (int)date('n', strtotime($selectedDate));
}

$honapok = [1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április', 5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus', 9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'];
$monthName = $year . '  ' . $honapok[$month];

/* ========================= ADATOK LEKÉRÉSE ========================= */

// Felhasználó adatai (profilkép + név ha nincs sessionben)
$stmtUser = $pdo->prepare("SELECT profilkep, nev FROM felhasznalo WHERE id = ?");
$stmtUser->execute([$userId]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
$profilkep = $userData['profilkep'] ?? null;
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = $userData['nev'] ?? 'User';
}

// Események lekérése a kiválasztott napra (Nap nézet)
$stmtEvents = $pdo->prepare("
    SELECT e.nev, e.leiras, e.kezdet, e.vege, k.szin
    FROM esemeny e
    LEFT JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
    WHERE DATE(e.kezdet) = ? AND e.felhasznalo_id = ?
    ORDER BY e.kezdet
");
$stmtEvents->execute([$selectedDate, $userId]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// Teendők lekérése a kiválasztott napra
$stmtTasks = $pdo->prepare("SELECT * FROM teendo WHERE DATE(hatarido) = ? AND felhasznalo_id = ?");
$stmtTasks->execute([$selectedDate, $userId]);
$tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

// Heti nézet adatai
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
$weekEnd   = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
$stmtWeek = $pdo->prepare("
    SELECT e.*, k.szin 
    FROM esemeny e
    INNER JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
    WHERE DATE(e.kezdet) BETWEEN ? AND ? AND e.felhasznalo_id = ?
    ORDER BY e.kezdet
");
$stmtWeek->execute([$weekStart, $weekEnd, $userId]);
$weekEvents = $stmtWeek->fetchAll(PDO::FETCH_ASSOC);

// Havi nézet adatai
$monthStart = "$year-" . sprintf("%02d", $month) . "-01";
$monthEnd = date('Y-m-t', strtotime($monthStart));
$stmtMonth = $pdo->prepare("
    SELECT e.*, k.szin 
    FROM esemeny e
    INNER JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
    WHERE DATE(e.kezdet) BETWEEN ? AND ? AND e.felhasznalo_id = ?
    ORDER BY e.kezdet
");
$stmtMonth->execute([$monthStart, $monthEnd, $userId]);
$monthEvents = $stmtMonth->fetchAll(PDO::FETCH_ASSOC);

// Mini-naptár indikátorok (minden nap, ahol van esemény vagy teendő)
$stmtIndicators = $pdo->prepare("
    SELECT DISTINCT DATE(kezdet) as datum FROM esemeny WHERE felhasznalo_id = ?
    UNION
    SELECT DISTINCT DATE(hatarido) as datum FROM teendo WHERE felhasznalo_id = ?
");
$stmtIndicators->execute([$userId, $userId]);
$indicatorDates = $stmtIndicators->fetchAll(PDO::FETCH_COLUMN);

// Kategóriák lekérése a modálhoz (Közös és saját)
try {
    $stmtCategories = $pdo->prepare("SELECT id, nev FROM esemeny_kategoria WHERE felhasznalo_id IS NULL OR felhasznalo_id = ?");
    $stmtCategories->execute([$userId]);
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ha a felhasznalo_id oszlop nem létezik
    $stmtCategories = $pdo->query("SELECT id, nev FROM esemeny_kategoria");
    $categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);
}

/* ========================= SEGÉDFÜGGVÉNYEK ========================= */
function timeToMinutes($time) {
    list($h, $m) = explode(':', $time);
    return $h * 60 + $m;
}

/* ======== ÜTKÖZÉSKEZELÉS ======== */

foreach ($events as &$event) {
    $start = new DateTime($event['kezdet']);
    $end = new DateTime($event['vege']);
    $event['startMin'] = ((int)$start->format('H')) * 60 + (int)$start->format('i');
    $event['endMin'] = ((int)$end->format('H')) * 60 + (int)$end->format('i');
    $event['start'] = $start->format('H:i');
    $event['end'] = $end->format('H:i');
}
unset($event);
usort($events, function($a, $b) {
    return $a['startMin'] <=> $b['startMin'];
});
$active = [];
for ($i = 0; $i < count($events); $i++) {
    $active = array_filter($active, function($e) use ($events, $i) {
        return $e['endMin'] > $events[$i]['startMin'];
    });
    $usedColumns = array_column($active, 'column');
    $col = 0;
    while (in_array($col, $usedColumns)) {
        $col++;
    }
    $events[$i]['column'] = $col;
    $active[] = &$events[$i];
}
foreach ($events as &$event) {
    $maxOverlap = 1;
    foreach ($events as $e2) {
        if (
            $event['startMin'] < $e2['endMin'] &&
            $event['endMin'] > $e2['startMin']
        ) {
            $overlapCount = 0;
            foreach ($events as $e3) {
                if (
                    $e2['startMin'] < $e3['endMin'] &&
                    $e2['endMin'] > $e3['startMin']
                ) {
                    $overlapCount++;
                }
            }
            $maxOverlap = max($maxOverlap, $overlapCount);
        }
    }
    $event['totalColumns'] = $maxOverlap;
}
unset($event);

/* ======== Esemény frissítése ======== */

$conn = new mysqli("localhost", "root", "", "calendar");

if ($conn->connect_error) {
die("DB hiba: " . $conn->connect_error);
}

$id = $_POST['id'] ?? 0;
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$date = $_POST['date'] ?? '';

$startFull = $date . " " . $start . ":00";
$endFull = $date . " " . $end . ":00";

$sql = "UPDATE esemeny
SET kezdet='$startFull', vege='$endFull'
WHERE id=$id";
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="main.css">
    <title>Főoldal</title>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">

<?php if ($success): ?><div class="msg success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>

<div class="header-controls">
    <div class="left-controls">
        <button id="menuBtn" type="button"><i class="fa-solid fa-bars"></i></button>

        <div class="dropdown2" title="Létrehozás">
            <i class="fa-solid fa-plus"></i>
            <div class="textBox"></div>
            <div class="option">
                <div>Esemény</div>
                <div>Teendő</div>
            </div>
        </div>
    </div>

    <div class="right-controls">

        <div class="view-switch">
            <select id="viewSelect" onchange="switchView(this.value)">
                <option value="day" <?= $view == 'day' ? 'selected' : '' ?>>Napi nézet</option>
                <option value="week" <?= $view == 'week' ? 'selected' : '' ?>>Heti nézet</option>
                <option value="month" <?= $view == 'month' ? 'selected' : '' ?>>Havi nézet</option>
            </select>
        </div>
        <div class="dropdown3" title="Beállítások">
            <i class="fa-solid fa-gear"></i>
            <div class="textBox"></div>
            <div class="option">
                <form method="get" action="settings.php"><div>Beállítások</div></form>
                <form method="Post" action="kijelentkezes.php" class="kijelentkezes"><div>Kijelentkezés</div></form>
            </div>
        </div>
        <a href="profil.php" id="profil" title="Profil">
            <div class="profile-icon">
                <?php if (!empty($profilkep)): ?>
                    <img src="Profilkepek/<?= $profilkep ?>" alt="profil">
                <?php else: ?>
                    <?= strtoupper($_SESSION["user_name"][0]) ?>
                <?php endif; ?>
            </div>
        </a>
    </div>
</div>

<div id="sidebar">
    <button id="closeSidebarBtn" type="button"><i class="fa-solid fa-bars"></i></button>
    <!-- DARK-MODE TOGGLE -->
    <button id="darkModeToggle" type="button">
        <i class="fa-solid fa-moon"></i>
    </button>
    <div class="dropdown">
        <i class="fa-solid fa-plus"></i>
        <div class="textBox">Létrehozás</div>
        <div class="option">
            <div>Esemény</div>
            <div>Teendő</div>
        </div>
    </div>

    <div class="calendar">
        <?php
            $prevMonth = ($month == 1) ? 12 : $month - 1;
            $prevYear  = ($month == 1) ? $year - 1 : $year;
            $nextMonth = ($month == 12) ? 1 : $month + 1;
            $nextYear  = ($month == 12) ? $year + 1 : $year;

            $prevDateParam = ($view !== 'month') ? '&date=' . date('Y-m-d', strtotime("$prevYear-$prevMonth-01")) : '';
            $nextDateParam = ($view !== 'month') ? '&date=' . date('Y-m-d', strtotime("$nextYear-$nextMonth-01")) : '';

            echo "<h2><a href='?year=$prevYear&month=$prevMonth&view=$view$prevDateParam'>&laquo;</a>$monthName<a href='?year=$nextYear&month=$nextMonth&view=$view$nextDateParam'>&raquo;</a></h2>";
        ?>
        <div class="weekdays">
            <div>H</div><div>K</div><div>Sze</div><div>Cs</div><div>P</div><div>Szo</div><div>V</div>
        </div>
        <div class='days'>
            <?php
            $firstDayTs = strtotime("$year-$month-01");
            $daysInMonth = date('t', $firstDayTs);
            $startDay = date('N', $firstDayTs) - 1;

            // Előző hónap napjai
            $prevMonthDays = date('t', strtotime('-1 month', $firstDayTs));
            for ($i = $startDay - 1; $i >= 0; $i--) {
                $dayNum = $prevMonthDays - $i;
                $cellDate = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $dayNum);
                $cls = 'day muted';
                if ($cellDate === date('Y-m-d')) $cls .= ' today';
                if ($selectedDate === $cellDate) $cls .= ' selected';
                $dot = in_array($cellDate, $indicatorDates) ? '<span class="event-dot"></span>' : '';
                echo "<div class='$cls' data-date='$cellDate'><span>$dayNum</span>$dot</div>";
            }

            // Jelen hónap napjai
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $cls = 'day';
                if ($cellDate === date('Y-m-d')) $cls .= ' today';
                if ($selectedDate === $cellDate) $cls .= ' selected';
                $dot = in_array($cellDate, $indicatorDates) ? '<span class="event-dot"></span>' : '';
                echo "<div class='$cls' data-date='$cellDate'><span>$d</span>$dot</div>";
            }

            // Következő hónap napjai
            $remaining = (7 - (($startDay + $daysInMonth) % 7)) % 7;
            for ($i = 1; $i <= $remaining; $i++) {
                $cellDate = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $i);
                $cls = 'day muted';
                if ($cellDate === date('Y-m-d')) $cls .= ' today';
                if ($selectedDate === $cellDate) $cls .= ' selected';
                $dot = in_array($cellDate, $indicatorDates) ? '<span class="event-dot"></span>' : '';
                echo "<div class='$cls' data-date='$cellDate'><span>$i</span>$dot</div>";
            }
            ?>
        </div>
    </div>
</div>


</div>

<div class="main-content">
    <div class="calendar-container">
        <!-- NAPI NÉZET -->
        <div id="dayView" class="calendar-view" style="<?= $view == 'day' ? '' : 'display:none;' ?>" ondrop="dropHandler(event)" ondragover="dragoverHandler(event)">
            <?php
                $prevDate = date('Y-m-d', strtotime("$selectedDate -1 day"));
                $nextDate = date('Y-m-d', strtotime("$selectedDate +1 day"));
                $dayNum = date('j', strtotime($selectedDate));
                $weekdays = ['V','H','K','Sze','Cs','P','Szo'];
                $dayName = $weekdays[date('N', strtotime($selectedDate)) % 7];
            ?>
            <h2>
                <a href="?date=<?= $prevDate ?>&view=day">&laquo;</a>
                <?= $dayName ?> <?= $dayNum ?>
                <a href="?date=<?= $nextDate ?>&view=day">&raquo;</a>
            </h2>
            <div class="day-view">
                <div class="time-column">
                    <?php for ($h=0; $h<24; $h++) echo "<div class='time-slot'><span>" . sprintf("%02d:00", $h) . "</span></div>"; ?>
                </div>
                <div class="calendar-grid">
                    <?php for ($h=0; $h<24; $h++) echo "<div class='hour-line'></div>"; ?>
                    <?php if ($selectedDate === date('Y-m-d')): ?>
                        <div class="current-time" style="top: <?= date('G')*60 + date('i') ?>px;"></div>
                    <?php endif; ?>

                    <?php foreach ($events as $event): 
                        $top = $event['startMin'];
                        $height = $event['endMin'] - $event['startMin'];
                        $width = 100 / $event['totalColumns'];
                        $left = $event['column'] * $width;
                    ?>
                        <div class="event" style="top:<?= $top ?>px; height:<?= $height ?>px; width:<?= $width ?>%; left:<?= $left ?>%; background: <?= $event['szin'] ?? '#4caf50' ?>;">
                            <?= htmlspecialchars($event['nev']) ?><br><?= $event['start'] ?> - <?= $event['end'] ?>
                        </div> 
                    <?php endforeach; ?>
                    
                    <?php foreach ($tasks as $task): ?>
                        <div class="task">✔ <?= htmlspecialchars($task['nev']) ?><br>📅 <?= $task['hatarido'] ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- HETI NÉZET -->
        <div id="weekView" class="calendar-view" style="<?= $view == 'week' ? '' : 'display:none;' ?>">
            <?php
                $prevWeek = date('Y-m-d', strtotime("$weekStart -7 days"));
                $nextWeek = date('Y-m-d', strtotime("$weekStart +7 days"));
            ?>
            <h2>
                <a href="?date=<?= $prevWeek ?>&view=week">&laquo;</a>
                <?= $weekStart ?> - <?= $weekEnd ?>
                <a href="?date=<?= $nextWeek ?>&view=week">&raquo;</a>
            </h2>
            <div class="week-grid">
                <?php
                $dayNames = ['H','K','Sze','Cs','P','Szo','V'];
                for ($d = 0; $d < 7; $d++):
                    $curr = date('Y-m-d', strtotime("$weekStart +$d days"));
                    $isToday = ($curr === date('Y-m-d')) ? ' today' : '';
                ?>
                    <div class="week-day" data-date="<?= $curr ?>">
                        <div class="week-day-header"><?= $dayNames[$d] ?> <span class="week-day-num<?= $isToday ?>"><?= date('d', strtotime($curr)) ?></span></div>
                        <?php 
                        $count = 0;
                        foreach ($weekEvents as $we):
                            if (date('Y-m-d', strtotime($we['kezdet'])) == $curr):
                                if (++$count > 4) { echo "<div class='week-event'>+ több...</div>"; break; }
                        ?>
                            <div class="week-event" style="background:<?= $we['szin'] ?>;"><?= htmlspecialchars($we['nev']) ?><br><?= date('H:i', strtotime($we['kezdet'])) ?></div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- HAVI NÉZET -->
        <div id="monthView" class="calendar-view" style="<?= $view == 'month' ? '' : 'display:none;' ?>">
            <h2>
                <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>&view=month">&laquo;</a>
                <?= $honapok[$month] ?> <?= $year ?>
                <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>&view=month">&raquo;</a>
            </h2>
            <div class="month-grid">
                <?php
                $firstDayOfMonth = date('N', strtotime($monthStart));
                $totalDays = date('t', strtotime($monthStart));
                
                // Előző hónap
                $prevMoDays = date('t', strtotime('-1 month', strtotime($monthStart)));
                for ($i = $firstDayOfMonth - 1; $i >= 1; $i--) {
                    $d = $prevMoDays - $i + 1;
                    $date = date('Y-m-d', strtotime("-{$i} days", strtotime($monthStart)));
                    echo "<div class='month-day other-month' data-date='$date'>$d</div>";
                }

                // Aktuális hónap
                for ($day = 1; $day <= $totalDays; $day++):
                    $curr = date('Y-m-d', strtotime($monthStart . " + " . ($day-1) . " days"));
                    $cls = ($curr === date('Y-m-d')) ? ' today' : '';
                ?>
                    <div class="month-day<?= $cls ?>" data-date="<?= $curr ?>">
                        <div class="month-day-number<?= $cls ?>"><?= $day ?></div>
                        <?php foreach ($monthEvents as $me): if (date('Y-m-d', strtotime($me['kezdet'])) == $curr): ?>
                            <div class="month-event" style="background:<?= $me['szin'] ?>;"><?= htmlspecialchars($me['nev']) ?></div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endfor; ?>

                <?php
                // Következő hónap
                $filled = ($firstDayOfMonth - 1) + $totalDays;
                $nextCnt = (7 - ($filled % 7)) % 7;
                for ($i = 1; $i <= $nextCnt; $i++) {
                    $date = date('Y-m-d', strtotime($monthEnd . " + $i days"));
                    echo "<div class='month-day other-month' data-date='$date'>$i</div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
    const sidebar = document.getElementById("sidebar");
    const menuBtn = document.getElementById("menuBtn");
    const closeSidebarBtn = document.getElementById("closeSidebarBtn");

    // Eseménykezelő gombnyomásra
    menuBtn.addEventListener("click", (e) => {
        e.stopPropagation(); // Megakadályozza, hogy a kattintás továbbterjedjen
        if (window.innerWidth > 1024) {
            sidebar.classList.toggle("close");
        } else {
            sidebar.classList.toggle("active");
        }
    });

    closeSidebarBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        if (window.innerWidth > 1024) {
            sidebar.classList.add("close");
        } else {
            sidebar.classList.remove("active");
        }
    });

    // Ha a menün kívül kattintunk mobilon, csukódjon be az oldalsáv
    document.addEventListener("click", (e) => {
        if (window.innerWidth <= 1024 && sidebar.classList.contains("active") && !sidebar.contains(e.target)) {
            sidebar.classList.remove("active");
        }
    });

    function switchView(view) {
        document.querySelectorAll('.calendar-view').forEach(el => el.style.display = 'none');
        const active = document.getElementById(view + 'View');
        if (active) active.style.display = 'block';
        
        // Frissítsük az URL-t view paraméterrel újratöltés nélkül, vagy navigáljunk
        const params = new URLSearchParams(window.location.search);
        params.set('view', view);
        window.history.replaceState({}, '', '?' + params.toString());
    }

    function toggleNewCategoryField() {
        const select = document.getElementById('categorySelect');
        const newCatDiv = document.getElementById('newCategoryDiv');
        if (select.value === 'new') {
            newCatDiv.style.display = 'block';
        } else {
            newCatDiv.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const view = params.get('view') || 'month';
        switchView(view);

        if (view === 'day') {
            const grid = document.querySelector('.calendar-grid');
            const currentTimeEl = document.querySelector('.current-time');
            if (grid && currentTimeEl) grid.scrollTop = currentTimeEl.offsetTop - grid.clientHeight/2;
        }

        // Cell navigáció
        document.addEventListener('click', (e) => {
            const cell = e.target.closest('.week-day, .month-day, .days .day');
            if (cell && cell.dataset.date) {
                window.location = '?date=' + cell.dataset.date + '&view=day';
            }
        });

        // Dropdownok
        document.querySelectorAll('.dropdown, .dropdown2, .dropdown3').forEach(dropdown => {
            dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                if (e.target.closest('.option')) return;
                document.querySelectorAll('.dropdown, .dropdown2, .dropdown3').forEach(d => { if(d !== dropdown) d.classList.remove('active'); });
                dropdown.classList.toggle('active');
            });
            dropdown.querySelectorAll('.option div').forEach(opt => {
                opt.addEventListener('click', (e) => {
                    const form = opt.closest('form');
                    if (form) form.submit();
                });
            });
        });
        document.addEventListener('click', () => {
            document.querySelectorAll('.active').forEach(d => d.classList.remove('active'));
            // sidebar.classList.remove("active"); // Don't close sidebar on every click
        });

        // Modálok
        const modals = { "Esemény": document.getElementById('eventModal'), "Teendő": document.getElementById('taskModal') };
        document.querySelectorAll('.option div').forEach(opt => {
            opt.onclick = () => { if (modals[opt.innerText.trim()]) modals[opt.innerText.trim()].style.display = 'block'; };
        });
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.onclick = () => document.querySelectorAll('.custom-modal-overlay').forEach(m => m.style.display = 'none');
        });
        window.onclick = (e) => { if (e.target.classList.contains('custom-modal-overlay')) e.target.style.display = 'none'; };
    });

    // draganddrop.js

    let draggedEvent = null;

    function dragstartHandler(e) {
        draggedEvent = e.target;
    }

    function dragoverHandler(e) {
        e.preventDefault();
    }

    function dropHandler(e) {
        e.preventDefault();

        if (!draggedEvent) return;

        const id = draggedEvent.dataset.eventId;
        const date = draggedEvent.dataset.date;

        console.log("ID:", id, "DATE:", date);

        const newStart = "10:00";
        const newEnd = "11:00";

        const params = new URLSearchParams();
        params.append("id", id);
        params.append("start", newStart);
        params.append("end", newEnd);
        params.append("date", date);

        fetch("update_event.php", {
            method: "POST",
            body: params
        })
            .then(r => r.text())
            .then(d => console.log("SERVER:", d))
            .catch(err => console.error(err));
    }
    //DARKMODE
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    const icon = darkModeToggle.querySelector('i');

    // 1. FUNKCIÓ A SÜTI MENTÉSÉHEZ (Ez teszi elérhetővé minden mappában)
    function setStyleCookie(theme) {
        // 30 napra mentjük el, a "path=/" miatt minden localhost mappában látszódni fog
        document.cookie = "theme=" + theme + "; max-age=" + (86400 * 30) + "; path=/";
    }

    // 2. BETÖLTÉSKOR: Ellenőrizzük a sütit (vagy marad a localStorage is biztonság kedvéért)
    const savedTheme = document.cookie.split('; ').find(row => row.startsWith('theme='))?.split('=')[1] 
                       || localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        if(icon) icon.classList.replace('fa-moon', 'fa-sun');
    }

    // 3. KATTINTÁSKOR: Mentünk sütibe ÉS localstorage-ba is
    darkModeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
                            
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            setStyleCookie('dark'); // Mentés minden mappának
            if(icon) icon.classList.replace('fa-moon', 'fa-sun');
        } else {
            localStorage.setItem('theme', 'light');
            setStyleCookie('light'); // Mentés minden mappának
            if(icon) icon.classList.replace('fa-sun', 'fa-moon');
        }
    });


</script>

<!-- MODÁLOK -->
<div id="eventModal" class="custom-modal-overlay">
    <div class="modal-content">
        <div class="modal-header"><h3>Új esemény</h3><button type="button" class="close-modal">&times;</button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="text" name="nev" placeholder="Esemény neve" required>
                <textarea name="leiras" placeholder="Leírás"></textarea>
                
                <div class="date-group">
                    <div class="date-field">
                        <label>Kezdet</label>
                        <input type="datetime-local" name="kezdet" required>
                    </div>
                    <div class="date-field">
                        <label>Vége</label>
                        <input type="datetime-local" name="vege">
                    </div>
                </div>

                <label>Kategória</label>
                <select name="esemenykat_id" id="categorySelect" onchange="toggleNewCategoryField()">
                    <option value="">-- Nincs kategória --</option>
                    <option value="new">-- Új kategória létrehozása --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nev']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="newCategoryDiv" style="display:none; margin-top: 15px; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color);">
                    <label>Új kategória neve</label>
                    <input type="text" name="uj_kategoria_nev" placeholder="Pl.: Születésnap">
                    
                    <label style="margin-top: 10px;">Szín</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" name="uj_kategoria_szin" value="#1a73e8" style="width: 40px; height: 40px; padding: 0; border: none; border-radius: 5px; cursor: pointer;">
                        <span style="font-size: 13px; color: var(--muted-text);">Válassz színt az eseménynek</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="save-btn" name="save_event">Mentés</button></div>
        </form>
    </div>
</div>

<div id="taskModal" class="custom-modal-overlay">
    <div class="modal-content">
        <div class="modal-header"><h3>Új teendő</h3><button type="button" class="close-modal">&times;</button></div>
        <form method="POST">
            <div class="modal-body">
                <input type="text" name="nev" placeholder="Feladat neve" required>
                <textarea name="leiras" placeholder="Leírás"></textarea>
                
                <div class="date-field">
                    <label>Határidő</label>
                    <input type="date" name="hatarido">
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="save-btn" name="save_task">Hozzáadás</button></div>
        </form>
    </div>
</div>
</body>
</html>
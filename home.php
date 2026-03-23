<?php
/* =========================SESSION + DB (MYSQLI)========================= */
$theme = $_COOKIE['theme'] ?? 'light';
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "calendar";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Hiba a kapcsolódáskor: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: log-reg.php");
    exit();
}

$userId = $_SESSION['user_id'];

$success = "";
$error = "";

/*===========================DARK-MODE==============================*/

// 1. Megnézzük, érkezett-e kérés a váltásra
if (isset($_GET['mod'])) {
    $valasztas = $_GET['mod']; // 'dark' vagy 'light'
    // 2. ELMENTJÜK A SÜTIT (ez a kulcs!)
    setcookie("tema", $valasztas, time() + (86400 * 30), "/"); 
    // 3. Visszaugrunk az oldalra, hogy lássuk az eredményt
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 4. KIOLVASSUK a sütit (ha nincs, alapból 'light')
$stilus = $_COOKIE['tema'] ?? 'light';


/* =========================ESEMÉNY MENTÉS========================= */
if(isset($_POST['save_event'])){

    $nev = $_POST['nev'] ?? '';
    $leiras = $_POST['leiras'] ?? '';
    $kezdet = $_POST['kezdet'] ?? '';
    $vege = $_POST['vege'] ?? '';
    $esemenykat_id = $_POST['esemenykat_id'] ?? NULL;

    $sql = "INSERT INTO esemeny 
    (nev, leiras, kezdet, vege, esemenykat_id, felhasznalo_id)
    VALUES 
    ('$nev','$leiras','$kezdet','$vege','$esemenykat_id','$userId')";

    $conn->query($sql);

    header("Location: home.php");
    exit();
}

/* =========================TEENDŐ MENTÉS========================= */
if(isset($_POST['save_task'])){

    $nev = $_POST['nev'] ?? '';
    $leiras = $_POST['leiras'] ?? '';
    $hatarido = $_POST['hatarido'] ?? '';
    $kesz = 0;

    $sql = "INSERT INTO teendo 
    (nev, leiras, hatarido, kesz, felhasznalo_id)
        VALUES 
    ('$nev','$leiras','$hatarido','$kesz','$userId')";

    $conn->query($sql);

    header("Location: home.php");
    exit();
}

/* =========================PDO LEKÉRDEZÉSEK========================= */
date_default_timezone_set('Europe/Budapest');

$pdo = new PDO("mysql:host=localhost;dbname=calendar;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================DÁTUM KEZELÉS========================= */
$selectedDate = $_GET['date'] ?? date('Y-m-d');

/* =========================ESEMÉNYEK LEKÉRÉSE========================= */
$stmtEvents = $pdo->prepare("
    SELECT e.nev, e.leiras, e.kezdet, e.vege, k.szin
    FROM esemeny e
    LEFT JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
    WHERE DATE(e.kezdet) = ?
    AND e.felhasznalo_id = ?
    ORDER BY e.kezdet
");
$stmtEvents->execute([$selectedDate, $userId]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

/* =========================TEENDŐK LEKÉRÉSE========================= */
$stmtTasks = $pdo->prepare("
    SELECT *
    FROM teendo
    WHERE DATE(hatarido) = ?
    AND felhasznalo_id = ?
");
$stmtTasks->execute([$selectedDate, $userId]);
$tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

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
<body>
<!-- =========================ÜZENETEK========================= -->
<?php if ($success): ?>
<div class="msg success"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<button id="menuBtn" type="button"><i class="fa-solid fa-bars"></i></button>
<div class="dropdown2">
        <i class="fa-solid fa-plus"></i>
        <input type="text" class="textBox"  placeholder=""  name="letrehozas" readonly >
        <div class="option">
            <div>Esemény</div>
            <div>Teendő</div>
        </div>
</div>

<div class="dropdown3">
        <i class="fa-solid fa-gear"></i>
        <input type="text" class="textBox"  placeholder=""  name="settings" readonly >
        <div class="option">
            <form method="get" action="profil.php">
                <div>  Fiók  </div>
            </form>
            <form method="Post" action="kijelentkezes.php" class="kijelentkezes">
                <div>  Kijelentkezés  </div>
            </form>
        </div>
</div>




<div id="sidebar">
    <button id="closeSidebarBtn" type="button"><i class="fa-solid fa-bars"></i></button>
    <div class="dropdown">
        <i class="fa-solid fa-plus"></i>
        <input type="text" class="textBox"  placeholder="Létrehozás"  name="letrehozas" readonly >
        <div class="option">
            <div>Esemény</div>
            <div>Teendő</div>
        </div>
    </div>

    <div class="calendar">
        <?php

            //aktuális dátum – els51sorban a GET parameterekre figyelünk, ha nincs meg, a kivasztott naphoz igazdunk
            if (isset($_GET['year']) && isset($_GET['month'])) {
                $year  = (int)$_GET['year'];
                $month = (int)$_GET['month'];
            } elseif (isset($_GET['date'])) {
                $year  = (int)date('Y', strtotime($_GET['date']));
                $month = (int)date('n', strtotime($_GET['date']));
            } else {
                $year  = date('Y');
                $month = date('n');
            }

            $honapok = [1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április', 5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus', 9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december'];
            $monthName = $year . '  ' . $honapok[$month];

            $firstDayOfMonth = strtotime("$year-$month-01");
            $daysInMonth = date('t', $firstDayOfMonth);
            $startDay = date('N', $firstDayOfMonth) - 1; //vasárnap

            // Aktuális mai dátum
            $todayYear = date('Y');
            $todayMonth = date('n');
            $todayDay = date('j');

            // Előző és következő hónap linkhez – a mini naptár fejlécéhez
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

            // melyik nézetben vagyunk (day/week/month) a GET-ből
            $view = isset($_GET['view']) ? $_GET['view'] : 'month';
            // ha a heti nézetben szeretnénk a mini navigációt, váltsuk az új hónap első napjára
            $prevDateParam = '';
            $nextDateParam = '';
            if ($view === 'week' || $view === 'day') {
                $prevDateParam = '&date=' . date('Y-m-d', strtotime("$prevYear-$prevMonth-01"));
                $nextDateParam = '&date=' . date('Y-m-d', strtotime("$nextYear-$nextMonth-01"));
            }

            echo "<h2> <a href='?year=$prevYear&month=$prevMonth&view=$view$prevDateParam'>&laquo;</a>$monthName<a href='?year=$nextYear&month=$nextMonth&view=$view$nextDateParam'>&raquo;</a> </h2>";
        ?>
            <div class="weekdays">
                <div>H</div><div>K</div><div>Sze</div><div>Cs</div><div>P</div><div>Szo</div><div>V</div>
            </div>
        <?php
            // determine which date (if any) has been selected via the GET param
            $miniSelectedDate = isset($_GET['date']) ? $_GET['date'] : null;

            echo "<div class='days'>";

            // Előző hónap napjai
            $prevMonthDays = date('t', strtotime('-1 month', $firstDayOfMonth));
            $prevYear = $month == 1 ? $year - 1 : $year;
            $prevMo   = $month == 1 ? 12 : $month - 1;
            for ($i = $startDay - 1; $i >= 0; $i--)
            {
                $dayNum = $prevMonthDays - $i;
                $cellDate = sprintf('%04d-%02d-%02d', $prevYear, $prevMo, $dayNum);
                // mini calendar cell – include today class if matching
                $additionalClass = '';
                if ($cellDate === date('Y-m-d')) {
                    $additionalClass .= ' today';
                }
                if ($miniSelectedDate && $cellDate === $miniSelectedDate) {
                    $additionalClass .= ' selected';
                }
                echo "<div class=\"day muted$additionalClass\" data-date=\"$cellDate\">$dayNum</div>";
            }

            // Aktuális hónap napjai
            for ($d = 1; $d <= $daysInMonth; $d++)
            {
                $class = 'day';
                $cellDate = sprintf('%04d-%02d-%02d', $year, $month, $d);

                // Mai nap jelölése
                if ($year == $todayYear && $month == $todayMonth && $d == $todayDay)
                {
                    $class .= ' today';
                }
                if ($miniSelectedDate && $cellDate === $miniSelectedDate) {
                    $class .= ' selected';
                }
                $numClass = ($cellDate === date('Y-m-d')) ? ' today' : '';
                echo "<div class='$class' data-date='$cellDate'><span class='month-day-number$numClass'>$d</span></div>";
            }

            // Következő hónap kitöltés
            $totalCells = $startDay + $daysInMonth;
            $remaining = 7 - ($totalCells % 7);
            if ($remaining < 7)
            {
                // a következő hónap év/hó értékei
                $nextYearVal = $month == 12 ? $year + 1 : $year;
                $nextMonthVal = $month == 12 ? 1 : $month + 1;
                for ($i = 1; $i <= $remaining; $i++) {
                    $cellDate = sprintf('%04d-%02d-%02d', $nextYearVal, $nextMonthVal, $i);
                    $add = '';
                    if ($cellDate === date('Y-m-d')) {
                        $add .= ' today';
                    }
                    if ($miniSelectedDate && $cellDate === $miniSelectedDate) {
                        $add .= ' selected';
                    }
                    echo "<div class='day muted$add' data-date='$cellDate'>$i</div>";
                }
            }
            echo "</div>";
        ?>
    </div>

<!-- DARK-MODE TOGGLE -->
    <button id="darkModeToggle" type="button">
        <i class="fa-solid fa-moon"></i>
    </button>

</div>

<div class="main-content">
    <div class="view-switch">
        <select id="viewSelect" onchange="switchView(this.value)">
            <option value="day">Napi nézet</option>
            <option value="week">Heti nézet</option>
            <option value="month">Havi nézet</option>
        </select>
    </div>

    <div class="calendar-container">
        <div id="dayView" class="calendar-view">
                <?php
                    date_default_timezone_set('Europe/Budapest');

                    $pdo = new PDO("mysql:host=localhost;dbname=calendar;charset=utf8", "root", "");
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

                    // napok közötti lapozáshoz
                    $prevDate = date('Y-m-d', strtotime("$selectedDate -1 day"));
                    $nextDate = date('Y-m-d', strtotime("$selectedDate +1 day"));

                    // év/hónap paraméter a linkekhez (a nap alapján)
                    $prevYear = date('Y', strtotime($prevDate));
                    $prevMonth = date('n', strtotime($prevDate));
                    $nextYear = date('Y', strtotime($nextDate));
                    $nextMonth = date('n', strtotime($nextDate));

                    // csak a nap száma + nap neve a fejlécben
                    $dayNum = date('j', strtotime($selectedDate));
                    $weekdays = ['V','H','K','Sze','Cs','P','Szo'];
                    $dayName = $weekdays[date('N', strtotime($selectedDate)) % 7];
                ?>
                <h2>
                    <a href="?date=<?= $prevDate ?>&view=day&year=<?= $prevYear ?>&month=<?= $prevMonth ?>">&laquo;</a>
                    <?= $dayName ?> <?= $dayNum ?>
                    <a href="?date=<?= $nextDate ?>&view=day&year=<?= $nextYear ?>&month=<?= $nextMonth ?>">&raquo;</a>
                </h2>
                <?php

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
                        $totalColumns = max(1, $event['totalColumns'] ?? 1);
                        $column = max(0, $event['column'] ?? 0);
                        
                        $width = 100 / $totalColumns;
                        $left = $column * $width;

                    for ($i = 0; $i < count($events); $i++) {

                        $events[$i]['column'] = 0;
                        $events[$i]['totalColumns'] = 1;

                        for ($j = 0; $j < count($events); $j++) {

                            if ($i == $j) continue;

                            if (
                                $events[$i]['startMin'] < $events[$j]['endMin'] &&
                                $events[$i]['endMin'] > $events[$j]['startMin']
                            ) {

                                $events[$i]['totalColumns']++;
                            }
                        }
                    }

                    /* ======== AKTUÁLIS IDŐ ======== */
                    $currentTop = date('G') * 60 + date('i');
                ?>

            <div class="day-view">
                <div class="time-column">
                    <?php
                    for ($h=0; $h<24; $h++) {
                        echo "<div class='time-slot'><span>" . sprintf("%02d:00", $h) . "</span></div>";
                    }
                    ?>
                </div>
                <div class="calendar-grid">
                    <?php
                    for ($h=0; $h<24; $h++) {
                        echo "<div class='hour-line'></div>";
                    }
                    ?>

                    <!-- Aktuális idő -->
                    <?php if ($selectedDate === date('Y-m-d')): ?>
                        <div class="current-time" style="top: <?= $currentTop ?>px;"></div>
                    <?php endif; ?>

                    <!-- Események -->
                    <?php if (!empty($events)): foreach ($events as $event):

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
                    <?php endforeach; endif; ?>
                    <?php if (!empty($tasks)): foreach ($tasks as $task): ?>
                        <div class="task">
                            ✔ <?= htmlspecialchars($task['nev']) ?><br>
                            📅 <?= $task['hatarido'] ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
            <?php
                // heti nézet számítások + lapozás
                $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
                $weekEnd   = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));

                $prevWeek = date('Y-m-d', strtotime("$weekStart -7 days"));
                $nextWeek = date('Y-m-d', strtotime("$weekStart +7 days"));

                $stmt = $pdo->prepare("
                    SELECT e.*, k.szin 
                    FROM esemeny e
                    INNER JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
                    WHERE DATE(e.kezdet) BETWEEN ? AND ? AND e.felhasznalo_id = ?
                    ORDER BY e.kezdet
                ");

                $stmt->execute([$weekStart, $weekEnd, $_SESSION['user_id']]);
                $weekEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div id="weekView" class="calendar-view" style="display:none;">
                <?php
                    $prevWeekMonth = date('n', strtotime($prevWeek));
                    $prevWeekYear  = date('Y', strtotime($prevWeek));
                    $nextWeekMonth = date('n', strtotime($nextWeek));
                    $nextWeekYear  = date('Y', strtotime($nextWeek));
                ?>
                <h2>
                    <a href="?date=<?= $prevWeek ?>&view=week&year=<?= $prevWeekYear ?>&month=<?= $prevWeekMonth ?>">&laquo;</a>
                    <?= date('Y-m-d', strtotime($weekStart)) ?> - <?= date('Y-m-d', strtotime($weekEnd)) ?>
                    <a href="?date=<?= $nextWeek ?>&view=week&year=<?= $nextWeekYear ?>&month=<?= $nextWeekMonth ?>">&raquo;</a>
                </h2>
                <div class="week-grid">
                    <?php
                    $dayNames = ['H','K','Sze','Cs','P','Szo','V'];
                    $todayDate = date('Y-m-d');
                    for ($d = 0; $d < 7; $d++):
                        $currentDay = date('Y-m-d', strtotime("$weekStart +$d days"));
                        $isToday = $currentDay === $todayDate ? ' today' : '';
                    ?>
                        <div class="week-day" data-date="<?= $currentDay ?>">
                            <div class="week-day-header">
                                <?= $dayNames[$d] ?> <span class="week-day-num<?= $isToday ?>"><?= date('d', strtotime($currentDay)) ?></span>
                            </div>
                        <?php                  
                        $count = 0;

                        foreach ($weekEvents as $event):
                        
                            $event_nap = date('Y-m-d', strtotime($event['kezdet']));
                        
                            if ($event_nap == $currentDay):
                            
                                if ($count >= 4) {
                                    echo "<div class='week-event'>+ több...</div>";
                                    break;
                                }
                            
                                $count++;
                        ?>
                                <div class="week-event"
                                     style="background:<?= $event['szin'] ?>;">
                                    <?= htmlspecialchars($event['nev']) ?><br>
                                    <?= date('H:i', strtotime($event['kezdet'])) ?>
                                </div>
                            <?php endif; endforeach; ?>
                                
                        </div>
                    <?php endfor; ?>
                                
                </div>
            </div>

            <?php
                // hónap nézet adatok – a navigációhoz használt $year/$month értékekre építünk
                $displayYear  = $year;
                $displayMonth = $month;

                // a hónap első napjának timestampje
                $monthStartTs = strtotime("$displayYear-$displayMonth-01");
                $monthEndStr  = date('Y-m-t', $monthStartTs); // ez egy string, ne küldjük vissza újabb date()-nak

                $stmt = $pdo->prepare("
                    SELECT e.*, k.szin 
                    FROM esemeny e
                    INNER JOIN esemeny_kategoria k ON e.esemenykat_id = k.id
                    WHERE DATE(e.kezdet) BETWEEN ? AND ? AND e.felhasznalo_id = ?
                    ORDER BY e.kezdet
                ");
                $stmt->execute([date('Y-m-d', $monthStartTs), $monthEndStr, $_SESSION['user_id']]);
                $monthEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $firstDayOfMonth = date('N', $monthStartTs);
                $totalDays        = date('t', $monthStartTs);
            ?>

            <div id="monthView" class="calendar-view" style="display:none;">
                <h2>
                    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>&view=month">&laquo;</a>
                    <?= $honapok[$displayMonth] ?> <?= $displayYear ?>
                    <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>&view=month">&raquo;</a>
                </h2>
                <div class="month-grid">

                    <?php
                    // előző hónap napjai (helyesített strtotime használattal)
                    $prevMonthDays = date('t', strtotime('-1 month', $monthStartTs));
                    $cntPrev = $firstDayOfMonth - 1;
                    for ($i = $cntPrev; $i >= 1; $i--) {
                        $dayNum = $prevMonthDays - $i + 1;
                        $otherDate = date('Y-m-d', strtotime("-{$i} days", $monthStartTs));
                        // nem dekoráljuk a szürke cellákat, még ha az aktuális napra esnek
                        // Javascript kezeli a kattintást, ezért nincs szükség hivatkozásra
                        echo "<div class='month-day other-month' data-date='$otherDate'>$dayNum</div>";
                    }

                    // jelen hónap napjai
                    for ($day = 1; $day <= $totalDays; $day++):
                        $currentDate = date('Y-m-d', strtotime('+'.($day-1).' days', $monthStartTs));
                        $today = date('Y-m-d');
                        $cls = $currentDate === $today ? ' today' : '';
                    ?>
                        <div class="month-day<?= $cls ?>" data-date="<?= $currentDate ?>">
                            <?php $numClass = ($currentDate === date('Y-m-d')) ? ' today' : ''; ?>
                            <div class="month-day-number<?= $numClass ?>"><?= $day ?></div>

                            <?php foreach ($monthEvents as $event):
                                if (date('Y-m-d', strtotime($event['kezdet'])) == $currentDate):
                            ?>

                            <div class="month-event"
                                 data-full="<?= htmlspecialchars($event['nev']) ?>"
                                 style="background:<?= $event['szin'] ?>;">
                                <?= htmlspecialchars($event['nev']) ?>
                            </div>

                            <?php endif; endforeach; ?>

                        </div>
                    <?php endfor; ?>

                    <?php
                    // következő hónap napjai
                    $filled = $cntPrev + $totalDays;
                    $cntNext = (7 - ($filled % 7)) % 7;
                    for ($i = 1; $i <= $cntNext; $i++) {
                        $otherDate = date('Y-m-d', strtotime('+'.($totalDays + $i - 1).' days', $monthStartTs));
                        // ugyanez: nem adjuk hozzá a 'today' osztályt a következő hónap napjaihoz
                        echo "<div class='month-day other-month' data-date='$otherDate'>$i</div>";
                    }
                    ?>

                </div>
            </div>
        </div> 
</div>

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
        
        function switchView(view) {
            const views = ['dayView', 'weekView', 'monthView'];

            views.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            const active = document.getElementById(view + 'View');
            if (active) active.style.display = 'block';
        }

        // ha URL-ben van view paraméter, válasszuk ki a megfelelő nézetet
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const view = params.get('view');
            if (view) switchView(view);

            // nap nézetnél, ha a kiválasztott dátum ma, gördítsük középre a piros vonalat
            if (view === 'day') {
                const selectedDate = params.get('date');
                const today = new Date().toISOString().slice(0,10);
                if (selectedDate === today) {
                    const grid = document.querySelector('.calendar-grid');
                    const currentTimeEl = document.querySelector('.current-time');
                    if (grid && currentTimeEl) {
                        const top = currentTimeEl.offsetTop; // relatív a gridhez
                        grid.scrollTop = top - grid.clientHeight/2;
                    }
                }
            }
        });

        // cell-click navigation for week/month/mini calendar
        document.addEventListener('click', (e) => {
            const cell = e.target.closest('.week-day, .month-day, .days .day');
            if (!cell) return;

            // prefer data-date attribute; dataset may sometimes be undefined
            let dateVal = cell.getAttribute('data-date') || '';
            if (!dateVal && cell.dataset) {
                dateVal = cell.dataset.date || '';
            }

            // if we still don't have a date, we can't navigate
            if (!dateVal) return;

            const [y, m] = dateVal.split('-');
            const url = '?date=' + dateVal + '&view=day' + '&year=' + y + '&month=' + m;
            window.location = url;
        });

        //Dropdown kezelés
        document.addEventListener('DOMContentLoaded', () => {                     
            const dropdowns = document.querySelectorAll('.dropdown, .dropdown2, .dropdown3');

            dropdowns.forEach(dropdown => {
                const input = dropdown.querySelector('.textBox');
                const optionBox = dropdown.querySelector('.option');

                if (!input || !optionBox) return;
                // NYITÁS / ZÁRÁS
                input.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // bezár minden mást
                    dropdowns.forEach(d => d.classList.remove('active'));
                    dropdown.classList.toggle('active');

                    const rect = input.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    // egyszerűsített pozicionálás
                    if (spaceBelow < optionBox.offsetHeight) {
                        optionBox.style.top = 'auto';
                        optionBox.style.bottom = input.offsetHeight + 'px';
                    } else {
                        optionBox.style.top = input.offsetHeight + 'px';
                        optionBox.style.bottom = 'auto';
                    }
                });
                // KIVÁLASZTÁS
                optionBox.querySelectorAll('div').forEach(option => {
                    option.addEventListener('click', () => {
                        dropdown.classList.remove('active');
                    });
                });

            });
            // KATTINTÁS KÍVÜL → BEZÁR
            document.addEventListener('click', () => {
                dropdowns.forEach(d => d.classList.remove('active'));
            });
        });

        //Modal kezelés
        document.addEventListener('DOMContentLoaded', () => {
            const modals = {
                "Esemény": document.getElementById('eventModal'),
                "Teendő": document.getElementById('taskModal')
            };

            document.querySelectorAll('.option div').forEach(opt => {
                opt.onclick = () => {
                    const modal = modals[opt.innerText.trim()];
                    if (modal) modal.style.display = 'block';
                };
            });

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.onclick = () => {
                    document.querySelectorAll('.custom-modal-overlay')
                        .forEach(m => m.style.display = 'none');
                };
            });

            window.onclick = (e) => {
                if (e.target.classList.contains('custom-modal-overlay')) {
                    e.target.style.display = 'none';
                }
            };
        });

//DARKMODE_____________________________________________________________________________________________________________________________
                                    
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

<!-- ESEMÉNY MODAL -->
<div id="eventModal" class="custom-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Új esemény</h3>
            <button class="close-modal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="text" name="nev" placeholder="Esemény neve" required>
                <textarea name="leiras" placeholder="Leírás"></textarea>
                <label>Kezdet</label>
                <input type="datetime-local" name="kezdet" required>
                <label>Vége</label>
                <input type="datetime-local" name="vege">
                <label>Kategória ID</label>
                <input type="number" name="esemenykat_id">
            </div>
            <div class="modal-footer">
                <button type="submit" class="save-btn" name="save_event">Mentés</button>
            </div>
        </form>
    </div>
</div>

<!-- TEENDŐ MODAL -->
<div id="taskModal" class="custom-modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Új teendő</h3>
            <button class="close-modal">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="text" name="nev" placeholder="Feladat neve" required>
                <textarea name="leiras" placeholder="Leírás"></textarea>
                <label>Határidő</label>
                <input type="date" name="hatarido">
            </div>
            <div class="modal-footer">
                <button type="submit" class="save-btn" name="save_task">Hozzáadás</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
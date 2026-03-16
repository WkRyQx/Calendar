
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
<div class="dropdown2">
        <i class="fa-solid fa-plus"></i>
        <input type="text" class="textBox"  placeholder=""  name="letrehozas" readonly >
        <div class="option">
            <div>
                Esemény
            </div>
            <div >
                Teendő
            </div>
        </div>
    </div>



<form method="Post" action="kijelentkezes.php" class="kijelentkezes">
    <div>
        <button type="submit">Kijelentkezés</button>
    </div>
</form>

<form method="get" action="profil.php">
    <div>
        <button>Fiók</button>
    </div>
</form>



<div id="sidebar">
    <button id="closeSidebarBtn" type="button"><i class="fa-solid fa-bars"></i></button>
    <div class="dropdown">
        <i class="fa-solid fa-plus"></i>
        <input type="text" class="textBox"  placeholder="Létrehozás"  name="letrehozas" readonly >
        <div class="option">
            <div>
                Esemény
            </div>
            <div >
                Teendő
            </div>
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

            echo "<h2>
            <a href='?year=$prevYear&month=$prevMonth&view=$view$prevDateParam'>&laquo;</a>
            $monthName
            <a href='?year=$nextYear&month=$nextMonth&view=$view$nextDateParam'>&raquo;</a>
            </h2>";
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
                    <?php endforeach; ?> 
                    <?php endif; ?>
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
                    WHERE DATE(e.kezdet) BETWEEN ? AND ?
                    ORDER BY e.kezdet
                ");
                $stmt->execute([$weekStart, $weekEnd]);
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
                    
                            <?php foreach ($weekEvents as $event):
                                if (date('Y-m-d', strtotime($event['kezdet'])) == $currentDay):
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
                    WHERE DATE(e.kezdet) BETWEEN ? AND ?
                    ORDER BY e.kezdet
                ");
                $stmt->execute([date('Y-m-d', $monthStartTs), $monthEndStr]);
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

        // dropdown initialisation
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                const input = dropdown.querySelector('.textBox');
                const optionBox = dropdown.querySelector('.option');
                if (!input || !optionBox) return;

                const options = optionBox.querySelectorAll('div');
                if (options.length === 0) return;

                input.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdown.classList.toggle('active');

                    const rect = input.getBoundingClientRect();
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    if (spaceBelow < optionBox.offsetHeight && spaceAbove > spaceBelow) {
                        optionBox.style.top = 'auto';
                        optionBox.style.bottom = `${input.offsetHeight}px`;
                    } else {
                        optionBox.style.top = `${input.offsetHeight}px`;
                        optionBox.style.bottom = 'auto';
                    }
                });

                options.forEach(option => {
                    option.addEventListener('click', (e) => {
                        dropdown.classList.remove('active');
                        const type = option.innerText.trim();
                    });
                });

                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
    const options = document.querySelectorAll('.option div');
    const eventModal = document.getElementById('eventModal');
    const taskModal = document.getElementById('taskModal');
    const closeBtns = document.querySelectorAll('.close-modal');

    options.forEach(opt => {
        opt.onclick = function() {
            const text = this.innerText.trim();
            if (text === "Esemény") eventModal.style.display = 'block';
            if (text === "Teendő") taskModal.style.display = 'block';
        };
    });

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            eventModal.style.display = 'none';
            taskModal.style.display = 'none';
        };
    });

    window.onclick = (event) => {
        if (event.target.classList.contains('custom-modal-overlay')) {
            event.target.style.display = 'none';
        }
    };
});


</script>

<?php
// ESEMÉNY MENTÉS
if(isset($_POST['save_event'])){

$nev = $_POST['nev'] ?? '';
$leiras = $_POST['leiras'] ?? '';
$kezdet = $_POST['kezdet'] ?? '';
$vege = $_POST['vege'] ?? '';
$esemenykat_id = $_POST['esemenykat_id'] ?? NULL;
$felhasznalo_id = $_POST['felhasznalo_id'] ?? NULL;

$sql = "INSERT INTO esemeny 
(nev, leiras, kezdet, vege, esemenykat_id, felhasznalo_id)
VALUES 
('$nev','$leiras','$kezdet','$vege','$esemenykat_id','$felhasznalo_id')";

$conn->query($sql);

header("Location: home.php");
exit();
}
?>

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

                <input type="hidden" name="felhasznalo_id" value="<?php echo $_SESSION['user_id']; ?>">

            </div>

            <div class="modal-footer">
                <button type="submit" class="save-btn" name="save_event">Mentés</button>
            </div>
        </form>

    </div>
</div>

<?php
// TEENDŐ MENTÉS
if(isset($_POST['save_task'])){

$nev = $_POST['nev'] ?? '';
$leiras = $_POST['leiras'] ?? '';
$hatarido = $_POST['hatarido'] ?? '';
$kesz = $_POST['kesz'] ?? 0;
$felhasznalo_id = $_POST['felhasznalo_id'] ?? NULL;

$sql = "INSERT INTO teendo 
(nev, leiras, hatarido, kesz, felhasznalo_id)
VALUES 
('$nev','$leiras','$hatarido','$kesz','$felhasznalo_id')";

$conn->query($sql);

header("Location: home.php");
exit();

}
?>

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

                <input type="hidden" name="felhasznalo_id" value="<?php echo $_SESSION['user_id']; ?>">

            </div>

            <div class="modal-footer">
                <button type="submit" class="save-btn" name="save_task">Hozzáadás</button>
            </div>
        </form>
    </div>


</div>
</body>
</html>
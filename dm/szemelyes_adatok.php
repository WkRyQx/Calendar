<?php
if (isset($_GET['mod'])) {
    $valasztas = $_GET['mod']; // 'dark' vagy 'light'
    setcookie("tema", $valasztas, time() + (86400 * 30), "/"); 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stilus = $_COOKIE['tema'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="profil.css">
    <title>Személyes adatok</title>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <div id="cimsor">
        <div id="cim">
            <form method="get" action="home.php">
                <button><-</button>
            </form> 
            <span>Személyes adatok</span>
        </div>
    </div>

    <div id="navdiv">
        <nav id="nav">
            <a href="profil.php" class="menupont">

                <div class="menupontDiv">
                    <i class="fa-solid fa-house"></i>
                    <div class="menupontSpan">
                        <span>Kezdőlap</span>
                    </div>     
                </div>
            </a>

            <br>
            <a href="szemelyes_adatok.php" class="menupont">

                <div class="menupontDiv">
                    <i class="fa-solid fa-user"></i>
                    <div class="menupontSpan">
                        <span>Személyes adatok</span>
                    </div>
                </div>
            </a>

            <br>
            <a href="biztonsag.php" class="menupont">

                <div class="menupontDiv">
                    <i class="fa-solid fa-lock"></i>
                    <div class="menupontSpan">
                        <span>Biztonság</span>
                    </div>
                </div>
            </a>

            <br>
        </nav>
    </div>
    
</body>
</html>
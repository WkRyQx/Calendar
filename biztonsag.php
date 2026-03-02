<?php

session_start();

$lang = $_SESSION['lang'] ?? 'hun';

$langFile = __DIR__ . "/lang/$lang.php";

if (!file_exists($langFile)) {
    die("Nyelvi fájl nem találtahó!: $langFile");
}

$translations = include $langFile;

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profil.css">
    <script src="setting.js" defer></script>
    <title>Biztonság</title>
</head>
<body>
    <div id="cimsor">
       
        <div id="cim">
            <form method="get" action="home.php">
            <button><-</button>
            </form> 
            Biztonság
        </div>
    </div>

    <div id="navdiv">
        <nav id="nav">
            <a href="profil.php" class="menupont">

                <div class="menupontDiv">
                    <img src="icons/home.png" alt="anyad" class="icon">
                    <div class="menupontSpan">
                        <span>Kezdőlap</span>
                    </div>     
                </div>
            </a>

            <br>
            <a href="szemelyes_adatok.php" class="menupont">

                <div class="menupontDiv">
                    <img src="icons/user.png" alt="anyad" class="icon">
                    <div class="menupontSpan">
                        <span>Személyes adatok</span>
                    </div>
                </div>
            </a>

            <br>
            <a href="biztonsag.php" class="menupont">

                <div class="menupontDiv">
                    <img src="icons/lock.png" alt="anyad" class="icon">
                    <div class="menupontSpan">
                        <span>Biztonság</span>
                    </div>
                </div>
            </a>

            <br>
        </nav>
    </div>
    
    <div class="menu2">
            <h3><?= $translations['szoveg4']?></h3>
            <hr>
            <div class="beltartalom">
                <label><?= $translations['profilelabel']?></label>
                <form method="post">
                    <input type="text" placeholder="<?= $translations['place1']?>">
                    <input type="text" placeholder="<?= $translations['place3']?>">
                    <input type="password" placeholder="<?= $translations['place2']?>">
                    <button type="button"><?= $translations['profile']?></button>
                    <button id="adat_submit"><?= $translations['profile2']?></button>
                </form>
            </div>
            <hr>
        </div>
</body>
</html>
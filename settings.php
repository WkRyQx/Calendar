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
    <title>Beállítások</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="settings.css">
    <script src="settings.js" defer></script>
</head>
<body>
    <header class="header">
        <i class="fa-solid fa-gear"></i>
        <h2><?= $translations['szoveg1'] ?></h2>
        <input type="text" id="kereso" placeholder="<?php $translations['search1']?>">
        <button onclick="keres()"><?php $translations['search2']?></button>
    </header>
    <div class="menu">
        <div class="menu2">
            <h3><?= $translations['szoveg2']?></h3>
            <hr>
            <div class="beltartalom">
                <label><?= $translations['label']?></label>
                <select id="languageSwitcher">
                    <option value="hun" <?= $lang === 'hun' ? 'selected': '' ?>>Magyar - Hungarian</option>
                    <option value="eng" <?= $lang === 'eng' ? 'selected': '' ?>>Angol - English</option>
                    <option value="deu" <?= $lang === 'deu' ? 'selected': '' ?>>Német - Deutsch</option>
                </select>
                <hr>
                <label><?= $translations['label2']?></label>
                <select name="timezone">
                    <option value="Europe/Budapest">Budapest (GMT+1)</option>
                </select>
            </div>
            <hr>
        </div>
        <div class="menu2">
            <h3><?= $translations['szoveg3']?></h3>
            <hr>
            <div class="beltartalom">
                <label><?= $translations['reminding']?></label>
                <select name="reminder">
                    <option value="5"><?= $translations['reminder1']?></option>
                    <option value="10"><?= $translations['reminder2']?></option>
                    <option value="30"><?= $translations['reminder3']?></option>
                </select>
            </div>
            <hr>
        </div>
        <div class="menu2_1">
            <h3><?= $translations['szoveg5']?></h3>
            <hr>
            <div class="beltartalom">
                <label><?= $translations['eventsee1']?></label>
                <select name="reminder">
                    <option value="1"><?= $translations['eventsee2']?></option>
                    <option value="2"><?= $translations['eventsee3']?></option>
                    <option value="3"><?= $translations['eventsee4']?></option>
                </select>
            </div>
            <hr>
        </div>
    </div>

<script>
    function kereses() {
    let szo = document.getElementById("searchInput").value.toLowerCase();
    if (!szo) return;

    clearHighlight();

    document.querySelectorAll("*").forEach(el => {
        if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
            let text = el.innerText;
            if (text && text.toLowerCase().includes(szo)) {
                el.innerHTML = text.replace(new RegExp(szo, "gi"), '<span class="highlight">$&</span>');
            }
        }
    });

    document.querySelectorAll("option").forEach(option => {
        if (option.text.toLowerCase().includes(szo)) {
            option.selected = true;
            option.parentElement.style.background = "yellow";
        }
    });
}

function clearHighlight() {
    document.querySelectorAll(".highlight").forEach(e => {
        e.replaceWith(e.textContent);
    });

    document.querySelectorAll("button,input,option").forEach(e => {
        e.style.background = "";
    });
}

document.getElementById('languageSwitcher').addEventListener('change', function () {
    fetch('set-language.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'lang=' + this.value
    }).then(() => location.reload());
});
</script>



</body>
</html>
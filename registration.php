<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>
    <link rel="stylesheet" href="regist.css">
    <script src="regist.js" defer></script>
</head>
<body>
    <header></header>
    <div class="registration">
        <div>
            <div id="input">
                <input class="input_text" type="text" placeholder="Felhasználónév: ">
                <input class="input_text" type="text" placeholder="Email: ">
                <input class="input_text" type="password" placeholder="Jelszó: ">
            </div>
            <button class="submit_gomb" type="button">Regisztráció</button>
        </div>
        <div class="gombok_regist">
            <button type="button" id="bejGomb"><a href="login.php">Bejelentkezés</a></button>
            <button type="button" id="regGomb"><a href="registration.php">Regisztráció</a></button>
        </div>
    </div>
</body>
</html>
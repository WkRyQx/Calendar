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

$action = $_POST['action'] ?? '';
if ($action === 'register')
    {
        $email = trim($_POST["email"] ?? "");
        $jelszo = $_POST["jelszo"] ?? "";
        $nev = trim($_POST["nev"] ?? "");
        if ($nev === "")
            {
                $error = ("A név megadása kötelező.");
            }
            else{
    
                    $hash = password_hash($jelszo, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO felhasznalo (email,  nev, jelszo, mikor_keszult , szerepkor_id) VALUES (?, ?, ?, NOW(), 2)");
                if ($stmt === false)
                    {
    
                        $error = ("Hiba előkészítéskor: " . $conn->error);
                    } else {
    
                        $stmt->bind_param('sss', $email, $nev, $hash);
                        if ($stmt->execute())
                            {
    
                                $success = ("Sikeres regisztráció! Most már bejelentkezhetsz.");
                            } else {
    
                                $error = ("Hiba a regisztrációnál: " . $stmt->error);
                            }
                            $stmt->close();
                }
            }
        }

?>
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

<?php if ($success): ?>
    <div class="msg success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="msg error"><?= $error ?></div>
<?php endif; ?>

    <div class="registration">
        <div>
            <form method="post">
                <div id="input">
                    <input type="hidden" name="action" value="register">
                    <input class="input_text" type="text" placeholder="Email: " name="email">
                    <input class="input_text" type="text" placeholder="Felhasználónév: " name="nev">
                    <input class="input_text" type="password" placeholder="Jelszó: " name="jelszo">
                </div>
                <button class="submit_gomb" type="submit" name="actio">Regisztráció</button>
            </form>   
        </div>

        <div class="gombok_regist">
            <button type="button" id="bejGomb"><a href="login.php">Bejelentkezés</a></button>
            <button type="button" id="regGomb"><a href="registration.php">Regisztráció</a></button>
        </div>
    </div>

<?php
$conn->close();
?>
</body>
</html>
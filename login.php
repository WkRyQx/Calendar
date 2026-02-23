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
if ($action === 'login') {
         
        $email = trim($_POST["emaillog"] ?? "");
        $jelszo = $_POST["jelszolog"] ?? "";

        if ($email === "" || $jelszo === "") {
            $error = ("Add meg az emailt és a jelszót.");
        } else {
                $stmt = $conn->prepare("SELECT id, email, jelszo, nev, szerepkor_id FROM felhasznalo WHERE email = ?");
            if ($stmt === false) {
                $error = ("Hiba előkészítéskor: " . $conn->error);
            } else {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $db_email, $hash, $nev, $szerepkor);
                    $stmt->fetch();

                    if (password_verify($jelszo, $hash)) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_name'] = $nev;
                        $_SESSION['user_email'] = $db_email;
                        $_SESSION['user_password'] = $hash;
                        $_SESSION['user_szerepkor'] = $szerepkor;

                        if($_SESSION['user_szerepkor'] == 2)
                            header("Location: home.php");
                        else{
                            header("Location: home_admin.php");
                        }
                        exit;
                    } else {
                        $error = ("Hibás email vagy jelszó.");
                    }
                } else {
                    $error = ("Hibás email vagy jelszó.");
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
                    <input type="hidden" name="action" value="login">
                    <input class="input_text" type="text" placeholder="Email: " name="emaillog">
                    <input class="input_text" type="password" placeholder="Jelszó: " name="jelszolog">
                </div>
                <button class="submit_gomb" type="submit" name="actio">Bejelentkezés</button>
            </form>   
        </div>
        <div class="gombok_login">
            <button type="button" id="bejGomb2"><a href="login.php">Bejelentkezés</a></button>
            <button type="button" id="regGomb2"><a href="registration.php">Regisztráció</a></button>
        </div>
    </div>

<?php
$conn->close();
?>
</body>
</html>
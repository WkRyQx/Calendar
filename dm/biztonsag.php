<?php
/* =========================SESSION + DB (MYSQLI)========================= */
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

if (isset($_GET['mod'])) {
    $valasztas = $_GET['mod']; // 'dark' vagy 'light'
    setcookie("tema", $valasztas, time() + (86400 * 30), "/"); 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$stilus = $_COOKIE['tema'] ?? 'light';

$userId = $_SESSION['user_id'];

$success = "";
$error = "";


$lang = $_SESSION['lang'] ?? 'hun';

$langFile = __DIR__ . "/lang/$lang.php";

if (!file_exists($langFile)) {
    die("Nyelvi fájl nem találtahó!: $langFile");
}

$translations = include $langFile;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profilkep']) && is_uploaded_file($_FILES['profilkep']['tmp_name'])) {

        $fileTmp = $_FILES['profilkep']['tmp_name'];
        $fileName = basename($_FILES['profilkep']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowed)) {
            $uploadDir = "Profilkepek/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = "user_" . $userId . "_" . time() . "." . $fileExt;
            $targetFile = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $targetFile)) {
                $stmt = $conn->prepare("UPDATE felhasznalo SET profilkep = ? WHERE id = ?");
                $stmt->bind_param("si", $newFileName, $userId);

                if ($stmt->execute()) {
                    $_SESSION["user_profilkep"] = $newFileName;
                    $message = "Profilkép frissítve!";
                } else {
                    $message = "Adatbázis hiba.";
                }

                $stmt->close();

            } else {
                $message = "Feltöltési hiba.";
            }

        } else {
            $message = "Csak képek engedélyezettek (jpg, png, gif).";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <script src="setting.js" defer></script>
    <title>Biztonság</title>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <div id="cimsor">
       
        <div id="cim">
            <form method="get" action="home.php">
            <button><-</button>
            </form> 
            <span>Biztonság</span>
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

<div style="text-align:center; margin-bottom:20px;">
    <div class="profile-icon" style="margin:auto;">
        <?php if (!empty($_SESSION["user_profilkep"])): ?>
            <img src="uploads/<?php echo $_SESSION["user_profilkep"]; ?>">
        <?php else: ?>
            <?php echo strtoupper($_SESSION["user_name"][0]); ?>
        <?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
        <input type="file" name="profilkep" required>
        <br><br>
        <button type="submit">Profilkép feltöltése</button>
    </form>
</div>
</body>
</html>
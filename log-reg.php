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
$success = "";
$error = "";

/* =========================Bejelentkezés-Regisztráció========================= */
if($_SERVER["REQUEST_METHOD"]==="POST"){ $action=$_POST["action"] ?? ""; if($action==="register"){ 
    $email=trim($_POST["email"] ?? ""); $nev=trim($_POST["nev"] ?? ""); 
    $jelszo=$_POST["jelszo"] ?? ""; 
    if($email==="" || $nev==="" || $jelszo===""){ 
        $error="Minden mezőt ki kell tölteni."; 
    }else{ 
        $hash=password_hash($jelszo,PASSWORD_DEFAULT); 
        $stmt=$conn->prepare("INSERT INTO felhasznalo(email,nev,jelszo,mikor_keszult,szerepkor_id) VALUES(?,?,?,NOW(),2)"); 
        if($stmt){ $stmt->bind_param("sss",$email,$nev,$hash); 
        if($stmt->execute()){ 
            $success="Sikeres regisztráció!"; 
        }else{ 
            $error="Hiba a regisztrációnál."; 
        } 
        $stmt->close(); } } } if($action==="login"){ $email=trim($_POST["emaillog"] ?? ""); 
        $jelszo=$_POST["jelszolog"] ?? ""; 
        if($email==="" || $jelszo===""){ 
            $error="Add meg az emailt és a jelszót.";
        }else{ 
            $stmt=$conn->prepare("SELECT id,email,jelszo,nev,profilkep,szerepkor_id FROM felhasznalo WHERE email=?"); 
            if($stmt){ $stmt->bind_param("s",$email); 
            $stmt->execute(); $stmt->store_result(); 
            if($stmt->num_rows===1){ 
                $stmt->bind_result($id,$db_email,$hash,$nev, $profilkep ,$szerepkor); 
                $stmt->fetch(); 
                if(password_verify($jelszo,$hash)){ 
                    session_regenerate_id(true); 
                    $_SESSION["user_id"]=$id; 
                    $_SESSION["user_name"]=$nev; 
                    $_SESSION["user_email"]=$db_email; 
                    $_SESSION["user_profilkep"]=$profilkep;
                    $_SESSION["user_szerepkor"]=$szerepkor; 
                    if($szerepkor==2){
                         header("Location: home.php"); 
                    }else{ 
                        header("Location: home_admin.php"); 
                    } exit; }
                    else{
                         $error="Hibás email vagy jelszó."; 
                    } }else{ 
                        $error="Hibás email vagy jelszó."; 
                        } 
                        $stmt->close(); 
                        } 
                    }
                }
            } 
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <title>Calendar</title>
    <style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }
    
    body{
        min-height: 100vh;
        display:flex;
        justify-content:center;
        align-items:center;
        background-color: white;
    }

    .wrapper{
        border-radius:20px;
        box-shadow:0 10px 25px rgba(0,0,0,0.15);
        width: 370px;
        overflow: hidden;
    }

    .container{
       position:relative;
       width: 100%;
       height: 380px;
       overflow: hidden;
    }

    .registration{
        position:absolute;
        border:2px solid black;
        width:370px;
        height: 380px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between; 
        padding: 30px 0;
        overflow: hidden;
        border-radius:20px;
        background: white;
        transition: left 0.5s ease;
    }

    #login{
        left:0;
    }

    #register{
        left:370px;
    }

    #input {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-grow: 1; 
        justify-content: center;
    }

    #bejGomb, #bejGomb2, #regGomb, #regGomb2 {
        color: black;
        background-color: white;
        border: 1px solid black;
        border-radius: 20px;
        height: 40px;
        width: 155px; 
        cursor: pointer;
    }

    #regGomb:hover, #bejGomb:hover, #regGomb2:hover, #bejGomb2:hover{
        background-color: black;
        color: white;
        border: 1px solid black;
    }

    .gombok_regist, .gombok_login {
        display: flex;
        justify-content: space-between;
        width: 330px;
        margin-top: 10px;
    }
    
    a {
        text-decoration: none;
        color: unset;
    }

    .input_text {
        border: 2px solid black;
        border-radius: 20px;
        height: 45px;
        width: 330px;
        padding: 0 15px;
        margin-top: 8px;
        display: block;
    }

    .submit_gomb {
        background-color: white;
        margin-top: 15px;
        height: 45px;
        width: 330px;
        border-radius: 20px;
        border: 1px solid black;
        cursor: pointer;
        font-weight: bold;
    }

    .submit_gomb:hover {
        background-color: black;
        color: white;
    }

    .password-container {
        position: relative;
        width: 330px;
        margin-top: 8px;
    }

    .password-container .input_text {
        margin-top: 0;
        width: 100%;
        padding-right: 45px; 
    }

    .password-container i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        z-index: 10;
        font-size: 16px;
    }

    h2{
        text-align: center;
        margin-bottom: 10px;
    }

    /* RESZPONZÍV */
    @media (max-width: 400px) {
        .wrapper {
            width: 95%;
        }
        .container, .registration {
            width: 100%;
        }
        .input_text, .submit_gomb, .password-container, .gombok_login, .gombok_regist {
            width: 90%; 
        }
        #bejGomb, #bejGomb2, #regGomb, #regGomb2 {
            width: 48%; 
        }
    }
    </style>
</head>
<body>

<?php if ($success): ?>
    <div class="msg success"><?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="msg error"><?= $error ?></div>
<?php endif; ?>

<div class="wrapper">
    <div class="container">
        <!-- BEJELENTKEZÉS -->
        <div class="registration" id="login">
            <h2>Bejelentkezés</h2>
            <form method="post" id="input">
                <input type="hidden" name="action" value="login">
                <input class="input_text" type="email" placeholder="Email: " name="emaillog" required>
                <div class="password-container">
                    <input class="input_text" id="show1" type="password" placeholder="Jelszó: " name="jelszolog" required>
                    <i class="fa-solid fa-eye" id="toggle1"></i>
                </div>
                <button class="submit_gomb" type="submit">Bejelentkezés</button>
            </form>   
            <div class="gombok_login">
                <button type="button" id="bejGomb" onclick="login()">Bejelentkezés</button>
                <button type="button" id="regGomb" onclick="register()">Regisztráció</button>
            </div>
        </div>

        <!-- REGISZTRÁCIÓ -->
        <div class="registration" id="register">
            <h2>Regisztráció</h2>
            <form method="post" id="input">
                <input type="hidden" name="action" value="register">
                <input class="input_text" type="email" placeholder="Email: " name="email" required>
                <input class="input_text" type="text" placeholder="Felhasználónév: " name="nev" required>
                <div class="password-container">
                    <input class="input_text" id="show2" type="password" placeholder="Jelszó: " name="jelszo" required>
                    <i class="fa-solid fa-eye" id="toggle2"></i>
                </div>
                <button class="submit_gomb" type="submit">Regisztráció</button>
            </form>   
            <div class="gombok_regist">
                <button type="button" id="bejGomb2" onclick="login()">Bejelentkezés</button>
                <button type="button" id="regGomb2" onclick="register()">Regisztráció</button>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
?>

<script>
    var l = document.getElementById("login");
    var r = document.getElementById("register");
    function login() {
        l.style.left = "0px";
        r.style.left = "370px";
    }
    function register() {
        l.style.left = "-370px";
        r.style.left = "0px";
    }

    function setupPasswordToggle(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleId);
        
        if (passwordInput && toggleIcon) {
            toggleIcon.addEventListener("click", () => {
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    toggleIcon.classList.replace("fa-eye", "fa-eye-slash");
                } else {
                    passwordInput.type = "password";
                    toggleIcon.classList.replace("fa-eye-slash", "fa-eye");
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupPasswordToggle("show1", "toggle1");
        setupPasswordToggle("show2", "toggle2");
    });
</script>
</body>
</html>

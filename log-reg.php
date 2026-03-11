<?php 
session_start();
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "calendar"; 
$conn = new mysqli($servername,$username,$password,$dbname); 

if ($conn->connect_error) { die("Hiba a kapcsolódáskor: ".$conn->connect_error); } 
$success=""; 
$error=""; 
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
            $stmt=$conn->prepare("SELECT id,email,jelszo,nev,szerepkor_id FROM felhasznalo WHERE email=?"); 
            if($stmt){ $stmt->bind_param("s",$email); 
            $stmt->execute(); $stmt->store_result(); 
            if($stmt->num_rows===1){ 
                $stmt->bind_result($id,$db_email,$hash,$nev,$szerepkor); 
                $stmt->fetch(); 
                if(password_verify($jelszo,$hash)){ 
                    session_regenerate_id(true); 
                    $_SESSION["user_id"]=$id; 
                    $_SESSION["user_name"]=$nev; 
                    $_SESSION["user_email"]=$db_email; 
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
    <title>Calendar</title>
    <style>
    *{
        margin:0;
        padding:0;
        box-sizing:border-box;
    }
    
    body{
        height:100vh;
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .registration{
        position:absolute;
        border:2px solid black;
        width:370px;
        height:370px;
        padding-top:5px;
        padding-left:5px;    
        justify-content: center;
        overflow: hidden;
        border-radius:20px;
    }

    #input {
        margin-left: 10px;
        padding-bottom: 5px;
    }

    #bejGomb, #bejGomb2 {
        color: black;
        background-color: white;
        border: 1px solid black;
        border-radius: 20px;
        height: 40px;
        width: 130px;
    }
    #regGomb, #regGomb2 {
        color: black ;
        background-color: white;
        border: 1px solid black;
        border-radius: 20px;
        height: 40px;
        width: 130px;
    }

    #regGomb:hover, #bejGomb:hover ,  #regGomb2:hover, #bejGomb2:hover{
        background-color: black;
        color: white;
        border: 1px solid black;
    }

    .gombok_regist {
        margin-top: 30px;
        margin-left: 44px;
    }
    .gombok_login {
        margin-top: 75px;
        margin-left: 44px;
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
        padding: 10px;
        margin-top: 5px;

    }
    .submit_gomb {
        background-color: white;
        margin-top: 10px;
        height: 45px;
        width: 330px;
        padding: 10px;
        border-radius: 20px;
        margin-left: 10px;
    }

    .submit_gomb:hover {
        background-color: black;
        color: white;
        border: 1px solid black;
    }

    .registration form{
        position: relative;
        transition: 0.4s;
    }

    #login{
        left:0;
        transition:left 0.5s ease;
    }

    #register{
        left:370px;
        transition:left 0.5s ease;
    }
        
    .container{
       position:relative;
       width:370px;
       height:370px;
       overflow: hidden;
    }

    .wrapper{
        border-radius:20px;
        box-shadow:0 10px 25px rgba(0,0,0,0.15);
    }

    h2{
        text-align: center;
        margin-bottom: 10px;
        margin-top: 20px;
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
        <div class="registration" id="login">
            <h2>Bejelentkezés</h2>
            <form method="post">
                <div id="input">
                    <input type="hidden" name="action" value="login">
                    <input class="input_text" type="text" placeholder="Email: " name="emaillog">
                    <input class="input_text" type="password" placeholder="Jelszó: " name="jelszolog">
                </div>
                    <button class="submit_gomb" type="submit" name="actio">Bejelentkezés</button>
            </form>   
            <div class="gombok_login">
                <button type="button" id="bejGomb" onclick="login()">Bejelentkezés</button>
                <button type="button" id="regGomb" onclick="register()">Regisztráció</button>
            </div>
        </div>

        <div class="registration" id="register">
            <h2>Regisztráció</h2>
            <form method="post">
                <div id="input">
                    <input type="hidden" name="action" value="register">
                    <input class="input_text" type="text" placeholder="Email: " name="email">
                    <input class="input_text" type="text" placeholder="Felhasználónév: " name="nev">
                    <input class="input_text" type="password" placeholder="Jelszó: " name="jelszo">
                </div>
                    <button class="submit_gomb" type="submit" name="actio">Regisztráció</button>
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

</script>
</body>
</html>
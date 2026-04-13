<?php

session_start();

$allowed = ['hun', 'eng', 'deu'];

if (isset($_POST['lang']) && in_array($_POST['lang'], $allowed)) {
    $_SESSION['lang'] = $_POST['lang'];
}

?>
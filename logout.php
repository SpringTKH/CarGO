<?php
    session_start();
    $_SESSION = array();
    session_destroy();
    $_SESSION['success_message'] = "You are now logged out!";
    header("location: index.php");
    exit;
?>
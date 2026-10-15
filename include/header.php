<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    $loggedIn = false;
    $fullName = "";

    if (isset($_SESSION['username'])) {
        $loggedIn = true;
        $username = $_SESSION['username'];

        $sql = "SELECT first_name, surname FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $first_name, $surname);
            if (mysqli_stmt_fetch($stmt)) {
                $fullName = htmlspecialchars($first_name . " " . $surname);
            }
            mysqli_stmt_close($stmt);
        }
    }
?>
<div class="fullHeader">
    <div class="header">
        <div class="logo">
            <img src="images/logo.png" alt="logo">
        </div>
        <div class="title">
            <h>CarGo Transport Services Sdn. Bhd.</h>
        </div>

        <div class="logSign">
            <?php if ($loggedIn): ?>
                <span class="welcome-text">Welcome, <?= $fullName ?></span>
                <div class="profile-container">
                    <img src="images/pfp.png" alt="Profile" class="profile-img" onclick="toggleDropdown()">
                    <div id="profile-dropdown" class="profile-dropdown">
                        <a href="reset_password.php">Reset Password</a>
                        <a href="logout.php">Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="button login-button">LOGIN</a>
                <a href="signUp.php" class="button signUp-button">SIGN UP</a>
            <?php endif; ?>
        </div>
    </div>
    <?php include("navigator.php"); ?>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById("profile-dropdown");
        dropdown.classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('.profile-img')) {
            const dropdown = document.getElementById("profile-dropdown");
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    }
</script>
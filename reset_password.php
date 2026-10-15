<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    $username = $new_password = $confirm_password = "";
    $username_err = $new_password_err = $confirm_password_err = $reset_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Phase 2.2: CSRF validation
        if (!verify_csrf_token()) {
            $reset_err = "Invalid security token. Please refresh the page and try again.";
        } else {
            if (isset($_SESSION["username"])) {
            $username = $_SESSION["username"];
        } else {
            if (empty(trim($_POST["username"]))) {
                $username_err = "Please enter your username.";
            } else {
                $username = trim($_POST["username"]);
            }
        }

        if (empty(trim($_POST["new_password"]))) {
            $new_password_err = "Please enter a new password.";
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,}$/', trim($_POST["new_password"]))) {
            $new_password_err = "Password must be at least 8 characters long with at least one letter, one number, and one special character.";
        } else {
            $new_password = trim($_POST["new_password"]);
        }

        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Please confirm the password.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($new_password_err) && $new_password !== $confirm_password) {
                $confirm_password_err = "Passwords do not match.";
            }
        }
        }

        if (empty($reset_err) && empty($username_err) && empty($new_password_err) && empty($confirm_password_err)) {
            $sql = "SELECT password_hash, user_id FROM users WHERE username = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $old_password_hash, $user_id);
                    mysqli_stmt_fetch($stmt);

                    if (password_verify($new_password, $old_password_hash)) {
                        $new_password_err = "New password must be different from the old password.";
                    } else {
                        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                            mysqli_stmt_bind_param($update_stmt, "si", $hashed_new_password, $user_id);
                            if (mysqli_stmt_execute($update_stmt)) {
                                session_destroy();
                                header("location: index.php");
                                exit();
                            } else {
                                $reset_err = "Error updating password. Please try again.";
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                } else {
                    $username_err = "Username not found.";
                }

                mysqli_stmt_close($stmt);
            }
        }

        mysqli_close($conn);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>reset Page</title>
    <link rel="stylesheet" href="styles/reset.css">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <div class="reset-page">
        <div class="reset-container">
            <form action="#" method="POST" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <div class ="resetIMG">
                    <div class ="resetLogo"><img src="images/logo.png"></div>
                    <div class ="resetTitle"><h>CarGo Transport Services Sdn. Bhd.</h></div>
                </div>
                <h2 style="font-size: 17px;">Create strong password!</h2>
                <?php 
                    if(!empty($reset_err)){
                        echo '<p style="color:red; font-family:sans-serif; font-size:12px;">' . htmlspecialchars($reset_err) . '</p>';
                    }     
                ?>
                <span style="color:red; font-family:sans-serif; font-size:12px;"><?php echo $new_password_err; ?></span>
                <span style="color:red; font-family:sans-serif; font-size:12px;"><?php echo $username_err; ?></span>
                <label for="username">Username</label>
                <?php if (isset($_SESSION["username"])): ?>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_SESSION["username"]); ?>" readonly>
                <?php else: ?>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                <?php endif; ?>

                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" id="password" 
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}"
                        placeholder="At least 8 characters, with numbers, alphabet & symbol required" required>
                    <div class="toggle-password" onclick="togglePassword()" id="toggleIcon">👁️</div>
                </div>

                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirmPassword" 
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}" placeholder="Repeat your password" required>
                    <div class="toggle-password" onclick="togglePassword()" id="toggleIcon">👁️</div>
                </div>
                <span style="color:red;"><?php echo $confirm_password_err; ?></span>

                <input type="submit" value="reset">

                <div class="reset-note">
                    <a href="login.php">Remember your password now? Click here!</a><br>
                    <a href="signUp.php">No account? Create one!</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const confirmInput = document.getElementById("confirm_password");
            const toggleIcon = document.getElementById("toggleIcon");

            const isHidden = passwordInput.type === "password";

            passwordInput.type = isHidden ? "text" : "password";
            if (confirmInput) {
                confirmInput.type = isHidden ? "text" : "password";
            }

            toggleIcon.textContent = isHidden ? "◡" : "◉";
        }
    </script>
</body>
</html>
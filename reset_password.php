<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    // Phase 2.4: Require authenticated session
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';

    $current_password = $new_password = $confirm_password = "";
    $current_password_err = $new_password_err = $confirm_password_err = $reset_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Phase 2.2: CSRF validation
        if (!verify_csrf_token()) {
            $reset_err = "Invalid security token. Please refresh the page and try again.";
        } else {
            if (empty(trim($_POST["current_password"] ?? ""))) {
                $current_password_err = "Please enter your current password.";
            } else {
                $current_password = trim($_POST["current_password"]);
            }

            if (empty(trim($_POST["new_password"] ?? ""))) {
                $new_password_err = "Please enter a new password.";
            } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,}$/', trim($_POST["new_password"]))) {
                $new_password_err = "Password must be at least 8 characters long with at least one letter, one number, and one special character.";
            } else {
                $new_password = trim($_POST["new_password"]);
            }

            if (empty(trim($_POST["confirm_password"] ?? ""))) {
                $confirm_password_err = "Please confirm the password.";
            } else {
                $confirm_password = trim($_POST["confirm_password"]);
                if (empty($new_password_err) && $new_password !== $confirm_password) {
                    $confirm_password_err = "Passwords do not match.";
                }
            }
        }

        if (empty($reset_err) && empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
            // Target strictly the authenticated user's account by session user_id
            $sql = "SELECT password_hash FROM users WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $old_password_hash);
                    mysqli_stmt_fetch($stmt);

                    if (!password_verify($current_password, $old_password_hash)) {
                        $current_password_err = "The current password you entered is incorrect.";
                    } elseif (password_verify($new_password, $old_password_hash)) {
                        $new_password_err = "New password must be different from the old password.";
                    } else {
                        $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                            mysqli_stmt_bind_param($update_stmt, "si", $hashed_new_password, $user_id);
                            if (mysqli_stmt_execute($update_stmt)) {
                                session_regenerate_id(true);
                                $_SESSION['success_message'] = "Password changed successfully!";
                                header("location: index.php");
                                exit();
                            } else {
                                $reset_err = "Error updating password. Please try again.";
                            }
                            mysqli_stmt_close($update_stmt);
                        } else {
                            $reset_err = "Oops! Something went wrong. Please try again later.";
                        }
                    }
                } else {
                    $reset_err = "User account not found.";
                }

                mysqli_stmt_close($stmt);
            } else {
                $reset_err = "Oops! Something went wrong. Please try again later.";
            }
        }

        mysqli_close($conn);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles/reset.css?v=1.1">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <style>
        /* Suppress Microsoft Edge native password reveal and clear controls */
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="reset-page">
        <div class="reset-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="reset-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <div class="resetIMG">
                    <div class="resetLogo"><img src="images/logo.png" alt="logo"></div>
                    <div class="resetTitle"><h>CarGo Transport Services Sdn. Bhd.</h></div>
                </div>
                <h2 style="font-size: 17px;">Change Your Password</h2>
                <?php 
                    if (!empty($reset_err)) {
                        echo '<p style="color:red; font-family:sans-serif; font-size:12px;">' . htmlspecialchars($reset_err) . '</p>';
                    }     
                ?>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>

                <label for="current_password">Current Password</label>
                <div class="password-wrapper">
                    <input type="password" name="current_password" id="current_password" required placeholder="Enter current password">
                    <button type="button" class="toggle-password" id="toggleCurrent" onclick="togglePassword('current_password', this)" aria-label="Toggle current password visibility">◉</button>
                </div>
                <?php if (!empty($current_password_err)): ?>
                    <span style="color:red; font-family:sans-serif; font-size:12px; display:block; margin-top:-10px; margin-bottom:10px;"><?php echo htmlspecialchars($current_password_err); ?></span>
                <?php endif; ?>

                <label for="new_password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" id="new_password"
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}"
                        placeholder="At least 8 characters, with numbers, alphabet & symbol required" required>
                    <button type="button" class="toggle-password" id="toggleNew" onclick="togglePassword('new_password', this)" aria-label="Toggle new password visibility">◉</button>
                </div>
                <?php if (!empty($new_password_err)): ?>
                    <span style="color:red; font-family:sans-serif; font-size:12px; display:block; margin-top:-10px; margin-bottom:10px;"><?php echo htmlspecialchars($new_password_err); ?></span>
                <?php endif; ?>

                <label for="confirm_password">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password"
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}" placeholder="Repeat your new password" required>
                    <button type="button" class="toggle-password" id="toggleConfirm" onclick="togglePassword('confirm_password', this)" aria-label="Toggle confirm password visibility">◉</button>
                </div>
                <?php if (!empty($confirm_password_err)): ?>
                    <span style="color:red; font-family:sans-serif; font-size:12px; display:block; margin-top:-10px; margin-bottom:10px;"><?php echo htmlspecialchars($confirm_password_err); ?></span>
                <?php endif; ?>

                <input type="submit" value="Change Password">

                <div class="reset-note">
                    <a href="index.php">Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function togglePassword(fieldId, toggleBtn) {
            const input = document.getElementById(fieldId);
            if (!input) return;

            const btn = (typeof toggleBtn === "string")
                ? document.getElementById(toggleBtn)
                : (toggleBtn || (typeof event !== "undefined" ? event.currentTarget : null));

            const isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";

            if (btn) {
                btn.textContent = isHidden ? "◡" : "◉";
                btn.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
            }
        }
    </script>
</body>
</html>
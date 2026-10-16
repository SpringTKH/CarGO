<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    require_once "include/connection.php";

    function get_safe_redirect(string $url): string {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return 'index.php';
        }

        // Reject protocol-relative URLs, leading backslashes, or bypass prefixes
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '\\') || str_starts_with($trimmed, '/\\')) {
            return 'index.php';
        }

        $parts = parse_url($trimmed);
        if ($parts === false) {
            return 'index.php';
        }

        // Reject any external scheme or host
        if (isset($parts['scheme']) || isset($parts['host'])) {
            return 'index.php';
        }

        $path = $parts['path'] ?? '';
        // Reject embedded colons (e.g. javascript:, data:)
        if (str_contains($path, ':')) {
            return 'index.php';
        }

        $base = basename($path);
        // Prevent redirect loops
        if (in_array($base, ['login.php', 'signUp.php', 'logout.php'], true)) {
            return 'index.php';
        }

        // Whitelist allowed internal destinations
        $allowed_pages = [
            'index.php', 'booking.php', 'booking_form.php', 'cart.php',
            'order.php', 'order_item.php', 'order_receipt.php',
            'aboutUs.php', 'FAQs.php', 'contact.php', 'reset_password.php'
        ];

        if (!in_array($base, $allowed_pages, true)) {
            return 'index.php';
        }

        return $trimmed;
    }

    $username = $password = "";
    $username_err = $password_err = $login_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Phase 2.2: CSRF validation
        if (!verify_csrf_token()) {
            $login_err = "Invalid security token. Please refresh the page and try again.";
        } else {
            if (empty(trim($_POST["username"]))) {
            $username_err = "Please enter username.";
        } else {
            $username = trim($_POST["username"]);
        }

        if (empty(trim($_POST["password"]))) {
            $password_err = "Please enter your password.";
        } else {
            $password = trim($_POST["password"]);
        }

        if (empty($username_err) && empty($password_err)) {
            $sql = "SELECT user_id, username, password_hash FROM users WHERE username = ?";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                $param_username = $username;

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                        if (mysqli_stmt_fetch($stmt)) {
                            if (password_verify($password, $hashed_password)) {
                                // Phase 2.4: Session fixation defense
                                session_regenerate_id(true);
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $id;
                                $_SESSION["username"] = $username;

                                // Phase 2.4: Safe internal redirect validation
                                $raw_redirect = $_POST['redirect'] ?? 'index.php';
                                $destination = get_safe_redirect($raw_redirect);
                                
                                $_SESSION['success_message'] = "You have successfully login! Congrats!";
                                header("Location: " . $destination);
                                exit;
                            } else {
                                $login_err = "Invalid username or password.";
                            }
                        }
                    } else {
                        $login_err = "Invalid username or password.";
                    }
                } else {
                    $login_err = "Oops! Something went wrong.";
                }

                mysqli_stmt_close($stmt);
            }
        }

        mysqli_close($conn);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Page</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <?php
                    $raw_ref = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.php';
                    $redirect_val = get_safe_redirect($raw_ref);
                ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_val); ?>">
                <div class ="loginIMG">
                    <div class ="loginLogo"><img src="images/logo.png"></div>
                    <div class ="loginTitle"><h>CarGo Transport Services Sdn. Bhd.</h></div>
                </div>
                <h2>Welcome Back!</h2>
                <?php 
                    if(!empty($login_err)){
                        echo '<p style="color:red; font-family:sans-serif; font-size:12px;">' . htmlspecialchars($login_err) . '</p>';
                    }        
                ?>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>

                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <div class="toggle-password" onclick="togglePassword()" id="toggleIcon">◉</div>
                </div>

                <input type="submit" value="Login">

                <div class="login-note">
                    <p style="margin: 0 0 8px 0; color: #444; font-size: 12px;">Forgot your password? Please contact customer support or the administrator for assistance.</p>
                    <a href="signUp.php">No account? Create one!</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.getElementById("toggleIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.textContent = "◡";
            } else {
                passwordInput.type = "password";
                toggleIcon.textContent = "◉";
            }
        }
    </script>
</body>
</html>
<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    $username = $password = $confirm_password = $first_name = $surname = $birthday = $email = "";
    $username_err = $password_err = $confirm_password_err = $first_name_err = $surname_err = $birthday_err = $email_err = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Phase 2.2: CSRF validation
        if (!verify_csrf_token()) {
            $username_err = "Invalid security token. Please refresh the page and try again.";
        } else {
            if (empty(trim($_POST["username"]))) {
            $username_err = "Please enter a username.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
            $username_err = "Username can only contain letters, numbers, and underscores.";
        } else {
            $sql = "SELECT user_id FROM users WHERE username = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_username);
                $param_username = trim($_POST["username"]);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $username_err = "This username is already taken.";
                    } else {
                        $username = $param_username;
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }

        if (empty(trim($_POST["first_name"]))) {
            $first_name_err = "Please enter your first name.";
        } else {
            $first_name = trim($_POST["first_name"]);
        }

        if (empty(trim($_POST["surname"]))) {
            $surname_err = "Please enter your surname.";
        } else {
            $surname = trim($_POST["surname"]);
        }

        if (empty($_POST["birthday"])) {
            $birthday_err = "Please enter your date of birth.";
        } else {
            $birthday = $_POST["birthday"];
            $today = new DateTime();
            $dob = new DateTime($birthday);
            $age = $today->diff($dob)->y;
            if ($age < 18) {
                $birthday_err = "You must be at least 16 years old to register.";
            }
        }

        if (empty(trim($_POST["email"]))) {
            $email_err = "Please enter your email.";
        } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        } else {
            $sql = "SELECT user_id FROM users WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = trim($_POST["email"]);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "This email is already registered.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }

                mysqli_stmt_close($stmt);
            }
        }

        if (empty(trim($_POST["password"]))) {
            $password_err = "Please enter a password.";
        } elseif (!preg_match('/(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}/', trim($_POST["password"]))) {
            $password_err = "Password must be at least 8 characters and include a letter, number, and special character.";
        } else {
            $password = trim($_POST["password"]);
        }

        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Please confirm password.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "Passwords do not match.";
            }
        }
        }

        if (empty($username_err) && empty($password_err) && empty($confirm_password_err)
            && empty($first_name_err) && empty($surname_err) && empty($birthday_err) && empty($email_err)) {

            $sql = "INSERT INTO users (username, first_name, surname, birthday, email, password_hash) VALUES (?, ?, ?, ?, ?, ?)";

            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssssss", $param_username, $param_first_name, 
                    $param_surname, $param_birthday, $param_email, $param_password);

                $param_username = $username;
                $param_first_name = $first_name;
                $param_surname = $surname;
                $param_birthday = $birthday;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);

                if (mysqli_stmt_execute($stmt)) {
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }

                    $new_user_id = mysqli_insert_id($conn);

                    // Phase 2.4: Session fixation defense
                    session_regenerate_id(true);

                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $new_user_id;
                    $_SESSION["username"] = $username;
                    $_SESSION["first_name"] = $first_name;
                    $_SESSION["surname"] = $surname;

                    header("location: index.php");
                    exit;
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles/signUp.css?v=1.2">
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
    <div class="register-page">
        <div class="register-form-container">
            <form class="register-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <h2>Sign Up</h2>
                <?php if ($username_err) echo "<p class='error'>$username_err</p>"; ?>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>

                <div class="name-row">
                    <div class="first-name">
                        <?php if ($first_name_err) echo "<p class='error'>$first_name_err</p>"; ?>
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>
                    <div class="surname">
                        <?php if ($surname_err) echo "<p class='error'>$surname_err</p>"; ?>
                        <label for="surname">Surname</label>
                        <input type="text" name="surname" id="surname" value="<?php echo htmlspecialchars($surname); ?>" required>
                    </div>
                </div>

                <?php if ($birthday_err) echo "<p class='error'>$birthday_err</p>"; ?>
                <label for="birthday">Date of Birth</label>
                <input type="date" name="birthday" id="birthday" value="<?php echo htmlspecialchars($birthday); ?>" required>

                <?php if ($email_err) echo "<p class='error'>$email_err</p>"; ?>
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="example@domain.com" required>

                <?php if ($password_err) echo "<p class='error'>$password_err</p>"; ?>
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password"
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}"
                        placeholder="At least 8 characters, with numbers, alphabet & symbol required" required>
                    <button type="button" class="toggle-password" id="togglePasswordBtn" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">◉</button>
                </div>

                <?php if ($confirm_password_err) echo "<p class='error'>$confirm_password_err</p>"; ?>
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" 
                        pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}" placeholder="Repeat your password" required>
                    <button type="button" class="toggle-password" id="toggleConfirmPasswordBtn" onclick="togglePassword('confirm_password', this)" aria-label="Toggle confirm password visibility">◉</button>
                </div>               

                <input type="submit" value="Create Account">

                <div class="login-note">
                    <a href="login.php">Already have an account? Log in here</a>
                </div>
            </form>
        </div>

        <div class="register-right-panel">
            <div class="signUpIMG">
                <div class="signUpLogo"><img src="images/logo.png" alt="Logo"></div>
                <div class="signUpTitle"><h>CarGo Transport Services Sdn. Bhd.</h>
                </div>
            </div>
            <div class="signUpDesc">
                <ul>
                    <li>Wherever you're headed,</li>
                    <li><span class="highlight">Car-Go</span> provides dependable rides</li>
                    <li>and smooth service to customers</li>
                    <li>———————your journey starts with us.</li>
                </ul>
            </div>
            <p id="namer">—Spring</p>
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
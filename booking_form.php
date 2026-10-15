<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        $redirect_url = "booking_form.php";
        if (isset($_GET['car_id'])) {
            $redirect_url .= "?car_id=" . urlencode($_GET['car_id']);
        }
        header("Location: login.php?redirect=" . urlencode($redirect_url));
        exit;
    }

    require_once 'include/connection.php';

    if (!isset($_GET['car_id']) || !is_numeric($_GET['car_id'])) {
        die("Invalid request.");
    }

    $car_id = (int)$_GET['car_id'];

    $stmt = $conn->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();

    if (!$car) {
        die("Car not found.");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Car Form</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/booking_form.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <?php if ($success_message): ?>
        <div class="flash-overlay" id="flashBox">
            <div class="flash-content">
                <?= htmlspecialchars($success_message) ?>
                <span class="flash-close" onclick="document.getElementById('flashBox').remove()">×</span>
            </div>
        </div>
    <?php endif; ?>
    <?php include "include/header.php"; ?>
    <div class="background-overlay">
        <div class="form-wrapper">
            <div class="booking-grid">
                <div class="column-left">
                    <h2><?= htmlspecialchars($car['car_name']) ?></h2>
                    <img src="<?= htmlspecialchars($car['car_picture']) ?>" alt="Car Image">
                </div>

                <div class="column-right">
                    <div class="car-info">
                        <div class="row car-info">
                            <div class="info-box">
                                <img src="images/CarType.png" alt="Car Type Icon" class="info-icon">
                                <?= htmlspecialchars($car['car_type']) ?>
                            </div>
                            <div class="info-box">
                                <img src="images/Seat.png" alt="Seat Icon" class="info-icon">
                                <?= htmlspecialchars($car['max_passengers']) ?>
                            </div>
                            <div class="info-box">
                                <img src="images/Gear.png" alt="Gear Icon" class="info-icon">
                                <?= htmlspecialchars($car['gear_type']) ?>
                            </div>
                        </div>
                        <div class="price-box">
                            <p>RM<?= htmlspecialchars($car['rental_price_per_day']) ?></p>
                        </div>
                    </div>

                    <form action="add_to_cart.php" method="POST" id="booking-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                        <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['car_id']) ?>">

                        <div class="input-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="quantity" id="quantity" min="1" required>
                        </div>

                        <div class="input-group">
                            <label for="begin_date">Start Date</label>
                            <input type="date" name="begin_date" id="begin_date" required>
                        </div>

                        <div class="input-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" required>
                        </div>

                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include "include/footer.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().split('T')[0];
        const beginInput = document.getElementById('begin_date');
        const endInput = document.getElementById('end_date');
        const form = document.getElementById('booking-form');

        beginInput.setAttribute('min', today);

        beginInput.addEventListener('change', () => {
            const beginDate = new Date(beginInput.value);
            if (!isNaN(beginDate)) {
                const nextDay = new Date(beginDate);
                nextDay.setDate(beginDate.getDate() + 1);
                endInput.min = nextDay.toISOString().split('T')[0];

                if (new Date(endInput.value) <= beginDate) {
                    endInput.value = '';
                }
            }
        });

        form.addEventListener('submit', function(e) {
            const start = new Date(beginInput.value);
            const end = new Date(endInput.value);

            if (start < new Date(today)) {
                alert("Start date cannot be in the past.");
                e.preventDefault();
                return;
            }

            if (end <= start) {
                alert("End date must be after start date.");
                e.preventDefault();
                return;
            }
        });
    });
    </script>    
</body>
</html>

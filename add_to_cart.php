<?php
    session_start();
    require_once 'include/connection.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['car_id'], $_POST['quantity'], $_POST['begin_date'], $_POST['end_date'])) {
        header("Location: booking.php");
        exit;
    }

    date_default_timezone_set("Asia/Kuala_Lumpur");

    $user_id     = $_SESSION['user_id'];
    $car_id      = intval($_POST['car_id']);
    $quantity    = intval($_POST['quantity']);
    $begin_date  = trim($_POST['begin_date']);
    $end_date    = trim($_POST['end_date']);

    if ($quantity <= 0) {
        die("Invalid quantity.");
    }

    // Validate date format (YYYY-MM-DD)
    $start = DateTime::createFromFormat('Y-m-d', $begin_date);
    $end   = DateTime::createFromFormat('Y-m-d', $end_date);

    if (!$start || $start->format('Y-m-d') !== $begin_date || !$end || $end->format('Y-m-d') !== $end_date) {
        die("Invalid date format.");
    }

    // Enforce booking date rules: start date not in past, end date strictly after start date
    $today = new DateTime(date('Y-m-d'));
    if ($start < $today) {
        die("Start date cannot be in the past.");
    }

    if ($end <= $start) {
        die("End date must be strictly after the start date.");
    }

    $rental_days = $start->diff($end)->days;
    if ($rental_days <= 0) {
        die("Invalid rental period.");
    }

    // Phase 1: Look up the trusted price from the database instead of using client-submitted values
    $stmt = $conn->prepare("SELECT car_name, rental_price_per_day FROM cars WHERE car_id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $stmt->bind_result($car_name, $single_price);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Car not found.");
    }
    $stmt->close();

    // Phase 1: Calculate final price server-side from trusted DB price
    $final_price = $single_price * $quantity * $rental_days;

    $sql = "INSERT INTO cart (user_id, car_id, car_name, quantity, begin_date, end_date, single_price, final_price, rental_days) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisissddi", $user_id, $car_id, $car_name, $quantity, $begin_date, 
        $end_date, $single_price, $final_price, $rental_days);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Car successfully added to your cart!";
        header("Location: booking.php");
        exit;
    } else {
        echo "Failed to add to cart.";
    }
?>
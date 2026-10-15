<?php
    session_start();
    require_once "include/connection.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $phone = $_POST['phone'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    if (!$phone || !$payment_method) {
        die("Missing required fields.");
    }

    $stmt = $conn->prepare("SELECT username, first_name, surname, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $full_name = $user['first_name'] . ' ' . $user['surname'];

    $card_number = $_POST['card_number'] ?? null;
    $cardholder_name = $_POST['card_name'] ?? null;
    $bank_name = $_POST['bank_name'] ?? null;
    $bank_account = $_POST['bank_account'] ?? null;

    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += $item['final_price'];
    }
    $addition_payment = 0;
    $total_price += $addition_payment;

    $stmt = $conn->prepare("INSERT INTO orders 
        (user_id, username, full_name, email, phone_number, payment_method, card_number, 
        cardholder_name, bank_name, bank_account, addition_payment, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");


    $stmt->bind_param("isssssssssdd", $user_id, $user['username'], 
        $full_name, $user['email'], $phone, $payment_method, $card_number, 
        $cardholder_name, $bank_name, $bank_account, $addition_payment, $total_price);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO order_items 
    (order_id, car_id, car_name, single_price, quantity, begin_date, end_date, rental_days, final_price)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($cart_items as $index => $item) {
        $car_id = $item['car_id'];
        $car_name = $item['car_name'];
        $single_price = (float)$item['single_price'];
        $quantity = (int)$item['quantity'];
        $begin_date = $item['begin_date'];
        $end_date = $item['end_date'];
        $rental_days = (int)$item['rental_days'];
        $final_price = (float)$item['final_price'];

        $begin_date_formatted = date('Y-m-d', strtotime($begin_date));
        $end_date_formatted = date('Y-m-d', strtotime($end_date));

        $stmt->bind_param("iisdissid", $order_id, $car_id, $car_name, $single_price, $quantity,
                        $begin_date_formatted, $end_date_formatted, $rental_days, $final_price);

        $stmt->execute();
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: order_receipt.php?order_id=" . $order_id);
    exit;
?>

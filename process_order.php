<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Phase 2.2: CSRF validation
    if (!verify_csrf_token()) {
        http_response_code(403);
        die("Invalid or missing CSRF token.");
    }

    $user_id = $_SESSION['user_id'];
    $phone = trim($_POST['phone'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (!$phone || !$payment_method) {
        die("Missing required fields.");
    }

    // Phase 2.1: Server-side validation of payment method and required fields
    $allowed_methods = ['card', 'bank', 'cash'];
    if (!in_array($payment_method, $allowed_methods, true)) {
        die("Invalid payment method.");
    }

    // Never persist full card numbers, CVVs, or banking credentials in storage
    $card_number = null;
    $cardholder_name = null;
    $bank_name = null;
    $bank_account = null;

    if ($payment_method === 'card') {
        $cardholder_name = trim($_POST['card_name'] ?? '');
        if ($cardholder_name === '') {
            die("Cardholder name is required.");
        }

        $raw_card = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        // Validate mock card number format: standard cards have 12 to 19 digits
        if (!preg_match('/^\d{12,19}$/', $raw_card)) {
            die("Invalid or malformed card number.");
        }

        $last4 = substr($raw_card, -4);
        $card_number = '**** **** **** ' . $last4;
    } elseif ($payment_method === 'bank') {
        $bank_name = trim($_POST['bank_name'] ?? '');
        $allowed_banks = [
            'Alliance Bank', 'Bank Islam', 'Bank Muamalat', 'Bank Rakyat',
            'Bank Simpanan Nasional', 'CIMB', 'Hong Leong Bank', 'Maybank',
            'Public Bank', 'RHB Bank', 'Other'
        ];
        if (!in_array($bank_name, $allowed_banks, true)) {
            die("Invalid bank selected.");
        }

        $raw_account = preg_replace('/\D/', '', $_POST['bank_account'] ?? '');
        // Validate mock bank account format: typically 5 to 20 digits
        if (!preg_match('/^\d{5,20}$/', $raw_account)) {
            die("Invalid or malformed bank account number.");
        }

        $last4 = substr($raw_account, -4);
        $bank_account = '******' . $last4;
    } elseif ($payment_method === 'cash') {
        // Cash payment simulation requires no card or bank metadata
        $card_number = null;
        $cardholder_name = null;
        $bank_name = null;
        $bank_account = null;
    }

    $stmt = $conn->prepare("SELECT username, first_name, surname, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("User not found.");
    }

    $full_name = $user['first_name'] . ' ' . $user['surname'];

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

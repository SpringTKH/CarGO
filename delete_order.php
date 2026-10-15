<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    require_once 'include/connection.php';

    // Phase 1: Authentication check
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $user_id = $_SESSION['user_id'];

    $order_item_id = intval($_POST['order_item_id']);
    $order_id = intval($_POST['order_id']);

    // Phase 1: Verify the order belongs to the logged-in user
    $stmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($order_owner_id);
    if (!$stmt->fetch() || (int)$order_owner_id !== $user_id) {
        $stmt->close();
        $_SESSION['delete_message'] = "Order not found.";
        header("Location: order.php");
        exit;
    }
    $stmt->close();

    // Phase 1: Verify the order item actually belongs to the specified order
    $stmt = $conn->prepare("SELECT single_price, rental_days, quantity, begin_date FROM order_items WHERE order_item_id = ? AND order_id = ?");
    $stmt->bind_param("ii", $order_item_id, $order_id);
    $stmt->execute();
    $stmt->bind_result($price_per_day, $rental_days, $quantity, $begin_date);
    if (!$stmt->fetch()) {
        $stmt->close();
        $_SESSION['delete_message'] = "Order item not found.";
        header("Location: order.php");
        exit;
    }
    $stmt->close();

    date_default_timezone_set("Asia/Kuala_Lumpur");
    $now = new DateTime();
    $begin = new DateTime($begin_date . ' 00:00:00');
    $hours_diff = ($begin->getTimestamp() - $now->getTimestamp()) / 3600;

    $item_total = $price_per_day * $rental_days * $quantity;

    // Enforce server-side cancellation eligibility: reject rentals that have already started or ended
    if ($hours_diff <= 0) {
        $_SESSION['delete_message'] = "Cancellation denied: The rental period has already started or ended.";
        header("Location: order_item.php?order_id=$order_id");
        exit;
    }

    $additional_charge = 0;
    if ($hours_diff > 24) {
        $additional_charge = 0;
    } else {
        // 0 < $hours_diff <= 24: 50% retained for late cancellation
        $additional_charge = $item_total * 0.5;
    }

    // Phase 1: Replace string interpolation with prepared statement
    $stmt = $conn->prepare("UPDATE orders SET addition_payment = addition_payment + ? WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("dii", $additional_charge, $order_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ? AND order_id = ?");
    $stmt->bind_param("ii", $order_item_id, $order_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT SUM(single_price * rental_days * quantity) FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($items_total);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT addition_payment FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($addition_payment);
    $stmt->fetch();
    $stmt->close();

    $items_total = $items_total ?? 0;
    $final_total = $items_total + $addition_payment;

    if ($final_total <= 0.00) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['delete_message'] = "Order item removed. Since there are no remaining charges, the full order has been cancelled.";
        header("Location: order.php");
        exit;
    } else {
        $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("dii", $final_total, $order_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['delete_message'] = "Order item removed successfully.";
        header("Location: order_item.php?order_id=$order_id");
        exit;
    }
} else {
    header("Location: order.php");
    exit;
}
?>

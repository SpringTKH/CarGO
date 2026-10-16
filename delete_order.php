<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once 'include/connection.php';

    // Phase 1: Authentication check
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Phase 2.2: CSRF validation
    if (!verify_csrf_token()) {
        http_response_code(403);
        die("Invalid or missing CSRF token.");
    }

    $user_id = (int)$_SESSION['user_id'];

    // Phase 2.5: Validate request IDs before performing database operations
    $raw_order_id = $_POST['order_id'] ?? null;
    if ($raw_order_id === null || !filter_var($raw_order_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $_SESSION['delete_message'] = "Order not found.";
        header("Location: order.php");
        exit;
    }
    $order_id = (int)$raw_order_id;

    $raw_order_item_id = $_POST['order_item_id'] ?? null;
    if ($raw_order_item_id === null || !filter_var($raw_order_item_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $_SESSION['delete_message'] = "Order item not found.";
        header("Location: order.php");
        exit;
    }
    $order_item_id = (int)$raw_order_item_id;

    // Phase 2.5: Atomic transaction covering validation, penalty update, item deletion, and order sync
    $transaction_started = false;

    try {
        if (!$conn->begin_transaction()) {
            throw new Exception("Failed to start transaction: " . $conn->error);
        }
        $transaction_started = true;

        // Phase 1 & 2.5: Verify the order belongs to the logged-in user (with row lock)
        $stmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("Order lookup prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("i", $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order lookup execute failed: " . $err);
        }
        $stmt->bind_result($order_owner_id);
        $order_found = $stmt->fetch();
        $stmt->close();

        if (!$order_found || (int)$order_owner_id !== $user_id) {
            $conn->rollback();
            $transaction_started = false;
            $_SESSION['delete_message'] = "Order not found.";
            header("Location: order.php");
            exit;
        }

        // Phase 1 & 2.5: Verify the order item belongs to the specified order (with row lock)
        $stmt = $conn->prepare("SELECT single_price, rental_days, quantity, begin_date FROM order_items WHERE order_item_id = ? AND order_id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("Order item lookup prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("ii", $order_item_id, $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order item lookup execute failed: " . $err);
        }
        $stmt->bind_result($price_per_day, $rental_days, $quantity, $begin_date);
        $item_found = $stmt->fetch();
        $stmt->close();

        if (!$item_found) {
            $conn->rollback();
            $transaction_started = false;
            $_SESSION['delete_message'] = "Order item not found.";
            header("Location: order.php");
            exit;
        }

        date_default_timezone_set("Asia/Kuala_Lumpur");
        $now = new DateTime();
        $begin = new DateTime($begin_date . ' 00:00:00');
        $hours_diff = ($begin->getTimestamp() - $now->getTimestamp()) / 3600;

        $item_total = (float)$price_per_day * (int)$rental_days * (int)$quantity;

        // Enforce server-side cancellation eligibility: reject rentals that have already started or ended
        if ($hours_diff <= 0) {
            $conn->rollback();
            $transaction_started = false;
            $_SESSION['delete_message'] = "Cancellation denied: The rental period has already started or ended.";
            header("Location: order_item.php?order_id=" . $order_id);
            exit;
        }

        $additional_charge = 0.0;
        if ($hours_diff > 24) {
            $additional_charge = 0.0;
        } else {
            // 0 < $hours_diff <= 24: 50% retained for late cancellation
            $additional_charge = $item_total * 0.5;
        }

        if ($additional_charge > 0) {
            $stmt = $conn->prepare("UPDATE orders SET addition_payment = addition_payment + ? WHERE order_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Addition payment update prepare failed: " . $conn->error);
            }
            if (!$stmt->bind_param("dii", $additional_charge, $order_id, $user_id) || !$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Addition payment update execute failed: " . $err);
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ? AND order_id = ?");
        if (!$stmt) {
            throw new Exception("Order item deletion prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("ii", $order_item_id, $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order item deletion execute failed: " . $err);
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT SUM(single_price * rental_days * quantity) FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Recalculate order items prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("i", $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Recalculate order items execute failed: " . $err);
        }
        $stmt->bind_result($items_total);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT addition_payment FROM orders WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Addition payment lookup prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("i", $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Addition payment lookup execute failed: " . $err);
        }
        $stmt->bind_result($addition_payment);
        $stmt->fetch();
        $stmt->close();

        $items_total = (float)($items_total ?? 0.0);
        $addition_payment = (float)($addition_payment ?? 0.0);
        $final_total = $items_total + $addition_payment;

        if ($final_total <= 0.00) {
            $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Order deletion prepare failed: " . $conn->error);
            }
            if (!$stmt->bind_param("ii", $order_id, $user_id) || !$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Order deletion execute failed: " . $err);
            }
            $stmt->close();

            $_SESSION['delete_message'] = "Order item removed. Since there are no remaining charges, the full order has been cancelled.";
            $redirect_url = "order.php";
        } else {
            $stmt = $conn->prepare("UPDATE orders SET total_price = ? WHERE order_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Order total update prepare failed: " . $conn->error);
            }
            if (!$stmt->bind_param("dii", $final_total, $order_id, $user_id) || !$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Order total update execute failed: " . $err);
            }
            $stmt->close();

            $_SESSION['delete_message'] = "Order item removed successfully.";
            $redirect_url = "order_item.php?order_id=" . $order_id;
        }

        if (!$conn->commit()) {
            throw new Exception("Transaction commit failed: " . $conn->error);
        }
        $transaction_started = false;

        header("Location: " . $redirect_url);
        exit;

    } catch (Throwable $e) {
        error_log("Order item deletion failed: " . $e->getMessage());
        if ($transaction_started) {
            if (!$conn->rollback()) {
                error_log("Transaction rollback failed: " . $conn->error);
            }
        }
        $_SESSION['delete_message'] = "An error occurred while processing your cancellation. Please try again.";
        header("Location: order.php");
        exit;
    }
} else {
    header("Location: order.php");
    exit;
}
?>

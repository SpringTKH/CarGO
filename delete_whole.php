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

    // Phase 2.5: Atomic transaction covering whole-order cancellation
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

        date_default_timezone_set("Asia/Kuala_Lumpur");
        $current_datetime = new DateTime();
        $current_date = $current_datetime->format("Y-m-d");

        // Verify if any items have already started or passed
        $stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND begin_date <= ?");
        if (!$stmt) {
            throw new Exception("Started items check prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("is", $order_id, $current_date) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Started items check execute failed: " . $err);
        }
        $stmt->bind_result($ongoing_count);
        $stmt->fetch();
        $stmt->close();

        if ($ongoing_count > 0) {
            $conn->rollback();
            $transaction_started = false;
            $_SESSION['delete_message'] = "Cancellation denied: One or more items have already started.";
            header("Location: order.php");
            exit;
        }

        // Fetch order items to calculate cancellation penalties (with row lock)
        $stmt = $conn->prepare("SELECT order_item_id, single_price, rental_days, quantity, begin_date FROM order_items WHERE order_id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("Order items fetch prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("i", $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order items fetch execute failed: " . $err);
        }
        $result = $stmt->get_result();
        if (!$result) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order items get_result failed: " . $err);
        }

        $total_additional = 0.0;
        while ($row = $result->fetch_assoc()) {
            $begin_datetime = new DateTime($row['begin_date'] . ' 00:00:00');
            $hours_diff = ($begin_datetime->getTimestamp() - $current_datetime->getTimestamp()) / 3600;

            // Phase 1: Use single_price (matching the column selected above) instead of rental_price
            $item_total = (float)$row['single_price'] * (int)$row['rental_days'] * (int)$row['quantity'];
            if ($hours_diff <= 24 && $hours_diff > 0) {
                $total_additional += $item_total * 0.5;
            }
        }
        $stmt->close();

        if ($total_additional > 0) {
            $stmt = $conn->prepare("UPDATE orders SET addition_payment = addition_payment + ? WHERE order_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Addition payment update prepare failed: " . $conn->error);
            }
            if (!$stmt->bind_param("dii", $total_additional, $order_id, $user_id) || !$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Addition payment update execute failed: " . $err);
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Order items deletion prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param("i", $order_id) || !$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Order items deletion execute failed: " . $err);
        }
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

        $addition_payment = (float)($addition_payment ?? 0.0);

        if ($addition_payment <= 0.00) {
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

            $_SESSION['delete_message'] = "All items and the order have been successfully removed.";
        } else {
            $stmt = $conn->prepare("UPDATE orders SET total_price = addition_payment WHERE order_id = ? AND user_id = ?");
            if (!$stmt) {
                throw new Exception("Order total update prepare failed: " . $conn->error);
            }
            if (!$stmt->bind_param("ii", $order_id, $user_id) || !$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Order total update execute failed: " . $err);
            }
            $stmt->close();

            $_SESSION['delete_message'] = "All rental items removed. Partial charges (50%) retained for late cancellation.";
        }

        if (!$conn->commit()) {
            throw new Exception("Transaction commit failed: " . $conn->error);
        }
        $transaction_started = false;

        header("Location: order.php");
        exit;

    } catch (Throwable $e) {
        error_log("Order whole deletion failed: " . $e->getMessage());
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
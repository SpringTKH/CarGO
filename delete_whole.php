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

        $user_id = $_SESSION['user_id'];

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

        date_default_timezone_set("Asia/Kuala_Lumpur");
        $current_datetime = new DateTime();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ? AND begin_date <= ?");
        $current_date = $current_datetime->format("Y-m-d");
        $stmt->bind_param("is", $order_id, $current_date);
        $stmt->execute();
        $stmt->bind_result($ongoing_count);
        $stmt->fetch();
        $stmt->close();

        if ($ongoing_count > 0) {
            $_SESSION['delete_message'] = "Cancellation denied: One or more items have already started.";
            header("Location: order.php");
            exit;
        }

        $stmt = $conn->prepare("SELECT order_item_id, single_price, rental_days, quantity, begin_date FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $total_additional = 0;
        while ($row = $result->fetch_assoc()) {
            $begin_datetime = new DateTime($row['begin_date'] . ' 00:00:00');
            $hours_diff = ($begin_datetime->getTimestamp() - $current_datetime->getTimestamp()) / 3600;

            // Phase 1: Use single_price (matching the column selected above) instead of rental_price
            $item_total = $row['single_price'] * $row['rental_days'] * $row['quantity'];
            if ($hours_diff < 24 && $hours_diff > 0) {
                $total_additional += $item_total * 0.5;
            }
        }
        $stmt->close();

        if ($total_additional > 0) {
            // Phase 1: Replace string interpolation with prepared statement
            $stmt = $conn->prepare("UPDATE orders SET addition_payment = addition_payment + ? WHERE order_id = ? AND user_id = ?");
            $stmt->bind_param("dii", $total_additional, $order_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT addition_payment FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->bind_result($addition_payment);
        $stmt->fetch();
        $stmt->close();

        if ($addition_payment <= 0.00) {
            $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['delete_message'] = "All items and the order have been successfully removed.";
        } else {
            $stmt = $conn->prepare("UPDATE orders SET total_price = addition_payment WHERE order_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['delete_message'] = "All rental items removed. Partial charges (50%) retained for late cancellation.";
        }

        header("Location: order.php");
        exit;
    } else {
        header("Location: order.php");
        exit;
    }
?>
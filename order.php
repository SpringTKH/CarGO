<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $delete_message = $_SESSION['delete_message'] ?? null;
    unset($_SESSION['delete_message']);

    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="stylesheet" href="styles/order_list.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <title>My Order List</title>
</head>
<body>
    <?php if ($delete_message): ?>
        <div class="flash-overlay-delete" id="flashBox">
            <div class="flash-content">
                <?= htmlspecialchars($delete_message) ?>
                <span class="flash-close" onclick="document.getElementById('flashBox').remove()">×</span>
            </div>
        </div>
    <?php endif; ?>
    <?php include("include/header.php") ?>
    <div id="mainContent" class="main-content">
        <div class="order-container">
            <?php if(!empty($orders)): ?>
                <h2 class="order-title">My Orders</h2>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date Created</th>
                                <th>Total Price (RM)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <?php
                                        $formatted_order_id = 'ORD-' . str_pad($order['order_id'], 
                                            8, '0', STR_PAD_LEFT);
                                    ?>
                                    <td id="str"><?= htmlspecialchars($formatted_order_id) ?></td>
                                    <td id><?= htmlspecialchars($order['created_at']) ?></td>
                                    <td id="num"><?= number_format($order['total_price'], 2) ?></td>
                                    <td id="actionSection">
                                        <a href="order_receipt.php?order_id=<?= $order['order_id'] ?>" 
                                            class="order-btn" id="review">Review Receipt
                                        </a>
                                        <a href="order_item.php?order_id=<?= $order['order_id'] ?>" 
                                            class="order-btn" id="manage">Manage Order
                                        </a>
                                        <button class="order-btn" id="cancel" onclick="
                                            openDeleteWholeModal(<?= $order['order_id'] ?>)">Cancel Whole
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            <?php else: ?>
                <div class="empty-order-message">
                    <h2>Your order list is currently empty. (´。＿。｀) </h2>
                    <br>
                    <p>Maybe you should go to the <a href="booking.php">Booking</a> page to add some cars.</p>
                    <p>Or you already booked something but NOTHING is here? <a href="contact.php">Contact</a> us to get things solved!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include("include/footer.php") ?>
    <div id="deleteWholeModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div style="display:flex; justify-content:right;">
                <span onclick="closeWholeModal()" style="cursor:pointer; font-weight:bold; font-size:20px;">&times;</span>
            </div>
            <div class="modal-header">
                <h2>Are you sure you want to cancel this entire order?</h2>
            </div>
            <form method="post" action="delete_whole.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <input type="hidden" name="order_id" id="wholeOrderId">
                <button type="submit" id="confirmDeleteBtn">Confirm Cancel</button>
            </form>
        </div>
    </div>
    <script>
        function openDeleteWholeModal(orderId) {
            document.getElementById('wholeOrderId').value = orderId;
            document.getElementById('deleteWholeModal').style.display = 'flex';
        }

        function closeWholeModal() {
            document.getElementById('deleteWholeModal').style.display = 'none';
        }
    </script>
</body>
</html>
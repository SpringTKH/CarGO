<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once "include/connection.php";

    // Phase 1: Authentication check
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $user_id = $_SESSION['user_id'];

    $delete_message = $_SESSION['delete_message'] ?? null;
    unset($_SESSION['delete_message']);

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    // Phase 1: Verify the order belongs to the logged-in user
    $stmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($order_owner_id);
    if (!$stmt->fetch() || $order_owner_id !== $user_id) {
        $stmt->close();
        // Generic message to avoid disclosing whether the order exists
        die("Order not found.");
    }
    $stmt->close();

    $sql = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $order_items = $result->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT addition_payment, total_price FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($addition_payment, $total_price);
    $stmt->fetch();
    $stmt->close();

    date_default_timezone_set("Asia/Kuala_Lumpur");
    $current_date = date("Y-m-d");
    $server_now = date("Y-m-d H:i:s");
    $order_contains_only_addition = (count($order_items) === 0 && $addition_payment > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Items</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/order_item.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
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
    <?php include ("include/header.php")?>
    <div class="main-content">
        <div class="cart-container">
            <?php
                $formatted_order_id = 'ORD-' . str_pad($order_id, 8, '0', STR_PAD_LEFT);
            ?>
            <h2>Your Order: <?= htmlspecialchars($formatted_order_id) ?></h2>
            <?php if (count($order_items) > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Car Name</th>
                        <th>Rental Date</th>
                        <th>Days</th>
                        <th>Quantity</th>
                        <th>Single Price</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($order_items as $item):
                        $total = $item['final_price'];
                        $begin_date = $item['begin_date'];
                        $isCancelable = strtotime($current_date) < strtotime($begin_date);
                    ?>
                        <tr>
                            <td id="str"> <?= htmlspecialchars($item['car_name']) ?> </td>
                            <td id="dateOrBtn"> <?= htmlspecialchars($item['begin_date']) ?> to <?= htmlspecialchars($item['end_date'])?></td>
                            <td> <?= htmlspecialchars($item['rental_days']) ?> </td>
                            <td> <?= htmlspecialchars($item['quantity']) ?> </td>
                            <td> RM<?= number_format($item['single_price'], 2) ?> </td>
                            <td> RM<?= number_format($total, 2) ?> </td>
                            <td id="dateOrBtn">
                                <?php if ($isCancelable): ?>
                                    <button id="deleteBtn" onclick="showDeleteModal(<?= $item['order_item_id'] ?>, '<?= $begin_date ?>')">Delete</button>
                                <?php else: ?>
                                    <button id="deleteBtn" disabled>Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
             <?php if ($order_contains_only_addition): ?>
                <div class="info-message">
                    ⚠️ This order currently contains no rental items. Only additional charges remain.
                </div>
            <?php endif; ?>
            <div class="cart-footer">
                <div class="addition-payment">Additional Payment: RM<?= number_format($addition_payment, 2) ?></div>
                <div class="total-price"><strong>Total: RM<?= number_format($total_price, 2) ?></strong></div>
            </div>
        </div>
    </div>
    <?php include ("include/footer.php")?>
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div style="display:flex; justify-content:right;" >
                <span onclick="closeModal()" style="cursor:pointer; font-weight:bold; font-size:20px;">&times;</span>
            </div>
            <div class="modal-header">
                <h2>Are you sure you want to Delete this order?</h2>
                <p id="deleteInfoText" style="margin-top:10px;"></p>
            </div>
            
            <form method="post" action="delete_order.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                <input type="hidden" name="order_item_id" id="deleteItemId">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" id="confirmDeleteBtn" style="margin-top:10px; padding:5px 15px;">Confirm</button>
            </form>
        </div>
    </div>
    <script>
        let serverNow;
        window.onload = function () {
            serverNow = new Date("<?= $server_now ?>");
        };

        function showDeleteModal(itemId, beginDate) {
            const modal = document.getElementById('deleteModal');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            const infoText = document.getElementById('deleteInfoText');

            const begin = new Date(beginDate + 'T00:00:00');
            const msDiff = begin - serverNow;
            const hourDiff = msDiff / (1000 * 60 * 60);

            if (hourDiff > 24) {
                infoText.innerText = "Free cancellation is available.";
                confirmBtn.disabled = false;
            } else if (hourDiff > 0) {
                infoText.innerText = "50% of the price will be remained.";
                confirmBtn.disabled = false;
            } else {
                infoText.innerText = "Cancellation is not allowed as the rental period has started.";
                confirmBtn.disabled = true;
            }

            document.getElementById('deleteItemId').value = itemId;
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</body>
</html>

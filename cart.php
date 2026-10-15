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
    $manage_message = $_SESSION['manage_message'] ?? null;
    unset($_SESSION['delete_message'], $_SESSION['manage_message']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['mode']) && $_POST['mode'] === 'edit') {
            foreach ($_POST['cart'] as $cart_id => $item) {
                $cart_id = (int)$cart_id;
                $quantity = (int)$item['quantity'];
                $begin_date = $item['begin_date'];
                $end_date = $item['end_date'];

                $rental_days = (strtotime($end_date) - strtotime($begin_date)) / (60 * 60 * 24);
                $today = date('Y-m-d');     
                if ($quantity > 0 && $rental_days > 0 && $begin_date >= $today) {
                    // Phase 1: Look up trusted single_price from DB instead of client-submitted hidden field
                    $price_stmt = $conn->prepare("SELECT single_price FROM cart WHERE cart_id = ? AND user_id = ?");
                    $price_stmt->bind_param("ii", $cart_id, $user_id);
                    $price_stmt->execute();
                    $price_stmt->bind_result($trusted_single_price);
                    if (!$price_stmt->fetch()) {
                        $price_stmt->close();
                        continue; // Skip items that don't belong to this user
                    }
                    $price_stmt->close();

                    $final_price = $quantity * $trusted_single_price * $rental_days;

                    $stmt = $conn->prepare("UPDATE cart SET quantity = ?, begin_date = ?, 
                        end_date = ?, rental_days = ?, final_price = ? WHERE cart_id = ? AND user_id = ?");
                    $stmt->bind_param("issiidi", $quantity, $begin_date, $end_date, 
                        $rental_days, $final_price, $cart_id, $user_id);
                    if ($stmt->execute()) {
                        $_SESSION['manage_message'] = "Car information has been modified!";
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['mode']) && $_POST['mode'] === 'delete' && isset($_POST['delete_id'])) {
            $delete_id = (int)$_POST['delete_id'];
            $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $delete_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['delete_message'] = "Car had been removed from your cart!";
            }
            $stmt->close();
        }
        header("Location: cart.php");
        exit;
    }

    $sql = "SELECT * FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += $item['final_price'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/cart.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <title>My Cart</title>
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
    <?php if ($manage_message): ?>
        <div class="flash-overlay-manage" id="flashBox">
            <div class="flash-content">
                <?= htmlspecialchars($manage_message) ?>
                <span class="flash-close" onclick="document.getElementById('flashBox').remove()">×</span>
            </div>
        </div>
    <?php endif; ?>
    <?php include ("include/header.php"); ?>
    <div id="mainContent" class="main-content">
        <div class="cart-container">
            <?php if(!empty($cart_items)): ?>
                <h2 style="display: flex; justify-content: space-between; align-items: center; padding: 0px 20px;">
                    My Cart
                    <span>
                        <button id="manageBtn">Manage</button>
                        <button id="deleteBtn">Delete</button>
                    </span>
                </h2>

                <form method="POST" id="cartForm">
                    <input type="hidden" name="mode" id="formMode" value="">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Car Name</th>
                                <th>Price (RM)</th>
                                <th>Quantity</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Total Price (RM)</th>
                                <th class="delete-col" style="display: none;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td id="carName"><?= htmlspecialchars($item['car_name']) ?></td>
                                    <td><?= number_format($item['single_price'], 2) ?></td>
                                    <td>
                                        <input type="number" name="cart[<?= $item['cart_id'] ?>][quantity]" 
                                            value="<?= $item['quantity'] ?>" min="1" disabled required>
                                    </td>
                                    <td id="date">
                                        <input type="date" name="cart[<?= $item['cart_id'] ?>][begin_date]" 
                                            value="<?= $item['begin_date'] ?>" disabled required>
                                    </td>
                                    <td id="date">
                                        <input type="date" name="cart[<?= $item['cart_id'] ?>][end_date]"s 
                                            value="<?= $item['end_date'] ?>" disabled required>
                                    </td>
                                    <td><?= number_format($item['final_price'], 2) ?></td>
                                    <td class="delete-col" style="display: none;">
                                        <button type="button" name="delete_id" 
                                            value="<?= $item['cart_id'] ?>" 
                                            class="delete-btn" 
                                            data-cart-id="<?= $item['cart_id'] ?>">✕</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-footer">
                        <div class="total-price">
                            <strong>Total: RM<?= number_format($total_price, 2) ?></strong>
                        </div>
                        <div class="confirm-btn">
                            <button type="submit" id="saveBtn" style="display: none;">Save</button>
                            <button type="button" id="confirmBtn">Confirm Order</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-cart-message">
                    <h2>Your cart is currently empty. (´。＿。｀) </h2>
                    <br>
                    <p>Maybe you should go to the <a href="booking.php">Booking</a> page to add some cars.</p>
                    <p>Or you already booked something but NOTHING is here? <a href="contact.php">Contact</a> us to get things solved!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include ("include/footer.php"); ?>
    <div id="confirmModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p>Are you sure you want to delete this item?</p>
            <div class="modal-buttons">
                <button id="delConfirmDelete">Sure</button>
                <button id="cancelDelete">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        let isManaging = false;
        let isDeleting = false;

        const manageBtn = document.getElementById('manageBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const formMode = document.getElementById('formMode');
        const saveBtn = document.getElementById('saveBtn');
        const confirmBtn = document.querySelector('.confirm-btn button[type="button"]');
        const inputs = document.querySelectorAll('input[type="number"], input[type="date"]');
        const deleteCols = document.querySelectorAll('.delete-col');

        const originalValues = {};
        inputs.forEach(input => {
            originalValues[input.name] = input.value;
        });

        manageBtn.addEventListener('click', () => {
            isManaging = !isManaging;
            formMode.value = isManaging ? 'edit' : '';

            inputs.forEach(input => {
                input.disabled = !isManaging;
                if (!isManaging) {
                    input.value = originalValues[input.name];
                }

                if (isManaging && input.type === 'date') {
                    const today = new Date().toISOString().split("T")[0];
                    if (input.name.includes('begin_date')) {
                        input.min = today;
                        input.addEventListener('change', handleBeginDateChange);
                    } else if (input.name.includes('end_date')) {
                        input.addEventListener('change', handleEndDateValidation);
                    }
                }
            });

            saveBtn.style.display = isManaging ? 'inline-block' : 'none';
            if (confirmBtn) confirmBtn.style.display = isManaging ? 'none' : 'inline-block';
            manageBtn.innerText = isManaging ? 'Cancel' : 'Manage';
            deleteBtn.disabled = isManaging;

            if (!isManaging) deleteBtn.disabled = false;

            if (isManaging && isDeleting) {
                isDeleting = false;
                formMode.value = '';
                deleteCols.forEach(col => col.style.display = 'none');
                deleteBtn.innerText = 'Delete';
            }
        });

        deleteBtn.addEventListener('click', () => {
            isDeleting = !isDeleting;
            formMode.value = isDeleting ? 'delete' : '';

            deleteCols.forEach(col => col.style.display = isDeleting ? 'table-cell' : 'none');
            deleteBtn.innerText = isDeleting ? 'Cancel' : 'Delete';

            saveBtn.style.display = 'none';
            if (confirmBtn) confirmBtn.style.display = isDeleting ? 'none' : 'inline-block';

            manageBtn.disabled = isDeleting;

            if (!isDeleting) {
                manageBtn.disabled = false;
            }

            if (isDeleting) {
                if (isManaging) {
                    isManaging = false;
                    formMode.value = '';
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.value = originalValues[input.name];
                    });
                    manageBtn.innerText = 'Manage';
                    saveBtn.style.display = 'none';
                }
            }
        });

        document.getElementById('confirmBtn').addEventListener('click', () => {
            window.location.href = 'confirm_order.php';
        });

        function handleBeginDateChange(e) {
            const beginInput = e.target;
            const cartId = beginInput.name.match(/\d+/)[0];
            const endInput = document.querySelector(`input[name="cart[${cartId}][end_date]"]`);

            const beginDate = new Date(beginInput.value);
            const endDate = new Date(endInput.value);

            if (beginDate >= endDate) {
                const nextDay = new Date(beginDate);
                nextDay.setDate(beginDate.getDate() + 1);
                const nextDayStr = nextDay.toISOString().split("T")[0];
                endInput.value = nextDayStr;
            }
            endInput.min = beginInput.value;
        }

        function handleEndDateValidation(e) {
            const endInput = e.target;
            const cartId = endInput.name.match(/\d+/)[0];
            const beginInput = document.querySelector(`input[name="cart[${cartId}][begin_date]"]`);

            const beginDate = new Date(beginInput.value);
            const endDate = new Date(endInput.value);

            if (endDate <= beginDate) {
                alert("End date must be at least one day after the start date.");
                const nextDay = new Date(beginDate);
                nextDay.setDate(beginDate.getDate() + 1);
                endInput.value = nextDay.toISOString().split("T")[0];
            }
        }
        let deleteForm = document.getElementById('cartForm');
        let confirmModal = document.getElementById('confirmModal');
        let delConfirmBtn = document.getElementById('delConfirmDelete');
        let cancelBtn = document.getElementById('cancelDelete');
        let pendingDeleteId = null;

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                pendingDeleteId = btn.getAttribute('data-cart-id');
                confirmModal.style.display = 'block';
            });
        });

        delConfirmBtn.addEventListener('click', () => {
            if (pendingDeleteId) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_id';
                input.value = pendingDeleteId;
                deleteForm.appendChild(input);
                deleteForm.submit();
            }
        });

        cancelBtn.addEventListener('click', () => {
            confirmModal.style.display = 'none';
            pendingDeleteId = null;
        });

        let mainContent = document.getElementById('mainContent');

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                pendingDeleteId = btn.getAttribute('data-cart-id');
                confirmModal.style.display = 'flex';
                mainContent.classList.add('blur');
            });
        });

        cancelBtn.addEventListener('click', () => {
            confirmModal.style.display = 'none';
            mainContent.classList.remove('blur');
            pendingDeleteId = null;
        });

        delConfirmBtn.addEventListener('click', () => {
            if (pendingDeleteId) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_id';
                input.value = pendingDeleteId;
                deleteForm.appendChild(input);
                confirmModal.style.display = 'none';
                mainContent.classList.remove('blur');
                deleteForm.submit();
            }
        });
    </script>
</body>
</html>
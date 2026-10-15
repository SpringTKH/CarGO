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

    // Fetch cart items
    $stmt = $conn->prepare("SELECT * FROM cart INNER JOIN cars ON cart.car_id = cars.car_id WHERE cart.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = [];
    $total_price = 0;
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['final_price'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Order</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/order.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <?php include("include/header.php") ?>
    <div class="main-container">
        <div class="order-summary">
            <h2 class="order-title">Booking Summary</h2>
            <?php foreach ($cart_items as $item): ?>
            <div class="order-item">
                <div class="row-1">
                    <div class="left"><strong><?= htmlspecialchars($item['car_name']) ?></strong></div>
                    <div class="right">
                        From <?= $item['begin_date'] ?> to <?= $item['end_date'] ?><br>
                        (<?= $item['rental_days'] ?>days)
                    </div>
                </div>
                <div class="row-2">
                    <div class="left">
                        Single Price: RM<?= number_format($item['single_price'], 2) ?>, 
                        Quantity: <?= $item['quantity'] ?>
                    </div>
                    <div class="right">
                        <strong>RM<?= number_format($item['final_price'], 2) ?></strong>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="order-total">
                Total Amount: RM<?= number_format($total_price, 2) ?>
            </div>
        </div>
        <form class="payment-form" method="POST" action="process_order.php">
            <h2 class="payment-title">Payment Process</h2>

            <div class="form-section">
                <label>Username:</label>
                <p class="readonly-text">
                    <?= htmlspecialchars($username) ?>
                </p>
                <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            </div>

            <div class="form-section">
                <label for="phone">Phone Number</label>
                <input type="tel" name="phone" id="phone" pattern="^(\+?60|0)[0-9]{9,10}$" placeholder="ex. +60 1X-XXXXXXX" required>
                <span class="error-msg" id="error_phone"></span>
            </div>

            <div class="form-section">
                <label style="margin-bottom: 10px;">Payment Method</label>
                <div class="payment-option">
                    <input type="radio" name="payment_method" value="card" id="pay_card" required>
                    <label for="pay_card">Debit/Credit Card</label>
                </div>
                <div class="payment-option">
                    <input type="radio" name="payment_method" value="bank" id="pay_bank">
                    <label for="pay_bank">Bank Transfer</label>
                </div>
                <div class="payment-option">
                    <input type="radio" name="payment_method" value="cash" id="pay_cash">
                    <label for="pay_cash">Cash Payment</label>
                </div>
            </div>
            <div class="form-section hidden" id="cardFields">
                <p style="font-size: 13px; color: #555; margin-bottom: 8px;">
                    <em>Simulation Mode: Enter mock card details for testing. Do not use real credentials.</em>
                </p>
                <label for="card_name">Cardholder Name</label>
                <input type="text" name="card_name" id="card_name" class="card-required" placeholder="e.g. John Doe">
                <span class="error-msg" id="error_card_name"></span>

                <label for="card_number">Card Number (Mock / Test)</label>
                <input type="text" name="card_number" id="card_number" class="card-required" placeholder="e.g. 4111 2222 3333 4444" maxlength="19">
                <span class="error-msg" id="error_card_number"></span>
            </div>
            <div class="form-section hidden" id="bankFields">
                <p style="font-size: 13px; color: #555; margin-bottom: 8px;">
                    <em>Simulation Mode: Select a bank and enter a mock account number for testing.</em>
                </p>
                <label for="bank_name">Bank Name</label>
                <select name="bank_name" id="bank_name" class="bank-required">
                    <option value="">-- Please select a bank --</option>
                    <option value="Alliance Bank">Alliance Bank</option>
                    <option value="Bank Islam">Bank Islam</option>
                    <option value="Bank Muamalat">Bank Muamalat</option>
                    <option value="Bank Rakyat">Bank Rakyat</option>
                    <option value="Bank Simpanan Nasional">Bank Simpanan Nasional</option>
                    <option value="CIMB">CIMB</option>
                    <option value="Hong Leong Bank">Hong Leong Bank</option>
                    <option value="Maybank">Maybank</option>
                    <option value="Public Bank">Public Bank</option>
                    <option value="RHB Bank">RHB Bank</option>
                    <option value="Other">Other</option>
                </select>
                <span class="error-msg" id="error_bank_name"></span>

                <label for="bank_account">Bank Account Number (Mock / Test)</label>
                <input type="text" name="bank_account" id="bank_account" class="bank-required" placeholder="e.g. 1234567890">
            </div>

            <button type="submit" class="submitOrder" id="submitBtn" disabled>Submit Order</button>
        </form>
    </div>
    <?php include("include/footer.php") ?>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const phoneInput = document.getElementById("phone");
        const submitButton = document.getElementById("submitBtn");

        const cardFields = document.getElementById("cardFields");
        const bankFields = document.getElementById("bankFields");

        const cardInputs = [
            document.getElementById("card_number"),
            document.getElementById("card_name")
        ];

        const bankInputs = [
            document.getElementById("bank_name"),
            document.getElementById("bank_account")
        ];

        const paymentRadios = document.querySelectorAll('input[name="payment_method"]');

        function getSelectedMethod() {
            const selected = [...paymentRadios].find(r => r.checked);
            return selected ? selected.value : null;
        }

        function toggleFields(method) {
            cardFields.classList.add("hidden");
            bankFields.classList.add("hidden");

            cardInputs.forEach(input => input.required = false);
            bankInputs.forEach(input => input.required = false);

            if (method === "card") {
                cardFields.classList.remove("hidden");
                cardInputs.forEach(input => input.required = true);
            } else if (method === "bank") {
                bankFields.classList.remove("hidden");
                bankInputs.forEach(input => input.required = true);
            }
        }

        function isFilled(inputs) {
            return inputs.every(input => input && input.value.trim() !== "");
        }

        function validateForm() {
            const phoneFilled = phoneInput.value.trim() !== "";
            const selectedMethod = getSelectedMethod();

            let paymentValid = false;

            if (selectedMethod === "card") {
                paymentValid = isFilled(cardInputs);
            } else if (selectedMethod === "bank") {
                paymentValid = isFilled(bankInputs);
            } else if (selectedMethod === "cash") {
                paymentValid = true;
            }

            submitButton.disabled = !(phoneFilled && paymentValid);
        }

        phoneInput.addEventListener("input", validateForm);

        [...cardInputs, ...bankInputs].forEach(input => {
            input.addEventListener("input", validateForm);
        });

        paymentRadios.forEach(radio => {
            radio.addEventListener("change", () => {
                toggleFields(radio.value);
                validateForm();
            });
        });

        toggleFields(getSelectedMethod());
        validateForm();
    });
    </script>
</body>
</html>
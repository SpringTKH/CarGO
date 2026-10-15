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

    if (!isset($_GET['order_id'])) {
        die("Order not found.");
    }

    $order_id = (int)$_GET['order_id'];
    $formatted_order_id = 'ORD-' . str_pad($order_id,8,'0', STR_PAD_LEFT);

    // Get order info
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Phase 1: Verify the order belongs to the logged-in user
    if (!$order || (int)$order['user_id'] !== $user_id) {
        // Generic message to avoid disclosing whether the order exists
        die("Order not found.");
    }

    // Get order items
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Receipt</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/receipt.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <?php include("include/header.php"); ?>
    <h1 style="width:100%; text-align:center; font-family: Sansation;">You have completed your order!</h1>
    <p style="text-align:center;">If there's any problem you've faced from making order, 
            please contact us, we will help you as much as we can!</p>
    <div style="text-align: center; margin-top: 20px;" class="no-print">
        <button id="printBtn" onclick="printReceipt()">Print Receipt</button>
        <button id="saveBtn" onclick="downloadPDF()">Save Receipt as PDF</button>
    </div>
    <div class="receipt-container">
        <div class="receipt">
            <div class="receipt-header">
                <div class ="receiptLogo"><img src="images/logo.png"></div>
                <div class ="receiptTitle"><h>CarGo Transport Services Sdn. Bhd.</h></div>
                <div class ="receiptQR"><img src="images/QRCode.png"></div>
            </div>
            <h2 class="order-title">Order Receipt</h2>
            <div class="order-info">
                <div class="customer-info">
                    <p><strong>Order ID: </strong><?= $formatted_order_id ?></p>
                    <p><strong>Name: </strong> <?= htmlspecialchars($order['full_name']) ?></p>
                    <p><strong>Email: </strong> <?= htmlspecialchars($order['email']) ?></p>
                    <p><strong>Phone: </strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                    <p><strong>Payment Method: </strong> <?= htmlspecialchars(ucfirst($order['payment_method'])) ?></p>

                    <?php if ($order['payment_method'] === 'card'): ?>
                        <div class="payment-desc">
                            <p><strong>Cardholder Name: </strong> <?= htmlspecialchars($order['cardholder_name']) ?></p>
                            <?php
                                $raw_card = trim((string)($order['card_number'] ?? ''));
                                $card_digits = preg_replace('/\D/', '', $raw_card);
                                if (strlen($card_digits) >= 4) {
                                    $card_display = '**** **** **** ' . substr($card_digits, -4);
                                } elseif (strlen($card_digits) > 0) {
                                    $card_display = '**** **** **** ' . str_pad($card_digits, 4, '*', STR_PAD_LEFT);
                                } else {
                                    $card_display = '**** **** **** ****';
                                }
                            ?>
                            <p><strong>Card Number: </strong> <?= htmlspecialchars($card_display) ?> <em>(Mock Payment)</em></p>
                        </div>
                    <?php elseif ($order['payment_method'] === 'bank'): ?>
                        <div class="payment-desc">
                            <p><strong>Bank Name: </strong> <?= htmlspecialchars($order['bank_name']) ?></p>
                            <?php
                                $raw_bank = trim((string)($order['bank_account'] ?? ''));
                                $bank_digits = preg_replace('/\D/', '', $raw_bank);
                                if (strlen($bank_digits) >= 4) {
                                    $bank_display = '******' . substr($bank_digits, -4);
                                } elseif (strlen($bank_digits) > 0) {
                                    $bank_display = '******' . str_pad($bank_digits, 4, '*', STR_PAD_LEFT);
                                } else {
                                    $bank_display = '**********';
                                }
                            ?>
                            <p><strong>Bank Account: </strong> <?= htmlspecialchars($bank_display) ?> <em>(Mock Transfer)</em></p>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="item-list">
                    <h3 class="order-items-title">[Order Items]</h3>
                    <table class="order-receipt-table">
                        <thead>
                            <tr>
                                <th>Car Name (ID)</th>
                                <th>Single (RM)</th>
                                <th>Quantity</th>
                                <th>Begin Date</th>
                                <th>End Date</th>
                                <th>Rental Days</th>
                                <th>Final (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>(<?= htmlspecialchars($item['car_id']) ?>) 
                                        <?= htmlspecialchars($item['car_name']) ?></td>
                                    <td id="num"><?= number_format($item['single_price'], 2) ?></td>
                                    <td id="num"><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td id="date"><?= htmlspecialchars($item['begin_date']) ?></td>
                                    <td id="date"><?= htmlspecialchars($item['end_date']) ?></td>
                                    <td id="num"><?= htmlspecialchars($item['rental_days']) ?></td>
                                    <td id="num"><?= number_format($item['final_price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p style="margin-bottom: 3px; padding: 1px 10px; font-style: italic; color: #777; font-size: 13px ">Additional Payment: RM
                    <?= number_format($order['addition_payment'], 2)?>
                </p>
                <div style="width:100%; background-color: #ddd; ">
                    <p style="font-size: 20px; font-family: Sansation; padding: 5px 5px; margin-top: 3px; ">
                        <strong>Total Payment: <u>RM<?= number_format($order['total_price'], 2)?></u></strong>
                    </p>
                </div>
                <div>
                    <p style="font-size:12px; padding: 10px; border: 1px dotted #555;">
                        <strong>Note: </strong><br> <i>By renting a vehicle from CarGo, you agree to our rental terms. 
                        Renter must hold a valid driver's license and return the car in original condition. 
                        Extra charges may apply for late returns, damages, low fuel, or traffic violations. 
                        Basic insurance is included; additional coverage is optional. 
                        Unauthorized use, including reckless driving or illegal activity, will <u>void the rental.</u>  
                        Full terms available upon request.</i>
                    </p>
                </div>
            </div>
            <div class="receipt-footer">
                <p>
                    <strong>+60 3-3191 7890 <br> cargoservice@gmail.com</strong><br>
                    Cargo Transport Service (M) Sdn. Bhd.<br>
                    13A, Persiaran Manusia Besar, 42500 Jenjarom, Kuala Langat, Selangor Darul Ehsan, Malaysia
                </p>
            </div>
        </div>
    </div>
    <?php include("include/footer.php"); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function printReceipt() {
            window.print();
        }

        function downloadPDF() {
            const element = document.querySelector(".receipt");

            const opt = {
                margin: [15, 15, 15, 15],
                filename: 'receipt_<?= $formatted_order_id ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
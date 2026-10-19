<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user_id = $_SESSION['user_id'] ?? null;
    $cart_count = 0;

    if ($user_id) {
        require_once 'connection.php'; // or however you connect to your DB

        $stmt = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($cart_count);
        $stmt->fetch();
        $stmt->close();
    }
?>
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="navbar">
    <div class="menu">
        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a>
        <a href="booking.php" class="<?= $current_page == 'booking.php' ? 'active' : '' ?>">Booking</a>
        <a href="aboutUs.php" class="<?= $current_page == 'aboutUs.php' ? 'active' : '' ?>">About Us</a>
        <a href="FAQs.php" class="<?= $current_page == 'FAQs.php' ? 'active' : '' ?>">FAQs</a>
        <a href="contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">Contact</a>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="cart">
            <a href="cart.php" class="cart-link">
                <img src="images/cart.png" alt="Cart" class="cart-icon" title="View Cart">
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="orders">
            <a href="order.php" class="cart-link">
                <img src="images/order.png" alt="Orders" class="cart-icon" title="View Order">
            </a>
        </div>
    <?php endif; ?>
</div>
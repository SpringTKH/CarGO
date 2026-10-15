<?php
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/contact.css">
    <title>Contact Us</title>
</head>
<body>
    <div id="formAlert" class="form-alert" style="display: none;">
        Your message has been successfully sent to CarGo! We will reply you once we read your message!
    </div>
    <?php if ($success_message): ?>
        <div class="flash-overlay" id="flashBox">
            <div class="flash-content">
                <?= htmlspecialchars($success_message) ?>
                <span class="flash-close" onclick="document.getElementById('flashBox').remove()">×</span>
            </div>
        </div>
    <?php endif; ?>
    <?php include("include/header.php"); ?>
    <main>
        <div class="contact-header">
            <h1>Contact Us!</h1>
            <p>Whether you have questions, feedback, or need assistance, our team is here to help.<br>
                Reach out to us through any of the channels below, and we’ll get back to you as soon as possible.</p>
        </div>
        <div class="contact-function">
            <div class="contact-details">
                <div class="contact-blocks">
                    <div class="contact-icon"><img src="images/ContactSupport.png" alt="logo"></div>
                    <div class="contact-desc">
                        <h3>Support</h3>
                        <p>+60 3-3191 7890<br>cargoservice@gmail.com</p>
                    </div>
                </div>
                <div class="contact-blocks">
                    <div class="contact-icon"><img src="images/ContactAddress.png" alt="logo"></div>
                    <div class="contact-desc">
                        <h3>Address</h3>
                        <p>Cargo Transport Service (M) Sdn. Bhd.<br>
                        13A, Persiaran Manusia Besar, 42500 Jenjarom,<br>
                        Kuala Langat, Selangor Darul Ehsan, Malaysia</p>
                    </div>
                </div>
                <div class="contact-blocks">
                    <div class="contact-icon"><img src="images/ContactTime.png" alt="logo"></div>
                    <div class="contact-desc">
                        <h3>Working Time</h3>
                        <p>Mon to Fri: 9am to 9pm<br>
                        Sat, Sun, P.Holiday: 9am to 5pm</p>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <form id="contactForm" onsubmit="return handleSubmit(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" pattern="^(\+?60|0)[0-9]{9,10}$" required placeholder="+60 12-3456789">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="example@gmail.com">
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" required placeholder="Write your message here..."></textarea>
                    </div>

                    <div class="form-btn">
                        <button type="submit">Submit Message</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include("include/footer.php"); ?>
    <script>
        function handleSubmit(event) {
            event.preventDefault();

            const form = document.getElementById('contactForm');
            const alertBox = document.getElementById('formAlert');

            if (form.checkValidity()) {
                alertBox.style.display = 'block';
                setTimeout(() => {
                    form.reset();
                    location.reload();
                }, 2500);
            } else {
                form.reportValidity();
            }
            return false;
        }
    </script>
</body>
</html>
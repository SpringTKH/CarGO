<?php
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQs</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/FAQs.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
</head>
<body>
    <?php if ($success_message): ?>
        <div class="flash-overlay" id="flashBox">
            <div class="flash-content">
                <?= htmlspecialchars($success_message) ?>
                <span class="flash-close" onclick="document.getElementById('flashBox').remove()">×</span>
            </div>
        </div>
    <?php endif; ?>
    <?php include("include/header.php") ?>
    <main>
        <div class="main-content">
            <h2>Frequently Asked Questions (FAQs)</h2>

            <div class="faq-item">
                <div class="faq-question">1. What types of vehicles do you offer for rent?</div>
                <div class="faq-answer">
                    We provide a diverse range of vehicles including compact cars for city driving, family-friendly sedans, 
                    spacious MPVs and SUVs for group travel, and premium cars for luxury experiences. Each vehicle is 
                    well-maintained and inspected regularly for your safety.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">2. What is the minimum rental period?</div>
                <div class="faq-answer">
                    The minimum rental period is 1 day (24 hours). You can choose to rent for multiple days or weeks depending 
                    on your travel or business needs.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">3. Do I need a valid driving license?</div>
                <div class="faq-answer">
                    Yes. You must present a valid Malaysian driving license (Class D) or a recognized International Driving 
                    Permit. Temporary or expired licenses are not accepted.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">4. What are the age requirements to rent a vehicle?</div>
                <div class="faq-answer">
                    Renters must be at least 18 years old and must have held a valid driving license for at least 1 year. 
                    Some luxury or premium cars may require the driver to be at least 25 years old.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">5. Is there a security deposit required?</div>
                <div class="faq-answer">
                    Yes, a refundable deposit is required at the time of pickup. The amount depends on the type of vehicle 
                    rented. It will be returned to you after the car is returned in the agreed condition.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">6. Can I cancel my booking?</div>
                <div class="faq-answer">
                    Yes, you can cancel for free up to 24 hours before your rental start time. Late cancellations  
                    may incur a partial charge (50%) as per our policy. No cancellation allows when the curret date is the days 
                    as the car rental's beginning date.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">7. Is fuel included in the rental price?</div>
                <div class="faq-answer">
                    Fuel is not included. Vehicles are provided with a certain fuel level and must be returned with the same 
                    level to avoid fuel charges.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">8. Are your vehicles insured?</div>
                <div class="faq-answer">
                    Yes, all our cars come with basic insurance including third-party liability coverage. 
                    You may opt for additional coverage for extra peace of mind.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">9. What should I do in case of an accident or breakdown?</div>
                <div class="faq-answer">
                    Call our 24/7 customer support line immediately. We will provide roadside assistance or vehicle 
                    replacement depending on the situation.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">10. Can I drive the rental car to another state?</div>
                <div class="faq-answer">
                    Yes, you may travel within Peninsular Malaysia. However, please notify us if you're planning 
                    long-distance travel for record and support purposes.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">11. Are there any hidden charges?</div>
                <div class="faq-answer">
                    No. We ensure transparent pricing. All charges including taxes, fees, and insurance options will 
                    be shown clearly before you confirm your booking.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">12. How do I make a booking?</div>
                <div class="faq-answer">
                    You can book online through our website, via our mobile app, or by calling our hotline. 
                    It's fast, simple, and secure.
                </div>
            </div>
        </div>
    </main>
    <?php include("include/footer.php") ?>
    <script>
        const questions = document.querySelectorAll('.faq-question');

        questions.forEach((question) => {
            question.addEventListener('click', () => {
            question.classList.toggle('open');
            const answer = question.nextElementSibling;

            if (answer.classList.contains('open')) {
                answer.style.maxHeight = '0px';
                answer.classList.remove('open');
            } else {
                answer.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
            });
        });

        window.addEventListener('resize', () => {
            document.querySelectorAll('.faq-answer.open').forEach((el) => {
            el.style.maxHeight = el.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html>
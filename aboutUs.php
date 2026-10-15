<?php
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/about.css">
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
        <div class="about-header">
            <h1>Who we are</h1>
            <p>
                CarGo Transport Services Sdn. Bhd. is a dedicated car rental company designed to meet the needs of modern travelers. 
                Whether you're on a business trip, a family vacation, or need a temporary ride, we offer a wide range of 
                well-maintained vehicles for flexible, short-term use. With a user-friendly online booking platform and 
                transparent pricing, we prioritize convenience, comfort, and customer satisfaction above all.
            </p>
        </div>
        <div class="about-picture">
            <div class="inner-about-picture"><img src="images/AboutUsBG.jpg"></div>
        </div>
        <div class="about-data">
            <h2>Since our inception, CarGo has:</h2>
            <div class="data-content">
                <div class="content-section">
                    <div class="data-image"><img src="images/Customer.png"></div>
                    <div class="data-desc">
                        <p>Served over <strong>10,000 satisfied customers</strong> across the country.</p>
                    </div>
                </div>
                <div class="content-section">
                    <div class="data-image"><img src="images/Rating.png"></div>
                    <div class="data-desc">
                        <p>Maintained an average customer satisfaction <strong>rating of 4.8/5</strong>.</p>
                    </div>
                </div>
                <div class="content-section">
                    <div class="data-image"><img src="images/Vehicle.png"></div>
                    <div class="data-desc">
                        <p>Offered a fleet of more than <strong>50+ vehicles</strong> across various categories.</p>
                    </div>
                </div>
                <div class="content-section">
                    <div class="data-image"><img src="images/Time.png"></div>
                    <div class="data-desc">
                        <p>Achieved <strong>98% on-time pickup</strong> and return success rate.</p>
                    </div>
                </div>
                <div class="content-section">
                    <div class="data-image"><img src="images/Instant.png"></div>
                    <div class="data-desc">
                        <p>Enabled <strong>instant confirmation for 85%</strong> of all bookings.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="about-mission">
            <h3>Our Mission</h3>
            <p>
                &emsp;&emsp;&emsp;&emsp;
                Our company are promised to make vehicle rental simple, reliable, and accessible to everyone. We aim to eliminate 
                the stress often associated with traditional car rental by offering a seamless digital booking experience, 
                clear rental terms, and a responsive support team. Whether you're planning a quick weekend getaway, a business trip, 
                or just need a temporary replacement vehicle, we are here to help you get on the road quickly and affordably.
            </p>
            <p>
                &emsp;&emsp;&emsp;&emsp;
                Additionally, we are committed to providing high-quality, well-maintained vehicles that meet a wide range of customer needs 
                and preferences. Through ongoing investment in technology and a customer-first mindset, we strive to deliver 
                convenience, safety, and satisfaction with every ride. Our mission goes beyond just renting cars — we aim to be a 
                trusted partner in your travel experience, every step of the way.
            </p>
        </div>
        <div class="about-values">
            <h3>Our Values</h3>
            <ul>
                <li>
                    <strong>Customer First:</strong><br>
                    Our customers are the core of everything we do. We actively listen, respond quickly, and design our services 
                    to ensure a smooth, hassle-free rental experience that meets real needs and expectations.
                </li>
                <li>
                    <strong>Integrity:</strong><br>
                    We operate with honesty and transparency at all times. From clear pricing to fair policies, we aim to earn 
                    our customers’ trust through consistent and ethical practices.
                </li>
                <li>
                    <strong>Excellence:</strong><br>
                    We pursue high standards in every detail, from vehicle cleanliness to timely service. Our team is committed 
                    to continuous improvement to deliver outstanding quality every time.
                </li>
                <li>
                    <strong>Innovation:</strong><br>
                    We embrace technology to enhance convenience, efficiency, and reliability. By staying up to date with digital 
                    trends, we provide smarter, more user-friendly rental solutions.
                </li>
                <li>
                    <strong>Responsibility:</strong><br>
                    We are dedicated to sustainable and ethical business practices. From maintaining safe vehicles to minimizing 
                    environmental impact, we act with care toward our community and planet.
                </li>
            </ul>
        </div>
        <div class="about-team">
            <h3>Our Team</h3>
            <div class="about-team-image">
                <img src="images/AboutTeam.jpg">
            </div>
            <p>
                <br>Behind CarGo is a passionate team of professionals—from customer service experts and mechanics to IT engineers 
                and logistics coordinators. Every team member plays a crucial role in ensuring smooth operations and a top-tier 
                rental experience. Our team brings together skills, creativity, and a shared vision: to drive CarGo forward, one 
                satisfied customer at a time.
            </p>
        </div>
        <div class="about-members">
            <div class="member">
                <div class="member-pfp"><img src="images/OperationsManager.png"></div>
                <div class="member-desc">
                    <h4>James H. Pierre</h4>
                    <p>Operations Manager</p>
                </div>
            </div>
            <div class="member">
                <div class="member-pfp"><img src="images/CustomerServiceLead.png"></div>
                <div class="member-desc">
                    <h4>Meridith C. Elwes</h4>
                    <p>Customer Service Leader</p>
                </div>
            </div>
            <div class="member">
                <div class="member-pfp"><img src="images/FleetManager.png"></div>
                <div class="member-desc">
                    <h4>Chern Zi Yue</h4>
                    <p>Fleet Manager</p>
                </div>
            </div>
            <div class="member">
                <div class="member-pfp"><img src="images/SalesExecutive.png"></div>
                <div class="member-desc">
                    <h4>Zaid S. El-Mofty</h4>
                    <p>Digital Marketing & <br>Sales Executive</p>
                </div>
            </div>
        </div>
    </main>
    <?php include("include/footer.php") ?>
</body>
</html>
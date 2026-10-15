<?php
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $success_message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="stylesheet" href="styles/notification.css">
    <link rel="icon" href="images/icon.ico" type="image/x-icon">
    <title>Car-GO!</title>
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
    <?php include("include/header.php"); ?>
    <main>
        <div class="home-image">
            <img src="images/HomeBG.jpeg">
            <div class="image-overlay">
                <div class="overlay-text">
                    <h1>Welcome to CarGo!</h1>
                    <p>
                        Your Journey Starts Here — <br>
                        Affordable & Flexible Car Rentals for Any Occasion.
                    </p>
                    <button onclick="window.location.href='booking.php'">BOOK NOW</button>
                </div>
            </div>
        </div>
        <div class="home-intro">
            <h2>Enjoy flexibility and unbeatable rates with our city car rentals</h2>
            <div class="intro-details">
                <div class="detail-block">
                    <div class="detail-image"><img src="images/Vehicle.png"></div>
                    <div class="detail-desc">
                        <h3>Well Maintained Vehicles</h3>
                        <p>All our cars are well-maintained and regularly serviced, ensuring safe and smooth driving.</p>
                    </div>
                </div>
                <div class="detail-block">
                    <div class="detail-image"><img src="images/Booking.png"></div>
                    <div class="detail-desc">
                        <h3>Easy Online Booking</h3>
                        <p>Book your car in minutes with our user-friendly online platform. Fast, simple, and convenient!</p>
                    </div>
                </div>
                <div class="detail-block">
                    <div class="detail-image"><img src="images/Price.png"></div>
                    <div class="detail-desc">
                        <h3>Affordable Pricing</h3>
                        <p>Enjoy competitive rates with no hidden fees. Rent the perfect car without breaking the bank.</p>
                    </div>
                </div>
                <div class="detail-block">
                    <div class="detail-image"><img src="images/Support.png"></div>
                    <div class="detail-desc">
                        <h3>24-7 Supports</h3>
                        <p>We’re here to assist you anytime, anywhere. Drive with peace of mind knowing help is just a call away.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="home-car">
            <div class="home-car-header">
                <h2>Our Cars</h2>
                <button onclick="window.location.href='booking.php'">See More Cars</button>
            </div>
            <div class="car-scroll-container">
                <?php
                    require_once 'include/connection.php';
                    $query = "SELECT car_name, car_picture, max_passengers, gear_type, car_type, 
                        rental_price_per_day FROM cars LIMIT 9";
                    $result = $conn->query($query);
                ?>    
                <?php if ($result->num_rows > 0): ?>
                <div class="car-list">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="car-box">
                            <div class="car-name">
                                <h3><?= htmlspecialchars($row['car_name']); ?></h3>
                            </div>
                            <div class="car-image">
                                <img src="<?= htmlspecialchars($row['car_picture']); ?>" alt="<?= htmlspecialchars($row['car_name']); ?>">
                            </div>
                            <div class="car-info">
                                <div class="info_1"><img src="images/CarType.png"> <?= htmlspecialchars($row['car_type'])?></div>
                                <div class="info_1"><img src="images/Seat.png"> <?= htmlspecialchars($row['max_passengers'])?></div>
                                <div class="info_1"><img src="images/Gear.png"> <?= htmlspecialchars($row['gear_type'])?></div>
                            </div>
                            <div class="car-price">
                                <p>Rental Price: <strong>RM<?= htmlspecialchars($row['rental_price_per_day']) ?></strong></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="home-steps">
            <video id="bgVideo" autoplay muted loop playsinline>
                <source src="images/CarGo.mp4" type="video/mp4">
            </video>
            <button class="video-toggle" id="toggleBtn" onclick="toggleVideo()">❚❚</button>
            <div class="video-overlay">
                <div class="video-overlay-text">
                    <h1>Rent your car in 4 easy steps:</h1>
                    <ol>
                        <li>
                            <strong>Browse Our Cars</strong><br>
                            Explore our wide selection of vehicles and choose one that fits your needs.
                        </li>
                        <li>
                            <strong>Select Dates & Quantites</strong><br>
                            Pick your rental start and end dates, and choose how many cars you need.
                        </li>
                        <li>
                            <strong>Confirm Booking</strong><br>
                            Review your choices and complete the booking with a few simple clicks.
                        </li>
                        <li>
                            <strong>Pick Up & Drive</strong><br>
                            Collect your car and hit the road — it's that easy!
                        </li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="home-rating">
            <h1>Feedback from Our Renters</h1>
            <div class="rating-section">
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>Kenneth_John_123</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            The booking process was so smooth, and the car was in excellent condition. Staff were professional 
                            and friendly. Definitely made my weekend trip easier. Will rent again without a doubt!
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ★</p>
                    </div>
                </div>
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>Missisippi</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            CarGO has a great selection of vehicles. I booked last-minute and still got a clean, reliable car. 
                            Only small issue was waiting 10 minutes during pickup, but overall very happy.
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ☆</p>
                    </div>
                </div>
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>A_doom_belle</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            Customer service was helpful when I needed to change my return time. Car ran perfectly and return 
                            process was fast. Would love to see more SUV choices in the future.
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ☆</p>
                    </div>
                </div>
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>Melinda_Grande</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            This was my first time renting online, and I was impressed! The instructions were clear, the rates 
                            were reasonable, and the car was spotless. I’ll be recommending this to friends.
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ★</p>
                    </div>
                </div>
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>Bob_Dylan_Fans</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            Loved how quick and easy it was to book. The site is user-friendly and the vehicle was even better than 
                            expected. Clean, fuel-efficient, and perfect for a road trip, the best & only choice is here!
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ★</p>
                    </div>
                </div>
                <div class="rating-block">
                    <div class="rating-user">
                        <img src="images/pfp.png">
                        <span>SanAnderasTheGOAT</span>
                    </div>
                    <div class="rating-desc">
                        <p>
                            CarGO really lives up to the hype! Affordable prices, polite staff, and a seamless rental experience. 
                            I felt confident and safe the entire drive. Definitely booking again for my next holiday.
                        </p>
                    </div>
                    <div class="rating-stars">
                        <p>★ ★ ★ ★ ★</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="home-about">
            <div class="about-image">
                <img src="images/HomeAbout.jpg">
            </div>
            <div class="about-texts">
                <div class="about-detail">
                <h1>Driving excellence in car rental services</h1>
                    <p>
                        With a diverse fleet of vehicles and a commitment to customer satisfaction, 
                        we strive to make your journey smooth and enjoyable.
                    </p>
                    <button onclick="window.location.href='aboutUs.php'">Know More About Us <strong>↵</strong></button>
                </div>
                <div class="about-data">
                    <div class="data-desc">
                        <h2>10,000+</h2>
                        <p>satisfied customers served across the cities.</p>
                    </div>
                    <div class="data-desc">
                        <h2>4.8/5</h2>
                        <p>average customer satisfaction rating maintained.</p>
                    </div>
                    <div class="data-desc">
                        <h2>50+</h2>
                        <p>vehicles offered across a wide range of categories.</p>
                    </div>
                    <div class="data-desc">
                        <h2>85%</h2>
                        <p>of all bookings enabled with instant confirmation.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include("include/footer.php"); ?>
    <script>
        function toggleVideo() {
            const video = document.getElementById('bgVideo');
            const button = document.getElementById('toggleBtn');

            if (video.paused) {
                video.play();
                button.textContent = '▶';
            } else {
                video.pause();
                button.textContent = '❚❚';
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('bgVideo');
            const button = document.getElementById('toggleBtn');

            button.textContent = video.paused ? '▶' : '❚❚';
        });
    </script>
</body>
</html>
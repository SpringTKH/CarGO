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
    <title>Booking Page</title>
    <link rel="stylesheet" href="styles/font.css">
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/booking.css">
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
    <?php include("include/header.php"); ?>
    <div class="container car-filter-layout">
        <aside class="filter-sidebar">
            <h2 class="filter-title">Filter</h2>
            <form method="GET" action="">
                <div class="filter-group closed">
                    <h3>
                        Car Type
                        <button type="button" class="toggle-btn" onclick="toggleFilter(this)">◢</button>
                    </h3>
                    <div class="filter-content">
                        <label><input type="checkbox" name="car_type[]" value="Sedan"> Sedan</label>
                        <label><input type="checkbox" name="car_type[]" value="Hatchback"> Hatchback</label>
                        <label><input type="checkbox" name="car_type[]" value="SUV"> SUV</label>
                        <label><input type="checkbox" name="car_type[]" value="Sports"> Sports</label>
                        <label><input type="checkbox" name="car_type[]" value="Convertible"> Convertible</label>
                        <label><input type="checkbox" name="car_type[]" value="Truck"> Truck</label>
                        <label><input type="checkbox" name="car_type[]" value="Minivan"> Minivan</label>
                        <label><input type="checkbox" name="car_type[]" value="Other"> Other</label>
                    </div>
                </div>
                <div class="filter-group closed">
                    <h3>
                        Car Price Range
                        <button type="button" class="toggle-btn" onclick="toggleFilter(this)">◢</button>
                    </h3>
                    <div class="filter-content">
                        <label for="min_price">Min:</label>
                        <input type="range" id="minPrice" name="min_price" min="0" max="500" 
                            value="<?= $_GET['min_price'] ?? 0 ?>" 
                            oninput="minPriceOutput.value = this.value">
                        <output id="minPriceOutput"><?= $_GET['min_price'] ?? 0 ?></output> 

                        <label for="max_price">Max:</label>
                        <input type="range" id="maxPrice" name="max_price" min="100" max="5000" 
                            value="<?= $_GET['max_price'] ?? 5000 ?>" 
                            oninput="maxPriceOutput.value = this.value">
                        <output id="maxPriceOutput"><?= $_GET['max_price'] ?? 5000 ?></output>
                    </div>
                </div>
                <div class="filter-group closed">
                    <h3>
                        Seats
                        <button type="button" class="toggle-btn" onclick="toggleFilter(this)">◢</button>
                    </h3>
                    <div class="filter-content">
                        <label><input type="checkbox" name="seats[]" value="2-3"> 2–3</label>
                        <label><input type="checkbox" name="seats[]" value="4-5"> 4–5</label>
                        <label><input type="checkbox" name="seats[]" value="6-8"> 6–8</label> 
                    </div>
                </div>
                <div class="filter-group closed">
                    <h3>
                        Car Gear
                        <button type="button" class="toggle-btn" onclick="toggleFilter(this)">◢</button>
                    </h3>
                    <div class="filter-content">
                        <label><input type="checkbox" name="gear_type[]" value="Auto"> Auto</label>
                        <label><input type="checkbox" name="gear_type[]" value="Manual"> Manual</label>
                    </div>
                </div>
                <div class="filter-group closed">
                    <h3>
                        Car Rating
                        <button type="button" class="toggle-btn" onclick="toggleFilter(this)">◢</button>
                    </h3>
                    <div class="filter-content">
                        <label><input type="checkbox" name="rating_range[]" value="0-1.9"> 0.0 to 1.9</label>
                        <label><input type="checkbox" name="rating_range[]" value="2-2.9"> 2.0 to 2.9</label>
                        <label><input type="checkbox" name="rating_range[]" value="3-3.9"> 3.0 to 3.9</label>
                        <label><input type="checkbox" name="rating_range[]" value="4-5"> 4.0 to 5.0</label>
                    </div>
                </div>
                <div class="filter-button">
                    <button type="submit" id="applyBtn">Apply Filters</button>
                    <button type="button" onclick="resetFilters()" id="resetBtn">Reset Filters</button>
                </div>
            </form>
        </aside>
        <section class="car-list-content">
            <?php
                include 'include/connection.php';

                $totalResult = $conn->query('SELECT COUNT(*) as total FROM cars');
                $totalCars = $totalResult->fetch_assoc()['total'];

                $filters = [];
                $params = [];
                $types = '';

                if (!empty($_GET['car_type'])) {
                    $placeholders = implode(',', array_fill(0, count($_GET['car_type']), '?'));
                    $filters[] = "car_type IN ($placeholders)";
                    $params = array_merge($params, $_GET['car_type']);
                    $types .= str_repeat('s', count($_GET['car_type']));
                }

                if (!empty($_GET['min_price'])) {
                    $filters[] = "rental_price_per_day >= ?";
                    $params[] = $_GET['min_price'];
                    $types .= 'd';
                }

                if (!empty($_GET['max_price'])) {
                    $filters[] = "rental_price_per_day <= ?";
                    $params[] = $_GET['max_price'];
                    $types .= 'd';
                }

                if (!empty($_GET['seats'])) {
                    $seatConditions = [];
                    foreach ($_GET['seats'] as $range) {
                        if ($range === '2-3') {
                            $seatConditions[] = '(max_passengers BETWEEN 2 AND 3)';
                        } elseif ($range === '4-5') {
                            $seatConditions[] = '(max_passengers BETWEEN 4 AND 5)';
                        } elseif ($range === '6-8') {
                            $seatConditions[] = '(max_passengers BETWEEN 6 AND 8)';
                        }
                    }
                    if (!empty($seatConditions)) {
                        $filters[] = '(' . implode(' OR ', $seatConditions) . ')';
                    }
                }

                if (!empty($_GET['gear_type'])) {
                    $placeholders = implode(',', array_fill(0, count($_GET['gear_type']), '?'));
                    $filters[] = "gear_type IN ($placeholders)";
                    $params = array_merge($params, $_GET['gear_type']);
                    $types .= str_repeat('s', count($_GET['gear_type']));
                }

                if (!empty($_GET['rating_range'])) {
                    $ratingConditions = [];

                    foreach ($_GET['rating_range'] as $range) {
                        if (preg_match('/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $range, $matches)) {
                            $start = (float)$matches[1];
                            $end = (float)$matches[2];
                            $ratingConditions[] = "(rating BETWEEN ? AND ?)";
                            $params[] = $start;
                            $params[] = $end;
                            $types .= 'dd';
                        }
                    }

                    if (!empty($ratingConditions)) {
                        $filters[] = '(' . implode(' OR ', $ratingConditions) . ')';
                    }
                }

                $query = 'SELECT * FROM cars';
                if ($filters) {
                    $query .= ' WHERE ' . implode(' AND ', $filters);
                }

                $sort = $_GET['sort'] ?? 'car_id';
                $validSortOptions = [
                    'car_id' => 'car_id ASC',
                    'car_name_asc' => 'car_name ASC',
                    'car_name_desc' => 'car_name DESC',
                    'rating_desc' => 'rating DESC',
                    'rating_asc' => 'rating ASC',
                    'seats_desc' => 'max_passengers DESC',
                    'seats_asc' => 'max_passengers ASC',
                ];

                $orderBy = $validSortOptions[$sort] ?? 'car_id ASC';
                $query .= " ORDER BY $orderBy";

                $stmt = $conn->prepare($query);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $filteredCars = $result->num_rows;
            ?>
            <div class="car-list-header">
                <div class="car-list-summary">
                    <?= $filteredCars ?> out of <?= $totalCars ?> cars found
                </div>
                <form method="GET" class="car-sort-form">
                    <?php
                    foreach ($_GET as $key => $value) {
                        if ($key !== 'sort') {
                            if (is_array($value)) {
                                foreach ($value as $v) {
                                    echo "<input type='hidden' name='{$key}[]' value='" . htmlspecialchars($v) . "'>";
                                }
                            } else {
                                echo "<input type='hidden' name='$key' value='" . htmlspecialchars($value) . "'>";
                            }
                        }
                    }
                    ?>
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="car_id" <?= $sort === 'car_id' ? 'selected' : '' ?>>Car ID</option>
                        <option value="car_name_asc" <?= $sort === 'car_name_asc' ? 'selected' : '' ?>>Name A → Z</option>
                        <option value="car_name_desc" <?= $sort === 'car_name_desc' ? 'selected' : '' ?>>Name Z → A</option>
                        <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Rating 5.0 → 0.0</option>
                        <option value="rating_asc" <?= $sort === 'rating_asc' ? 'selected' : '' ?>>Rating 0.0 → 5.0</option>
                        <option value="seats_desc" <?= $sort === 'seats_desc' ? 'selected' : '' ?>>Seats High → Low</option>
                        <option value="seats_asc" <?= $sort === 'seats_asc' ? 'selected' : '' ?>>Seats Low → High</option>
                    </select>
                </form>
            </div>
            <div class="car-pagination-wrapper">
                <div class="car-pages" id="carPages">
                    <?php $index = 0; while ($row = $result->fetch_assoc()): ?>
                        <div class="carLists car-page-item" data-index="<?= $index ?>" 
                            data-car-id="<?= htmlspecialchars($row['car_id']) ?>">
                            <div class="car-bar">
                                <div class="car-image">
                                    <img src="<?= htmlspecialchars($row['car_picture']) ?>" alt="Car Image">
                                </div>
                                <div class="car-details">
                                    <div class="row car-name"><?= htmlspecialchars($row['car_name']) ?></div>
                                    <div class="row car-info">
                                        <div class="info-box">
                                            <img src="images/CarType.png" alt="Car Type Icon" class="info-icon">
                                            <?= htmlspecialchars($row['car_type']) ?>
                                        </div>
                                        <div class="info-box">
                                            <img src="images/Seat.png" alt="Seat Icon" class="info-icon">
                                            <?= htmlspecialchars($row['max_passengers']) ?>
                                        </div>
                                        <div class="info-box">
                                            <img src="images/Gear.png" alt="Gear Icon" class="info-icon">
                                            <?= htmlspecialchars($row['gear_type']) ?>
                                        </div>
                                    </div>
                                    <div class="row car-conditions">
                                        <div class="cond-box"><?= $row['free_cancellation'] ? '✅' : '❌' ?> Free Cancellation</div>
                                        <div class="cond-box"><?= $row['price_guaranteed'] ? '✅' : '❌' ?> Price Guaranteed</div>
                                        <div class="cond-box"><?= $row['instantly_confirmed'] ? '✅' : '❌' ?> Instantly Confirmed</div>
                                        <div class="cond-box"><?= $row['third_party_liability'] ? '✅' : '❌' ?> 3rd-Party Liability</div>
                                    </div>
                                    <div class="row car-rating">
                                        <span id="ratingSection">⭐ <?= htmlspecialchars($row['rating']) ?> 
                                            (<?= htmlspecialchars($row['review_count']) ?> reviews)</span>
                                        <button class="rate-now">Rate</button>
                                    </div>
                                </div>
                                <div class="car-price">
                                    <div class="price">RM<?= htmlspecialchars($row['rental_price_per_day']) ?></div>
                                    <div class="per-day">per day</div>
                                    <a href="booking_form.php?car_id=<?= urlencode($row['car_id']) ?>">
                                        <button class="book-now">BOOK NOW</button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php $index++; endwhile; ?>
                </div>

                <div class="pagination-controls">
                    <button onclick="prevPage()" id="prevBtn"> &lt; </button>
                    <span id="pageIndicator">Page 1</span>
                    <button onclick="nextPage()" id="nextBtn"> &gt; </button>
                </div>
            </div>
        </section>
    </div>
    <?php include("include/footer.php"); ?>
    <div class="rating-popup-overlay" id="ratingPopup" style="display: none;">
        <div class="rating-popup">
            <button class="close-btn" onclick="closeRatingPopup()">×</button>
            <h2 class="rating-title">How much do you like this car?</h2>
            <div class="star-container" id="starContainer">
                <span class="star" data-value="1">&#9733;</span>
                <span class="star" data-value="2">&#9733;</span>
                <span class="star" data-value="3">&#9733;</span>
                <span class="star" data-value="4">&#9733;</span>
                <span class="star" data-value="5">&#9733;</span>
            </div>
            <button id="submitRating" class="submit-rating-btn" disabled>Submit Rating</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const minSlider = document.getElementById('minPrice');
            const maxSlider = document.getElementById('maxPrice');
            const minOutput = document.getElementById('minPriceOutput');
            const maxOutput = document.getElementById('maxPriceOutput');
            const defaultMax = 5000;

            function syncMin() {
                const minVal = parseInt(minSlider.value);
                const maxVal = parseInt(maxSlider.value);

                if (minVal >= maxVal) {
                    maxSlider.value = defaultMax;
                    maxOutput.textContent = defaultMax;
                }
                minOutput.textContent = minSlider.value;
            }

            function syncMax() {
                const minVal = parseInt(minSlider.value);
                const maxVal = parseInt(maxSlider.value);

                if (maxVal <= minVal) {
                    maxSlider.value = minVal + 100;
                }
                maxOutput.textContent = maxSlider.value;
            }

            minSlider.addEventListener('input', syncMin);
            maxSlider.addEventListener('input', syncMax);
        });

        function toggleFilter(button) {
            const group = button.closest('.filter-group');
            const content = group.querySelector('.filter-content');
            const isClosed = group.classList.contains('closed');

            if (isClosed) {
                content.style.display = 'block';
                content.style.paddingBottom = '15px';
                content.style.height = content.scrollHeight + 'px';
                group.classList.remove('closed');
                button.textContent = '◤';

                content.addEventListener('transitionend', function autoHeight(e) {
                    if (e.propertyName === 'height') {
                        content.style.height = 'auto';
                        content.removeEventListener('transitionend', autoHeight);
                    }
                });
            } else {
                content.style.height = content.scrollHeight + 'px';
                void content.offsetHeight;
                content.style.height = '0';
                content.style.paddingBottom = '0';
                group.classList.add('closed');
                button.textContent = '◢';
            }
        }

        function resetFilters() {    
            window.location.href = window.location.pathname;
        }

        const itemsPerPage = 5;
        let currentPage = 0;
        const carItems = Array.from(document.querySelectorAll('.car-page-item'));

        function showPage(page) {
            const totalPages = Math.ceil(carItems.length / itemsPerPage);
            currentPage = Math.max(0, Math.min(page, totalPages - 1));

            const start = currentPage * itemsPerPage;
            const end = start + itemsPerPage;

            carItems.forEach((item, index) => {
                item.style.display = (index >= start && index < end) ? 'block' : 'none';
            });

            document.getElementById('prevBtn').disabled = currentPage === 0;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages - 1;

            const pageIndicator = document.getElementById('pageIndicator');
            pageIndicator.textContent = `Page ${currentPage + 1} of ${totalPages}`;
        }

        function prevPage() {
            showPage(currentPage - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function nextPage() {
            showPage(currentPage + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.addEventListener('DOMContentLoaded', () => {
            showPage(0);
        });

        let selectedRating = 0;
        let selectedCarId = null;

        document.querySelectorAll('.rate-now').forEach(button => {
            button.addEventListener('click', (e) => {
                selectedCarId = e.target.closest('.carLists').dataset.carId;
                openRatingPopup();
            });
        });

        function openRatingPopup() {
            selectedRating = 0;
            document.getElementById('submitRating').disabled = true;
            document.querySelectorAll('.star').forEach(star => {
                star.classList.remove('selected', 'hover');
            });
            document.getElementById('ratingPopup').style.display = 'flex';
        }

        function closeRatingPopup() {
            document.getElementById('ratingPopup').style.display = 'none';
        }

        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.dataset.value);
                highlightStars(val, 'hover');
            });
            star.addEventListener('mouseleave', () => {
                highlightStars(selectedRating, 'selected');
            });
            star.addEventListener('click', () => {
                selectedRating = parseInt(star.dataset.value);
                highlightStars(selectedRating, 'selected');
                document.getElementById('submitRating').disabled = false;
            });
        });

        function highlightStars(upTo, className) {
            document.querySelectorAll('.star').forEach(star => {
                star.classList.remove('hover', 'selected');
                if (parseInt(star.dataset.value) <= upTo) {
                    star.classList.add(className);
                }
            });
        }

        document.getElementById('submitRating').addEventListener('click', () => {
            fetch('rate_car.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `car_id=${encodeURIComponent(selectedCarId)}&rating=${selectedRating}`
            })
            .then(res => res.text())
            .then(response => {
                if (response.trim() === 'success') {
                    alert('Thank you for your rating!');
                    closeRatingPopup();
                    location.reload();
                } else {
                    alert('Error: ' + response);
                }
            });
        });
    </script>
</body>
</html>
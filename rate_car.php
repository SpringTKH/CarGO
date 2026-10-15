<?php
    // Phase 1: Authentication check
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo "Unauthorized";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'], $_POST['rating'])) {
        require_once 'include/connection.php';

        $car_id = intval($_POST['car_id']);
        $submitted_rating = floatval($_POST['rating']);

        // Phase 1: Validate rating range
        if ($submitted_rating < 0 || $submitted_rating > 5) {
            echo "Invalid rating value";
            exit;
        }

        $stmt = $conn->prepare("SELECT rating, review_count FROM cars WHERE car_id = ?");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $current_rating = $row['rating'];
            $review_count = $row['review_count'];

            $new_review_count = $review_count + 1;
            $new_rating = (($review_count * $current_rating) + $submitted_rating) / $new_review_count;

            $update = $conn->prepare("UPDATE cars SET rating = ?, review_count = ? WHERE car_id = ?");
            $update->bind_param("dii", $new_rating, $new_review_count, $car_id);
            $update->execute();

            echo "success";
        } else {
            echo "Car not found";
        }
    } else {
        echo "Invalid request";
    }
?>
<?php
session_start();
include 'db_connection.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $hotel_id = filter_input(INPUT_POST, 'hotel_id', FILTER_VALIDATE_INT);
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $check_in_date_str = $_POST['check_in_date'] ?? '';
    $check_out_date_str = $_POST['check_out_date'] ?? '';
    $num_rooms = filter_input(INPUT_POST, 'num_rooms', FILTER_VALIDATE_INT);
    $num_guests = filter_input(INPUT_POST, 'num_guests', FILTER_VALIDATE_INT);
    $meal_plan = $_POST['meal_plan'] ?? 'none';

    // Input validation
    if (!$hotel_id || !$room_id || !$num_rooms || $num_rooms <= 0 || !$num_guests || $num_guests <= 0 || empty($check_in_date_str) || empty($check_out_date_str)) {
        $_SESSION['error_message'] = 'All booking fields are required and must be valid.';
        header('Location: hotel_details.php?hotel_id=' . $hotel_id); // Redirect back with error
        exit();
    }

    $check_in_date = new DateTime($check_in_date_str);
    $check_out_date = new DateTime($check_out_date_str);
    $today = new DateTime(date('Y-m-d'));

    if ($check_in_date < $today || $check_out_date <= $check_in_date) {
        $_SESSION['error_message'] = 'Invalid check-in/check-out dates. Dates cannot be in the past, and check-out must be after check-in.';
        header('Location: hotel_details.php?hotel_id=' . $hotel_id); // Redirect back with error
        exit();
    }

    $interval = $check_in_date->diff($check_out_date);
    $number_of_nights = $interval->days;

    if ($number_of_nights <= 0) {
        $_SESSION['error_message'] = 'Booking must be for at least one night.';
        header('Location: hotel_details.php?hotel_id=' . $hotel_id); // Redirect back with error
        exit();
    }

    // Validate meal plan
    $allowed_meal_plans = ['none', 'breakfast', 'half_board', 'full_board'];
    if (!in_array($meal_plan, $allowed_meal_plans)) {
        $meal_plan = 'none'; // Default to none if invalid
    }

    // --- Transaction Start --- 
    $conn->begin_transaction();

    try {
        // 1. Fetch room details and check availability
        $stmt_room = $conn->prepare("SELECT room_type, capacity, price_per_night, num_rooms_available FROM hotel_rooms WHERE room_id = ? AND hotel_id = ? FOR UPDATE");
        $stmt_room->bind_param("ii", $room_id, $hotel_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        $room_data = $result_room->fetch_assoc();
        $stmt_room->close();

        if (!$room_data) {
            throw new Exception('Selected room type not found or not associated with this hotel.');
        }

        if ($room_data['num_rooms_available'] < $num_rooms) {
            throw new Exception('Not enough rooms of this type available. Only ' . $room_data['num_rooms_available'] . ' left.');
        }

        if ($num_guests > ($room_data['capacity'] * $num_rooms)) {
            throw new Exception('Number of guests exceeds the capacity for the selected rooms.');
        }

        $price_per_night = $room_data['price_per_night'];
        $total_price = $price_per_night * $num_rooms * $number_of_nights;

        // Add meal plan cost - simplified example, actual cost logic might be more complex
        // For simplicity, let's assume a fixed extra cost per guest per night for meal plans
        $meal_cost_per_guest_per_night = 0;
        switch ($meal_plan) {
            case 'breakfast': $meal_cost_per_guest_per_night = 15; break;
            case 'half_board': $meal_cost_per_guest_per_night = 40; break;
            case 'full_board': $meal_cost_per_guest_per_night = 70; break;
        }
        $total_price += ($meal_cost_per_guest_per_night * $num_guests * $number_of_nights);

        // 2. Insert into hotel_bookings table
        $stmt_insert = $conn->prepare("INSERT INTO hotel_bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, num_rooms, num_guests, meal_plan, total_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_insert->bind_param("iiissiisd", $user_id, $hotel_id, $room_id, $check_in_date_str, $check_out_date_str, $num_rooms, $num_guests, $meal_plan, $total_price);
        
        if (!$stmt_insert->execute()) {
            throw new Exception('Failed to create hotel booking: ' . $stmt_insert->error);
        }
        $hotel_booking_id = $stmt_insert->insert_id;
        $stmt_insert->close();

        // 3. Decrement num_rooms_available
        $stmt_update_rooms = $conn->prepare("UPDATE hotel_rooms SET num_rooms_available = num_rooms_available - ? WHERE room_id = ?");
        $stmt_update_rooms->bind_param("ii", $num_rooms, $room_id);
        if (!$stmt_update_rooms->execute()) {
            throw new Exception('Failed to update room availability: ' . $stmt_update_rooms->error);
        }
        $stmt_update_rooms->close();

        $conn->commit();

        // Redirect to payment page with the new hotel_booking_id
        header('Location: payment.php?hotel_booking_id=' . $hotel_booking_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Booking failed: ' . $e->getMessage();
        header('Location: hotel_details.php?hotel_id=' . $hotel_id); // Redirect back with error
        exit();
    }

} else {
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: index.php'); // Or a general error page
    exit();
}

$conn->close();
?>
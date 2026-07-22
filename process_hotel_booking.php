<?php
session_start();
// Issue 1 Fix: Changed 'include' to 'require_once' for critical dependencies like db_connection.php.
// This ensures the file is loaded exactly once and halts script execution if the file is missing,
// providing clearer error indication and consistency.
require_once 'db_connection.php';
// Issue 1 Fix: Changed 'include' to 'require_once' for critical dependencies like config.php.
// This ensures the file is loaded exactly once and halts script execution if the file is missing,
// providing clearer error indication and consistency.
require_once 'config.php'; // Provides application-wide configuration settings and constants.

// Issue 9: Register shutdown function to ensure database connection is closed even if script exits early.
register_shutdown_function(function() use ($conn) {
    // Check if the connection object exists and is still active before attempting to close.
    if ($conn instanceof mysqli && $conn->ping()) {
        $conn->close();
    }
});

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

    // Issue 5: Define redirect URL as a variable to avoid repetition and improve maintainability.
    // Issue 4: The preceding check `!$hotel_id` already mitigates incomplete/incorrect URL for `$hotel_id` validity.
    $redirect_url_hotel_details = 'hotel_details.php?hotel_id=' . ($hotel_id ?: ''); 

    // Input validation
    if (!$hotel_id || !$room_id || !$num_rooms || $num_rooms <= 0 || !$num_guests || $num_guests <= 0 || empty($check_in_date_str) || empty($check_out_date_str)) {
        // Issue 1 Fix: Changed the error message to be more generic to prevent information leakage.
        $_SESSION['error_message'] = 'An error occurred with the provided booking details.';
        header('Location: ' . $redirect_url_hotel_details); // Redirect back with error
        exit();
    }

    // Issue 2: Use DateTime::createFromFormat for explicit date format validation.
    // Define date format constant locally for minimal changes.
    const DATE_FORMAT = 'Y-m-d';
    $check_in_date = DateTime::createFromFormat(DATE_FORMAT, $check_in_date_str);
    $check_out_date = DateTime::createFromFormat(DATE_FORMAT, $check_out_date_str);
    // Issue 2 Fix: Simplified getting today's date without time. 'new DateTime('today')' is clearer and more concise
    // than 'new DateTime(date(DATE_FORMAT))' for getting the current date at midnight.
    $today = new DateTime('today');

    // Check if dates were successfully parsed or contain warnings (e.g., '2023-02-30')
    if ($check_in_date === false || $check_out_date === false || ($check_in_date->getLastErrors()['warning_count'] ?? 0) > 0 || ($check_out_date->getLastErrors()['warning_count'] ?? 0) > 0) {
        // Issue 2 Fix: Changed the error message to be more generic, avoiding explicit format details for security.
        $_SESSION['error_message'] = 'Invalid date provided.';
        header('Location: ' . $redirect_url_hotel_details);
        exit();
    }

    if ($check_in_date < $today || $check_out_date <= $check_in_date) {
        // Issue 3 Fix: Changed the error message to be more generic, avoiding specific validation rule details for security.
        $_SESSION['error_message'] = 'The selected dates are invalid.';
        header('Location: ' . $redirect_url_hotel_details); // Redirect back with error
        exit();
    }

    $interval = $check_in_date->diff($check_out_date);
    $number_of_nights = $interval->days;

    if ($number_of_nights <= 0) {
        // Issue 4 Fix: Changed the error message to be more generic, avoiding specific constraint details for security.
        $_SESSION['error_message'] = 'The booking duration is invalid.';
        header('Location: ' . $redirect_url_hotel_details); // Redirect back with error
        exit();
    }

    // Validate meal plan
    $allowed_meal_plans = ['none', 'breakfast', 'half_board', 'full_board'];
    if (!in_array($meal_plan, $allowed_meal_plans)) {
        // Issue 8: Instead of defaulting, trigger an error message for invalid meal plan input.
        $_SESSION['error_message'] = 'Invalid meal plan selected.';
        header('Location: ' . $redirect_url_hotel_details);
        exit();
    }

    // --- Transaction Start --- 
    $conn->begin_transaction();

    try {
        // 1. Fetch room details and check availability
        // Issue 10: 'FOR UPDATE' locks rows to prevent race conditions in concurrent bookings, ensuring data integrity.
        $stmt_room = $conn->prepare("SELECT room_type, capacity, price_per_night, num_rooms_available FROM hotel_rooms WHERE room_id = ? AND hotel_id = ? FOR UPDATE");
        $stmt_room->bind_param("ii", $room_id, $hotel_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();
        $room_data = $result_room->fetch_assoc();
        $stmt_room->close();

        if (!$room_data) {
            // Issue 5 Fix: Changed the error message to be more generic, preventing information leakage about internal resource details.
            throw new Exception('An error occurred during booking.');
        }

        if ($room_data['num_rooms_available'] < $num_rooms) {
            // Issue 12: This message is informative for the user and acceptable for a booking system.
            throw new Exception('Not enough rooms of this type available. Only ' . $room_data['num_rooms_available'] . ' left.');
        }

        if ($num_guests > ($room_data['capacity'] * $num_rooms)) {
            // Issue 13: This message is informative for the user and acceptable for a booking system.
            throw new Exception('Number of guests exceeds the capacity for the selected rooms.');
        }

        $price_per_night = $room_data['price_per_night'];
        $total_price = $price_per_night * $num_rooms * $number_of_nights;

        // Add meal plan cost - simplified example, actual cost logic might be more complex
        // For simplicity, let's assume a fixed extra cost per guest per night for meal plans
        $meal_cost_per_guest_per_night = 0;
        // Issue 14: Define meal plan costs as constants for better maintainability.
        // Assuming these constants are defined either in config.php or locally for minimal change.
        const MEAL_COST_BREAKFAST = 15;
        const MEAL_COST_HALF_BOARD = 40;
        const MEAL_COST_FULL_BOARD = 70;

        switch ($meal_plan) {
            case 'breakfast': $meal_cost_per_guest_per_night = MEAL_COST_BREAKFAST; break;
            case 'half_board': $meal_cost_per_guest_per_night = MEAL_COST_HALF_BOARD; break;
            case 'full_board': $meal_cost_per_guest_per_night = MEAL_COST_FULL_BOARD; break;
        }
        $total_price += ($meal_cost_per_guest_per_night * $num_guests * $number_of_nights);

        // 2. Insert into hotel_bookings table
        // Issue 15: Define booking_status as a constant for readability and maintainability.
        const BOOKING_STATUS_PENDING = 'pending';
        $stmt_insert = $conn->prepare("INSERT INTO hotel_bookings (user_id, hotel_id, room_id, check_in_date, check_out_date, num_rooms, num_guests, meal_plan, total_price, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iiissiisds", $user_id, $hotel_id, $room_id, $check_in_date_str, $check_out_date_str, $num_rooms, $num_guests, $meal_plan, $total_price, BOOKING_STATUS_PENDING);
        
        if (!$stmt_insert->execute()) {
            // Issue 16: Log detailed database error internally and provide a generic message to the user.
            error_log("Database error in hotel_bookings insert: " . $stmt_insert->error);
            throw new Exception('Failed to create booking. Please try again.');
        }
        $hotel_booking_id = $stmt_insert->insert_id;
        $stmt_insert->close();

        // 3. Decrement num_rooms_available
        $stmt_update_rooms = $conn->prepare("UPDATE hotel_rooms SET num_rooms_available = num_rooms_available - ? WHERE room_id = ?");
        $stmt_update_rooms->bind_param("ii", $num_rooms, $room_id);
        if (!$stmt_update_rooms->execute()) {
            // Issue 17: Log detailed database error internally and provide a generic message to the user.
            error_log("Database error in room availability update: " . $stmt_update_rooms->error);
            throw new Exception('Failed to update room availability. Please try again.');
        }
        $stmt_update_rooms->close();

        $conn->commit();

        // Redirect to payment page with the new hotel_booking_id
        header('Location: payment.php?hotel_booking_id=' . $hotel_booking_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Issue 18: Log detailed exception message internally and provide a generic message to the user.
        error_log("Booking exception: " . $e->getMessage());
        $_SESSION['error_message'] = 'Booking failed. An unexpected error occurred. Please try again.';
        header('Location: ' . $redirect_url_hotel_details); // Redirect back with error
        exit();
    }

} else {
    // Issue 18: Make error message more generic, similar to other user-facing errors.
    $_SESSION['error_message'] = 'Invalid request. Please submit the booking form.';
    header('Location: index.php'); // Or a general error page
    exit();
}

// Issue 9: The register_shutdown_function handles connection closing, so this explicit close is no longer needed here.
// $conn->close();
?>
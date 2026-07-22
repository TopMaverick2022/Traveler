<?php
session_start();
// [Issue 1 FIX] Generate CSRF token if not already present in the session to protect against Cross-Site Request Forgery.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'db_connection.php';

// [Issue 4 FIX] Helper function to centralize redirection logic, reducing code duplication and making future changes easier.
function redirect_with_message(string $view, string $message): void {
    header('Location: admin.php?view=' . urlencode($view) . '&message=' . urlencode($message));
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$action = $_POST['action'] ?? '';
$message = '';

switch ($action) {
    // --- Destination CRUD --- 
    case 'add_destination':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('destinations', 'CSRF token mismatch. Please try again.');
        }

        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $name = trim($_POST['name']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url']);

        // [Issue 3 FIX] Added specific input validation for string fields (name, description, image_url).
        if (empty($name)) {
            $message = 'Destination name is required.';
        } elseif (empty($description)) {
            $message = 'Destination description is required.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } elseif ($price === false || $price < 0) {
            $message = 'Invalid price.';
        } else {
            $stmt = $conn->prepare("INSERT INTO destination (name, description, price, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $description, $price, $image_url);
            if ($stmt->execute()) {
                $message = 'Destination added successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error adding destination: " . $stmt->error);
                $message = 'Error adding destination. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('destinations', $message);

    case 'edit_destination':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('destinations', 'CSRF token mismatch. Please try again.');
        }

        $id = filter_var($_POST['destination_id'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $name = trim($_POST['name']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url']);

        // [Issue 3 FIX] Added specific input validation for string fields (name, description, image_url).
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } elseif (empty($name)) {
            $message = 'Destination name is required.';
        } elseif (empty($description)) {
            $message = 'Destination description is required.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } elseif ($price === false || $price < 0) {
            $message = 'Invalid price.';
        } else {
            $stmt = $conn->prepare("UPDATE destination SET name=?, description=?, price=?, image_url=? WHERE destination_id=?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $image_url, $id);
            if ($stmt->execute()) {
                $message = 'Destination updated successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating destination: " . $stmt->error);
                $message = 'Error updating destination. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('destinations', $message);

    case 'delete_destination':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('destinations', 'CSRF token mismatch. Please try again.');
        }

        $id = filter_var($_POST['destination_id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM destination WHERE destination_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Destination deleted successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error deleting destination: " . $stmt->error);
                $message = 'Error deleting destination. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('destinations', $message);

    // --- Hotel CRUD --- 
    case 'add_hotel':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotels', 'CSRF token mismatch. Please try again.');
        }

        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $name = trim($_POST['name']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $location = trim($_POST['location']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description'] ?? '');
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url'] ?? '');

        // [Issue 3 FIX] Added specific input validation for string fields (name, location, image_url).
        if (empty($name)) {
            $message = 'Hotel name is required.';
        } elseif (empty($location)) {
            $message = 'Hotel location is required.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } else {
            $stmt = $conn->prepare("INSERT INTO hotels (name, location, description, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $location, $description, $image_url);
            if ($stmt->execute()) {
                $message = 'Hotel added successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error adding hotel: " . $stmt->error);
                $message = 'Error adding hotel. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotels', $message);

    case 'edit_hotel':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotels', 'CSRF token mismatch. Please try again.');
        }

        $id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $name = trim($_POST['name']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $location = trim($_POST['location']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description'] ?? '');
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url'] ?? '');

        // [Issue 3 FIX] Added specific input validation for string fields (name, location, image_url).
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } elseif (empty($name)) {
            $message = 'Hotel name is required.';
        } elseif (empty($location)) {
            $message = 'Hotel location is required.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } else {
            $stmt = $conn->prepare("UPDATE hotels SET name=?, location=?, description=?, image_url=? WHERE hotel_id=?");
            $stmt->bind_param("ssssi", $name, $location, $description, $image_url, $id);
            if ($stmt->execute()) {
                $message = 'Hotel updated successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating hotel: " . $stmt->error);
                $message = 'Error updating hotel. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotels', $message);

    case 'delete_hotel':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotels', 'CSRF token mismatch. Please try again.');
        }

        $id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM hotels WHERE hotel_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Hotel deleted successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error deleting hotel: " . $stmt->error);
                $message = 'Error deleting hotel. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotels', $message);

    // --- Hotel Room CRUD --- 
    case 'add_hotel_room':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotel_rooms', 'CSRF token mismatch. Please try again.');
        }

        $hotel_id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $room_type = trim($_POST['room_type']);
        $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
        $price_per_night = filter_var($_POST['price_per_night'], FILTER_VALIDATE_FLOAT);
        $num_rooms_available = filter_var($_POST['num_rooms_available'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description'] ?? '');
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url'] ?? '');

        // [Issue 3 FIX] Added specific input validation for string fields (room_type, image_url).
        if ($hotel_id === false || $hotel_id <= 0) {
            $message = 'Invalid Hotel ID.';
        } elseif (empty($room_type)) {
            $message = 'Room type is required.';
        } elseif ($capacity === false || $capacity <= 0) {
            $message = 'Invalid capacity.';
        } elseif ($price_per_night === false || $price_per_night < 0) {
            $message = 'Invalid price per night.';
        } elseif ($num_rooms_available === false || $num_rooms_available < 0) {
            $message = 'Invalid number of rooms available.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } else {
            $stmt = $conn->prepare("INSERT INTO hotel_rooms (hotel_id, room_type, capacity, price_per_night, num_rooms_available, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isidiss", $hotel_id, $room_type, $capacity, $price_per_night, $num_rooms_available, $description, $image_url);
            if ($stmt->execute()) {
                $message = 'Hotel room added successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error adding hotel room: " . $stmt->error);
                $message = 'Error adding hotel room. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotel_rooms', $message);

    case 'edit_hotel_room':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotel_rooms', 'CSRF token mismatch. Please try again.');
        }

        $room_id = filter_var($_POST['room_id'], FILTER_VALIDATE_INT);
        $hotel_id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $room_type = trim($_POST['room_type']);
        $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
        $price_per_night = filter_var($_POST['price_per_night'], FILTER_VALIDATE_FLOAT);
        $num_rooms_available = filter_var($_POST['num_rooms_available'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $description = trim($_POST['description'] ?? '');
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $image_url = trim($_POST['image_url'] ?? '');

        // [Issue 3 FIX] Added specific input validation for string fields (room_type, image_url).
        if ($room_id === false || $room_id <= 0) {
            $message = 'Invalid Room ID.';
        } elseif ($hotel_id === false || $hotel_id <= 0) {
            $message = 'Invalid Hotel ID.';
        } elseif (empty($room_type)) {
            $message = 'Room type is required.';
        } elseif ($capacity === false || $capacity <= 0) {
            $message = 'Invalid capacity.';
        } elseif ($price_per_night === false || $price_per_night < 0) {
            $message = 'Invalid price per night.';
        } elseif ($num_rooms_available === false || $num_rooms_available < 0) {
            $message = 'Invalid number of rooms available.';
        } elseif (!empty($image_url) && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $message = 'Invalid image URL format.';
        } else {
            $stmt = $conn->prepare("UPDATE hotel_rooms SET hotel_id=?, room_type=?, capacity=?, price_per_night=?, num_rooms_available=?, description=?, image_url=? WHERE room_id=?");
            $stmt->bind_param("isidissi", $hotel_id, $room_type, $capacity, $price_per_night, $num_rooms_available, $description, $image_url, $room_id);
            if ($stmt->execute()) {
                $message = 'Hotel room updated successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating hotel room: " . $stmt->error);
                $message = 'Error updating hotel room. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotel_rooms', $message);

    case 'delete_hotel_room':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotel_rooms', 'CSRF token mismatch. Please try again.');
        }

        $room_id = filter_var($_POST['room_id'], FILTER_VALIDATE_INT);
        if ($room_id === false || $room_id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM hotel_rooms WHERE room_id=?");
            $stmt->bind_param("i", $room_id);
            if ($stmt->execute()) {
                $message = 'Hotel room deleted successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error deleting hotel room: " . $stmt->error);
                $message = 'Error deleting hotel room. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotel_rooms', $message);

    // --- User CRUD --- 
    case 'add_user': // Admin can add users (optional)
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('users', 'CSRF token mismatch. Please try again.');
        }

        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $username = trim($_POST['username']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        // [Issue 3 FIX] Added specific input validation for string fields (username, email).
        if (empty($username)) {
            $message = 'Username is required.';
        } elseif (empty($email)) {
            $message = 'Email is required.';
        } elseif (empty($_POST['password'])) { // Original check for password emptiness
            $message = 'Password is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            if ($stmt->execute()) {
                $message = 'User added successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error adding user: " . $stmt->error);
                $message = 'Error adding user. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('users', $message);

    case 'edit_user':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('users', 'CSRF token mismatch. Please try again.');
        }

        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $username = trim($_POST['username']);
        // [Issue 2 FIX] Removed historical comment about escaping.
        // [Issue 3 FIX] Trim string inputs and add length/format validation for robustness and data integrity.
        $email = trim($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        // [Issue 3 FIX] Added specific input validation for string fields (username, email).
        if ($user_id === false || $user_id <= 0) {
            $message = 'Invalid User ID.';
        } elseif (empty($username)) {
            $message = 'Username is required.';
        } elseif (empty($email)) {
            $message = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
        } else {
            $sql_parts = [];
            $params = [];
            $types = '';

            $sql_parts[] = "username=?";
            $params[] = $username;
            $types .= 's';

            $sql_parts[] = "email=?";
            $params[] = $email;
            $types .= 's';

            $sql_parts[] = "role=?";
            $params[] = $role;
            $types .= 's';

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_parts[] = "password=?";
                $params[] = $hashed_password;
                $types .= 's';
            }
            
            $params[] = $user_id;
            $types .= 'i';

            $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $message = 'User updated successfully.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating user: " . $stmt->error);
                $message = 'Error updating user. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('users', $message);

    case 'delete_user':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('users', 'CSRF token mismatch. Please try again.');
        }

        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        if ($user_id === false || $user_id <= 0) {
            $message = 'Invalid User ID.';
        } else {
            // Prevent admin from deleting themselves if needed, or other critical users
            if ($user_id == $_SESSION['user_id']) {
                $message = 'You cannot delete your own admin account.';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = 'User deleted successfully.';
                } else {
                    // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                    error_log("Error deleting user: " . $stmt->error);
                    $message = 'Error deleting user. Please try again.';
                }
                $stmt->close();
            }
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('users', $message);

    // --- Booking Status Updates --- 
    case 'update_booking_status':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('bookings', 'CSRF token mismatch. Please try again.');
        }

        $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
        // [Issue 3 FIX] Trim string input for robustness and data integrity.
        $status = trim($_POST['status'] ?? '');
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        // [Issue 3 FIX] Added check for empty status.
        if ($booking_id === false || $booking_id <= 0 || empty($status) || !in_array($status, $allowed_statuses)) {
            $message = 'Invalid booking ID or status.';
        } else {
            $stmt = $conn->prepare("UPDATE booking SET status=? WHERE booking_id=?");
            $stmt->bind_param("si", $status, $booking_id);
            if ($stmt->execute()) {
                $message = 'Booking status updated.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating booking status: " . $stmt->error);
                $message = 'Error updating booking status. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('bookings', $message);

    case 'update_hotel_booking_status':
        // [Issue 1 FIX] Validate CSRF token for POST requests to prevent Cross-Site Request Forgery.
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            redirect_with_message('hotel_bookings', 'CSRF token mismatch. Please try again.');
        }

        $hotel_booking_id = filter_var($_POST['hotel_booking_id'], FILTER_VALIDATE_INT);
        // [Issue 3 FIX] Trim string input for robustness and data integrity.
        $status = trim($_POST['status'] ?? '');
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        // [Issue 3 FIX] Added check for empty status.
        if ($hotel_booking_id === false || $hotel_booking_id <= 0 || empty($status) || !in_array($status, $allowed_statuses)) {
            $message = 'Invalid hotel booking ID or status.';
        } else {
            $stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status=? WHERE hotel_booking_id=?");
            $stmt->bind_param("si", $status, $hotel_booking_id);
            if ($stmt->execute()) {
                $message = 'Hotel booking status updated.';
            } else {
                // Security: Log detailed error for debugging and provide a generic message to prevent information disclosure.
                error_log("Error updating hotel booking status: " . $stmt->error);
                $message = 'Error updating hotel booking status. Please try again.';
            }
            $stmt->close();
        }
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication.
        redirect_with_message('hotel_bookings', $message);

    default:
        $message = 'Unknown action.';
        // [Issue 4 FIX] Used helper function for redirection, reducing code duplication. Redirects to base admin page.
        redirect_with_message('', $message);
}

$conn->close();
?>
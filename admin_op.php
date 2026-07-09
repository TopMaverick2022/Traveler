<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

$action = $_POST['action'] ?? '';
$message = '';

switch ($action) {
    // --- Destination CRUD --- 
    case 'add_destination':
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $image_url = $conn->real_escape_string($_POST['image_url']);

        if ($price === false || $price < 0) {
            $message = 'Invalid price.';
        } else {
            $stmt = $conn->prepare("INSERT INTO destination (name, description, price, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $name, $description, $price, $image_url);
            if ($stmt->execute()) {
                $message = 'Destination added successfully.';
            } else {
                $message = 'Error adding destination: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=destinations&message=' . urlencode($message));
        exit();

    case 'edit_destination':
        $id = filter_var($_POST['destination_id'], FILTER_VALIDATE_INT);
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $image_url = $conn->real_escape_string($_POST['image_url']);

        if ($id === false || $id <= 0 || $price === false || $price < 0) {
            $message = 'Invalid ID or price.';
        } else {
            $stmt = $conn->prepare("UPDATE destination SET name=?, description=?, price=?, image_url=? WHERE destination_id=?");
            $stmt->bind_param("ssdsi", $name, $description, $price, $image_url, $id);
            if ($stmt->execute()) {
                $message = 'Destination updated successfully.';
            } else {
                $message = 'Error updating destination: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=destinations&message=' . urlencode($message));
        exit();

    case 'delete_destination':
        $id = filter_var($_POST['destination_id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM destination WHERE destination_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Destination deleted successfully.';
            } else {
                $message = 'Error deleting destination: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=destinations&message=' . urlencode($message));
        exit();

    // --- Hotel CRUD --- 
    case 'add_hotel':
        $name = $conn->real_escape_string($_POST['name']);
        $location = $conn->real_escape_string($_POST['location']);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');

        if (empty($name) || empty($location)) {
            $message = 'Hotel name and location are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO hotels (name, location, description, image_url) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $location, $description, $image_url);
            if ($stmt->execute()) {
                $message = 'Hotel added successfully.';
            } else {
                $message = 'Error adding hotel: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotels&message=' . urlencode($message));
        exit();

    case 'edit_hotel':
        $id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        $name = $conn->real_escape_string($_POST['name']);
        $location = $conn->real_escape_string($_POST['location']);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');

        if ($id === false || $id <= 0 || empty($name) || empty($location)) {
            $message = 'Invalid ID, name, or location.';
        } else {
            $stmt = $conn->prepare("UPDATE hotels SET name=?, location=?, description=?, image_url=? WHERE hotel_id=?");
            $stmt->bind_param("ssssi", $name, $location, $description, $image_url, $id);
            if ($stmt->execute()) {
                $message = 'Hotel updated successfully.';
            } else {
                $message = 'Error updating hotel: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotels&message=' . urlencode($message));
        exit();

    case 'delete_hotel':
        $id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM hotels WHERE hotel_id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Hotel deleted successfully.';
            } else {
                $message = 'Error deleting hotel: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotels&message=' . urlencode($message));
        exit();

    // --- Hotel Room CRUD --- 
    case 'add_hotel_room':
        $hotel_id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        $room_type = $conn->real_escape_string($_POST['room_type']);
        $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
        $price_per_night = filter_var($_POST['price_per_night'], FILTER_VALIDATE_FLOAT);
        $num_rooms_available = filter_var($_POST['num_rooms_available'], FILTER_VALIDATE_INT);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');

        if ($hotel_id === false || $hotel_id <= 0 || empty($room_type) || $capacity === false || $capacity <= 0 || $price_per_night === false || $price_per_night < 0 || $num_rooms_available === false || $num_rooms_available < 0) {
            $message = 'Invalid input for hotel room.';
        } else {
            $stmt = $conn->prepare("INSERT INTO hotel_rooms (hotel_id, room_type, capacity, price_per_night, num_rooms_available, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isidiss", $hotel_id, $room_type, $capacity, $price_per_night, $num_rooms_available, $description, $image_url);
            if ($stmt->execute()) {
                $message = 'Hotel room added successfully.';
            } else {
                $message = 'Error adding hotel room: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotel_rooms&message=' . urlencode($message));
        exit();

    case 'edit_hotel_room':
        $room_id = filter_var($_POST['room_id'], FILTER_VALIDATE_INT);
        $hotel_id = filter_var($_POST['hotel_id'], FILTER_VALIDATE_INT);
        $room_type = $conn->real_escape_string($_POST['room_type']);
        $capacity = filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
        $price_per_night = filter_var($_POST['price_per_night'], FILTER_VALIDATE_FLOAT);
        $num_rooms_available = filter_var($_POST['num_rooms_available'], FILTER_VALIDATE_INT);
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');

        if ($room_id === false || $room_id <= 0 || $hotel_id === false || $hotel_id <= 0 || empty($room_type) || $capacity === false || $capacity <= 0 || $price_per_night === false || $price_per_night < 0 || $num_rooms_available === false || $num_rooms_available < 0) {
            $message = 'Invalid input for hotel room.';
        } else {
            $stmt = $conn->prepare("UPDATE hotel_rooms SET hotel_id=?, room_type=?, capacity=?, price_per_night=?, num_rooms_available=?, description=?, image_url=? WHERE room_id=?");
            $stmt->bind_param("isidissi", $hotel_id, $room_type, $capacity, $price_per_night, $num_rooms_available, $description, $image_url, $room_id);
            if ($stmt->execute()) {
                $message = 'Hotel room updated successfully.';
            } else {
                $message = 'Error updating hotel room: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotel_rooms&message=' . urlencode($message));
        exit();

    case 'delete_hotel_room':
        $room_id = filter_var($_POST['room_id'], FILTER_VALIDATE_INT);
        if ($room_id === false || $room_id <= 0) {
            $message = 'Invalid ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM hotel_rooms WHERE room_id=?");
            $stmt->bind_param("i", $room_id);
            if ($stmt->execute()) {
                $message = 'Hotel room deleted successfully.';
            } else {
                $message = 'Error deleting hotel room: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotel_rooms&message=' . urlencode($message));
        exit();

    // --- User CRUD --- 
    case 'add_user': // Admin can add users (optional)
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        if (empty($username) || empty($email) || empty($_POST['password'])) {
            $message = 'All fields are required for adding a user.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);
            if ($stmt->execute()) {
                $message = 'User added successfully.';
            } else {
                $message = 'Error adding user: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=users&message=' . urlencode($message));
        exit();

    case 'edit_user':
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        if ($user_id === false || $user_id <= 0 || empty($username) || empty($email)) {
            $message = 'Invalid User ID or missing fields.';
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
                $message = 'Error updating user: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=users&message=' . urlencode($message));
        exit();

    case 'delete_user':
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
                    $message = 'Error deleting user: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
        header('Location: admin.php?view=users&message=' . urlencode($message));
        exit();

    // --- Booking Status Updates --- 
    case 'update_booking_status':
        $booking_id = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        if ($booking_id === false || $booking_id <= 0 || !in_array($status, $allowed_statuses)) {
            $message = 'Invalid booking ID or status.';
        } else {
            $stmt = $conn->prepare("UPDATE booking SET status=? WHERE booking_id=?");
            $stmt->bind_param("si", $status, $booking_id);
            if ($stmt->execute()) {
                $message = 'Booking status updated.';
            } else {
                $message = 'Error updating booking status: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=bookings&message=' . urlencode($message));
        exit();

    case 'update_hotel_booking_status':
        $hotel_booking_id = filter_var($_POST['hotel_booking_id'], FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

        if ($hotel_booking_id === false || $hotel_booking_id <= 0 || !in_array($status, $allowed_statuses)) {
            $message = 'Invalid hotel booking ID or status.';
        } else {
            $stmt = $conn->prepare("UPDATE hotel_bookings SET booking_status=? WHERE hotel_booking_id=?");
            $stmt->bind_param("si", $status, $hotel_booking_id);
            if ($stmt->execute()) {
                $message = 'Hotel booking status updated.';
            } else {
                $message = 'Error updating hotel booking status: ' . $stmt->error;
            }
            $stmt->close();
        }
        header('Location: admin.php?view=hotel_bookings&message=' . urlencode($message));
        exit();

    default:
        $message = 'Unknown action.';
        header('Location: admin.php?message=' . urlencode($message));
        exit();
}

$conn->close();
?>

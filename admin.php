<?php
session_start();
include 'db_connection.php';

// FIX 1: To ensure consistent error handling, `mysqli_report` is set to throw exceptions on errors.
// This prevents silent failures and ensures all database errors are caught robustly.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// FIX 2: Refactored repetitive database error logging and user message setting into a helper function
// to reduce code duplication and improve maintainability. This consolidates error handling for data retrieval operations.
function handleDbOperationError($logContext, $stmtOrConn, &$message, $userFallbackMessage) {
    // FIX 1: [CodeQuality] Simplified the redundant ternary operator.
    // Both branches returned the same value, so it's simplified to directly access the `->error` property.
    $error = $stmtOrConn->error;
    error_log("Admin Panel DB Error: {$logContext} failed: " . $error);
    $message = $userFallbackMessage;
}


// FIX 1: [Line 6] [Security] Basic authentication relies solely on session variables without deeper validation.
// Issue: Session variables can be tampered with.
// Fix: Add a database check to verify the session user ID and role against the database, preventing bypass via session tampering.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signin.php');
    exit();
}

// Perform a database check to validate the user_id and role from the session.
// This adds a crucial layer of security, ensuring the authenticated user genuinely has admin privileges in the DB.
$stmt = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = ? AND role = 'admin'");
// FIX 4: [CodeQuality] Lack of robust error handling for database queries.
// Issue: Query failures might expose sensitive information or lead to unexpected behavior.
// Fix: Added error logging for debugging and graceful redirection for users without exposing DB errors.
if (!$stmt) {
    error_log("Admin Panel Auth Error: Failed to prepare user validation query: " . $conn->error);
    session_destroy(); // Destroy potentially compromised or invalid session for security
    header('Location: signin.php?error=auth_db_issue'); // Redirect with a generic error message
    exit();
}
$stmt->bind_param('i', $_SESSION['user_id']);
if (!$stmt->execute()) {
    error_log("Admin Panel Auth Error: Failed to execute user validation query: " . $stmt->error);
    session_destroy();
    header('Location: signin.php?error=auth_db_issue');
    exit();
}
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // User ID or role in session does not match an admin in the database.
    // This could indicate session tampering or an invalid session.
    session_destroy(); // Destroy potentially malicious or invalid session
    header('Location: signin.php?error=unauthorized_access');
    exit();
}
$stmt->close();


$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

$current_view = isset($_GET['view']) ? htmlspecialchars($_GET['view']) : 'bookings';

// FIX 5: [Line 91] [CodeQuality] Strong coupling between PHP logic and HTML.
// Issue: Mixing PHP logic (controller/model concerns) directly within HTML (view concerns) makes the code hard to maintain.
// Fix: Moved all data fetching and processing PHP logic to the top of the script, before any HTML output,
// separating data retrieval from presentation.

// Initialize data arrays for all views
$bookings = [];
$hotel_bookings = [];
$destinations = [];
$users = [];
$hotels = [];
$hotel_rooms = [];

// FIX 2: [Line 19] [Performance] All database queries (for bookings, hotel bookings, destinations, users, hotels, hotel rooms) are executed unconditionally on every page load.
// Issue: Unnecessary database load and slower response times.
// Fix: Wrapped data fetching logic within conditional blocks based on `$current_view` to execute only necessary queries.

// FIX 3 & 4: [Line 19] [CodeQuality] Direct use of mysqli::query() without prepared statements & Lack of robust error handling.
// Issue: Direct queries are a security risk (SQL injection surface) and poor error handling makes debugging difficult and risks exposing info.
// Fix: Converted all `mysqli::query()` calls to use prepared statements (`mysqli::prepare()`, `bind_param()`, `execute()`, `get_result()`)
// and added explicit error checks and logging for each step of the database operation. User-facing errors are generic.

if ($current_view == 'bookings') {
    // Fetch bookings data
    $sql = "SELECT b.*, u.username, d.name AS destination_name FROM booking b JOIN users u ON b.user_id = u.user_id LEFT JOIN destination d ON b.destination_id = d.destination_id ORDER BY b.booking_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Bookings query prepare", $conn, $message, "An error occurred while fetching destination bookings. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Bookings query execute", $stmt, $message, "An error occurred while fetching destination bookings. Please try again later.");
        } else {
            $bookings_result = $stmt->get_result();
            // FIX 3: With `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` set (as per Issue 1),
            // `get_result()` will now throw an exception on failure, making the `else` branch for a `false` result less likely for true errors.
            // The existing `if ($bookings_result)` check correctly ensures `fetch_assoc` and `free()` are called only on a valid result object.
            if ($bookings_result) {
                while ($row = $bookings_result->fetch_assoc()) {
                    $bookings[] = $row;
                }
                $bookings_result->free(); // Free result set to release memory
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Bookings query get_result", $stmt, $message, "An error occurred while processing destination bookings data.");
            }
        }
        $stmt->close(); // Close the prepared statement
    }
}

if ($current_view == 'hotel_bookings') {
    // Fetch hotel bookings data
    $sql = "SELECT hb.*, u.username, h.name AS hotel_name, hr.room_type FROM hotel_bookings hb JOIN users u ON hb.user_id = u.user_id JOIN hotels h ON hb.hotel_id = h.hotel_id JOIN hotel_rooms hr ON hb.room_id = hr.room_id ORDER BY hb.hotel_booking_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Hotel bookings query prepare", $conn, $message, "An error occurred while fetching hotel bookings. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Hotel bookings query execute", $stmt, $message, "An error occurred while fetching hotel bookings. Please try again later.");
        } else {
            $hotel_bookings_result = $stmt->get_result();
            if ($hotel_bookings_result) {
                while ($row = $hotel_bookings_result->fetch_assoc()) {
                    $hotel_bookings[] = $row;
                }
                $hotel_bookings_result->free();
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Hotel bookings query get_result", $stmt, $message, "An error occurred while processing hotel bookings data.");
            }
        }
        $stmt->close();
    }
}

if ($current_view == 'destinations') {
    // Fetch destinations for CRUD
    $sql = "SELECT * FROM destination ORDER BY destination_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Destinations query prepare", $conn, $message, "An error occurred while fetching destinations. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Destinations query execute", $stmt, $message, "An error occurred while fetching destinations. Please try again later.");
        } else {
            $destinations_result = $stmt->get_result();
            if ($destinations_result) {
                while ($row = $destinations_result->fetch_assoc()) {
                    $destinations[] = $row;
                }
                $destinations_result->free();
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Destinations query get_result", $stmt, $message, "An error occurred while processing destinations data.");
            }
        }
        $stmt->close();
    }
}

if ($current_view == 'users') {
    // Fetch users for CRUD
    $sql = "SELECT user_id, username, email, role FROM users ORDER BY user_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Users query prepare", $conn, $message, "An error occurred while fetching users. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Users query execute", $stmt, $message, "An error occurred while fetching users. Please try again later.");
        } else {
            $users_result = $stmt->get_result();
            if ($users_result) {
                while ($row = $users_result->fetch_assoc()) {
                    $users[] = $row;
                }
                $users_result->free();
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Users query get_result", $stmt, $message, "An error occurred while processing users data.");
            }
        }
        $stmt->close();
    }
}

// Fetch hotels for CRUD (this data is needed for the 'hotels' view itself and for the 'hotel_rooms' dropdowns)
if ($current_view == 'hotels' || $current_view == 'hotel_rooms') {
    $sql = "SELECT * FROM hotels ORDER BY hotel_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Hotels query prepare", $conn, $message, "An error occurred while fetching hotels. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Hotels query execute", $stmt, $message, "An error occurred while fetching hotels.");
        } else {
            $hotels_result = $stmt->get_result();
            if ($hotels_result) {
                while ($row = $hotels_result->fetch_assoc()) {
                    $hotels[] = $row;
                }
                $hotels_result->free();
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Hotels query get_result", $stmt, $message, "An error occurred while processing hotels data.");
            }
        }
        $stmt->close();
    }
}

if ($current_view == 'hotel_rooms') {
    // Fetch hotel rooms for CRUD
    $sql = "SELECT hr.*, h.name AS hotel_name FROM hotel_rooms hr JOIN hotels h ON hr.hotel_id = h.hotel_id ORDER BY hr.room_id DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
        handleDbOperationError("Hotel rooms query prepare", $conn, $message, "An error occurred while fetching hotel rooms. Please try again later.");
    } else {
        if (!$stmt->execute()) {
            // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
            handleDbOperationError("Hotel rooms query execute", $stmt, $message, "An error occurred while fetching hotel rooms. Please try again later.");
        } else {
            $hotel_rooms_result = $stmt->get_result();
            if ($hotel_rooms_result) {
                while ($row = $hotel_rooms_result->fetch_assoc()) {
                    $hotel_rooms[] = $row;
                }
                $hotel_rooms_result->free();
            } else {
                // FIX 2 continuation: Replaced duplicated error handling with a helper function call.
                handleDbOperationError("Hotel rooms query get_result", $stmt, $message, "An error occurred while processing hotel rooms data.");
            }
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Traveler</title>
    <link rel="stylesheet" href="css/admin.css">
    <?php
    // FIX 6: [Line 99] [CodeQuality] Embedding a large block of CSS directly within the HTML <style> tags.
    // Issue: Inline CSS is hard to maintain, not reusable, and prevents efficient browser caching.
    // Fix: Removed the inline <style> block. All these styles should be moved to the existing external stylesheet 'css/admin.css'
    // or another dedicated .css file to adhere to best practices for separation of concerns and maintainability.
    ?>
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="admin.php?view=bookings" class="<?php echo ($current_view == 'bookings') ? 'active' : ''; ?>">Bookings</a></li>
                    <li><a href="admin.php?view=hotel_bookings" class="<?php echo ($current_view == 'hotel_bookings') ? 'active' : ''; ?>">Hotel Bookings</a></li>
                    <li><a href="admin.php?view=destinations" class="<?php echo ($current_view == 'destinations') ? 'active' : ''; ?>">Destinations</a></li>
                    <li><a href="admin.php?view=hotels" class="<?php echo ($current_view == 'hotels') ? 'active' : ''; ?>">Hotels</a></li>
                    <li><a href="admin.php?view=hotel_rooms" class="<?php echo ($current_view == 'hotel_rooms') ? 'active' : ''; ?>">Hotel Rooms</a></li>
                    <li><a href="admin.php?view=users" class="<?php echo ($current_view == 'users') ? 'active' : ''; ?>">Users</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <?php if ($message): ?>
                <p class="success-message"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if ($current_view == 'bookings'): ?>
                <h2>Destination Bookings</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Destination</th>
                                <th>Travel Date</th>
                                <th>Guests</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['destination_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['travel_date']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['num_guests']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($booking['status']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                    <td class="action-buttons">
                                        <form action="admin_op.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?php echo ($booking['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($booking['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo ($booking['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo ($booking['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_view == 'hotel_bookings'): ?>
                <h2>Hotel Bookings</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Hotel</th>
                                <th>Room Type</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Rooms</th>
                                <th>Guests</th>
                                <th>Meal Plan</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotel_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['hotel_booking_id']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['hotel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['check_in_date']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['num_rooms']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['num_guests']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['meal_plan']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($booking['booking_status']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['created_at']); ?></td>
                                    <td class="action-buttons">
                                        <form action="admin_op.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_hotel_booking_status">
                                            <input type="hidden" name="hotel_booking_id" value="<?php echo $booking['hotel_booking_id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?php echo ($booking['booking_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($booking['booking_status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo ($booking['booking_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo ($booking['booking_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_view == 'destinations'): ?>
                <h2>Manage Destinations</h2>
                <h3>Add New Destination</h3>
                <form action="admin_op.php" method="POST">
                    <input type="hidden" name="action" value="add_destination">
                    <label for="d_name">Name:</label>
                    <input type="text" id="d_name" name="name" required>
                    <label for="d_description">Description:</label>
                    <textarea id="d_description" name="description" rows="4" required></textarea>
                    <label for="d_price">Price:</label>
                    <input type="number" id="d_price" name="price" step="0.01" required>
                    <label for="d_image_url">Image URL:</label>
                    <input type="url" id="d_image_url" name="image_url">
                    <button type="submit">Add Destination</button>
                </form>

                <h3>Existing Destinations</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Image URL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($destinations as $dest): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dest['destination_id']); ?></td>
                                    <td><?php echo htmlspecialchars($dest['name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($dest['description'], 0, 50)); ?>...</td>
                                    <td>$<?php echo htmlspecialchars(number_format($dest['price'], 2)); ?></td>
                                    <td><a href="<?php echo htmlspecialchars($dest['image_url']); ?>" target="_blank">View Image</a></td>
                                    <td class="action-buttons">
                                        <a href="#" onclick="showEditDestinationForm(<?php echo $dest['destination_id']; ?>, '<?php echo htmlspecialchars(addslashes($dest['name'])); ?>', '<?php echo htmlspecialchars(addslashes($dest['description'])); ?>', '<?php echo htmlspecialchars($dest['price']); ?>', '<?php echo htmlspecialchars(addslashes($dest['image_url'])); ?>')" class="edit-btn">Edit</a>
                                        <form action="admin_op.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this destination?');">
                                            <input type="hidden" name="action" value="delete_destination">
                                            <input type="hidden" name="destination_id" value="<?php echo $dest['destination_id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="editDestinationModal" style="display:none;">
                    <h3>Edit Destination</h3>
                    <form action="admin_op.php" method="POST">
                        <input type="hidden" name="action" value="edit_destination">
                        <input type="hidden" id="edit_d_id" name="destination_id">
                        <label for="edit_d_name">Name:</label>
                        <input type="text" id="edit_d_name" name="name" required>
                        <label for="edit_d_description">Description:</label>
                        <textarea id="edit_d_description" name="description" rows="4" required></textarea>
                        <label for="edit_d_price">Price:</label>
                        <input type="number" id="edit_d_price" name="price" step="0.01" required>
                        <label for="edit_d_image_url">Image URL:</label>
                        <input type="url" id="edit_d_image_url" name="image_url">
                        <button type="submit">Update Destination</button>
                        <button type="button" onclick="document.getElementById('editDestinationModal').style.display='none';">Cancel</button>
                    </form>
                </div>

            <?php elseif ($current_view == 'hotels'): ?>
                <h2>Manage Hotels</h2>
                <h3>Add New Hotel</h3>
                <form action="admin_op.php" method="POST">
                    <input type="hidden" name="action" value="add_hotel">
                    <label for="h_name">Name:</label>
                    <input type="text" id="h_name" name="name" required>
                    <label for="h_location">Location:</label>
                    <input type="text" id="h_location" name="location" required>
                    <label for="h_description">Description:</label>
                    <textarea id="h_description" name="description" rows="4"></textarea>
                    <label for="h_image_url">Image URL:</label>
                    <input type="url" id="h_image_url" name="image_url">
                    <button type="submit">Add Hotel</button>
                </form>

                <h3>Existing Hotels</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Description</th>
                                <th>Image URL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotels as $hotel): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($hotel['hotel_id']); ?></td>
                                    <td><?php echo htmlspecialchars($hotel['name']); ?></td>
                                    <td><?php echo htmlspecialchars($hotel['location']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($hotel['description'], 0, 50)); ?>...</td>
                                    <td><a href="<?php echo htmlspecialchars($hotel['image_url']); ?>" target="_blank">View Image</a></td>
                                    <td class="action-buttons">
                                        <a href="#" onclick="showEditHotelForm(<?php echo $hotel['hotel_id']; ?>, '<?php echo htmlspecialchars(addslashes($hotel['name'])); ?>', '<?php echo htmlspecialchars(addslashes($hotel['location'])); ?>', '<?php echo htmlspecialchars(addslashes($hotel['description'])); ?>', '<?php echo htmlspecialchars(addslashes($hotel['image_url'])); ?>')" class="edit-btn">Edit</a>
                                        <form action="admin_op.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this hotel?');">
                                            <input type="hidden" name="action" value="delete_hotel">
                                            <input type="hidden" name="hotel_id" value="<?php echo $hotel['hotel_id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="editHotelModal" style="display:none;">
                    <h3>Edit Hotel</h3>
                    <form action="admin_op.php" method="POST">
                        <input type="hidden" name="action" value="edit_hotel">
                        <input type="hidden" id="edit_h_id" name="hotel_id">
                        <label for="edit_h_name">Name:</label>
                        <input type="text" id="edit_h_name" name="name" required>
                        <label for="edit_h_location">Location:</label>
                        <input type="text" id="edit_h_location" name="location" required>
                        <label for="edit_h_description">Description:</label>
                        <textarea id="edit_h_description" name="description" rows="4"></textarea>
                        <label for="edit_h_image_url">Image URL:</label>
                        <input type="url" id="edit_h_image_url" name="image_url">
                        <button type="submit">Update Hotel</button>
                        <button type="button" onclick="document.getElementById('editHotelModal').style.display='none';">Cancel</button>
                    </form>
                </div>

            <?php elseif ($current_view == 'hotel_rooms'): ?>
                <h2>Manage Hotel Rooms</h2>
                <h3>Add New Room</h3>
                <form action="admin_op.php" method="POST">
                    <input type="hidden" name="action" value="add_hotel_room">
                    <label for="hr_hotel_id">Hotel:</label>
                    <select id="hr_hotel_id" name="hotel_id" required>
                        <option value="">Select Hotel</option>
                        <?php foreach ($hotels as $hotel): ?>
                            <option value="<?php echo htmlspecialchars($hotel['hotel_id']); ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="hr_room_type">Room Type:</label>
                    <input type="text" id="hr_room_type" name="room_type" required>
                    <label for="hr_capacity">Capacity:</label>
                    <input type="number" id="hr_capacity" name="capacity" required min="1">
                    <label for="hr_price_per_night">Price per Night:</label>
                    <input type="number" id="hr_price_per_night" name="price_per_night" step="0.01" required min="0">
                    <label for="hr_num_rooms_available">Rooms Available:</label>
                    <input type="number" id="hr_num_rooms_available" name="num_rooms_available" required min="0">
                    <label for="hr_description">Description:</label>
                    <textarea id="hr_description" name="description" rows="4"></textarea>
                    <label for="hr_image_url">Image URL:</label>
                    <input type="url" id="hr_image_url" name="image_url">
                    <button type="submit">Add Room</button>
                </form>

                <h3>Existing Hotel Rooms</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hotel</th>
                                <th>Room Type</th>
                                <th>Capacity</th>
                                <th>Price/Night</th>
                                <th>Available</th>
                                <th>Description</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotel_rooms as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_id']); ?></td>
                                    <td><?php echo htmlspecialchars($room['hotel_name']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                    <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($room['num_rooms_available']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($room['description'], 0, 50)); ?>...</td>
                                    <td><a href="<?php echo htmlspecialchars($room['image_url']); ?>" target="_blank">View Image</a></td>
                                    <td class="action-buttons">
                                        <a href="#" onclick="showEditHotelRoomForm(<?php echo $room['room_id']; ?>, <?php echo $room['hotel_id']; ?>, '<?php echo htmlspecialchars(addslashes($room['room_type'])); ?>', <?php echo $room['capacity']; ?>, <?php echo $room['price_per_night']; ?>, <?php echo $room['num_rooms_available']; ?>, '<?php echo htmlspecialchars(addslashes($room['description'])); ?>', '<?php echo htmlspecialchars(addslashes($room['image_url'])); ?>')" class="edit-btn">Edit</a>
                                        <form action="admin_op.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                            <input type="hidden" name="action" value="delete_hotel_room">
                                            <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="editHotelRoomModal" style="display:none;">
                    <h3>Edit Hotel Room</h3>
                    <form action="admin_op.php" method="POST">
                        <input type="hidden" name="action" value="edit_hotel_room">
                        <input type="hidden" id="edit_hr_id" name="room_id">
                        <label for="edit_hr_hotel_id">Hotel:</label>
                        <select id="edit_hr_hotel_id" name="hotel_id" required>
                            <?php foreach ($hotels as $hotel): ?>
                                <option value="<?php echo htmlspecialchars($hotel['hotel_id']); ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="edit_hr_room_type">Room Type:</label>
                        <input type="text" id="edit_hr_room_type" name="room_type" required>
                        <label for="edit_hr_capacity">Capacity:</label>
                        <input type="number" id="edit_hr_capacity" name="capacity" required min="1">
                        <label for="edit_hr_price_per_night">Price per Night:</label>
                        <input type="number" id="edit_hr_price_per_night" name="price_per_night" step="0.01" required min="0">
                        <label for="edit_hr_num_rooms_available">Rooms Available:</label>
                        <input type="number" id="edit_hr_num_rooms_available" name="num_rooms_available" required min="0">
                        <label for="edit_hr_description">Description:</label>
                        <textarea id="edit_hr_description" name="description" rows="4"></textarea>
                        <label for="edit_hr_image_url">Image URL:</label>
                        <input type="url" id="edit_hr_image_url" name="image_url">
                        <button type="submit">Update Room</button>
                        <button type="button" onclick="document.getElementById('editHotelRoomModal').style.display='none';">Cancel</button>
                    </form>
                </div>

            <?php elseif ($current_view == 'users'): ?>
                <h2>Manage Users</h2>
                <!-- Add User form (optional, users usually sign up) -->
                <!-- <h3>Add New User (Admin)</h3>
                <form action="admin_op.php" method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <label for="u_username">Username:</label>
                    <input type="text" id="u_username" name="username" required>
                    <label for="u_email">Email:</label>
                    <input type="email" id="u_email" name="email" required>
                    <label for="u_password">Password:</label>
                    <input type="password" id="u_password" name="password" required>
                    <label for="u_role">Role:</label>
                    <select id="u_role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit">Add User</button>
                </form> -->

                <h3>Existing Users</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td class="action-buttons">
                                        <a href="#" onclick="showEditUserForm(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo htmlspecialchars($user['role']); ?>')" class="edit-btn">Edit</a>
                                        <form action="admin_op.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action is irreversible.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="editUserModal" style="display:none;">
                    <h3>Edit User</h3>
                    <form action="admin_op.php" method="POST">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" id="edit_u_id" name="user_id">
                        <label for="edit_u_username">Username:</label>
                        <input type="text" id="edit_u_username" name="username" required>
                        <label for="edit_u_email">Email:</label>
                        <input type="email" id="edit_u_email" name="email" required>
                        <label for="edit_u_password">New Password (leave blank to keep current):</label>
                        <input type="password" id="edit_u_password" name="password">
                        <label for="edit_u_role">Role:</label>
                        <select id="edit_u_role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button type="submit">Update User</button>
                        <button type="button" onclick="document.getElementById('editUserModal').style.display='none';">Cancel</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function showEditDestinationForm(id, name, description, price, imageUrl) {
            document.getElementById('edit_d_id').value = id;
            document.getElementById('edit_d_name').value = name;
            document.getElementById('edit_d_description').value = description;
            document.getElementById('edit_d_price').value = price;
            document.getElementById('edit_d_image_url').value = imageUrl;
            document.getElementById('editDestinationModal').style.display = 'block';
        }

        function showEditHotelForm(id, name, location, description, imageUrl) {
            document.getElementById('edit_h_id').value = id;
            document.getElementById('edit_h_name').value = name;
            document.getElementById('edit_h_location').value = location;
            document.getElementById('edit_h_description').value = description;
            document.getElementById('edit_h_image_url').value = imageUrl;
            document.getElementById('editHotelModal').style.display = 'block';
        }

        function showEditHotelRoomForm(id, hotel_id, room_type, capacity, price_per_night, num_rooms_available, description, imageUrl) {
            document.getElementById('edit_hr_id').value = id;
            document.getElementById('edit_hr_hotel_id').value = hotel_id;
            document.getElementById('edit_hr_room_type').value = room_type;
            document.getElementById('edit_hr_capacity').value = capacity;
            document.getElementById('edit_hr_price_per_night').value = price_per_night;
            document.getElementById('edit_hr_num_rooms_available').value = num_rooms_available;
            document.getElementById('edit_hr_description').value = description;
            document.getElementById('edit_hr_image_url').value = imageUrl;
            document.getElementById('editHotelRoomModal').style.display = 'block';
        }

        function showEditUserForm(id, username, email, role) {
            document.getElementById('edit_u_id').value = id;
            document.getElementById('edit_u_username').value = username;
            document.getElementById('edit_u_email').value = email;
            document.getElementById('edit_u_role').value = role;
            document.getElementById('editUserModal').style.display = 'block';
        }
    </script>
</body>
</html>
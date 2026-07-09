<?php
session_start();
include 'db_connection.php';
include 'config.php';

$hotel_id = filter_input(INPUT_GET, 'hotel_id', FILTER_VALIDATE_INT);
$hotel = null;
$rooms = [];
$error = '';

if (!$hotel_id || $hotel_id <= 0) {
    $error = 'Invalid hotel selected.';
} else {
    // Fetch hotel details
    $stmt_hotel = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
    $stmt_hotel->bind_param("i", $hotel_id);
    $stmt_hotel->execute();
    $result_hotel = $stmt_hotel->get_result();
    if ($result_hotel->num_rows > 0) {
        $hotel = $result_hotel->fetch_assoc();
    } else {
        $error = 'Hotel not found.';
    }
    $stmt_hotel->close();

    // Fetch available room types for the hotel
    if ($hotel) {
        $stmt_rooms = $conn->prepare("SELECT * FROM hotel_rooms WHERE hotel_id = ? AND num_rooms_available > 0 ORDER BY price_per_night ASC");
        $stmt_rooms->bind_param("i", $hotel_id);
        $stmt_rooms->execute();
        $result_rooms = $stmt_rooms->get_result();
        while ($row = $result_rooms->fetch_assoc()) {
            $rooms[] = $row;
        }
        $stmt_rooms->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hotel ? htmlspecialchars($hotel['name']) : 'Hotel Details'; ?> - Traveler</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hotel.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'mainPage.php'; // Include navigation/header ?>

    <main class="container">
        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
            <a href="hotel.php" class="btn-details">Back to Hotels</a>
        <?php elseif ($hotel): ?>
            <div class="hotel-details-header">
                <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                <img src="<?php echo htmlspecialchars($hotel['image_url'] ?? 'https://via.placeholder.com/800x400?text=Hotel+Image'); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-main-image">
                <p class="location">Location: <?php echo htmlspecialchars($hotel['location']); ?></p>
            </div>

            <div class="hotel-info">
                <div class="hotel-description">
                    <h3>About This Hotel</h3>
                    <p><?php echo nl2br(htmlspecialchars($hotel['description'])); ?></p>
                </div>
                <div class="room-types">
                    <h3>Available Room Types</h3>
                    <?php if (!empty($rooms)): ?>
                        <?php foreach ($rooms as $room): ?>
                            <div class="room-type-card">
                                <h4><?php echo htmlspecialchars($room['room_type']); ?> (Capacity: <?php echo htmlspecialchars($room['capacity']); ?>)</h4>
                                <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                                <p class="price">Price per night: $<?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></p>
                                <p class="availability">
                                    <?php if ($room['num_rooms_available'] > 0): ?>
                                        <?php echo htmlspecialchars($room['num_rooms_available']); ?> rooms available
                                    <?php else: ?>
                                        <span class="unavailable">Sold Out</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No rooms currently available for this hotel.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($rooms) && isset($_SESSION['user_id'])): // Only show booking form if rooms available and user logged in ?>
                <div class="booking-form-section">
                    <h3>Book Your Stay</h3>
                    <form action="process_hotel_booking.php" method="POST" class="booking-form">
                        <input type="hidden" name="hotel_id" value="<?php echo htmlspecialchars($hotel['hotel_id']); ?>">
                        
                        <label for="check_in_date">Check-in Date:</label>
                        <input type="date" id="check_in_date" name="check_in_date" required min="<?php echo date('Y-m-d'); ?>">

                        <label for="check_out_date">Check-out Date:</label>
                        <input type="date" id="check_out_date" name="check_out_date" required>

                        <label for="room_id">Select Room Type:</label>
                        <select id="room_id" name="room_id" required>
                            <option value="">-- Select a Room Type --</option>
                            <?php foreach ($rooms as $room): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($room['room_id']); ?>"
                                    data-price="<?php echo htmlspecialchars($room['price_per_night']); ?>"
                                    data-available-rooms="<?php echo htmlspecialchars($room['num_rooms_available']); ?>"
                                >
                                    <?php echo htmlspecialchars($room['room_type']); ?> (Max <?php echo htmlspecialchars($room['capacity']); ?> guests, $<?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?>/night, <?php echo htmlspecialchars($room['num_rooms_available']); ?> available)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="num_rooms">Number of Rooms:</label>
                        <input type="number" id="num_rooms" name="num_rooms" value="1" min="1" required>

                        <label for="num_guests">Number of Guests:</label>
                        <input type="number" id="num_guests" name="num_guests" value="1" min="1" required>

                        <label for="meal_plan">Meal Plan:</label>
                        <select id="meal_plan" name="meal_plan">
                            <option value="none">No Meal Plan</option>
                            <option value="breakfast">Breakfast Only</option>
                            <option value="half_board">Half Board (Breakfast & Dinner)</option>
                            <option value="full_board">Full Board (Breakfast, Lunch & Dinner)</option>
                        </select>

                        <div class="booking-total-price">
                            Total Price: $<span id="display_total_price">0.00</span>
                        </div>

                        <button type="submit">Proceed to Payment</button>
                    </form>
                </div>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <p class="error-message">Please <a href="signin.php">sign in</a> to book a hotel.</p>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <?php include 'footer.php'; // Assuming you have a footer.php ?>

    <script>
        $(document).ready(function() {
            const checkInDateInput = $('#check_in_date');
            const checkOutDateInput = $('#check_out_date');
            const roomIdInput = $('#room_id');
            const numRoomsInput = $('#num_rooms');
            const numGuestsInput = $('#num_guests');
            const displayTotalPrice = $('#display_total_price');

            function calculateTotalPrice() {
                const checkInDate = new Date(checkInDateInput.val());
                const checkOutDate = new Date(checkOutDateInput.val());
                const selectedRoomOption = roomIdInput.find(':selected');
                const pricePerNight = parseFloat(selectedRoomOption.data('price')) || 0;
                const numRooms = parseInt(numRoomsInput.val()) || 1;

                if (isNaN(checkInDate.getTime()) || isNaN(checkOutDate.getTime()) || checkOutDate <= checkInDate) {
                    displayTotalPrice.text('0.00');
                    return;
                }

                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

                if (nights <= 0) {
                    displayTotalPrice.text('0.00');
                    return;
                }

                let total = pricePerNight * numRooms * nights;
                displayTotalPrice.text(total.toFixed(2));
            }

            function validateBookingDates() {
                const checkInDate = new Date(checkInDateInput.val());
                const checkOutDate = new Date(checkOutDateInput.val());
                const today = new Date();
                today.setHours(0,0,0,0);

                checkOutDateInput.attr('min', checkInDateInput.val());

                if (checkInDate < today) {
                    alert('Check-in date cannot be in the past.');
                    checkInDateInput.val('');
                    return false;
                }

                if (checkOutDate <= checkInDate) {
                    alert('Check-out date must be after check-in date.');
                    checkOutDateInput.val('');
                    return false;
                }
                return true;
            }

            function validateRoomAvailability() {
                const selectedRoomOption = roomIdInput.find(':selected');
                const availableRooms = parseInt(selectedRoomOption.data('available-rooms')) || 0;
                const requestedRooms = parseInt(numRoomsInput.val()) || 1;

                if (requestedRooms > availableRooms) {
                    alert(`Only ${availableRooms} rooms of this type are available.`);
                    numRoomsInput.val(availableRooms > 0 ? availableRooms : 1);
                    return false;
                }
                if (requestedRooms <= 0) {
                    numRoomsInput.val(1);
                    return false;
                }
                return true;
            }

            function validateGuestCapacity() {
                const selectedRoomOption = roomIdInput.find(':selected');
                const roomCapacity = parseInt(selectedRoomOption.data('capacity')) || 0; // Assuming capacity is stored in data-capacity
                const numRooms = parseInt(numRoomsInput.val()) || 1;
                const numGuests = parseInt(numGuestsInput.val()) || 1;

                const maxGuestsForSelection = roomCapacity * numRooms;

                if (numGuests > maxGuestsForSelection) {
                    alert(`Maximum guests for ${numRooms} rooms of this type is ${maxGuestsForSelection}.`);
                    numGuestsInput.val(maxGuestsForSelection > 0 ? maxGuestsForSelection : 1);
                    return false;
                }
                 if (numGuests <= 0) {
                    numGuestsInput.val(1);
                    return false;
                }
                return true;
            }

            // Event Listeners
            checkInDateInput.on('change', function() {
                validateBookingDates();
                calculateTotalPrice();
            });
            checkOutDateInput.on('change', function() {
                validateBookingDates();
                calculateTotalPrice();
            });
            roomIdInput.on('change', function() {
                numRoomsInput.attr('max', $(this).find(':selected').data('available-rooms'));
                numRoomsInput.val(1); // Reset rooms to 1 when room type changes
                numGuestsInput.val(1); // Reset guests to 1
                validateRoomAvailability();
                calculateTotalPrice();
            });
            numRoomsInput.on('change keyup', function() {
                validateRoomAvailability();
                validateGuestCapacity();
                calculateTotalPrice();
            });
            numGuestsInput.on('change keyup', function() {
                validateGuestCapacity();
            });

            // Initial calculations on page load
            if (roomIdInput.val()) {
                 numRoomsInput.attr('max', roomIdInput.find(':selected').data('available-rooms'));
            }
            calculateTotalPrice();

            // Form submission validation
            $('.booking-form').on('submit', function() {
                if (!validateBookingDates() || !validateRoomAvailability() || !validateGuestCapacity()) {
                    return false;
                }
                return confirm('Confirm your hotel booking?');
            });
        });
    </script>
</body>
</html>

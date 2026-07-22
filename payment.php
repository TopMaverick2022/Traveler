<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'db_connection.php';       // Fix for Issue 1: Use require_once for critical dependencies to ensure they are loaded and prevent re-inclusion.
require_once 'config.php';              // Fix for Issue 1: Use require_once for critical dependencies to ensure they are loaded and prevent re-inclusion.

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
// Fix for Issue 1: Retrieve sensitive API keys from environment variables for enhanced security and ease of deployment management.
Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
$hotel_booking_id = filter_input(INPUT_GET, 'hotel_booking_id', FILTER_VALIDATE_INT);

$total_amount_cents = 0;
$booking_type = '';
$item_name = '';
$booking_identifier = null;

// Fix for Issue 4: Refactored duplicate logic for fetching booking details into a more generic approach to reduce redundancy.
// Common variables for DB interaction
$stmt = null;
$sql = null;
$id_param = null;
$error_msg_prefix = '';
$item_name_base = '';
$bind_types = '';

// Define common variables for bind_param, using constants for status
// Assumes BOOKING_STATUS_PENDING is defined in config.php (Fix for Issue 5)
// Fix for Issue 1: Removed conditional constant definition. BOOKING_STATUS_PENDING should be defined once in 'config.php'.
$user_id = $_SESSION['user_id'];
$pending_status = BOOKING_STATUS_PENDING;

// Determine booking type and setup dynamic query parts
if ($booking_id && $booking_id > 0) {
    // Fix for Issue 2: Removed conditional constant definition. BOOKING_TYPE_DESTINATION should be defined once in 'config.php'.
    // Destination booking
    $booking_type = BOOKING_TYPE_DESTINATION; // Fix for Issue 7: Use constant for booking type, ideally defined in config.php.
    $booking_identifier = $booking_id;
    $id_param = $booking_id;
    $error_msg_prefix = 'Destination';
    // Alias destination.name as item_display_name for consistency in fetching
    $sql = "SELECT b.total_price, d.name AS item_display_name FROM booking b JOIN destination d ON b.destination_id = d.destination_id WHERE b.booking_id = ? AND b.user_id = ? AND b.status = ?";
    $bind_types = "iis"; // int, int, string
    $item_name_base = 'Destination Booking: ';

} elseif ($hotel_booking_id && $hotel_booking_id > 0) {
    // Fix for Issue 3: Removed conditional constant definition. BOOKING_TYPE_HOTEL should be defined once in 'config.php'.
    // Hotel booking
    $booking_type = BOOKING_TYPE_HOTEL; // Fix for Issue 7: Use constant for booking type, ideally defined in config.php.
    $booking_identifier = $hotel_booking_id;
    $id_param = $hotel_booking_id;
    $error_msg_prefix = 'Hotel';
    // Alias hotel.name as item_display_name for consistency in fetching
    $sql = "SELECT hb.total_price, h.name AS item_display_name, hr.room_type FROM hotel_bookings hb JOIN hotels h ON hb.hotel_id = h.hotel_id JOIN hotel_rooms hr ON hb.room_id = hr.room_id WHERE hb.hotel_booking_id = ? AND hb.user_id = ? AND hb.booking_status = ?";
    $bind_types = "iis"; // int, int, string
    $item_name_base = 'Hotel Booking: ';

} else {
    $_SESSION['error_message'] = 'No valid booking ID provided.';
    header('Location: index.php'); // Redirect to a suitable page
    exit();
}

// Common logic for query execution and result processing
if ($sql) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($bind_types, $id_param, $user_id, $pending_status); // Fix for Issue 5: Use constant for 'pending' status.
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        $total_amount_cents = round($booking_data['total_price'] * 100); // Stripe expects cents
        $item_name = $item_name_base . htmlspecialchars($booking_data['item_display_name']);
        if ($booking_type === BOOKING_TYPE_HOTEL && isset($booking_data['room_type'])) {
            $item_name .= ' - ' . htmlspecialchars($booking_data['room_type']);
        }
    } else {
        $_SESSION['error_message'] = $error_msg_prefix . ' booking not found or already processed.';
        header('Location: index.php'); // Redirect to a suitable page
        exit();
    }
    $stmt->close();
}

// Create a Checkout Session.
try {
    // Fix for Issue 4: Removed conditional constant definition. DEFAULT_CURRENCY should be defined once in 'config.php'.
    $checkout_session = Stripe\Checkout\Session::create([
        'line_items' => [[ // Use line_items for items being purchased
            'price_data' => [
                'currency' => DEFAULT_CURRENCY, // Fix for Issue 6: Use constant for currency, ideally defined in config.php.
                'product_data' => [
                    'name' => $item_name,
                ],
                'unit_amount' => $total_amount_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => BASE_URL . 'payment_success.php?session_id={CHECKOUT_SESSION_ID}', // Fix for Issue 2: Changed to 'payment_success.php' to clarify this is a user-facing page, not a backend processing endpoint, as definitive payment status should be handled by webhooks.
        'cancel_url' => BASE_URL . 'index.php?payment_cancelled=true',
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'booking_type' => $booking_type,
            ($booking_type == BOOKING_TYPE_DESTINATION ? 'booking_id' : 'hotel_booking_id') => $booking_identifier // Fix for Issue 7: Use constants for booking types.
        ],
    ]);

    // Store the session ID with the booking for later verification (optional, but good practice)
    // This can be done by adding a stripe_session_id column to booking/hotel_bookings tables
    // Or linking it later via the webhook.

} catch (Exception $e) {
    error_log('Stripe checkout session creation failed: ' . $e->getMessage()); // Fix for Issue 3: Log detailed error internally for debugging.
    $_SESSION['error_message'] = 'An unexpected error occurred. Please try again or contact support.'; // Fix for Issue 3: Provide a generic, user-friendly error message to prevent exposing sensitive internal details.
    header('Location: index.php'); // Redirect to an error page
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Traveler</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <?php include 'mainPage.php'; ?>

    <div class="payment-container">
        <h1>Complete Your Payment</h1>
        <p>You are about to pay for: <strong><?php echo htmlspecialchars($item_name); ?></strong></p>
        <p>Amount: <strong>$<?php echo htmlspecialchars(number_format($total_amount_cents / 100, 2)); ?></strong></p>
        <button id="checkout-button" class="stripe-button">Pay with Card</button>
        <p class="payment-note">You will be redirected to a secure Stripe page to complete your payment.</p>
    </div>

    <script type="text/javascript">
        // Create a Stripe client in 'payment.js' or directly here
        var stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        var checkoutButton = document.getElementById('checkout-button');

        checkoutButton.addEventListener('click', function() {
            // When the customer clicks on the button, redirect them to Checkout.
            stripe.redirectToCheckout({
                sessionId: '<?php echo $checkout_session->id; ?>'
            }).then(function (result) {
                if (result.error) {
                    // If `redirectToCheckout` fails due to a browser or network error,
                    // display the localized error message to your customer.
                    alert(result.error.message);
                }
            });
        });
    </script>

    <?php include 'footer.php'; // Assuming a footer.php exists ?>
</body>
</html>
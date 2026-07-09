<?php
session_start();
require_once 'vendor/autoload.php';
include 'db_connection.php';
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
$hotel_booking_id = filter_input(INPUT_GET, 'hotel_booking_id', FILTER_VALIDATE_INT);

$total_amount_cents = 0;
$booking_type = '';
$item_name = '';
$booking_identifier = null;

// Determine booking type and fetch details
if ($booking_id && $booking_id > 0) {
    // Destination booking
    $booking_type = 'destination';
    $booking_identifier = $booking_id;

    $stmt = $conn->prepare("SELECT b.total_price, d.name AS destination_name FROM booking b JOIN destination d ON b.destination_id = d.destination_id WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'pending'");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        $total_amount_cents = round($booking_data['total_price'] * 100); // Stripe expects cents
        $item_name = 'Destination Booking: ' . htmlspecialchars($booking_data['destination_name']);
    } else {
        $_SESSION['error_message'] = 'Destination booking not found or already processed.';
        header('Location: index.php'); // Redirect to a suitable page
        exit();
    }
    $stmt->close();
} elseif ($hotel_booking_id && $hotel_booking_id > 0) {
    // Hotel booking
    $booking_type = 'hotel';
    $booking_identifier = $hotel_booking_id;

    $stmt = $conn->prepare("SELECT hb.total_price, h.name AS hotel_name, hr.room_type FROM hotel_bookings hb JOIN hotels h ON hb.hotel_id = h.hotel_id JOIN hotel_rooms hr ON hb.room_id = hr.room_id WHERE hb.hotel_booking_id = ? AND hb.user_id = ? AND hb.booking_status = 'pending'");
    $stmt->bind_param("ii", $hotel_booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        $total_amount_cents = round($booking_data['total_price'] * 100); // Stripe expects cents
        $item_name = 'Hotel Booking: ' . htmlspecialchars($booking_data['hotel_name']) . ' - ' . htmlspecialchars($booking_data['room_type']);
    } else {
        $_SESSION['error_message'] = 'Hotel booking not found or already processed.';
        header('Location: index.php'); // Redirect to a suitable page
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = 'No valid booking ID provided.';
    header('Location: index.php'); // Redirect to a suitable page
    exit();
}

// Create a Checkout Session.
try {
    $checkout_session = Stripe\Checkout\Session::create([
        'line_items' => [[ // Use line_items for items being purchased
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $item_name,
                ],
                'unit_amount' => $total_amount_cents,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => BASE_URL . 'process_payment.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => BASE_URL . 'index.php?payment_cancelled=true',
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'booking_type' => $booking_type,
            ($booking_type == 'destination' ? 'booking_id' : 'hotel_booking_id') => $booking_identifier
        ],
    ]);

    // Store the session ID with the booking for later verification (optional, but good practice)
    // This can be done by adding a stripe_session_id column to booking/hotel_bookings tables
    // Or linking it later via the webhook.

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error creating Stripe checkout session: ' . $e->getMessage();
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

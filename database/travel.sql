-- Existing tables (assumed)
-- CREATE TABLE users (user_id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), email VARCHAR(255), password VARCHAR(255));
-- CREATE TABLE bookings (booking_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, destination_id INT, booking_date DATE, amount DECIMAL(10, 2), status VARCHAR(50));

-- Table for payments
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL, -- FK to users table (or a specific customers table if it exists)
    booking_id INT NOT NULL,  -- FK to bookings table
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'usd',
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- e.g., 'pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'
    transaction_id VARCHAR(255) UNIQUE, -- Stripe Charge ID (deprecated in favor of payment_intent_id for newer flows)
    payment_intent_id VARCHAR(255) UNIQUE NOT NULL, -- Stripe Payment Intent ID
    payment_method_id VARCHAR(255), -- Stripe Payment Method ID used
    payment_gateway VARCHAR(50) NOT NULL DEFAULT 'stripe',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Table for refund requests
CREATE TABLE IF NOT EXISTS refund_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL, -- FK to users table
    payment_id INT NOT NULL,  -- FK to payments table
    reason TEXT,              -- Reason for the refund request
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- e.g., 'pending', 'approved', 'rejected', 'refunded'
    refund_id VARCHAR(255) UNIQUE, -- Stripe Refund ID
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE
);

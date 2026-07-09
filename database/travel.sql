-- Create hotels table
CREATE TABLE IF NOT EXISTS hotels (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create hotel_rooms table
CREATE TABLE IF NOT EXISTS hotel_rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_type VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    num_rooms_available INT NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE
);

-- Create hotel_bookings table
CREATE TABLE IF NOT EXISTS hotel_bookings (
    hotel_booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    num_rooms INT NOT NULL,
    num_guests INT NOT NULL,
    meal_plan ENUM('none', 'breakfast', 'half_board', 'full_board') DEFAULT 'none',
    total_price DECIMAL(10, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE, -- Assuming a 'users' table exists
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES hotel_rooms(room_id) ON DELETE CASCADE
);

-- Modify payments table to support hotel bookings
ALTER TABLE payments
ADD COLUMN hotel_booking_id INT NULL,
MODIFY COLUMN booking_id INT NULL;

-- Add a check constraint to ensure at least one ID is present
-- This might need to be handled in application logic for older MySQL versions or for flexibility
-- For modern MySQL (8.0.16+), a CHECK constraint can be added:
-- ALTER TABLE payments
-- ADD CONSTRAINT chk_booking_type CHECK ( (booking_id IS NOT NULL AND hotel_booking_id IS NULL) OR (booking_id IS NULL AND hotel_booking_id IS NOT NULL) );

-- Ensure `users` table exists for foreign key references
-- If it does not exist, add a basic one for testing:
-- CREATE TABLE IF NOT EXISTS users (
--     user_id INT AUTO_INCREMENT PRIMARY KEY,
--     username VARCHAR(255) UNIQUE NOT NULL,
--     password VARCHAR(255) NOT NULL,
--     email VARCHAR(255) UNIQUE NOT NULL,
--     role ENUM('user', 'admin') DEFAULT 'user',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- Example Data for Hotels (for testing)
INSERT INTO hotels (name, location, description, image_url) VALUES
('Grand Plaza Hotel', 'New York City, USA', 'A luxurious hotel in the heart of Manhattan.', 'https://example.com/grand_plaza.jpg'),
('Seaside Resort & Spa', 'Malibu, USA', 'Relaxing resort with stunning ocean views.', 'https://example.com/seaside_resort.jpg'),
('Mountain View Lodge', 'Aspen, USA', 'Cozy lodge perfect for winter sports.', 'https://example.com/mountain_lodge.jpg');

-- Example Data for Hotel Rooms (for testing)
INSERT INTO hotel_rooms (hotel_id, room_type, capacity, price_per_night, num_rooms_available, description, image_url) VALUES
(1, 'Standard Room', 2, 150.00, 10, 'Comfortable standard room with city view.', 'https://example.com/standard_room.jpg'),
(1, 'Deluxe Suite', 4, 350.00, 3, 'Spacious deluxe suite with separate living area.', 'https://example.com/deluxe_suite.jpg'),
(2, 'Oceanfront King', 2, 250.00, 7, 'King bed room with direct ocean view.', 'https://example.com/oceanfront_king.jpg'),
(2, 'Family Suite', 5, 400.00, 2, 'Large suite perfect for families.', 'https://example.com/family_suite.jpg'),
(3, 'Economy Double', 2, 100.00, 15, 'Affordable double room near slopes.', 'https://example.com/economy_double.jpg'),
(3, 'Premium Chalet', 6, 600.00, 1, 'Private chalet with fireplace and mountain views.', 'https://example.com/premium_chalet.jpg');

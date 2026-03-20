-- ============================================================
-- Online Bus Ticket Booking System - Database Setup
-- ============================================================

CREATE DATABASE IF NOT EXISTS bus_booking;
USE bus_booking;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(15),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(100),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buses Table
CREATE TABLE IF NOT EXISTS buses (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    bus_name        VARCHAR(100) NOT NULL,
    from_city       VARCHAR(50)  NOT NULL,
    to_city         VARCHAR(50)  NOT NULL,
    travel_date     DATE         NOT NULL,
    total_seats     INT          NOT NULL,
    price_per_seat  DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seats Table
CREATE TABLE IF NOT EXISTS seats (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    bus_id      INT,
    seat_number INT,
    status      ENUM('available','booked') DEFAULT 'available',
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id                   INT PRIMARY KEY AUTO_INCREMENT,
    pnr                  VARCHAR(20) UNIQUE NOT NULL,
    user_id              INT,
    bus_id               INT,
    seat_numbers         TEXT NOT NULL,
    total_amount         DECIMAL(10,2) NOT NULL,
    payment_status       ENUM('pending','paid','failed') DEFAULT 'pending',
    razorpay_order_id    VARCHAR(100),
    razorpay_payment_id  VARCHAR(100),
    booking_date         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bus_id)  REFERENCES buses(id)
);

-- ============================================================
-- Sample Data
-- ============================================================

INSERT INTO buses (bus_name, from_city, to_city, travel_date, total_seats, price_per_seat) VALUES
('AC Sleeper - KPN Travels',    'Chennai', 'Bangalore',  '2026-03-12', 30, 200.00),
('Semi Sleeper - SRS Travels',  'Chennai', 'Coimbatore', '2026-03-12', 36, 150.00);

-- Seats for Bus 1 (30 seats)
INSERT INTO seats (bus_id, seat_number) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),
(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),
(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30);

-- Seats for Bus 2 (36 seats)
INSERT INTO seats (bus_id, seat_number) VALUES
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),
(2,11),(2,12),(2,13),(2,14),(2,15),(2,16),(2,17),(2,18),(2,19),(2,20),
(2,21),(2,22),(2,23),(2,24),(2,25),(2,26),(2,27),(2,28),(2,29),(2,30),
(2,31),(2,32),(2,33),(2,34),(2,35),(2,36);

-- Test User  (plain password for display/testing)
INSERT INTO users (username, email, password, phone) VALUES
('testuser', 'test@example.com', 'password', '9876543210');

-- Admin (plain password for display/testing)
INSERT INTO admins (username, password, email) VALUES
('admin', 'admin123', 'admin@busbooking.com');

-- ============================================================
-- Quick Reference - Show all passwords (plain text for testing)
-- ============================================================
-- SELECT username, email, password AS plain_password FROM users;
-- SELECT username, email, password AS plain_password FROM admins;

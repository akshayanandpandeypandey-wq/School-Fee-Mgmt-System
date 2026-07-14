/**
 * School Fee Management System - Database Schema
 * ==============================================
 * Execute this SQL script to create the database and table
 * with sample data for testing the application.
 * 
 * Database: school_fee_management
 * Table: fee_payments
 */

-- Create Database
CREATE DATABASE IF NOT EXISTS school_fee_management;

-- Use the database
USE school_fee_management;

-- Create fee_payments table
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    class VARCHAR(20) NOT NULL,
    roll_no INT NOT NULL,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_name (student_name),
    INDEX idx_class (class),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data
-- Record 1
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Aarav Sharma', '10-A', 1, 'Tuition', 5000.00, '2024-01-15', 'Bank Transfer', 'Paid', 'Payment received successfully');

-- Record 2
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Priya Patel', '9-B', 12, 'Sports', 1500.00, '2024-01-18', 'Cash', 'Paid', 'Sports fee for the quarter');

-- Record 3
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Rohan Kumar', '10-A', 5, 'Transport', 2000.00, '2024-01-20', 'UPI', 'Pending', 'Awaiting confirmation');

-- Record 4
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Diya Verma', '9-C', 8, 'Tuition', 5000.00, '2024-01-22', 'Cheque', 'Paid', 'Cheque #12345');

-- Record 5
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Arjun Singh', '10-B', 15, 'Exam', 800.00, '2024-01-25', 'Cash', 'Paid', 'Board exam fee');

-- Record 6
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Neha Gupta', '9-A', 3, 'Activity', 500.00, '2024-01-28', 'Card', 'Pending', 'Activity fee - pending payment');

-- Record 7
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Vikram Reddy', '10-C', 25, 'Uniform', 1200.00, '2024-02-01', 'Cash', 'Paid', 'Uniform and shoes');

-- Record 8
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Ishita Mishra', '9-B', 7, 'Tuition', 5000.00, '2024-02-05', 'Bank Transfer', 'Paid', '');

-- Record 9
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Aditya Nair', '10-A', 18, 'Sports', 1500.00, '2024-02-08', 'UPI', 'Paid', 'Cricket team registration');

-- Record 10
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
VALUES ('Sanya Bhat', '9-C', 22, 'Transport', 2000.00, '2024-02-12', 'Cash', 'Paid', 'Monthly transport fee');

-- Verify table creation
SHOW TABLES;

-- Display all records
SELECT * FROM fee_payments;

-- Display total collected
SELECT COUNT(*) as total_records, SUM(amount) as total_amount FROM fee_payments WHERE status = 'Paid';

-- Display pending payments
SELECT COUNT(*) as pending_count FROM fee_payments WHERE status = 'Pending';

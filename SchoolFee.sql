-- =====================================================
-- School Fee Management System Database
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS school_fee_management;
USE school_fee_management;

-- =====================================================
-- Create Fee Payments Table
-- =====================================================
CREATE TABLE IF NOT EXISTS fee_payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique payment ID',
    student_name VARCHAR(100) NOT NULL COMMENT 'Name of the student',
    class VARCHAR(50) NOT NULL COMMENT 'Class/Grade of the student',
    roll_no VARCHAR(50) NOT NULL COMMENT 'Roll number of the student',
    fee_type VARCHAR(100) NOT NULL COMMENT 'Type of fee (Tuition, Lab, etc)',
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Amount paid',
    payment_date DATE NOT NULL COMMENT 'Date of payment',
    payment_method VARCHAR(50) NOT NULL COMMENT 'Method of payment (Cash, Online, Cheque)',
    status VARCHAR(50) NOT NULL DEFAULT 'Completed' COMMENT 'Payment status',
    remarks TEXT COMMENT 'Additional remarks or notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
    
    -- Index for faster searches
    INDEX idx_student_name (student_name),
    INDEX idx_class (class),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Sample Records
-- =====================================================
INSERT INTO fee_payments (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) VALUES
('Aarav Singh', '10-A', '001', 'Tuition Fee', 5000.00, '2026-01-15', 'Online', 'Completed', 'Payment received via UPI'),
('Ananya Sharma', '10-A', '002', 'Lab Fee', 2500.00, '2026-01-16', 'Cash', 'Completed', 'Lab fee for Science stream'),
('Rohan Patel', '11-B', '015', 'Tuition Fee', 5500.00, '2026-01-17', 'Cheque', 'Completed', 'Cheque deposited successfully'),
('Priya Verma', '9-C', '045', 'Sports Fee', 1500.00, '2026-01-18', 'Online', 'Completed', 'Sports activities fee'),
('Arjun Kumar', '12-A', '089', 'Tuition Fee', 6000.00, '2026-01-19', 'Cash', 'Completed', 'Senior section fee'),
('Zaara Khan', '10-A', '003', 'Library Fee', 500.00, '2026-02-01', 'Online', 'Completed', 'Annual library membership'),
('Vikram Singh', '11-B', '016', 'Tuition Fee', 5500.00, '2026-02-05', 'Online', 'Completed', 'February payment received'),
('Sneha Gupta', '9-C', '046', 'Computer Fee', 2000.00, '2026-02-10', 'Cash', 'Completed', 'Computer lab access fee'),
('Ravi Sharma', '10-A', '004', 'Tuition Fee', 5000.00, '2026-02-15', 'Cash', 'Pending', 'Awaiting confirmation'),
('Neha Singh', '12-A', '090', 'Exam Fee', 1500.00, '2026-02-20', 'Online', 'Completed', 'Board exam registration fee');

-- =====================================================
-- Verify Table Creation
-- =====================================================
SELECT 'Database and tables created successfully!' as Status;

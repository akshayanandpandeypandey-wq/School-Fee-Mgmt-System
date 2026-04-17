<?php
/**
 * =====================================================
 * Delete Payment
 * =====================================================
 * Purpose: Handle deletion of fee payment records
 * Features: Secure deletion with prepared statements, confirmation
 * 
 * @author School Fee Management System
 * @version 1.0
 */

session_start();
require_once 'includes/db_connection.php';

// =====================================================
// Get and Validate Payment ID
// =====================================================
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($payment_id <= 0) {
    $_SESSION['error'] = "Invalid payment ID";
    header("Location: view_payments.php");
    exit();
}

// =====================================================
// Prepare DELETE Query with Prepared Statement
// ===================================================== 
// Using prepared statements to prevent SQL injection
$delete_sql = "DELETE FROM fee_payments WHERE payment_id = ?";
$stmt = $conn->prepare($delete_sql);

if ($stmt === false) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: view_payments.php");
    exit();
}

// Bind the payment ID parameter
$stmt->bind_param("i", $payment_id);

// =====================================================
// Execute Deletion
// =====================================================
if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        // Successfully deleted
        $_SESSION['success'] = "Payment deleted successfully!";
    } else {
        // No record found with that ID
        $_SESSION['error'] = "Payment not found";
    }
} else {
    // Deletion error
    $_SESSION['error'] = "Error deleting payment: " . $stmt->error;
}

// Close prepared statement
$stmt->close();

// Close database connection
$conn->close();

// =====================================================
// Redirect back to View Payments Page
// ===================================================== 
header("Location: view_payments.php");
exit();

?>

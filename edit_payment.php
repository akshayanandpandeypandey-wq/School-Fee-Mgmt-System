<?php

/**
 * =====================================================
 * Edit Payment Page
 * =====================================================
 * Purpose: Edit existing fee payment records
 * Features: Fetch payment data, validation, update with prepared statements
 * 
 * @author School Fee Management System
 * @version 1.0
 */

require_once 'includes/db_connection.php';

// =====================================================
// Initialize Variables
// =====================================================
$errors = array();
$success = false;
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form_data = array();

// =====================================================
// Validate Payment ID
// =====================================================
if ($payment_id <= 0) {
    die("Invalid payment ID. <a href='view_payments.php'>Go back</a>");
}

// =====================================================
// Fetch Payment Data for Editing
// ===================================================== 
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = "SELECT * FROM fee_payments WHERE payment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Payment not found. <a href='view_payments.php'>Go back</a>");
    }

    $form_data = $result->fetch_assoc();
    $stmt->close();
}

// =====================================================
// Handle Form Submission (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // =====================================================
    // Step 1: Retrieve and Sanitize Form Data
    // =====================================================
    $form_data = array(
        'student_name' => isset($_POST['student_name']) ? trim($_POST['student_name']) : '',
        'class' => isset($_POST['class']) ? trim($_POST['class']) : '',
        'roll_no' => isset($_POST['roll_no']) ? trim($_POST['roll_no']) : '',
        'fee_type' => isset($_POST['fee_type']) ? trim($_POST['fee_type']) : '',
        'amount' => isset($_POST['amount']) ? trim($_POST['amount']) : '',
        'payment_date' => isset($_POST['payment_date']) ? trim($_POST['payment_date']) : '',
        'payment_method' => isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '',
        'status' => isset($_POST['status']) ? trim($_POST['status']) : 'Completed',
        'remarks' => isset($_POST['remarks']) ? trim($_POST['remarks']) : ''
    );

    // =====================================================
    // Step 2: Validate Form Data
    // =====================================================

    // Validate Student Name
    if (empty($form_data['student_name'])) {
        $errors[] = "Student name is required";
    } elseif (strlen($form_data['student_name']) < 2) {
        $errors[] = "Student name must be at least 2 characters";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $form_data['student_name'])) {
        $errors[] = "Student name should only contain letters and spaces";
    }

    // Validate Class
    if (empty($form_data['class'])) {
        $errors[] = "Class is required";
    }

    // Validate Roll Number
    if (empty($form_data['roll_no'])) {
        $errors[] = "Roll number is required";
    }

    // Validate Fee Type
    if (empty($form_data['fee_type'])) {
        $errors[] = "Fee type is required";
    }

    // Validate Amount
    if (empty($form_data['amount'])) {
        $errors[] = "Amount is required";
    } elseif (!is_numeric($form_data['amount']) || $form_data['amount'] <= 0) {
        $errors[] = "Amount must be a valid positive number";
    }

    // Validate Payment Date
    if (empty($form_data['payment_date'])) {
        $errors[] = "Payment date is required";
    } elseif (strtotime($form_data['payment_date']) > strtotime(date('Y-m-d'))) {
        $errors[] = "Payment date cannot be in the future";
    }

    // Validate Payment Method
    if (empty($form_data['payment_method'])) {
        $errors[] = "Payment method is required";
    }

    // =====================================================
    // Step 3: Update Record If No Errors
    // =====================================================
    if (empty($errors)) {

        // Prepare UPDATE query with placeholders
        $update_sql = "UPDATE fee_payments 
                       SET student_name = ?, class = ?, roll_no = ?, fee_type = ?, 
                           amount = ?, payment_date = ?, payment_method = ?, status = ?, remarks = ?
                       WHERE payment_id = ?";

        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt === false) {
            $errors[] = "Database error: " . $conn->error;
        } else {

            $update_stmt->bind_param(
                "ssssdsissi", // ✅ 10 types
                $form_data['student_name'],   // s
                $form_data['class'],          // s
                $form_data['roll_no'],        // s
                $form_data['fee_type'],       // s
                $form_data['amount'],         // d
                $form_data['payment_date'],   // s
                $form_data['payment_method'], // s
                $form_data['status'],         // s
                $form_data['remarks'],        // s
                $payment_id                  // i
            );

            // Execute the prepared statement
            if ($update_stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "Error updating payment: " . $update_stmt->error;
            }

            $update_stmt->close();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payment - School Fee Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">

        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>🏫 School Fee Management System</h1>
                <p class="subtitle">Edit Fee Payment</p>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="add_payment.php" class="nav-link">Add Payment</a></li>
                <li><a href="view_payments.php" class="nav-link active">View Payments</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="form-container">

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✓ Payment updated successfully! Redirecting...
                    </div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'view_payments.php';
                        }, 2000);
                    </script>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>⚠ Errors found:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <form method="POST" class="form">
                    <h2 class="form-title">Update Payment Details</h2>

                    <!-- =====================================================
                         Row 1: Student Name, Class, Roll Number
                         ===================================================== -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_name">Student Name *</label>
                            <input type="text" id="student_name" name="student_name"
                                value="<?php echo htmlspecialchars($form_data['student_name']); ?>"
                                placeholder="Enter full name" required>
                        </div>

                        <div class="form-group">
                            <label for="class">Class *</label>
                            <input type="text" id="class" name="class"
                                value="<?php echo htmlspecialchars($form_data['class']); ?>"
                                placeholder="e.g., 10-A, 11-B" required>
                        </div>

                        <div class="form-group">
                            <label for="roll_no">Roll Number *</label>
                            <input type="text" id="roll_no" name="roll_no"
                                value="<?php echo htmlspecialchars($form_data['roll_no']); ?>"
                                placeholder="e.g., 001, 002" required>
                        </div>
                    </div>

                    <!-- =====================================================
                         Row 2: Fee Type, Amount, Payment Date
                         ===================================================== -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fee_type">Fee Type *</label>
                            <input type="text" id="fee_type" name="fee_type"
                                value="<?php echo htmlspecialchars($form_data['fee_type']); ?>"
                                placeholder="e.g., Tuition, Lab, Sports" required>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount (₹) *</label>
                            <input type="number" id="amount" name="amount" step="0.01"
                                value="<?php echo htmlspecialchars($form_data['amount']); ?>"
                                placeholder="0.00" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_date">Payment Date *</label>
                            <input type="date" id="payment_date" name="payment_date"
                                value="<?php echo htmlspecialchars($form_data['payment_date']); ?>" required>
                        </div>
                    </div>

                    <!-- =====================================================
                         Row 3: Payment Method, Status
                         ===================================================== -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_method">Payment Method *</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="">-- Select Method --</option>
                                <option value="Cash" <?php echo ($form_data['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="Online" <?php echo ($form_data['payment_method'] == 'Online') ? 'selected' : ''; ?>>Online Transfer</option>
                                <option value="Cheque" <?php echo ($form_data['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                <option value="Card" <?php echo ($form_data['payment_method'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="Completed" <?php echo ($form_data['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Pending" <?php echo ($form_data['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Failed" <?php echo ($form_data['status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                    </div>

                    <!-- =====================================================
                         Row 4: Remarks
                         ===================================================== -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="remarks">Remarks (Optional)</label>
                            <textarea id="remarks" name="remarks" rows="3"
                                placeholder="Add any additional notes or comments"><?php echo htmlspecialchars($form_data['remarks']); ?></textarea>
                        </div>
                    </div>

                    <!-- =====================================================
                         Form Buttons
                         ===================================================== -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Update Payment</button>
                        <a href="view_payments.php" class="btn btn-secondary">Cancel</a>
                    </div>

                    <!-- Required Fields Note -->
                    <p class="form-note">* Required fields</p>
                </form>

            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2026 School Fee Management System. All rights reserved.</p>
        </footer>

    </div>

    <script src="js/script.js"></script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>
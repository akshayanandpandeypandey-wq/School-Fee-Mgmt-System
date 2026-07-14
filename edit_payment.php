<<<<<<< HEAD
<?php
/**
 * Edit Fee Payment Page
 * =====================
 * Modifies transaction parameters of a fee payment record.
 */

// Include DB connection
include 'db_connection.php';

// Enforce auth
check_auth();

$page_title = "Edit Payment Transaction";
$success = false;
$error = '';
$payment_data = null;

// Get payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id > 0) {
    // Fetch details with student JOIN
    $stmt = $conn->prepare("
        SELECT p.*, s.student_name, s.class, s.roll_no 
        FROM fee_payments p
        JOIN students s ON p.student_id = s.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $payment_data = $result->fetch_assoc();
    } else {
        $error = "❌ Payment transaction log not found!";
    }
    $stmt->close();
} else {
    $error = "❌ Invalid transaction ID!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $payment_data && !$error) {
    $fee_type = trim($_POST['fee_type'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_method = trim($_POST['payment_method'] ?? '');
    $status = trim($_POST['status'] ?? 'Paid');
    $remarks = trim($_POST['remarks'] ?? '');

    // Validation
    if (empty($fee_type) || empty($amount) || empty($payment_date) || empty($payment_method) || empty($status)) {
        $error = "❌ All fields marked with * are required!";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "❌ Amount must be a positive decimal value!";
    } else {
        // Update query
        $up_stmt = $conn->prepare("
            UPDATE fee_payments 
            SET fee_type = ?, amount = ?, payment_date = ?, payment_method = ?, status = ?, remarks = ?
            WHERE id = ?
        ");
        $up_stmt->bind_param("sdssssi", $fee_type, $amount, $payment_date, $payment_method, $status, $remarks, $payment_id);
        
        if ($up_stmt->execute()) {
            $_SESSION['flash_success'] = "🎉 Payment transaction updated successfully!";
            header("Location: view_payments.php");
            exit;
        } else {
            $error = "❌ Database update failed: " . $up_stmt->error;
        }
        $up_stmt->close();
    }
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Edit Payment Transaction</h2>
        <p>Correct transaction values, change status flags, or add auditor remarks to this receipt.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="view_payments.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Transaction Ledger</a>
    </div>
</div>

<div class="form-outer-wrapper">
    <div class="form-container glass-card">
        <h3><i class="fa-solid fa-pen-to-square text-warning"></i> Modify Transaction details</h3>
        
        <?php if ($error && !$payment_data): ?>
            <div class="alert alert-error">
                <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                <span class="alert-message"><?php echo $error; ?></span>
            </div>
            <a href="view_payments.php" class="btn btn-secondary">← Back to Ledger</a>
        <?php else: ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <span class="alert-message"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-modern">
                <!-- Read-only Student Info Header Banner -->
                <div class="student-profile-banner glass-card" style="margin-bottom: 25px; padding: 15px; border-left: 4px solid var(--primary-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size: 13px; color: var(--text-muted); text-transform: uppercase;">Payment Logged For</span>
                            <h4 style="margin: 3px 0 0 0; font-size: 18px; color: var(--text-color);"><?php echo htmlspecialchars($payment_data['student_name']); ?></h4>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge-class">Class <?php echo htmlspecialchars($payment_data['class']); ?></span>
                            <span class="badge-class">Roll #<?php echo htmlspecialchars($payment_data['roll_no']); ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 10px; font-size: 13px; color: var(--text-muted);">
                        <span>Receipt ID Reference: <strong><?php echo htmlspecialchars($payment_data['receipt_no']); ?></strong></span>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Fee Category -->
                    <div class="form-group-modern">
                        <label for="fee_type">Fee Category <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-list"></i>
                            <select id="fee_type" name="fee_type" class="select-modern" required>
                                <option value="Tuition" <?php echo ($payment_data['fee_type'] == 'Tuition') ? 'selected' : ''; ?>>Tuition</option>
                                <option value="Sports" <?php echo ($payment_data['fee_type'] == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                <option value="Transport" <?php echo ($payment_data['fee_type'] == 'Transport') ? 'selected' : ''; ?>>Transport</option>
                                <option value="Exam" <?php echo ($payment_data['fee_type'] == 'Exam') ? 'selected' : ''; ?>>Exam</option>
                                <option value="Activity" <?php echo ($payment_data['fee_type'] == 'Activity') ? 'selected' : ''; ?>>Activity</option>
                                <option value="Uniform" <?php echo ($payment_data['fee_type'] == 'Uniform') ? 'selected' : ''; ?>>Uniform</option>
                                <option value="Library" <?php echo ($payment_data['fee_type'] == 'Library') ? 'selected' : ''; ?>>Library</option>
                                <option value="Other" <?php echo ($payment_data['fee_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Amount -->
                    <div class="form-group-modern">
                        <label for="amount">Payment Amount <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-coins"></i>
                            <input type="number" id="amount" name="amount" placeholder="0.00" value="<?php echo htmlspecialchars($payment_data['amount']); ?>" step="0.01" min="0.01" required>
                        </div>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Payment Date -->
                    <div class="form-group-modern">
                        <label for="payment_date">Payment Date <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-calendar-day"></i>
                            <input type="date" id="payment_date" name="payment_date" value="<?php echo htmlspecialchars($payment_data['payment_date']); ?>" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group-modern">
                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-credit-card"></i>
                            <select id="payment_method" name="payment_method" class="select-modern" required>
                                <option value="Cash" <?php echo ($payment_data['payment_method'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="UPI" <?php echo ($payment_data['payment_method'] == 'UPI') ? 'selected' : ''; ?>>UPI / NetBanking</option>
                                <option value="Cheque" <?php echo ($payment_data['payment_method'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                <option value="Bank Transfer" <?php echo ($payment_data['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="Card" <?php echo ($payment_data['payment_method'] == 'Card') ? 'selected' : ''; ?>>Credit/Debit Card</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Status -->
                    <div class="form-group-modern">
                        <label for="status">Payment Status <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-circle-question"></i>
                            <select id="status" name="status" class="select-modern" required>
                                <option value="Paid" <?php echo ($payment_data['status'] == 'Paid') ? 'selected' : ''; ?>>Paid (Receipt Issued)</option>
                                <option value="Pending" <?php echo ($payment_data['status'] == 'Pending') ? 'selected' : ''; ?>>Pending (Awaiting clearance)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="form-group-modern">
                    <label for="remarks">Remarks / Notes</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-comment-dots" style="top: 18px; transform: none;"></i>
                        <textarea id="remarks" name="remarks" placeholder="Enter cheque details or bank transfer ID..." rows="3"><?php echo htmlspecialchars($payment_data['remarks']); ?></textarea>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                    <a href="view_payments.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
// Include Footer
include 'footer.php';
?>
=======
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
>>>>>>> ed2fd208afdb1b4dfed28115495e0d4942f7fb87

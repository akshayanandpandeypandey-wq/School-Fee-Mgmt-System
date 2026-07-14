<<<<<<< HEAD
<?php
/**
 * Add Fee Payment Transaction Page
 * ================================
 * Logs fee collections against registered student profiles.
 * Features auto-suggest fields for student classes and roll numbers.
 */

// Include DB connection
include 'db_connection.php';

// Enforce auth
check_auth();

$page_title = "Log Fee Payment";
$success = false;
$error = '';

// Check if student_id is passed in URL
$preset_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

$student_id = $preset_student_id;
$fee_type = '';
$amount = '';
$payment_date = date('Y-m-d');
$payment_method = '';
$status = 'Paid';
$remarks = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $fee_type = trim($_POST['fee_type'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $payment_date = $_POST['payment_date'] ?? '';
    $payment_method = trim($_POST['payment_method'] ?? '');
    $status = trim($_POST['status'] ?? 'Paid');
    $remarks = trim($_POST['remarks'] ?? '');

    // Validation
    if (empty($student_id) || empty($fee_type) || empty($amount) || empty($payment_date) || empty($payment_method) || empty($status)) {
        $error = "❌ All fields marked with * are required!";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "❌ Amount must be a positive decimal number!";
    } elseif ($student_id <= 0) {
        $error = "❌ Please select a valid student from the directory list!";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First insert with empty receipt_no, we will update it using the insert ID
            $stmt = $conn->prepare("
                INSERT INTO fee_payments (student_id, fee_type, amount, payment_date, payment_method, status, remarks, receipt_no) 
                VALUES (?, ?, ?, ?, ?, ?, ?, '')
            ");
            
            $stmt->bind_param("issssss", $student_id, $fee_type, $amount, $payment_date, $payment_method, $status, $remarks);
            
            if ($stmt->execute()) {
                $payment_id = $conn->insert_id;
                $stmt->close();
                
                // Generate secure unique receipt number
                $receipt_no = "REC-" . date('Ymd', strtotime($payment_date)) . "-" . str_pad($payment_id, 4, '0', STR_PAD_LEFT);
                
                // Update receipt_no
                $up_stmt = $conn->prepare("UPDATE fee_payments SET receipt_no = ? WHERE id = ?");
                $up_stmt->bind_param("si", $receipt_no, $payment_id);
                $up_stmt->execute();
                $up_stmt->close();
                
                $conn->commit();
                $_SESSION['flash_success'] = "🎉 Fee payment of " . format_amount($amount) . " logged successfully! Receipt # is $receipt_no";
                
                // Redirect to receipt
                header("Location: receipt.php?id=" . $payment_id);
                exit;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "❌ Transaction failed: " . $e->getMessage();
        }
    }
}

// Fetch active student directory for dropdown
$students_stmt = $conn->prepare("SELECT id, student_name, class, roll_no FROM students WHERE status = 'Active' ORDER BY student_name ASC");
$students_stmt->execute();
$students_result = $students_stmt->get_result();

$students_list = [];
$js_students_map = []; // Map for JS autocompletion
while ($row = $students_result->fetch_assoc()) {
    $students_list[] = $row;
    $js_students_map[$row['id']] = [
        'name' => $row['student_name'],
        'class' => $row['class'],
        'roll' => $row['roll_no']
    ];
}
$students_stmt->close();

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Log Fee Payment</h2>
        <p>Log a fee payment transaction, issue receipts, and audit balances for active students.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="view_payments.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Transaction Ledger</a>
    </div>
</div>

<div class="form-outer-wrapper">
    <div class="form-container glass-card">
        <h3><i class="fa-solid fa-cash-register text-success"></i> Payment Ledger Form</h3>
        <p class="form-intro-text">Fill in the fields below. Required values are marked with <span class="required">*</span>.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                <span class="alert-message"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-modern">
            
            <div class="form-row-modern">
                <!-- Select Student -->
                <div class="form-group-modern">
                    <label for="student_id">Select Student <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-user-graduate"></i>
                        <select id="student_id" name="student_id" class="select-modern" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($students_list as $stud): ?>
                                <option value="<?php echo $stud['id']; ?>" <?php echo ($student_id == $stud['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($stud['student_name']) . " (Class " . htmlspecialchars($stud['class']) . ", Roll: " . htmlspecialchars($stud['roll_no']) . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Fee Category -->
                <div class="form-group-modern">
                    <label for="fee_type">Fee Category <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-list"></i>
                        <select id="fee_type" name="fee_type" class="select-modern" required>
                            <option value="">-- Choose Category --</option>
                            <option value="Tuition" <?php echo ($fee_type == 'Tuition') ? 'selected' : ''; ?>>Tuition</option>
                            <option value="Sports" <?php echo ($fee_type == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                            <option value="Transport" <?php echo ($fee_type == 'Transport') ? 'selected' : ''; ?>>Transport</option>
                            <option value="Exam" <?php echo ($fee_type == 'Exam') ? 'selected' : ''; ?>>Exam</option>
                            <option value="Activity" <?php echo ($fee_type == 'Activity') ? 'selected' : ''; ?>>Activity</option>
                            <option value="Uniform" <?php echo ($fee_type == 'Uniform') ? 'selected' : ''; ?>>Uniform</option>
                            <option value="Library" <?php echo ($fee_type == 'Library') ? 'selected' : ''; ?>>Library</option>
                            <option value="Other" <?php echo ($fee_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Auto-filled Metadata Info Boxes -->
            <div class="metadata-info-row" id="student-meta-box" style="display: none;">
                <div class="meta-box-item">
                    <span class="meta-box-label">Student Name</span>
                    <span class="meta-box-val" id="meta-name">-</span>
                </div>
                <div class="meta-box-item">
                    <span class="meta-box-label">Class Section</span>
                    <span class="meta-box-val" id="meta-class">-</span>
                </div>
                <div class="meta-box-item">
                    <span class="meta-box-label">Roll Number</span>
                    <span class="meta-box-val" id="meta-roll">-</span>
                </div>
            </div>

            <div class="form-row-modern">
                <!-- Amount -->
                <div class="form-group-modern">
                    <label for="amount">Payment Amount <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-coins"></i>
                        <input type="number" id="amount" name="amount" placeholder="0.00" value="<?php echo htmlspecialchars($amount); ?>" step="0.01" min="0.01" required>
                    </div>
                </div>

                <!-- Payment Date -->
                <div class="form-group-modern">
                    <label for="payment_date">Payment Date <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-calendar-day"></i>
                        <input type="date" id="payment_date" name="payment_date" value="<?php echo htmlspecialchars($payment_date); ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-row-modern">
                <!-- Payment Method -->
                <div class="form-group-modern">
                    <label for="payment_method">Payment Method <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-credit-card"></i>
                        <select id="payment_method" name="payment_method" class="select-modern" required>
                            <option value="">-- Choose Method --</option>
                            <option value="Cash" <?php echo ($payment_method == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="UPI" <?php echo ($payment_method == 'UPI') ? 'selected' : ''; ?>>UPI / NetBanking</option>
                            <option value="Cheque" <?php echo ($payment_method == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                            <option value="Bank Transfer" <?php echo ($payment_method == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Card" <?php echo ($payment_method == 'Card') ? 'selected' : ''; ?>>Credit/Debit Card</option>
                        </select>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group-modern">
                    <label for="status">Payment Status <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-circle-question"></i>
                        <select id="status" name="status" class="select-modern" required>
                            <option value="Paid" <?php echo ($status == 'Paid') ? 'selected' : ''; ?>>Paid (Receipt Issued)</option>
                            <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending (Awaiting clearance)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="form-group-modern">
                <label for="remarks">Remarks / Internal Notes</label>
                <div class="input-wrapper-modern">
                    <i class="fa-solid fa-comment-dots" style="top: 18px; transform: none;"></i>
                    <textarea id="remarks" name="remarks" placeholder="Enter cheque details, transaction reference, parent requests..." rows="3"><?php echo htmlspecialchars($remarks); ?></textarea>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="form-buttons-modern">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Log Transaction</button>
                <a href="view_payments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const studentSelect = document.getElementById("student_id");
        const studentMetaBox = document.getElementById("student-meta-box");
        const metaName = document.getElementById("meta-name");
        const metaClass = document.getElementById("meta-class");
        const metaRoll = document.getElementById("meta-roll");
        
        // Load student data map from PHP
        const studentsMap = <?php echo json_encode($js_students_map); ?>;
        
        function updateStudentMeta() {
            const studentId = studentSelect.value;
            if (studentId && studentsMap[studentId]) {
                const s = studentsMap[studentId];
                metaName.textContent = s.name;
                metaClass.textContent = "Class " + s.class;
                metaRoll.textContent = "Roll #" + s.roll;
                studentMetaBox.style.display = "flex";
            } else {
                studentMetaBox.style.display = "none";
            }
        }
        
        // Listen to change events
        studentSelect.addEventListener("change", updateStudentMeta);
        
        // Initial load check
        updateStudentMeta();
    });
</script>

<?php
// Include Footer
include 'footer.php';
=======
<?php

/**
 * =====================================================
 * Add Payment Page
 * =====================================================
 * Purpose: Form to add new fee payment records
 * Features: Input validation, error handling, success messages
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
$form_data = array();

// =====================================================
// Handle Form Submission
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
    // Step 3: Process Form If No Errors
    // =====================================================
    if (empty($errors)) {

        // Prepare SQL query with placeholders (Prepared Statements)
        $sql = "INSERT INTO fee_payments 
                (student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Create prepared statement
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $errors[] = "Database error: " . $conn->error;
        } else {

            // Bind parameters to prepared statement
            // Types: s = string, i = integer, d = double
            $stmt->bind_param(
                "ssssdsiss",  // 9 parameters
                $form_data['student_name'],   // s
                $form_data['class'],          // s
                $form_data['roll_no'],        // s
                $form_data['fee_type'],       // s
                $form_data['amount'],         // d
                $form_data['payment_date'],   // s
                $form_data['payment_method'], // s
                $form_data['status'],         // s
                $form_data['remarks']         // s
            );

            // Execute the prepared statement
            if ($stmt->execute()) {
                $success = true;
                $payment_id = $conn->insert_id;

                // Clear form data after successful submission
                $form_data = array(
                    'student_name' => '',
                    'class' => '',
                    'roll_no' => '',
                    'fee_type' => '',
                    'amount' => '',
                    'payment_date' => date('Y-m-d'),
                    'payment_method' => 'Cash',
                    'status' => 'Completed',
                    'remarks' => ''
                );
            } else {
                $errors[] = "Error inserting payment: " . $stmt->error;
            }

            // Close prepared statement
            $stmt->close();
        }
    }
}

// Set default values for form
if (empty($form_data)) {
    $form_data = array(
        'student_name' => '',
        'class' => '',
        'roll_no' => '',
        'fee_type' => '',
        'amount' => '',
        'payment_date' => date('Y-m-d'),
        'payment_method' => 'Cash',
        'status' => 'Completed',
        'remarks' => ''
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment - School Fee Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">

        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>🏫 School Fee Management System</h1>
                <p class="subtitle">Add New Fee Payment</p>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="navbar">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="add_payment.php" class="nav-link active">Add Payment</a></li>
                <li><a href="view_payments.php" class="nav-link">View Payments</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="form-container">

                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✓ Payment added successfully!
                        <a href="receipt.php?id=<?php echo $payment_id; ?>" class="link-underline">View Receipt</a>
                    </div>
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

                <!-- Form Start -->
                <form method="POST" class="form">
                    <h2 class="form-title">Enter Payment Details</h2>

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
                        <button type="submit" class="btn btn-primary">💾 Add Payment</button>
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
>>>>>>> ed2fd208afdb1b4dfed28115495e0d4942f7fb87
?>
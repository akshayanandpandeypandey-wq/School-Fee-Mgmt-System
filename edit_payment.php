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

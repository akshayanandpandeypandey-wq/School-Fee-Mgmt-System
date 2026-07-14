<?php
/**
 * Delete Payment Page
 * ===================
 * Confirms and executes removal of payment records.
 * Restricted to Administrator accounts only.
 */

// Include DB connection
include 'db_connection.php';

// Enforce Admin auth
check_auth('Admin');

$page_title = "Delete Payment";
$success = false;
$error = '';
$payment_data = null;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// Get payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id > 0) {
    // Fetch details with student details joined
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
        $error = "❌ Payment record not found!";
    }
    $stmt->close();
} else {
    $error = "❌ Invalid payment ID!";
}

if ($action === 'confirm' && $payment_id > 0 && $payment_data && !$error) {
    $del_stmt = $conn->prepare("DELETE FROM fee_payments WHERE id = ?");
    if ($del_stmt) {
        $del_stmt->bind_param("i", $payment_id);
        if ($del_stmt->execute()) {
            $_SESSION['flash_success'] = "🗑️ Payment receipt reference " . $payment_data['receipt_no'] . " deleted successfully.";
            header("Location: view_payments.php");
            exit;
        } else {
            $error = "❌ Error deleting payment: " . $del_stmt->error;
        }
        $del_stmt->close();
    } else {
        $error = "❌ Database query preparation error: " . $conn->error;
    }
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Delete Payment Transaction</h2>
        <p>Permanently remove fee payment transaction logs and billing ledger reference details.</p>
    </div>
</div>

<div class="form-outer-wrapper" style="max-width: 600px; margin: 40px auto;">
    <?php if ($error && !$payment_data): ?>
        <div class="alert alert-error">
            <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
            <span class="alert-message"><?php echo $error; ?></span>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="view_payments.php" class="btn btn-secondary">← Back to Ledger</a>
        </div>
    <?php else: ?>
        <div class="confirmation-card-modern glass-card border-danger">
            <div class="conf-title-row">
                <span class="conf-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h3>Confirm Deletion</h3>
            </div>
            
            <p class="confirmation-text">
                Are you sure you want to permanently delete payment receipt <strong><?php echo htmlspecialchars($payment_data['receipt_no']); ?></strong>?
                This transaction ledger removal is irreversible!
            </p>
            
            <div class="payment-summary">
                <table class="summary-table">
                    <tr>
                        <td><strong>Receipt # Reference:</strong></td>
                        <td><strong><?php echo htmlspecialchars($payment_data['receipt_no']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Student Name:</strong></td>
                        <td><?php echo htmlspecialchars($payment_data['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Class / Roll:</strong></td>
                        <td>Class <?php echo htmlspecialchars($payment_data['class']); ?> (Roll: <?php echo htmlspecialchars($payment_data['roll_no']); ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>Fee Category:</strong></td>
                        <td><span class="badge-fee-type"><?php echo htmlspecialchars($payment_data['fee_type']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td><strong class="text-danger"><?php echo format_amount($payment_data['amount']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Date Logged:</strong></td>
                        <td><?php echo date('d-M-Y', strtotime($payment_data['payment_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status Flag:</strong></td>
                        <td>
                            <span class="badge-status <?php echo (strtolower($payment_data['status']) === 'paid') ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $payment_data['status']; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="confirmation-buttons">
                <a href="delete_payment.php?id=<?php echo $payment_id; ?>&action=confirm" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Yes, Delete Log</a>
                <a href="view_payments.php" class="btn btn-secondary">✗ Cancel</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include Footer
include 'footer.php';
?>

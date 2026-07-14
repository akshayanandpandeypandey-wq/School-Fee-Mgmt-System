<?php
/**
 * Professional printable Payment Receipt Page
 * ============================================
 * Generates audit-ready printable fee invoice details using school profile settings.
 */

// Include DB connection
include 'db_connection.php';

// Enforce auth
check_auth();

$error = '';
$payment = null;

// Get receipt ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id > 0) {
    // Fetch details JOIN student
    $stmt = $conn->prepare("
        SELECT p.*, s.student_name, s.class, s.roll_no, s.email as student_email, s.parent_name
        FROM fee_payments p
        JOIN students s ON p.student_id = s.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $payment = $result->fetch_assoc();
    } else {
        $error = "❌ Payment record not found!";
    }
    $stmt->close();
} else {
    $error = "❌ Invalid transaction reference!";
}

// Fetch School Configuration settings
$school_name = get_setting('school_name', 'Greenwood International School');
$school_address = get_setting('school_address', '456 Education Blvd, Knowledge City');
$school_phone = get_setting('school_phone', '+91-9876543210');
$school_email = get_setting('school_email', 'contact@greenwood.edu');

$page_title = "Receipt " . ($payment ? htmlspecialchars($payment['receipt_no']) : '');

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block no-print">
    <div class="welcome-banner">
        <h2>Payment Receipt</h2>
        <p>Preview and print professional fee invoices or save them as PDF documents.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="view_payments.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Transaction Ledger</a>
        <?php if ($payment): ?>
            <button onclick="window.print()" class="btn-action-primary"><i class="fa-solid fa-print"></i> Print Receipt</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($error || !$payment): ?>
    <div class="alert alert-error no-print">
        <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
        <span class="alert-message"><?php echo $error; ?></span>
    </div>
<?php else: ?>

    <!-- Printable Receipt Container Wrapper -->
    <div class="receipt-outer-wrapper">
        <div class="receipt-invoice-card" id="printable-receipt">
            <!-- Branding Header -->
            <div class="invoice-header">
                <div class="invoice-school-details">
                    <div class="school-logo-invoice"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div>
                        <h2><?php echo htmlspecialchars($school_name); ?></h2>
                        <p class="school-sub-meta"><?php echo htmlspecialchars($school_address); ?></p>
                        <p class="school-sub-meta">Phone: <?php echo htmlspecialchars($school_phone); ?> | Email: <?php echo htmlspecialchars($school_email); ?></p>
                    </div>
                </div>
                <div class="invoice-title-block">
                    <h1>FEE RECEIPT</h1>
                    <div class="receipt-ref-badge">
                        <span>RECEIPT NO</span>
                        <strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="invoice-divider"></div>
            
            <!-- Metadata Row: Dates and Account info -->
            <div class="invoice-meta-row">
                <div class="meta-block">
                    <span class="meta-block-label">Date of Issue:</span>
                    <strong class="meta-block-val"><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></strong>
                </div>
                <div class="meta-block">
                    <span class="meta-block-label">Payment Method:</span>
                    <strong class="meta-block-val"><?php echo htmlspecialchars($payment['payment_method']); ?></strong>
                </div>
                <div class="meta-block">
                    <span class="meta-block-label">Status Flag:</span>
                    <span class="badge-status <?php echo (strtolower($payment['status']) === 'paid') ? 'badge-success' : 'badge-warning'; ?> font-bold">
                        <?php echo htmlspecialchars($payment['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="invoice-divider"></div>
            
            <!-- Customer/Student details -->
            <div class="invoice-billing-row">
                <div class="billing-party">
                    <h3>BILL TO (STUDENT)</h3>
                    <p class="party-name"><?php echo htmlspecialchars($payment['student_name']); ?></p>
                    <p class="party-sub">Class Section: <strong>Class <?php echo htmlspecialchars($payment['class']); ?></strong></p>
                    <p class="party-sub">Roll Number: <strong>Roll #<?php echo htmlspecialchars($payment['roll_no']); ?></strong></p>
                    <p class="party-sub">Email: <?php echo htmlspecialchars($payment['student_email'] ?: 'N/A'); ?></p>
                </div>
                
                <div class="billing-party" style="text-align: right;">
                    <h3>SPONSOR / GUARDIAN</h3>
                    <p class="party-name"><?php echo htmlspecialchars($payment['parent_name'] ?: 'N/A'); ?></p>
                    <p class="party-sub">Parent details recorded</p>
                    <p class="party-sub">Receipt Generated: <?php echo date('d-M-Y H:i:s'); ?></p>
                </div>
            </div>
            
            <!-- Billing Details Grid Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 8%; text-align: center;">SL</th>
                        <th style="width: 60%; text-align: left;">FEE ALLOCATION DESCRIPTION</th>
                        <th style="width: 32%; text-align: right;">AMOUNT PAID</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: center;">01</td>
                        <td>
                            <strong><?php echo htmlspecialchars($payment['fee_type']); ?> Fee</strong>
                            <p class="item-description-sub">Academic term syllabus fees logs.</p>
                        </td>
                        <td style="text-align: right;" class="font-bold"><?php echo format_amount($payment['amount']); ?></td>
                    </tr>
                    <!-- Subtotal details -->
                    <tr class="subtotal-row">
                        <td colspan="2" style="text-align: right;">Subtotal:</td>
                        <td style="text-align: right;"><?php echo format_amount($payment['amount']); ?></td>
                    </tr>
                    <tr class="grand-total-row">
                        <td colspan="2" style="text-align: right;">Total Amount Received:</td>
                        <td style="text-align: right;" class="total-highlight-val"><?php echo format_amount($payment['amount']); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Internal remarks notes -->
            <?php if (!empty($payment['remarks'])): ?>
                <div class="invoice-remarks-box">
                    <strong>Auditor Remarks / Notes:</strong>
                    <p><?php echo htmlspecialchars($payment['remarks']); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Signatures validation block -->
            <div class="invoice-signatures-row">
                <div class="signature-block">
                    <div class="sig-line"></div>
                    <span>Depositor Signature</span>
                </div>
                <div class="signature-block" style="text-align: right;">
                    <div class="sig-line"></div>
                    <span>Authorized Cashier / Stamp</span>
                </div>
            </div>
            
            <div class="invoice-divider"></div>
            
            <!-- Footer Notes -->
            <div class="invoice-footer-notes">
                <p>Thank you for choosing Greenwood educational institutions!</p>
                <p class="disclaimer-sub">This is a system-generated financial transaction invoice. Valid without manual signature when printed with authorized institution header barcodes.</p>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
// Include Footer
include 'footer.php';
?>

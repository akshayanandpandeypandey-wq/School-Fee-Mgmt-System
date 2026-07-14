<<<<<<< HEAD
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
=======
<?php
/**
 * =====================================================
 * Receipt Page
 * =====================================================
 * Purpose: Display payment receipt with print functionality
 * Features: Professional receipt layout, print-friendly design, receipt generation
 * 
 * @author School Fee Management System
 * @version 1.0
 */

require_once 'includes/db_connection.php';

// =====================================================
// Get and Validate Payment ID
// =====================================================
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($payment_id <= 0) {
    die("Invalid payment ID. <a href='view_payments.php'>Go back</a>");
}

// =====================================================
// Fetch Payment Details
// =====================================================
$query = "SELECT * FROM fee_payments WHERE payment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Payment not found. <a href='view_payments.php'>Go back</a>");
}

$payment = $result->fetch_assoc();
$stmt->close();

// =====================================================
// Generate Receipt Number (for display)
// =====================================================
$receipt_number = "RCP-" . str_pad($payment['payment_id'], 6, "0", STR_PAD_LEFT);
$receipt_date = date('d-m-Y');
$receipt_time = date('H:i:s');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - School Fee Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* =====================================================
           Print Styles for Receipt
           ===================================================== */
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .receipt-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
            }
        }

        /* Receipt Container with Clean Design */
        .receipt-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 40px;
            background: white;
            border: 2px solid #2c3e50;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-family: 'Arial', sans-serif;
        }

        /* Receipt Header */
        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .receipt-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }

        .receipt-number {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }

        /* Receipt Info Section */
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .receipt-info-item label {
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-bottom: 3px;
        }

        .receipt-info-item span {
            color: #34495e;
        }

        /* Receipt Details Section */
        .receipt-details {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .receipt-details h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 14px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px solid #bdc3c7;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #7f8c8d;
            font-weight: 500;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: bold;
        }

        /* Amount Box */
        .amount-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 25px;
        }

        .amount-box h4 {
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: normal;
            opacity: 0.9;
        }

        .amount-box .amount {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }

        /* Status Section */
        .receipt-status {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 25px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-badge.completed {
            background: #2ecc71;
            color: white;
        }

        .status-badge.pending {
            background: #f39c12;
            color: white;
        }

        .status-badge.failed {
            background: #e74c3c;
            color: white;
        }

        /* Footer */
        .receipt-footer {
            border-top: 2px solid #ecf0f1;
            padding-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #7f8c8d;
        }

        .receipt-footer p {
            margin: 5px 0;
        }

        /* Action Buttons */
        .receipt-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn-print {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-print:hover {
            background: #2980b9;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Header (Not printed) -->
        <header class="header no-print">
            <div class="header-content">
                <h1>🏫 School Fee Management System</h1>
                <p class="subtitle">Payment Receipt</p>
            </div>
        </header>

        <!-- Navigation (Not printed) -->
        <nav class="navbar no-print">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="view_payments.php" class="nav-link active">View Payments</a></li>
            </ul>
        </nav>

        <!-- Main Receipt -->
        <main class="main-content">
            
            <!-- Receipt Container -->
            <div class="receipt-container">
                
                <!-- Receipt Header -->
                <div class="receipt-header">
                    <h1>📋 PAYMENT RECEIPT</h1>
                    <div class="receipt-number"><?php echo $receipt_number; ?></div>
                </div>

                <!-- Receipt Date & Time -->
                <div class="receipt-info">
                    <div class="receipt-info-item">
                        <label>Receipt Date:</label>
                        <span><?php echo $receipt_date; ?></span>
                    </div>
                    <div class="receipt-info-item">
                        <label>Receipt Time:</label>
                        <span><?php echo $receipt_time; ?></span>
                    </div>
                </div>

                <!-- Student & Payment Details -->
                <div class="receipt-details">
                    <h3>📚 Student Information</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Student Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment['student_name']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Class:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment['class']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Roll Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment['roll_no']); ?></span>
                    </div>
                </div>

                <!-- Fee Payment Details -->
                <div class="receipt-details">
                    <h3>💳 Payment Details</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Fee Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment['fee_type']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Payment Date:</span>
                        <span class="detail-value"><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                    </div>
                    
                    <?php if (!empty($payment['remarks'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Remarks:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['remarks']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Amount Box -->
                <div class="amount-box">
                    <h4>Total Amount Paid</h4>
                    <p class="amount">₹<?php echo number_format($payment['amount'], 2); ?></p>
                </div>

                <!-- Payment Status -->
                <div class="receipt-status">
                    <p style="margin: 0 0 10px 0; font-size: 12px; color: #7f8c8d;">Payment Status</p>
                    <span class="status-badge <?php echo strtolower($payment['status']); ?>">
                        <?php 
                        $status = strtoupper($payment['status']);
                        if ($status == 'COMPLETED') {
                            echo '✓ ' . $status;
                        } elseif ($status == 'PENDING') {
                            echo '⏳ ' . $status;
                        } else {
                            echo '✗ ' . $status;
                        }
                        ?>
                    </span>
                </div>

                <!-- Receipt Footer -->
                <div class="receipt-footer">
                    <p>Thank you for the payment!</p>
                    <p>School Fee Management System</p>
                    <p>Generated on <?php echo date('d-M-Y H:i:s'); ?></p>
                </div>
            </div>

            <!-- Action Buttons (Not printed) -->
            <div class="receipt-actions no-print">
                <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
                <a href="view_payments.php" class="btn-back">← Back to Payments</a>
            </div>

        </main>

        <!-- Footer (Not printed) -->
        <footer class="footer no-print">
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

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

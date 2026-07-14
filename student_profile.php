<?php
/**
 * Student Ledger Profile Page
 * ==========================
 * Displays detailed information about a student, class fee structure allocations,
 * transaction payment logs, and dynamic balance summaries.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authentication
check_auth();

$page_title = "Student Profile & Ledger";
$error = '';
$student = null;

// Get student ID
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id > 0) {
    // 1. Fetch student base information
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $error = "❌ Student record not found in system directory!";
    }
    $stmt->close();
} else {
    $error = "❌ Invalid student identifier specified!";
}

if ($student && !$error) {
    $student_class = $student['class'];
    
    // 2. Fetch fee structures allocated for this student's class
    $fs_stmt = $conn->prepare("SELECT * FROM fee_structures WHERE class = ? ORDER BY fee_type ASC");
    $fs_stmt->bind_param("s", $student_class);
    $fs_stmt->execute();
    $fs_result = $fs_stmt->get_result();
    
    $assigned_fees = [];
    $total_assigned_raw = 0;
    while ($fs_row = $fs_result->fetch_assoc()) {
        $assigned_fees[$fs_row['fee_type']] = $fs_row['amount'];
        $total_assigned_raw += $fs_row['amount'];
    }
    $discount_percent = intval($student['discount_percent']);
    $total_assigned = round($total_assigned_raw * (1 - $discount_percent / 100), 2);
    $fs_stmt->close();
    
    // 3. Fetch payment transaction history for this student
    $pay_stmt = $conn->prepare("SELECT * FROM fee_payments WHERE student_id = ? ORDER BY payment_date DESC, id DESC");
    $pay_stmt->bind_param("i", $student_id);
    $pay_stmt->execute();
    $pay_result = $pay_stmt->get_result();
    
    $payments = [];
    $total_paid = 0;
    while ($p_row = $pay_result->fetch_assoc()) {
        $payments[] = $p_row;
        if (strtolower($p_row['status']) === 'paid') {
            $total_paid += $p_row['amount'];
        }
    }
    $pay_stmt->close();
    
    $total_due = max(0, $total_assigned - $total_paid);
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Student Ledger & Profile</h2>
        <p>Comprehensive overview of student credentials, assigned class fees, and payment audits.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="students.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Student Directory</a>
        <?php if ($student): ?>
            <a href="add_payment.php?student_id=<?php echo $student['id']; ?>" class="btn-action-primary"><i class="fa-solid fa-cash-register"></i> Log Payment</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($error || !$student): ?>
    <div class="alert alert-error">
        <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
        <span class="alert-message"><?php echo $error; ?></span>
    </div>
<?php else: ?>

    <div class="profile-grid-container">
        <!-- 1. Profile Left Sidebar Info -->
        <div class="profile-left-column">
            <div class="profile-badge-card glass-card">
                <div class="profile-badge-avatar">
                    <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                </div>
                <h3 class="profile-badge-name"><?php echo htmlspecialchars($student['student_name']); ?></h3>
                <span class="profile-badge-class">Class <?php echo htmlspecialchars($student['class']); ?> (Roll: <?php echo htmlspecialchars($student['roll_no']); ?>)</span>
                
                <span class="badge-status <?php echo (strtolower($student['status']) === 'active') ? 'badge-success' : 'badge-danger'; ?> status-spacing">
                    <?php echo htmlspecialchars($student['status']); ?>
                </span>
                
                <div class="profile-details-list">
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-envelope detail-icon"></i>
                        <div>
                            <small>Student Email</small>
                            <p><?php echo htmlspecialchars($student['email'] ?: 'No email configured'); ?></p>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-people-roof detail-icon"></i>
                        <div>
                            <small>Guardian / Parent</small>
                            <p><?php echo htmlspecialchars($student['parent_name'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-phone detail-icon"></i>
                        <div>
                            <small>Parent Phone Contact</small>
                            <p><?php echo htmlspecialchars($student['parent_phone'] ?: 'N/A'); ?></p>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-percent detail-icon"></i>
                        <div>
                            <small>Scholarship Discount</small>
                            <p><?php echo $student['discount_percent'] > 0 ? htmlspecialchars($student['discount_percent']) . '%' : 'None (Full Fees)'; ?></p>
                        </div>
                    </div>
                    <div class="profile-detail-item">
                        <i class="fa-solid fa-calendar-days detail-icon"></i>
                        <div>
                            <small>Admission Date</small>
                            <p><?php echo date('d-M-Y', strtotime($student['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions-wrapper">
                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary btn-full"><i class="fa-solid fa-user-pen"></i> Edit Profile Information</a>
                </div>
            </div>
        </div>
        
        <!-- 2. Ledger Right Column Info -->
        <div class="profile-right-column">
            <!-- Balance Board Card -->
            <div class="balance-summary-row">
                <div class="balance-box glass-card border-primary-light">
                    <span class="balance-label">Total Fee Assigned</span>
                    <h4 class="balance-number"><?php echo format_amount($total_assigned); ?></h4>
                    <small>Standard class setup</small>
                </div>
                <div class="balance-box glass-card border-success-light">
                    <span class="balance-label">Total Fee Paid</span>
                    <h4 class="balance-number text-success"><?php echo format_amount($total_paid); ?></h4>
                    <small>Paid & confirmed receipts</small>
                </div>
                <div class="balance-box glass-card border-warning-light">
                    <span class="balance-label">Outstanding Balance</span>
                    <h4 class="balance-number text-danger"><?php echo format_amount($total_due); ?></h4>
                    <small>Due payments remaining</small>
                </div>
            </div>
            
            <!-- Fee Structures Details -->
            <div class="assigned-fees-card glass-card margin-spacing">
                <h4><i class="fa-solid fa-school-flag text-primary"></i> Class Fee Allocations (Class <?php echo htmlspecialchars($student['class']); ?>)</h4>
                <p class="section-intro-sub">Fees assigned automatically based on standard syllabus setups.</p>
                
                <?php if (count($assigned_fees) > 0): ?>
                    <div class="fees-breakdown-list">
                        <?php foreach ($assigned_fees as $type => $amt): 
                            // Check if student has paid this specific type
                            $is_type_paid = false;
                            foreach ($payments as $p) {
                                if (strtolower($p['fee_type']) === strtolower($type) && strtolower($p['status']) === 'paid') {
                                    $is_type_paid = true;
                                    break;
                                }
                            }
                        ?>
                            <div class="fee-breakdown-item">
                                <span class="fee-type-name"><i class="fa-solid fa-tag text-muted"></i> <?php echo htmlspecialchars($type); ?></span>
                                <span class="fee-type-amount"><?php echo format_amount($amt); ?></span>
                                <span class="badge-status <?php echo $is_type_paid ? 'badge-success' : 'badge-warning'; ?> badge-sm">
                                    <?php echo $is_type_paid ? 'Paid' : 'Unpaid'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-sub-panel">
                        <i class="fa-solid fa-triangle-exclamation warning-icon"></i>
                        <p>No standard fee structures defined for Class <strong><?php echo htmlspecialchars($student['class']); ?></strong>.</p>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <a href="fee_structures.php" class="btn btn-secondary btn-sm">Setup Standard Fees</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Transaction Ledger -->
            <div class="transaction-history-card glass-card">
                <h4><i class="fa-solid fa-clock-rotate-left text-success"></i> Transaction Logs & Ledgers</h4>
                
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Fee Type</th>
                                <th>Method</th>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="text-align: center;">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $pay): 
                                    $status_badge = (strtolower($pay['status']) === 'paid') ? 'badge-success' : 'badge-warning';
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($pay['receipt_no']); ?></strong></td>
                                        <td><span class="badge-fee-type"><?php echo htmlspecialchars($pay['fee_type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($pay['payment_date'])); ?></td>
                                        <td><strong><?php echo format_amount($pay['amount']); ?></strong></td>
                                        <td><span class="badge-status <?php echo $status_badge; ?>"><?php echo htmlspecialchars($pay['status']); ?></span></td>
                                        <td style="text-align: center;">
                                            <a href="receipt.php?id=<?php echo $pay['id']; ?>" class="btn-table-icon" title="View Receipt"><i class="fa-solid fa-file-invoice-dollar"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-records-cell">
                                        <i class="fa-solid fa-cash-register empty-icon"></i>
                                        <p>No fee payment logs registered for this scholar.</p>
                                        <a href="add_payment.php?student_id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">➕ Add First Payment</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
// Include Footer
include 'footer.php';
?>

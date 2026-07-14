<?php
/**
 * View Payments Ledger Page
 * ========================
 * Displays audit log of all fee payments with advanced filters,
 * dynamic search queries, and CSV exports.
 */

// Include DB connection
include 'db_connection.php';

// Enforce auth
check_auth();

$page_title = "Fee Payments Ledger";

// Fetch search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$fee_type_filter = isset($_GET['fee_type']) ? trim($_GET['fee_type']) : '';

// Build query conditions
$where_clause = "1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $search_term = "%{$search}%";
    $where_clause .= " AND (s.student_name LIKE ? OR s.class LIKE ? OR s.roll_no LIKE ? OR p.receipt_no LIKE ?)";
    $params = [$search_term, $search_term, $search_term, $search_term];
    $types = "ssss";
}

if (!empty($status_filter)) {
    $where_clause .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($fee_type_filter)) {
    $where_clause .= " AND p.fee_type = ?";
    $params[] = $fee_type_filter;
    $types .= "s";
}

// Fetch query logs joining student names
$query = "
    SELECT p.*, s.student_name, s.class, s.roll_no 
    FROM fee_payments p
    JOIN students s ON p.student_id = s.id
    WHERE $where_clause
    ORDER BY p.payment_date DESC, p.id DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total_records = $result->num_rows;
} else {
    die("Database query execution error: " . $conn->error);
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Fee Payments Ledger</h2>
        <p>Audit and search detailed fee transactions, view receipts, and update payment states.</p>
    </div>
    
    <div class="quick-nav-actions">
        <button onclick="exportTableToCSV('fee_payments_ledger.csv')" class="btn-action-secondary"><i class="fa-solid fa-file-csv"></i> Export to CSV</button>
        <a href="add_payment.php" class="btn-action-primary"><i class="fa-solid fa-plus"></i> Collect Fee</a>
    </div>
</div>

<!-- Advanced Filter Panel -->
<div class="filter-section glass-card">
    <form method="GET" action="view_payments.php" class="filter-form-modern">
        <div class="filter-inputs-row">
            <!-- Search Text -->
            <div class="filter-input-group search-group">
                <label for="search">Quick Search</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="search" name="search" placeholder="Search by Receipt #, student name, class..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="filter-input-group">
                <label for="status">Payment Status</label>
                <select name="status" id="status" class="filter-select-modern">
                    <option value="">All Statuses</option>
                    <option value="Paid" <?php echo ($status_filter == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>

            <!-- Fee Type Filter -->
            <div class="filter-input-group">
                <label for="fee_type">Fee Category</label>
                <select name="fee_type" id="fee_type" class="filter-select-modern">
                    <option value="">All Categories</option>
                    <option value="Tuition" <?php echo ($fee_type_filter == 'Tuition') ? 'selected' : ''; ?>>Tuition</option>
                    <option value="Sports" <?php echo ($fee_type_filter == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                    <option value="Transport" <?php echo ($fee_type_filter == 'Transport') ? 'selected' : ''; ?>>Transport</option>
                    <option value="Exam" <?php echo ($fee_type_filter == 'Exam') ? 'selected' : ''; ?>>Exam</option>
                    <option value="Activity" <?php echo ($fee_type_filter == 'Activity') ? 'selected' : ''; ?>>Activity</option>
                    <option value="Uniform" <?php echo ($fee_type_filter == 'Uniform') ? 'selected' : ''; ?>>Uniform</option>
                    <option value="Library" <?php echo ($fee_type_filter == 'Library') ? 'selected' : ''; ?>>Library</option>
                    <option value="Other" <?php echo ($fee_type_filter == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="filter-buttons-row">
            <button type="submit" class="btn-filter-submit"><i class="fa-solid fa-filter"></i> Apply Filter</button>
            <a href="view_payments.php" class="btn-filter-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Summary Stats Row -->
<div class="results-meta-row">
    <span class="total-results-count">Found <strong><?php echo $total_records; ?></strong> transaction logs</span>
</div>

<!-- Ledger Payments Table -->
<div class="payments-ledger-container glass-card">
    <div class="table-responsive">
        <table class="table-modern" id="ledger-table">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Roll No</th>
                    <th>Fee Category</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_records > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status_badge = (strtolower($row['status']) === 'paid') ? 'badge-success' : 'badge-warning';
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['receipt_no']); ?></strong></td>
                            <td>
                                <div class="student-name-cell">
                                    <span class="student-initial"><?php echo strtoupper(substr($row['student_name'], 0, 1)); ?></span>
                                    <a href="student_profile.php?id=<?php echo $row['student_id']; ?>" class="student-profile-link"><?php echo htmlspecialchars($row['student_name']); ?></a>
                                </div>
                            </td>
                            <td><span class="badge-class">Class <?php echo htmlspecialchars($row['class']); ?></span></td>
                            <td>Roll #<?php echo htmlspecialchars($row['roll_no']); ?></td>
                            <td><span class="badge-fee-type"><?php echo htmlspecialchars($row['fee_type']); ?></span></td>
                            <td><strong><?php echo format_amount($row['amount']); ?></strong></td>
                            <td><?php echo date('d-M-Y', strtotime($row['payment_date'])); ?></td>
                            <td><i class="fa-solid fa-money-bill-transfer icon-small text-muted"></i> <?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td><span class="badge-status <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <div class="action-buttons-group">
                                    <!-- Print Receipt -->
                                    <a href="receipt.php?id=<?php echo $row['id']; ?>" class="action-btn view" title="View Receipt"><i class="fa-solid fa-file-invoice-dollar"></i></a>
                                    
                                    <!-- Edit Transaction -->
                                    <a href="edit_payment.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit Transaction"><i class="fa-solid fa-pen-to-square"></i></a>
                                    
                                    <!-- Delete Transaction -->
                                    <a href="delete_payment.php?id=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete Transaction" onclick="return confirm('Are you sure you want to delete this payment record permanently?')"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="no-records-cell">
                            <i class="fa-solid fa-box-open empty-icon"></i>
                            <p>No fee payment transactions match the filter criteria.</p>
                            <a href="add_payment.php" class="btn btn-primary btn-sm">➕ Log First Payment</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include Footer
include 'footer.php';
?>

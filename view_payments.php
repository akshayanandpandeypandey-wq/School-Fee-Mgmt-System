<<<<<<< HEAD
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
=======
<?php
/**
 * =====================================================
 * View All Payments Page
 * =====================================================
 * Purpose: Display all fee payments with filtering, searching, and pagination
 * Features: Search, Filter by status, Responsive table, Edit/Delete/View actions
 * 
 * @author School Fee Management System
 * @version 1.0
 */

require_once 'includes/db_connection.php';

// =====================================================
// Initialize Variables
// =====================================================
$search_query = '';
$filter_status = '';
$payments_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $payments_per_page;

// =====================================================
// Handle Search and Filter
// =====================================================

// Get search keyword from URL
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Get status filter from URL
if (isset($_GET['filter'])) {
    $filter_status = trim($_GET['filter']);
}

// =====================================================
// Build SQL Query with Conditions
// =====================================================

$where_conditions = array();
$count_query = "SELECT COUNT(*) as total FROM fee_payments";
$select_query = "SELECT payment_id, student_name, class, roll_no, fee_type, amount, payment_date, payment_method, status 
                 FROM fee_payments";

// Add search condition
if (!empty($search_query)) {
    // Search in multiple fields
    $search_term = "%{$search_query}%";
    $where_conditions[] = "(student_name LIKE ? OR class LIKE ? OR roll_no LIKE ? OR fee_type LIKE ?)";
}

// Add status filter condition
if (!empty($filter_status)) {
    $where_conditions[] = "status = ?";
}

// Combine conditions
if (!empty($where_conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
    $count_query .= $where_clause;
    $select_query .= $where_clause;
}

// =====================================================
// Get Total Number of Records
// =====================================================

$count_stmt = $conn->prepare($count_query);

if (!empty($search_query) && !empty($filter_status)) {
    $count_stmt->bind_param(
        "sssss",
        $search_term, $search_term, $search_term, $search_term,
        $filter_status
    );
} elseif (!empty($search_query)) {
    $count_stmt->bind_param(
        "ssss",
        $search_term, $search_term, $search_term, $search_term
    );
} elseif (!empty($filter_status)) {
    $count_stmt->bind_param("s", $filter_status);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $payments_per_page);

// =====================================================
// Fetch Payments with Pagination
// =====================================================

$select_query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($select_query);

if (!empty($search_query) && !empty($filter_status)) {
    $stmt->bind_param(
        "sssssii",
        $search_term, $search_term, $search_term, $search_term,
        $filter_status,
        $payments_per_page,
        $offset
    );

} elseif (!empty($search_query)) {
    $stmt->bind_param(
        "ssssii",
        $search_term, $search_term, $search_term, $search_term,
        $payments_per_page,
        $offset
    );

} elseif (!empty($filter_status)) {
    $stmt->bind_param(
        "sii",
        $filter_status,
        $payments_per_page,
        $offset
    );

} else {
    // only pagination
    $stmt->bind_param("ii", $payments_per_page, $offset);
}

$stmt->execute();
$payments_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payments - School Fee Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <h1>🏫 School Fee Management System</h1>
                <p class="subtitle">View and Manage All Payments</p>
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
            
            <!-- =====================================================
                 Search and Filter Section
                 ===================================================== -->
            <div class="search-filter-section">
                <h2 class="section-title">Search & Filter Payments</h2>
                
                <form method="GET" class="search-form">
                    <div class="search-inputs">
                        <!-- Search Box -->
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Search by student name, class, or fee type..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn-search">🔍 Search</button>
                        </div>

                        <!-- Filter Dropdown -->
                        <div class="filter-box">
                            <select name="filter" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="Completed" <?php echo ($filter_status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Failed" <?php echo ($filter_status == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>

                        <!-- Clear Filters Link -->
                        <?php if (!empty($search_query) || !empty($filter_status)): ?>
                            <a href="view_payments.php" class="btn btn-secondary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Results Info -->
                <p class="results-info">
                    Showing <strong><?php echo ($offset + 1); ?></strong> to 
                    <strong><?php echo min($offset + $payments_per_page, $total_records); ?></strong> 
                    of <strong><?php echo $total_records; ?></strong> payments
                </p>
            </div>

            <!-- =====================================================
                 Payments Table
                 ===================================================== -->
            <section class="payments-table-section">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Roll No.</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments_result->num_rows > 0): ?>
                                <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($payment['student_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($payment['class']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['roll_no']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                        <td class="amount">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <span class="badge-method"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($payment['status']); ?>">
                                                <?php echo $payment['status']; ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <!-- View Receipt Button -->
                                            <a href="receipt.php?id=<?php echo $payment['payment_id']; ?>" 
                                               class="btn-action btn-view" title="View Receipt">👁️</a>
                                            
                                            <!-- Edit Button -->
                                            <a href="edit_payment.php?id=<?php echo $payment['payment_id']; ?>" 
                                               class="btn-action btn-edit" title="Edit">✏️</a>
                                            
                                            <!-- Delete Button -->
                                            <a href="delete_payment.php?id=<?php echo $payment['payment_id']; ?>" 
                                               class="btn-action btn-delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this payment?');">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No payments found. <?php echo (!empty($search_query) || !empty($filter_status)) ? '<a href="view_payments.php">Clear filters</a>' : ''; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- =====================================================
                     Pagination
                     ===================================================== -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- Previous Button -->
                        <?php if ($current_page > 1): ?>
                            <a href="?page=1<?php echo (!empty($search_query)) ? '&search=' . urlencode($search_query) : ''; ?><?php echo (!empty($filter_status)) ? '&filter=' . urlencode($filter_status) : ''; ?>" 
                               class="pagination-link">« First</a>
                            <a href="?page=<?php echo ($current_page - 1); ?><?php echo (!empty($search_query)) ? '&search=' . urlencode($search_query) : ''; ?><?php echo (!empty($filter_status)) ? '&filter=' . urlencode($filter_status) : ''; ?>" 
                               class="pagination-link">‹ Previous</a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo (!empty($search_query)) ? '&search=' . urlencode($search_query) : ''; ?><?php echo (!empty($filter_status)) ? '&filter=' . urlencode($filter_status) : ''; ?>" 
                               class="pagination-link <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo ($current_page + 1); ?><?php echo (!empty($search_query)) ? '&search=' . urlencode($search_query) : ''; ?><?php echo (!empty($filter_status)) ? '&filter=' . urlencode($filter_status) : ''; ?>" 
                               class="pagination-link">Next ›</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo (!empty($search_query)) ? '&search=' . urlencode($search_query) : ''; ?><?php echo (!empty($filter_status)) ? '&filter=' . urlencode($filter_status) : ''; ?>" 
                               class="pagination-link">Last »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </section>

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
$stmt->close();
$count_stmt->close();
$conn->close();
?>
>>>>>>> ed2fd208afdb1b4dfed28115495e0d4942f7fb87

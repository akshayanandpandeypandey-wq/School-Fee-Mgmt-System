<?php
/**
 * Students Management List
 * ========================
 * Lists all registered students, their class details, total fees paid, 
 * outstanding balances, and active statuses.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authentication
check_auth();

$page_title = "Student Directory";

// Filters and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';

$where_clause = "1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $search_term = "%{$search}%";
    $where_clause .= " AND (s.student_name LIKE ? OR s.roll_no LIKE ? OR s.parent_name LIKE ?)";
    $params = [$search_term, $search_term, $search_term];
    $types = "sss";
}

if (!empty($class_filter)) {
    $where_clause .= " AND s.class = ?";
    $params[] = $class_filter;
    $types .= "s";
}

// Fetch students with their paid amounts and outstanding balance computed
$query = "
    SELECT s.*, 
           IFNULL(p.total_paid, 0) as total_paid,
           IFNULL(fs.total_class_fee, 0) as raw_class_fee,
           ROUND(IFNULL(fs.total_class_fee, 0) * (1 - s.discount_percent / 100), 2) as total_class_fee,
           GREATEST(0, ROUND(IFNULL(fs.total_class_fee, 0) * (1 - s.discount_percent / 100), 2) - IFNULL(p.total_paid, 0)) as total_due
    FROM students s
    LEFT JOIN (
        SELECT student_id, SUM(amount) as total_paid 
        FROM fee_payments 
        WHERE status = 'Paid' 
        GROUP BY student_id
    ) p ON s.id = p.student_id
    LEFT JOIN (
        SELECT class, SUM(amount) as total_class_fee 
        FROM fee_structures 
        GROUP BY class
    ) fs ON s.class = fs.class
    WHERE $where_clause
    ORDER BY s.class ASC, s.roll_no ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total_students = $result->num_rows;
} else {
    die("Database query error: " . $conn->error);
}

// Fetch list of unique classes for filter dropdown
$classes_query = "SELECT DISTINCT class FROM students ORDER BY class ASC";
$classes_result = $conn->query($classes_query);

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Student Directory</h2>
        <p>Manage school admission list, view student fees ledger, and audit student balances.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="add_student.php" class="btn-action-primary"><i class="fa-solid fa-user-plus"></i> Register Student</a>
    </div>
</div>

<!-- Filters Panel -->
<div class="filter-section glass-card">
    <form method="GET" action="students.php" class="filter-form-modern">
        <div class="filter-inputs-row">
            <div class="filter-input-group search-group">
                <label for="search">Search Scholar</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="search" name="search" placeholder="Search by name, roll, or parent..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="filter-input-group">
                <label for="class">Filter by Class</label>
                <select name="class" id="class" class="filter-select-modern">
                    <option value="">All Classes</option>
                    <?php if ($classes_result && $classes_result->num_rows > 0): ?>
                        <?php while ($c_row = $classes_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($c_row['class']); ?>" <?php echo ($class_filter == $c_row['class']) ? 'selected' : ''; ?>>
                                Class <?php echo htmlspecialchars($c_row['class']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div class="filter-buttons-row">
            <button type="submit" class="btn-filter-submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
            <a href="students.php" class="btn-filter-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Results Info -->
<div class="results-meta-row">
    <span class="total-results-count">Total registered: <strong><?php echo $total_students; ?></strong> student(s)</span>
</div>

<!-- Students Directory Table -->
<div class="students-list-container glass-card">
    <div class="table-responsive">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Parent / Contact</th>
                    <th>Total Assigned</th>
                    <th>Total Paid</th>
                    <th>Balance Due</th>
                    <th>Status</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_students > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status_badge = (strtolower($row['status']) === 'active') ? 'badge-success' : 'badge-danger';
                        $due_class = ($row['total_due'] > 0) ? 'text-danger font-bold' : 'text-success';
                    ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($row['roll_no'], 2, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <div class="student-name-cell">
                                    <span class="student-initial"><?php echo strtoupper(substr($row['student_name'], 0, 1)); ?></span>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <a href="student_profile.php?id=<?php echo $row['id']; ?>" class="student-profile-link"><?php echo htmlspecialchars($row['student_name']); ?></a>
                                            <?php if ($row['discount_percent'] > 0): ?>
                                                <span class="badge-role cashier" style="font-size: 8px; padding: 1px 4px; border-radius: 4px; display: inline-block;">Disc: <?php echo $row['discount_percent']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="student-email-sub"><?php echo htmlspecialchars($row['email'] ?? 'No email logged'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-class">Class <?php echo htmlspecialchars($row['class']); ?></span></td>
                            <td>
                                <div class="parent-info-cell">
                                    <span><?php echo htmlspecialchars($row['parent_name'] ?? 'N/A'); ?></span>
                                    <small class="phone-sub"><i class="fa-solid fa-phone phone-icon"></i> <?php echo htmlspecialchars($row['parent_phone'] ?? 'N/A'); ?></small>
                                </div>
                            </td>
                            <td><?php echo format_amount($row['total_class_fee']); ?></td>
                            <td class="text-success font-bold"><?php echo format_amount($row['total_paid']); ?></td>
                            <td class="<?php echo $due_class; ?>"><?php echo format_amount($row['total_due']); ?></td>
                            <td><span class="badge-status <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <div class="action-buttons-group">
                                    <!-- Profile View -->
                                    <a href="student_profile.php?id=<?php echo $row['id']; ?>" class="action-btn view" title="View Profile"><i class="fa-solid fa-address-card"></i></a>
                                    
                                    <!-- Edit Profile -->
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit Student"><i class="fa-solid fa-user-pen"></i></a>
                                    
                                    <!-- Delete Student -->
                                    <a href="delete_student.php?id=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete Student" onclick="return confirm('Are you sure you want to delete student: <?php echo htmlspecialchars(addslashes($row['student_name'])); ?>? This will delete all their billing records!')"><i class="fa-solid fa-trash-can"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-records-cell">
                            <i class="fa-solid fa-graduation-cap empty-icon"></i>
                            <p>No student accounts match the filter criteria.</p>
                            <a href="add_student.php" class="btn btn-primary btn-sm">➕ Register New Student</a>
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

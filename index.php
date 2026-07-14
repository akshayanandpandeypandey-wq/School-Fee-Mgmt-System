<<<<<<< HEAD
<?php
/**
 * Interactive Dashboard Page
 * ==========================
 * Shows key system statistics, collection charts, and recent records.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authentication
check_auth();

$page_title = "Dashboard";

// 1. Fetch Total Payments count
$total_pay_stmt = $conn->prepare("SELECT COUNT(*) as total FROM fee_payments");
$total_pay_stmt->execute();
$total_payments = $total_pay_stmt->get_result()->fetch_assoc()['total'];
$total_pay_stmt->close();

// 2. Fetch Total Collected amount (Paid status)
$amount_stmt = $conn->prepare("SELECT SUM(amount) as total FROM fee_payments WHERE status = 'Paid'");
$amount_stmt->execute();
$total_collected = $amount_stmt->get_result()->fetch_assoc()['total'] ?? 0.00;
$amount_stmt->close();

// 3. Fetch Active Students count
$student_stmt = $conn->prepare("SELECT COUNT(*) as total FROM students WHERE status = 'Active'");
$student_stmt->execute();
$active_students = $student_stmt->get_result()->fetch_assoc()['total'];
$student_stmt->close();

// 4. Calculate Total Dues (Allocated standard fees - Amount collected)
$allocated_query = "
    SELECT SUM(t.total_class_fee) as grand_total FROM (
        SELECT s.class, 
               SUM(ROUND(IFNULL(fs.total_fees, 0) * (1 - s.discount_percent / 100), 2)) as total_class_fee
        FROM students s
        LEFT JOIN (
            SELECT class, SUM(amount) as total_fees FROM fee_structures GROUP BY class
        ) fs ON s.class = fs.class
        WHERE s.status = 'Active'
        GROUP BY s.class
    ) t
";
$allocated_stmt = $conn->prepare($allocated_query);
$allocated_stmt->execute();
$grand_allocated = $allocated_stmt->get_result()->fetch_assoc()['grand_total'] ?? 0.00;
$allocated_stmt->close();

$total_dues = max(0, $grand_allocated - $total_collected);

// 5. Fetch recent transactions (limit 5)
$recent_query = "
    SELECT p.*, s.student_name, s.class, s.roll_no 
    FROM fee_payments p 
    JOIN students s ON p.student_id = s.id 
    ORDER BY p.payment_date DESC, p.id DESC LIMIT 5
";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

// 6. Fetch monthly trends data (last 6 months) for Chart.js
$trend_query = "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_raw, 
           DATE_FORMAT(payment_date, '%b %Y') as month_label, 
           SUM(amount) as total_amount
    FROM fee_payments
    WHERE status = 'Paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month_raw, month_label
    ORDER BY month_raw ASC
";
$trend_stmt = $conn->prepare($trend_query);
$trend_stmt->execute();
$trend_res = $trend_stmt->get_result();

$trend_months = [];
$trend_sums = [];
while ($t_row = $trend_res->fetch_assoc()) {
    $trend_months[] = $t_row['month_label'];
    $trend_sums[] = (float)$t_row['total_amount'];
}
$trend_stmt->close();

// 7. Fetch fee type distribution for Chart.js
$dist_query = "
    SELECT fee_type, SUM(amount) as total_amount
    FROM fee_payments
    WHERE status = 'Paid'
    GROUP BY fee_type
";
$dist_stmt = $conn->prepare($dist_query);
$dist_stmt->execute();
$dist_res = $dist_stmt->get_result();

$dist_types = [];
$dist_sums = [];
while ($d_row = $dist_res->fetch_assoc()) {
    $dist_types[] = $d_row['fee_type'];
    $dist_sums[] = (float)$d_row['total_amount'];
}
$dist_stmt->close();

// Include Header Layout
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Dashboard Overview</h2>
        <p>Real-time fee collections, student accounts status, and transactional auditing.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="add_payment.php" class="btn-action-primary"><i class="fa-solid fa-plus"></i> Collect Fee</a>
        <a href="add_student.php" class="btn-action-secondary"><i class="fa-solid fa-user-plus"></i> Register Student</a>
    </div>
</div>

<!-- Statistics Widgets Grid -->
<div class="stats-grid">
    <!-- Total Collections Card -->
    <div class="stat-card glass-card border-success">
        <div class="stat-icon-wrapper success"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
        <div class="stat-card-details">
            <span class="stat-label">Total Collected</span>
            <h3 class="stat-value"><?php echo format_amount($total_collected); ?></h3>
            <span class="stat-note"><i class="fa-solid fa-square-check text-success"></i> All confirmed payments</span>
        </div>
    </div>

    <!-- Outstanding Dues Card -->
    <div class="stat-card glass-card border-warning">
        <div class="stat-icon-wrapper warning"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="stat-card-details">
            <span class="stat-label">Total Outstanding Dues</span>
            <h3 class="stat-value"><?php echo format_amount($total_dues); ?></h3>
            <span class="stat-note"><i class="fa-solid fa-circle-info text-warning"></i> Based on class fee allocations</span>
        </div>
    </div>

    <!-- Active Students Card -->
    <div class="stat-card glass-card border-primary">
        <div class="stat-icon-wrapper primary"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="stat-card-details">
            <span class="stat-label">Active Students</span>
            <h3 class="stat-value"><?php echo number_format($active_students); ?></h3>
            <span class="stat-note"><i class="fa-solid fa-circle-check text-primary"></i> Currently enrolled scholars</span>
        </div>
    </div>

    <!-- Total Transactions Card -->
    <div class="stat-card glass-card border-info">
        <div class="stat-icon-wrapper info"><i class="fa-solid fa-receipt"></i></div>
        <div class="stat-card-details">
            <span class="stat-label">Transactions Logs</span>
            <h3 class="stat-value"><?php echo number_format($total_payments); ?></h3>
            <span class="stat-note"><i class="fa-solid fa-tags text-info"></i> Paid & Pending receipts</span>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="dashboard-charts-grid">
    <!-- Chart 1: Line Trend -->
    <div class="chart-card glass-card">
        <div class="chart-header">
            <h4><i class="fa-solid fa-chart-line text-primary"></i> Monthly Collection Trend</h4>
            <span class="chart-subtitle">Payments collected over last 6 months</span>
        </div>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Doughnut Distribution -->
    <div class="chart-card glass-card">
        <div class="chart-header">
            <h4><i class="fa-solid fa-chart-pie text-success"></i> Collection by Fee Category</h4>
            <span class="chart-subtitle">Revenue split by allocation types</span>
        </div>
        <div class="chart-container">
            <canvas id="distributionChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Transactions List -->
<div class="recent-payments-container glass-card">
    <div class="container-header-row">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Transactions</h3>
        <a href="view_payments.php" class="btn-link">View All Logs <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    
    <div class="table-responsive">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Student Name</th>
                    <th>Class (Roll)</th>
                    <th>Fee Type</th>
                    <th>Method</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_result->num_rows > 0): ?>
                    <?php while ($row = $recent_result->fetch_assoc()): 
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
                            <td><?php echo htmlspecialchars($row['class'] . " (Roll: " . $row['roll_no'] . ")"); ?></td>
                            <td><span class="badge-fee-type"><?php echo htmlspecialchars($row['fee_type']); ?></span></td>
                            <td><i class="fa-solid fa-credit-card icon-small"></i> <?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td><?php echo date('d-M-Y', strtotime($row['payment_date'])); ?></td>
                            <td><strong><?php echo format_amount($row['amount']); ?></strong></td>
                            <td><span class="badge-status <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <a href="receipt.php?id=<?php echo $row['id']; ?>" class="btn-table-icon" title="View Receipt"><i class="fa-solid fa-file-invoice-dollar"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-records-cell">
                            <i class="fa-solid fa-box-open empty-icon"></i>
                            <p>No recent payment transactions logged yet.</p>
                            <a href="add_payment.php" class="btn btn-primary btn-sm">➕ Add Payment Now</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart initialization scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Data passed from PHP
        const trendLabels = <?php echo json_encode($trend_months); ?>;
        const trendData = <?php echo json_encode($trend_sums); ?>;
        
        const distLabels = <?php echo json_encode($dist_types); ?>;
        const distData = <?php echo json_encode($dist_sums); ?>;

        // Colors configurations
        const primaryColor = '#3b82f6';
        const primaryGlow = 'rgba(59, 130, 246, 0.1)';
        const themeFonts = {
            family: "'Outfit', sans-serif",
            color: '#94a3b8'
        };

        // 1. Line Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels.length ? trendLabels : ['No Data'],
                datasets: [{
                    label: 'Monthly Collections',
                    data: trendData.length ? trendData : [0],
                    borderColor: primaryColor,
                    backgroundColor: primaryGlow,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointBackgroundColor: primaryColor,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: themeFonts.family }, color: themeFonts.color }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { 
                            font: { family: themeFonts.family }, 
                            color: themeFonts.color,
                            callback: function(value) { return '₹' + value; }
                        }
                    }
                }
            }
        });

        // 2. Doughnut Distribution Chart
        const distCtx = document.getElementById('distributionChart').getContext('2d');
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'];
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: distLabels.length ? distLabels : ['No Records'],
                datasets: [{
                    data: distData.length ? distData : [100],
                    backgroundColor: distData.length ? colors.slice(0, distLabels.length) : ['rgba(255, 255, 255, 0.05)'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { family: themeFonts.family },
                            color: themeFonts.color,
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>

<?php
// Include Footer Layout
include 'footer.php';
?>
=======
<?php
/**
 * =====================================================
 * Dashboard / Home Page
 * =====================================================
 * Purpose: Display main dashboard with statistics and quick links
 * Features: Shows total students, total amount collected, recent payments
 * 
 * @author School Fee Management System
 * @version 1.0
 */

require_once 'includes/db_connection.php';

// =====================================================
// Fetch Dashboard Statistics
// =====================================================

// Query 1: Total number of payments
$query_total_payments = "SELECT COUNT(*) as total_payments FROM fee_payments";
$result = $conn->query($query_total_payments);
$row = $result->fetch_assoc();
$total_payments = $row['total_payments'];

// Query 2: Total amount collected
$query_total_amount = "SELECT SUM(amount) as total_amount FROM fee_payments WHERE status = 'Completed'";
$result = $conn->query($query_total_amount);
$row = $result->fetch_assoc();
$total_amount = $row['total_amount'] ?? 0;

// Query 3: Number of unique students
$query_total_students = "SELECT COUNT(DISTINCT student_name) as total_students FROM fee_payments";
$result = $conn->query($query_total_students);
$row = $result->fetch_assoc();
$total_students = $row['total_students'];

// Query 4: Pending payments
$query_pending = "SELECT COUNT(*) as pending_count FROM fee_payments WHERE status = 'Pending'";
$result = $conn->query($query_pending);
$row = $result->fetch_assoc();
$pending_count = $row['pending_count'];

// Query 5: Recent 5 payments for dashboard preview
$query_recent = "SELECT payment_id, student_name, class, fee_type, amount, payment_date, status 
                 FROM fee_payments 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$recent_payments = $conn->query($query_recent);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Fee Management System - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- =====================================================
         Main Container
         ===================================================== -->
    <div class="container">
        
        <!-- Header Section -->
        <header class="header">
            <div class="header-content">
                <h1>🏫 School Fee Management System</h1>
                <p class="subtitle">Manage student fee payments efficiently</p>
            </div>
        </header>

        <!-- Navigation Menu -->
        <nav class="navbar">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link active">Dashboard</a></li>
                <li><a href="add_payment.php" class="nav-link">Add Payment</a></li>
                <li><a href="view_payments.php" class="nav-link">View Payments</a></li>
            </ul>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            
            <!-- =====================================================
                 Statistics Cards Section
                 ===================================================== -->
            <section class="statistics">
                <h2 class="section-title">Dashboard Statistics</h2>
                
                <!-- Total Payments Card -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        📋
                    </div>
                    <div class="stat-content">
                        <h3>Total Payments</h3>
                        <p class="stat-number"><?php echo $total_payments; ?></p>
                        <small>Payment records in system</small>
                    </div>
                </div>

                <!-- Total Amount Card -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        💰
                    </div>
                    <div class="stat-content">
                        <h3>Total Amount</h3>
                        <p class="stat-number">₹<?php echo number_format($total_amount, 2); ?></p>
                        <small>Amount collected</small>
                    </div>
                </div>

                <!-- Total Students Card -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        👨‍🎓
                    </div>
                    <div class="stat-content">
                        <h3>Total Students</h3>
                        <p class="stat-number"><?php echo $total_students; ?></p>
                        <small>Unique students</small>
                    </div>
                </div>

                <!-- Pending Payments Card -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        ⏳
                    </div>
                    <div class="stat-content">
                        <h3>Pending Payments</h3>
                        <p class="stat-number"><?php echo $pending_count; ?></p>
                        <small>Awaiting confirmation</small>
                    </div>
                </div>
            </section>

            <!-- =====================================================
                 Recent Payments Section
                 ===================================================== -->
            <section class="recent-payments">
                <div class="section-header">
                    <h2 class="section-title">Recent Payments</h2>
                    <a href="view_payments.php" class="btn btn-secondary">View All</a>
                </div>

                <!-- Responsive Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_payments->num_rows > 0): ?>
                                <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($payment['student_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($payment['class']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                        <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($payment['status']); ?>">
                                                <?php echo $payment['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- =====================================================
                 Quick Actions Section
                 ===================================================== -->
            <section class="quick-actions">
                <h2 class="section-title">Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_payment.php" class="btn btn-primary">
                        ➕ Add New Payment
                    </a>
                    <a href="view_payments.php" class="btn btn-info">
                        📊 View All Payments
                    </a>
                    <a href="view_payments.php?filter=pending" class="btn btn-warning">
                        ⏳ Pending Payments
                    </a>
                </div>
            </section>

        </main>

        <!-- Footer Section -->
        <footer class="footer">
            <p>&copy; 2026 School Fee Management System. All rights reserved.</p>
        </footer>

    </div>

    <script src="js/script.js"></script>
</body>
</html>

<?php
// Close database connection at the end
$conn->close();
?>
>>>>>>> ed2fd208afdb1b4dfed28115495e0d4942f7fb87

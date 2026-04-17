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

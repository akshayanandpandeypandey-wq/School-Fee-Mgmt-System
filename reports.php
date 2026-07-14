<?php
/**
 * System Financial Reports & Audits Page
 * =====================================
 * Generates class-wise audits, collection breakdowns by category,
 * and monthly revenue summaries. Enabled for Admins only.
 */

// Include DB connection
include 'db_connection.php';

// Enforce Admin auth
check_auth('Admin');

$page_title = "Financial Reports & Audits";

// 1. Fetch Class Collections Summary
$class_reports_query = "
    SELECT s.class, 
           SUM(CASE WHEN p.status = 'Paid' THEN p.amount ELSE 0 END) as total_collected,
           SUM(CASE WHEN p.status = 'Pending' THEN p.amount ELSE 0 END) as total_pending,
           COUNT(p.id) as transaction_count
    FROM students s
    JOIN fee_payments p ON s.id = p.student_id
    GROUP BY s.class
    ORDER BY s.class ASC
";
$class_stmt = $conn->prepare($class_reports_query);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

// 2. Fetch Fee Type revenue distribution
$fee_reports_query = "
    SELECT fee_type, 
           SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_collected,
           SUM(CASE WHEN status = 'Pending' THEN amount ELSE 0 END) as total_pending,
           COUNT(id) as transaction_count
    FROM fee_payments
    GROUP BY fee_type
    ORDER BY total_collected DESC
";
$fee_stmt = $conn->prepare($fee_reports_query);
$fee_stmt->execute();
$fee_result = $fee_stmt->get_result();

// 3. Fetch Monthly collection logs (past 12 months)
$monthly_reports_query = "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_raw, 
           DATE_FORMAT(payment_date, '%b %Y') as month_label, 
           SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as total_collected,
           COUNT(id) as transaction_count
    FROM fee_payments
    GROUP BY month_raw, month_label
    ORDER BY month_raw DESC
    LIMIT 12
";
$monthly_stmt = $conn->prepare($monthly_reports_query);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block no-print">
    <div class="welcome-banner">
        <h2>Financial Reports & Audits</h2>
        <p>Analyze class-wise performance audits, category revenues, and monthly historical collection logs.</p>
    </div>
    
    <div class="quick-nav-actions">
        <button onclick="window.print()" class="btn-action-primary"><i class="fa-solid fa-print"></i> Print Audit Page</button>
    </div>
</div>

<!-- Class-wise collections table card -->
<div class="report-section-wrapper margin-spacing">
    <div class="records-card glass-card">
        <div class="container-header-row">
            <h4><i class="fa-solid fa-school text-primary"></i> Class-wise Collection Audit</h4>
            <span class="total-results-count">Syllabus revenue splits</span>
        </div>
        
        <div class="table-responsive">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Class Section</th>
                        <th>Total Confirmed Collections</th>
                        <th>Total Pending Clearance</th>
                        <th style="text-align: center;">Transactions Logged</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_collected = 0;
                    $grand_pending = 0;
                    $grand_transactions = 0;
                    
                    if ($class_result->num_rows > 0): 
                        while ($row = $class_result->fetch_assoc()):
                            $grand_collected += $row['total_collected'];
                            $grand_pending += $row['total_pending'];
                            $grand_transactions += $row['transaction_count'];
                    ?>
                        <tr>
                            <td><span class="badge-class">Class <?php echo htmlspecialchars($row['class']); ?></span></td>
                            <td class="text-success font-bold"><?php echo format_amount($row['total_collected']); ?></td>
                            <td class="text-warning font-bold"><?php echo format_amount($row['total_pending']); ?></td>
                            <td style="text-align: center;"><?php echo number_format($row['transaction_count']); ?> logs</td>
                        </tr>
                    <?php 
                        endwhile;
                    ?>
                        <!-- Grand Total Footer Row -->
                        <tr class="table-footer-row" style="background: rgba(255, 255, 255, 0.03); font-weight: bold; border-top: 2px solid var(--card-border);">
                            <td>Grand Total:</td>
                            <td class="text-success"><?php echo format_amount($grand_collected); ?></td>
                            <td class="text-warning"><?php echo format_amount($grand_pending); ?></td>
                            <td style="text-align: center;"><?php echo number_format($grand_transactions); ?> logs</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-records-cell">No transaction logs available to build class audits.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="split-layout-container">
    <!-- Left Column: Fee Category Revenue -->
    <div class="split-left-column">
        <div class="records-card glass-card">
            <div class="container-header-row">
                <h4><i class="fa-solid fa-tags text-success"></i> Category Performance</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Revenue</th>
                            <th style="text-align: center;">Logs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fee_result->num_rows > 0): ?>
                            <?php while ($row = $fee_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge-fee-type"><?php echo htmlspecialchars($row['fee_type']); ?></span></td>
                                    <td class="text-success font-bold"><?php echo format_amount($row['total_collected']); ?></td>
                                    <td style="text-align: center;"><?php echo $row['transaction_count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-records-cell">No revenue logs.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Historical Monthly Revenue -->
    <div class="split-right-column">
        <div class="records-card glass-card">
            <div class="container-header-row">
                <h4><i class="fa-solid fa-calendar-days text-info"></i> Monthly Revenue Trend</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Month Reference</th>
                            <th>Collections</th>
                            <th style="text-align: center;">Logs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($monthly_result->num_rows > 0): ?>
                            <?php while ($row = $monthly_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['month_label']); ?></strong></td>
                                    <td class="text-success font-bold"><?php echo format_amount($row['total_collected']); ?></td>
                                    <td style="text-align: center;"><?php echo $row['transaction_count']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-records-cell">No monthly summaries.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$class_stmt->close();
$fee_stmt->close();
$monthly_stmt->close();

// Include Footer
include 'footer.php';
?>

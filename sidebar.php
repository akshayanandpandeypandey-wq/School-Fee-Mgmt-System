<?php
/**
 * Shared Sidebar Navigation Template
 * ==================================
 * Renders the responsive collapsable sidebar navigation with list controls.
 */

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'Cashier';
?>
<aside class="sidebar-nav" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span class="logo-icon-box"><i class="fa-solid fa-wallet"></i></span>
            <div class="logo-text">
                <span class="logo-title">FEE CORE</span>
                <span class="logo-subtitle">MANAGEMENT SYSTEM</span>
            </div>
        </div>
    </div>
    
    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-menu">
            <li class="menu-label">CORE PANEL</li>
            
            <li>
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="students.php" class="<?php echo (in_array($current_page, ['students.php', 'add_student.php', 'edit_student.php', 'student_profile.php'])) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li>
                <a href="view_payments.php" class="<?php echo (in_array($current_page, ['view_payments.php', 'add_payment.php', 'edit_payment.php', 'receipt.php'])) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Fee Payments</span>
                </a>
            </li>
            
            <?php if ($user_role === 'Admin'): ?>
            <li>
                <a href="fee_structures.php" class="<?php echo ($current_page == 'fee_structures.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Fee Structures</span>
                </a>
            </li>
            
            <li class="menu-label">ANALYTICS & SYSTEM</li>
            
            <li>
                <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Financial Reports</span>
                </a>
            </li>
            
            <li>
                <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gears"></i>
                    <span>System Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="user-quick-profile">
            <div class="user-avatar-small">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="user-details-small">
                <p class="user-name-small"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></p>
                <p class="user-role-small"><?php echo htmlspecialchars($user_role); ?></p>
            </div>
            <a href="logout.php" class="btn-logout-small" title="Sign Out">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>

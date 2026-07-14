<?php
/**
 * Shared Header Template
 * ======================
 * Renders the doctype, structural head element, font assets,
 * and initializes base page structures.
 */

// If header is included, ensure the page is authenticated
if (function_exists('check_auth')) {
    check_auth();
}

$school_name = get_setting('school_name', 'Greenwood International');
$user_role = $_SESSION['role'] ?? 'Cashier';
$user_name = $_SESSION['full_name'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_setting('theme', 'dark')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - School Fee Management" : "School Fee Management"; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Panel -->
        <div class="main-panel">
            <!-- Top Navbar Header -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle" class="icon-btn-toggle" title="Toggle Sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <span class="school-identity"><i class="fa-solid fa-graduation-cap header-logo-icon"></i> <?php echo htmlspecialchars($school_name); ?></span>
                </div>
                
                <div class="top-bar-right">
                    <!-- Quick Search / Action -->
                    <div class="theme-toggle-wrapper">
                        <button id="theme-toggle-btn" class="theme-btn" title="Toggle Dark/Light Mode">
                            <i class="fa-solid fa-moon"></i>
                        </button>
                    </div>
                    
                    <div class="user-profile-widget">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                        <div class="profile-meta">
                            <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="profile-role badge-role <?php echo strtolower($user_role); ?>"><?php echo $user_role; ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dynamic Alert Notifications -->
            <div class="content-body">
                <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-error alert-dismissible">
                        <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                        <span class="alert-message"><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></span>
                        <button class="alert-close-btn">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <span class="alert-icon"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="alert-message"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></span>
                        <button class="alert-close-btn">&times;</button>
                    </div>
                <?php endif; ?>

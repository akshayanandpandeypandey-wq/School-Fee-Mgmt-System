<?php
/**
 * System Configurations & User Administration Page
 * ==================================================
 * Configures institution profile metadata and manages staff credentials.
 * Enabled for Administrator accounts only.
 */

// Include DB connection
include 'db_connection.php';

// Enforce Admin auth
check_auth('Admin');

$page_title = "System Settings & Users";
$error = '';
$success = '';

// Handle Post Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update School Profile settings
    if ($action === 'school_profile') {
        $school_name = trim($_POST['school_name'] ?? '');
        $school_address = trim($_POST['school_address'] ?? '');
        $school_phone = trim($_POST['school_phone'] ?? '');
        $school_email = trim($_POST['school_email'] ?? '');
        $currency = trim($_POST['currency'] ?? 'INR');

        if (empty($school_name) || empty($school_address) || empty($school_phone) || empty($school_email)) {
            $error = "❌ All school profile fields are required!";
        } else {
            $settings = [
                'school_name' => $school_name,
                'school_address' => $school_address,
                'school_phone' => $school_phone,
                'school_email' => $school_email,
                'currency' => $currency
            ];

            $stmt = $conn->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            foreach ($settings as $key => $val) {
                $stmt->bind_param("sss", $key, $val, $val);
                $stmt->execute();
            }
            $stmt->close();
            $_SESSION['flash_success'] = "🎉 School profile settings updated successfully!";
            header("Location: settings.php");
            exit;
        }
    }

    // 2. Change Password
    if ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $error = "❌ Please fill in all password fields!";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "❌ New passwords do not match!";
        } elseif (strlen($new_pass) < 6) {
            $error = "❌ Password must be at least 6 characters long!";
        } else {
            // Verify old password
            $u_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $u_stmt->bind_param("i", $_SESSION['user_id']);
            $u_stmt->execute();
            $curr_hash = $u_stmt->get_result()->fetch_assoc()['password'];
            $u_stmt->close();

            if (password_verify($current_pass, $curr_hash)) {
                // Update password
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $up_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $up_stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
                if ($up_stmt->execute()) {
                    $_SESSION['flash_success'] = "🎉 Password changed successfully!";
                    header("Location: settings.php");
                    exit;
                } else {
                    $error = "❌ Error updating password: " . $up_stmt->error;
                }
                $up_stmt->close();
            } else {
                $error = "❌ Incorrect current password!";
            }
        }
    }

    // 3. Register New Staff User Account
    if ($action === 'register_user') {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Cashier');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
            $error = "❌ All user creation fields are required!";
        } elseif (strlen($password) < 6) {
            $error = "❌ Password must be at least 6 characters!";
        } else {
            // Check duplicate
            $chk_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $chk_stmt->bind_param("ss", $username, $email);
            $chk_stmt->execute();
            if ($chk_stmt->get_result()->num_rows > 0) {
                $error = "❌ Username or Email address already registered!";
                $chk_stmt->close();
            } else {
                $chk_stmt->close();
                // Insert new user
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $in_stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $in_stmt->bind_param("sssss", $username, $hash, $full_name, $email, $role);
                if ($in_stmt->execute()) {
                    $_SESSION['flash_success'] = "🎉 Staff account registered successfully!";
                    header("Location: settings.php");
                    exit;
                } else {
                    $error = "❌ Error registering user: " . $in_stmt->error;
                }
                $in_stmt->close();
            }
        }
    }
}

// Load current school configuration
$s_name = get_setting('school_name', 'Greenwood International School');
$s_address = get_setting('school_address', '456 Education Blvd, Knowledge City');
$s_phone = get_setting('school_phone', '+91-9876543210');
$s_email = get_setting('school_email', 'contact@greenwood.edu');
$s_currency = get_setting('currency', 'INR');

// Load list of current users
$users_result = $conn->query("SELECT id, username, full_name, email, role, created_at FROM users ORDER BY role ASC, username ASC");

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>System Settings & Administration</h2>
        <p>Manage institution profile headers, currency symbols, admin passwords, and staff access accounts.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
        <span class="alert-message"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="split-layout-container">
    <!-- Left Column: Settings Forms -->
    <div class="split-left-column">
        <!-- 1. School Profile Form -->
        <div class="form-container glass-card margin-spacing">
            <h3><i class="fa-solid fa-graduation-cap text-primary"></i> School Profile Details</h3>
            <p class="form-intro-text">This details are printed on student receipts.</p>
            
            <form method="POST" action="settings.php" class="form-modern">
                <input type="hidden" name="action" value="school_profile">
                
                <div class="form-group-modern">
                    <label for="school_name">School Name <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-building-columns"></i>
                        <input type="text" id="school_name" name="school_name" value="<?php echo htmlspecialchars($s_name); ?>" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="school_address">School Address <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <input type="text" id="school_address" name="school_address" value="<?php echo htmlspecialchars($s_address); ?>" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="school_phone">Contact Phone <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-phone"></i>
                        <input type="text" id="school_phone" name="school_phone" value="<?php echo htmlspecialchars($s_phone); ?>" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="school_email">Contact Email <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="school_email" name="school_email" value="<?php echo htmlspecialchars($s_email); ?>" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="currency">Local Currency</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <select id="currency" name="currency" class="select-modern">
                            <option value="INR" <?php echo ($s_currency === 'INR') ? 'selected' : ''; ?>>Indian Rupee (₹)</option>
                            <option value="USD" <?php echo ($s_currency === 'USD') ? 'selected' : ''; ?>>US Dollar ($)</option>
                            <option value="EUR" <?php echo ($s_currency === 'EUR') ? 'selected' : ''; ?>>Euro (€)</option>
                            <option value="GBP" <?php echo ($s_currency === 'GBP') ? 'selected' : ''; ?>>British Pound (£)</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary btn-full"><i class="fa-solid fa-save"></i> Save Configuration</button>
                </div>
            </form>
        </div>
        
        <!-- 2. Change Password Form -->
        <div class="form-container glass-card">
            <h3><i class="fa-solid fa-key text-warning"></i> Change Password</h3>
            <p class="form-intro-text">Update security credentials for your account.</p>
            
            <form method="POST" action="settings.php" class="form-modern">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group-modern">
                    <label for="current_password">Current Password <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="new_password">New Password <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-shield"></i>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-check-double"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary btn-full"><i class="fa-solid fa-key"></i> Update Password</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Staff Registrations & Lists -->
    <div class="split-right-column">
        <!-- New User Form -->
        <div class="form-container glass-card margin-spacing">
            <h3><i class="fa-solid fa-user-plus text-info"></i> Register Staff Account</h3>
            <p class="form-intro-text">Register a new cashier or administrator account.</p>
            
            <form method="POST" action="settings.php" class="form-modern">
                <input type="hidden" name="action" value="register_user">
                
                <div class="form-group-modern">
                    <label for="username">Username <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Login username" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-address-card"></i>
                        <input type="text" id="full_name" name="full_name" placeholder="Staff member's full name" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="staff@greenwood.edu" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="role">User Role</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-user-shield"></i>
                        <select id="role" name="role" class="select-modern">
                            <option value="Cashier">Cashier (Billing clerk)</option>
                            <option value="Admin">Administrator (Full control)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                    </div>
                </div>

                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary btn-full"><i class="fa-solid fa-user-plus"></i> Register Account</button>
                </div>
            </form>
        </div>
        
        <!-- Users List -->
        <div class="records-card glass-card">
            <div class="container-header-row">
                <h4><i class="fa-solid fa-users text-primary"></i> Registered Staff Directories</h4>
            </div>
            
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && $users_result->num_rows > 0): ?>
                            <?php while ($row = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                    <td>
                                        <div class="parent-info-cell">
                                            <span><?php echo htmlspecialchars($row['full_name']); ?></span>
                                            <small class="phone-sub"><?php echo htmlspecialchars($row['email']); ?></small>
                                        </div>
                                    </td>
                                    <td><span class="badge-role <?php echo strtolower($row['role']); ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-records-cell">No registered staff users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Include Footer
include 'footer.php';
?>

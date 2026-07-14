<?php
/**
 * Database Initializer & Migration Script
 * ========================================
 * Run this file in your browser to set up or upgrade the database:
 * http://localhost/SchoolFeeManagement/db_init.php
 */

// Temporarily disable strict DB check in db_connection.php if included
// We will do direct connection here
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'school_fee_management';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup & Migration</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; color: #333; padding: 40px; margin: 0; }
        .setup-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h1 { color: #2e7d32; border-bottom: 2px solid #a5d6a7; padding-bottom: 10px; margin-top: 0; }
        .log-box { background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: 'Courier New', Courier, monospace; height: 350px; overflow-y: auto; margin-bottom: 20px; white-space: pre-wrap; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 24px; background: #2e7d32; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; border: none; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #1b5e20; }
        .success-banner { background-color: #e8f5e9; border-left: 6px solid #2e7d32; color: #1b5e20; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .error-banner { background-color: #ffebee; border-left: 6px solid #c62828; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class='setup-container'>
    <h1>⚙️ Database Setup & Migration Wizard</h1>";

flush();

// Establish initial connection without database to create it if not exists
$conn = @new mysqli($db_host, $db_username, $db_password);
if ($conn->connect_error) {
    echo "<div class='error-banner'>
            <h3>Database Server Connection Failed</h3>
            <p>Could not connect to MySQL server at <strong>$db_host</strong> with user <strong>$db_username</strong>.</p>
            <p><strong>Error details:</strong> {$conn->connect_error}</p>
            <p><em>Please ensure that XAMPP Control Panel is open and MySQL is started!</em></p>
          </div>
          <button onclick='window.location.reload()' class='btn'>🔄 Retry Connection</button>
          </div></body></html>";
    exit;
}

echo "<div class='log-box' id='log'>";
echo "[INFO] Connected to MySQL Server successfully.\n";

// 1. Create Database if not exists
if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    echo "[SUCCESS] Database `$db_name` checked/created.\n";
} else {
    echo "[ERROR] Failed to create database: " . $conn->error . "\n";
    echo "</div></div></body></html>";
    exit;
}

// Reconnect to the database
$conn->select_db($db_name);
$conn->set_charset("utf8mb4");

// 2. Read database_update.sql schema queries
$sql_file = __DIR__ . '/database_update.sql';
if (!file_exists($sql_file)) {
    echo "[ERROR] Schema file `database_update.sql` not found at $sql_file\n";
    echo "</div></div></body></html>";
    exit;
}

echo "[INFO] Reading database schema from `database_update.sql`...\n";
$sql_content = file_get_contents($sql_file);

// Basic split by semicolon - ignoring comments
$queries = [];
$lines = explode("\n", $sql_content);
$temp_query = "";
foreach ($lines as $line) {
    // Strip comments
    $trimmed = trim($line);
    if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '*') === 0) {
        continue;
    }
    $temp_query .= $line . "\n";
    if (substr($trimmed, -1) == ';') {
        $queries[] = $temp_query;
        $temp_query = "";
    }
}

// Run schema queries
$success_count = 0;
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    // Ignore USE statement as we are already connected
    if (strpos(strtoupper($query), 'USE ') === 0) continue;
    
    if ($conn->query($query)) {
        $success_count++;
    } else {
        echo "[WARNING] Error running schema query: " . substr($query, 0, 50) . "... \nReason: " . $conn->error . "\n";
    }
}
echo "[SUCCESS] Applied $success_count schema updates.\n";

// 3. Detect and Perform Data Migration
$migration_successful = true;
$has_old_data = false;

// Check if old fee_payments table exists and has student_name column (indicating old schema)
$check_table = $conn->query("SHOW TABLES LIKE 'fee_payments'");
if ($check_table && $check_table->num_rows > 0) {
    $check_columns = $conn->query("SHOW COLUMNS FROM fee_payments LIKE 'student_name'");
    if ($check_columns && $check_columns->num_rows > 0) {
        $has_old_data = true;
        echo "[MIGRATION] Old `fee_payments` schema detected! Preparing data migration...\n";
    }
}

if ($has_old_data) {
    // Start Transaction
    $conn->begin_transaction();
    
    try {
        // Fetch all unique students from old payments
        $student_result = $conn->query("SELECT DISTINCT student_name, class, roll_no FROM fee_payments");
        $student_mappings = []; // Key: "name|class|roll", Value: student_id
        
        if ($student_result && $student_result->num_rows > 0) {
            echo "[MIGRATION] Found " . $student_result->num_rows . " unique students to migrate.\n";
            $stmt = $conn->prepare("INSERT INTO students (student_name, class, roll_no, status) VALUES (?, ?, ?, 'Active')");
            
            while ($student = $student_result->fetch_assoc()) {
                $name = $student['student_name'];
                $class = $student['class'];
                $roll = intval($student['roll_no']);
                
                $stmt->bind_param("ssi", $name, $class, $roll);
                if ($stmt->execute()) {
                    $student_id = $conn->insert_id;
                    $student_mappings["$name|$class|$roll"] = $student_id;
                    echo "  -> Migrated Student: $name ($class, Roll: $roll) [ID: $student_id]\n";
                } else {
                    // Check if duplicate due to unique constraint, fetch existing
                    $dup_check = $conn->prepare("SELECT id FROM students WHERE class=? AND roll_no=?");
                    $dup_check->bind_param("si", $class, $roll);
                    $dup_check->execute();
                    $dup_res = $dup_check->get_result();
                    if ($dup_res && $dup_res->num_rows > 0) {
                        $student_id = $dup_res->fetch_assoc()['id'];
                        $student_mappings["$name|$class|$roll"] = $student_id;
                    }
                    $dup_check->close();
                }
            }
            $stmt->close();
        }
        
        // Fetch all old payments and insert into fee_payments_new
        $payment_result = $conn->query("SELECT * FROM fee_payments ORDER BY id ASC");
        if ($payment_result && $payment_result->num_rows > 0) {
            echo "[MIGRATION] Found " . $payment_result->num_rows . " payment records to migrate.\n";
            $pay_stmt = $conn->prepare("INSERT INTO fee_payments_new (id, student_id, fee_type, amount, payment_date, payment_method, status, remarks, receipt_no, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            while ($payment = $payment_result->fetch_assoc()) {
                $old_id = $payment['id'];
                $name = $payment['student_name'];
                $class = $payment['class'];
                $roll = $payment['roll_no'];
                $key = "$name|$class|$roll";
                
                if (!isset($student_mappings[$key])) {
                    echo "[WARNING] Student mapping not found for record ID: $old_id ($name). Skipping...\n";
                    continue;
                }
                
                $student_id = $student_mappings[$key];
                $fee_type = $payment['fee_type'];
                $amount = $payment['amount'];
                $pay_date = $payment['payment_date'];
                $method = $payment['payment_method'];
                $status = $payment['status'];
                $remarks = $payment['remarks'];
                $receipt_no = "REC-" . date('Ymd', strtotime($pay_date)) . "-" . str_pad($old_id, 4, '0', STR_PAD_LEFT);
                $created = $payment['created_at'];
                $updated = $payment['updated_at'];
                
                $pay_stmt->bind_param("iisssssssss", $old_id, $student_id, $fee_type, $amount, $pay_date, $method, $status, $remarks, $receipt_no, $created, $updated);
                $pay_stmt->execute();
            }
            $pay_stmt->close();
            echo "[MIGRATION] All payment records migrated successfully to temporary table.\n";
        }
        
        // Drop old table
        $conn->query("DROP TABLE fee_payments");
        echo "[MIGRATION] Old `fee_payments` table dropped.\n";
        
        // Rename new table to active
        $conn->query("RENAME TABLE fee_payments_new TO fee_payments");
        echo "[SUCCESS] Temporary table renamed to `fee_payments`.\n";
        
        $conn->commit();
        echo "[SUCCESS] Data migration completed and committed successfully!\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        $migration_successful = false;
        echo "[FATAL ERROR] Migration failed! Rolled back changes. Reason: " . $e->getMessage() . "\n";
    }
} else {
    // If table doesn't exist or is already upgraded, check if fee_payments table needs setup
    $check_table_new = $conn->query("SHOW TABLES LIKE 'fee_payments'");
    if (!$check_table_new || $check_table_new->num_rows == 0) {
        // Just rename fee_payments_new directly since there's no old data
        if ($conn->query("RENAME TABLE fee_payments_new TO fee_payments")) {
            echo "[INFO] New database created. Active table set to `fee_payments`.\n";
        } else {
            echo "[ERROR] Failed to set active table: " . $conn->error . "\n";
        }
    } else {
        // Clean up fee_payments_new if it was created and is redundant
        $conn->query("DROP TABLE IF EXISTS fee_payments_new");
        echo "[INFO] Database already upgraded. Skipping migration.\n";
    }
}

// 4. Seed Default Settings
$settings = [
    'school_name' => 'Greenwood International School',
    'school_address' => '456 Education Blvd, Knowledge City, State 56001',
    'school_phone' => '+91-9876543210',
    'school_email' => 'contact@greenwood.edu',
    'currency' => 'INR',
    'theme' => 'dark'
];

echo "[INFO] Seeding school settings...\n";
$set_stmt = $conn->prepare("INSERT INTO school_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
foreach ($settings as $key => $val) {
    $set_stmt->bind_param("sss", $key, $val, $val);
    $set_stmt->execute();
}
$set_stmt->close();
echo "[SUCCESS] School settings seeded.\n";

// 5. Seed Default Users
$users = [
    [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'full_name' => 'School Administrator',
        'email' => 'admin@greenwood.edu',
        'role' => 'Admin'
    ],
    [
        'username' => 'cashier',
        'password' => password_hash('cashier123', PASSWORD_BCRYPT),
        'full_name' => 'Junior Cashier',
        'email' => 'cashier@greenwood.edu',
        'role' => 'Cashier'
    ]
];

echo "[INFO] Seeding administrator and staff accounts...\n";
$usr_stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = ?");
foreach ($users as $user) {
    $usr_stmt->bind_param("ssssss", $user['username'], $user['password'], $user['full_name'], $user['email'], $user['role'], $user['full_name']);
    $usr_stmt->execute();
}
$usr_stmt->close();
echo "[SUCCESS] Staff accounts registered successfully.\n";

// 6. Seed Default Fee Structures
$fee_structures = [
    ['10-A', 'Tuition', 5000.00],
    ['10-A', 'Sports', 1500.00],
    ['10-A', 'Transport', 2000.00],
    ['10-A', 'Exam', 800.00],
    ['9-B', 'Tuition', 5000.00],
    ['9-B', 'Sports', 1500.00],
    ['9-B', 'Transport', 2000.00],
    ['9-B', 'Exam', 800.00],
    ['9-C', 'Tuition', 5000.00],
    ['9-C', 'Transport', 2000.00],
    ['9-A', 'Activity', 500.00],
    ['10-B', 'Exam', 800.00],
    ['10-C', 'Uniform', 1200.00]
];

echo "[INFO] Seeding standard class fee structures...\n";
$fee_stmt = $conn->prepare("INSERT INTO fee_structures (class, fee_type, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?");
foreach ($fee_structures as $fs) {
    $fee_stmt->bind_param("ssdd", $fs[0], $fs[1], $fs[2], $fs[2]);
    $fee_stmt->execute();
}
$fee_stmt->close();
echo "[SUCCESS] Standard fee structures defined.\n";

// Close connection
$conn->close();

echo "\n[INFO] Setup complete! System is ready to run.\n";
echo "</div>"; // end log-box

if ($migration_successful) {
    echo "<div class='success-banner'>
            <h3>🎉 Upgraded Successfully!</h3>
            <p>Your School Fee Management System has been fully initialized with a clean relational layout.</p>
            <p><strong>Initial Logins:</strong></p>
            <ul>
                <li><strong>Admin Panel:</strong> Username: <code>admin</code> | Password: <code>admin123</code></li>
                <li><strong>Cashier Panel:</strong> Username: <code>cashier</code> | Password: <code>cashier123</code></li>
            </ul>
          </div>
          <a href='login.php' class='btn'>🔑 Go to Login</a>";
} else {
    echo "<div class='error-banner'>
            <h3>⚠️ Migration Failed</h3>
            <p>An issue was encountered during database migration. Please review the terminal log output in the dark box above.</p>
          </div>";
}

echo "</div>
<script>
    var log = document.getElementById('log');
    log.scrollTop = log.scrollHeight;
</script>
</body>
</html>";
?>

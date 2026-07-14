<?php
/**
 * Delete Student Page
 * ===================
 * Confirms and executes deletion of student record.
 * Handles cascading removal of all related transactions to prevent DB inconsistencies.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authorization (Admin Only)
check_auth('Admin');

$page_title = "Delete Student";
$success = false;
$error = '';
$student_data = null;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// Get student ID
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id > 0) {
    // Fetch details first
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
    } else {
        $error = "❌ Student record not found!";
    }
    $stmt->close();
} else {
    $error = "❌ Invalid student ID!";
}

if ($action === 'confirm' && $student_id > 0 && $student_data && !$error) {
    $conn->begin_transaction();
    try {
        // Prepare delete payments query
        $del_pay = $conn->prepare("DELETE FROM fee_payments WHERE student_id = ?");
        $del_pay->bind_param("i", $student_id);
        $del_pay->execute();
        $del_pay->close();

        // Prepare delete student query
        $del_stud = $conn->prepare("DELETE FROM students WHERE id = ?");
        $del_stud->bind_param("i", $student_id);
        $del_stud->execute();
        $del_stud->close();

        $conn->commit();
        $_SESSION['flash_success'] = "🗑️ Student and all their associated fee payments have been deleted permanently.";
        header("Location: students.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Deletion failed: " . $e->getMessage();
    }
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Delete Student Profile</h2>
        <p>Permanently remove student account and balance ledger from the active directory.</p>
    </div>
</div>

<div class="form-outer-wrapper" style="max-width: 600px; margin: 40px auto;">
    <?php if ($error && !$student_data): ?>
        <div class="alert alert-error">
            <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
            <span class="alert-message"><?php echo $error; ?></span>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="students.php" class="btn btn-secondary">← Back to Directory</a>
        </div>
    <?php else: ?>
        <div class="confirmation-card-modern glass-card border-danger">
            <div class="conf-title-row">
                <span class="conf-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <h3>Confirm Deletion</h3>
            </div>
            
            <p class="confirmation-text">
                Are you sure you want to permanently delete student <strong><?php echo htmlspecialchars($student_data['student_name']); ?></strong>? 
                This action is irreversible and will delete all their transaction records!
            </p>
            
            <div class="payment-summary">
                <table class="summary-table">
                    <tr>
                        <td><strong>Student ID:</strong></td>
                        <td>#<?php echo $student_data['id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td><?php echo htmlspecialchars($student_data['student_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Class Grade:</strong></td>
                        <td>Class <?php echo htmlspecialchars($student_data['class']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Roll Number:</strong></td>
                        <td>Roll #<?php echo htmlspecialchars($student_data['roll_no']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="confirmation-buttons">
                <a href="delete_student.php?id=<?php echo $student_id; ?>&action=confirm" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Yes, Delete Account</a>
                <a href="students.php" class="btn btn-secondary">✗ Cancel</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include Footer
include 'footer.php';
?>

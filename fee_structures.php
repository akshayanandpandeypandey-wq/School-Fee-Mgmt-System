<?php
/**
 * Class-wise Fee Structure Management
 * ===================================
 * Configures the standard fee schedules assigned to classes.
 * Enabled for Administrator accounts only.
 */

// Include DB connection
include 'db_connection.php';

// Enforce Admin auth
check_auth('Admin');

$page_title = "Standard Fee Structures";
$error = '';
$success = '';

// Edit Mode Variables
$edit_id = 0;
$form_class = '';
$form_fee_type = '';
$form_amount = '';

// 1. Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    if ($del_id > 0) {
        $del_stmt = $conn->prepare("DELETE FROM fee_structures WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            $_SESSION['flash_success'] = "🗑️ Fee structure deleted successfully.";
            header("Location: fee_structures.php");
            exit;
        } else {
            $error = "❌ Failed to delete record: " . $del_stmt->error;
        }
        $del_stmt->close();
    }
}

// 2. Handle Edit Setup Action
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    if ($edit_id > 0) {
        $edit_stmt = $conn->prepare("SELECT * FROM fee_structures WHERE id = ?");
        $edit_stmt->bind_param("i", $edit_id);
        $edit_stmt->execute();
        $edit_res = $edit_stmt->get_result();
        if ($edit_res && $edit_res->num_rows > 0) {
            $fs_edit = $edit_res->fetch_assoc();
            $form_class = $fs_edit['class'];
            $form_fee_type = $fs_edit['fee_type'];
            $form_amount = $fs_edit['amount'];
        }
        $edit_stmt->close();
    }
}

// 3. Handle Form Submission (Save/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submit_id = intval($_POST['id'] ?? 0);
    $class = trim($_POST['class'] ?? '');
    $fee_type = trim($_POST['fee_type'] ?? '');
    $amount = $_POST['amount'] ?? '';

    if (empty($class) || empty($fee_type) || empty($amount)) {
        $error = "❌ All fields are required!";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "❌ Amount must be a positive number!";
    } else {
        if ($submit_id > 0) {
            // Update existing
            $up_stmt = $conn->prepare("UPDATE fee_structures SET class = ?, fee_type = ?, amount = ? WHERE id = ?");
            $up_stmt->bind_param("ssdi", $class, $fee_type, $amount, $submit_id);
            if ($up_stmt->execute()) {
                $_SESSION['flash_success'] = "🎉 Fee structure updated successfully!";
                header("Location: fee_structures.php");
                exit;
            } else {
                $error = "❌ Error updating record: " . $up_stmt->error;
            }
            $up_stmt->close();
        } else {
            // Insert or update on duplicate key (class + fee_type is unique key)
            $in_stmt = $conn->prepare("INSERT INTO fee_structures (class, fee_type, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?");
            $in_stmt->bind_param("ssdd", $class, $fee_type, $amount, $amount);
            if ($in_stmt->execute()) {
                $_SESSION['flash_success'] = "🎉 Fee structure saved successfully!";
                header("Location: fee_structures.php");
                exit;
            } else {
                $error = "❌ Error saving record: " . $in_stmt->error;
            }
            $in_stmt->close();
        }
    }
}

// 4. Fetch all standard fee structures
$list_stmt = $conn->prepare("SELECT * FROM fee_structures ORDER BY class ASC, fee_type ASC");
$list_stmt->execute();
$fee_structures = $list_stmt->get_result();
$total_structures = $fee_structures->num_rows;
$list_stmt->close();

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Standard Fee Structures</h2>
        <p>Define global syllabus-wise, class-wise payment categories and rates to automate balance calculations.</p>
    </div>
</div>

<div class="split-layout-container">
    <!-- Left Column: Manage/Save Form -->
    <div class="split-left-column">
        <div class="form-container glass-card sticky-sidebar">
            <h3><i class="fa-solid fa-square-plus text-primary"></i> <?php echo ($edit_id > 0) ? 'Edit Structure' : 'Create Structure'; ?></h3>
            <p class="form-intro-text">Assign standard amounts to grade levels.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <span class="alert-message"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="fee_structures.php" class="form-modern">
                <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                
                <div class="form-group-modern">
                    <label for="class">Class / Grade <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-school"></i>
                        <input type="text" id="class" name="class" placeholder="e.g. 10-A, 9-B" value="<?php echo htmlspecialchars($form_class); ?>" required>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="fee_type">Fee Category <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-list"></i>
                        <select id="fee_type" name="fee_type" class="select-modern" required>
                            <option value="">-- Select Category --</option>
                            <option value="Tuition" <?php echo ($form_fee_type == 'Tuition') ? 'selected' : ''; ?>>Tuition</option>
                            <option value="Sports" <?php echo ($form_fee_type == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                            <option value="Transport" <?php echo ($form_fee_type == 'Transport') ? 'selected' : ''; ?>>Transport</option>
                            <option value="Exam" <?php echo ($form_fee_type == 'Exam') ? 'selected' : ''; ?>>Exam</option>
                            <option value="Activity" <?php echo ($form_fee_type == 'Activity') ? 'selected' : ''; ?>>Activity</option>
                            <option value="Uniform" <?php echo ($form_fee_type == 'Uniform') ? 'selected' : ''; ?>>Uniform</option>
                            <option value="Library" <?php echo ($form_fee_type == 'Library') ? 'selected' : ''; ?>>Library</option>
                            <option value="Other" <?php echo ($form_fee_type == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-modern">
                    <label for="amount">Standard Amount <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-coins"></i>
                        <input type="number" id="amount" name="amount" placeholder="0.00" value="<?php echo htmlspecialchars($form_amount); ?>" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary btn-full"><i class="fa-solid fa-save"></i> Save Structure</button>
                    <?php if ($edit_id > 0): ?>
                        <a href="fee_structures.php" class="btn btn-secondary btn-full">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Database Records List -->
    <div class="split-right-column">
        <div class="records-card glass-card">
            <div class="container-header-row">
                <h4><i class="fa-solid fa-table-list text-success"></i> Configured Schedules</h4>
                <span class="total-results-count">Total defined: <strong><?php echo $total_structures; ?></strong> records</span>
            </div>
            
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class</th>
                            <th>Fee Category</th>
                            <th>Schedules Rate</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_structures > 0): ?>
                            <?php while ($row = $fee_structures->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><span class="badge-class">Class <?php echo htmlspecialchars($row['class']); ?></span></td>
                                    <td><span class="badge-fee-type"><?php echo htmlspecialchars($row['fee_type']); ?></span></td>
                                    <td><strong><?php echo format_amount($row['amount']); ?></strong></td>
                                    <td>
                                        <div class="action-buttons-group">
                                            <a href="fee_structures.php?action=edit&id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit Rate"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <a href="fee_structures.php?action=delete&id=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete Schedule" onclick="return confirm('Are you sure you want to delete this fee structure? This will recalculate balances for all students in Class <?php echo htmlspecialchars(addslashes($row['class'])); ?>!')"><i class="fa-solid fa-trash-can"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-records-cell">
                                    <i class="fa-solid fa-list-check empty-icon"></i>
                                    <p>No class fee schedules configured yet. Use the profile form to assign standard rates.</p>
                                </td>
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

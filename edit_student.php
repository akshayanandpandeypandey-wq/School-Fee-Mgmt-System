<?php
/**
 * Edit Student Profile Page
 * ========================
 * Modifies an existing student's record and metadata.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authentication
check_auth();

$page_title = "Edit Student Profile";
$success = false;
$error = '';
$student_data = null;

// Get student ID
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id > 0) {
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $student_data && !$error) {
    $student_name = trim($_POST['student_name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    // Validation
    if (empty($student_name) || empty($class) || empty($roll_no)) {
        $error = "❌ Student Name, Class, and Roll Number are required!";
    } elseif (!is_numeric($roll_no) || intval($roll_no) <= 0) {
        $error = "❌ Roll number must be a valid positive integer!";
    } else {
        // Check if unique class + roll_no exists for other students
        $dup_stmt = $conn->prepare("SELECT id FROM students WHERE class = ? AND roll_no = ? AND id != ?");
        $dup_stmt->bind_param("sii", $class, $roll_no, $student_id);
        $dup_stmt->execute();
        $dup_result = $dup_stmt->get_result();
        
        if ($dup_result && $dup_result->num_rows > 0) {
            $error = "❌ A student with Roll No. $roll_no is already registered in Class $class!";
            $dup_stmt->close();
        } else {
            $dup_stmt->close();
            
            // Validate discount
            if ($discount_percent < 0 || $discount_percent > 100) {
                $error = "❌ Scholarship discount must be between 0% and 100%!";
            } else {
                // Update student
                $update_stmt = $conn->prepare("UPDATE students SET student_name = ?, class = ?, roll_no = ?, email = ?, parent_name = ?, parent_phone = ?, discount_percent = ?, status = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ssisssisi", $student_name, $class, $roll_no, $email, $parent_name, $parent_phone, $discount_percent, $status, $student_id);
                    if ($update_stmt->execute()) {
                        $_SESSION['flash_success'] = "🎉 Student profile updated successfully!";
                        header("Location: students.php");
                        exit;
                    } else {
                        $error = "❌ Database execution error: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                } else {
                    $error = "❌ Database query preparation error: " . $conn->error;
                }
            }
        }
    }
}

// Include Header
include 'header.php';
?>

<div class="dashboard-header-block">
    <div class="welcome-banner">
        <h2>Edit Student Profile</h2>
        <p>Modify the enrollment profile, class grade, roll assignment, and contact details of the scholar.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="students.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Student Directory</a>
    </div>
</div>

<div class="form-outer-wrapper">
    <div class="form-container glass-card">
        <h3><i class="fa-solid fa-user-pen text-warning"></i> Modify Profile Details</h3>
        
        <?php if ($error && !$student_data): ?>
            <div class="alert alert-error">
                <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                <span class="alert-message"><?php echo $error; ?></span>
            </div>
            <a href="students.php" class="btn btn-secondary">← Back to Directory</a>
        <?php else: ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                    <span class="alert-message"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-modern">
                <div class="form-row-modern">
                    <!-- Name Field -->
                    <div class="form-group-modern">
                        <label for="student_name">Student Name <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" id="student_name" name="student_name" placeholder="Enter student's full name" value="<?php echo htmlspecialchars($student_data['student_name']); ?>" required>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group-modern">
                        <label for="email">Student Email Address</label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="student@example.com" value="<?php echo htmlspecialchars($student_data['email']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Class Field -->
                    <div class="form-group-modern">
                        <label for="class">Class / Grade <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-school"></i>
                            <input type="text" id="class" name="class" placeholder="e.g., 10-A, 9-B" value="<?php echo htmlspecialchars($student_data['class']); ?>" required>
                        </div>
                    </div>

                    <!-- Roll Number Field -->
                    <div class="form-group-modern">
                        <label for="roll_no">Roll Number <span class="required">*</span></label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-arrow-down-1-9"></i>
                            <input type="number" id="roll_no" name="roll_no" placeholder="e.g., 12" value="<?php echo htmlspecialchars($student_data['roll_no']); ?>" min="1" required>
                        </div>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Parent Name -->
                    <div class="form-group-modern">
                        <label for="parent_name">Parent / Guardian Name</label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-person-breastfeeding"></i>
                            <input type="text" id="parent_name" name="parent_name" placeholder="Parent or guardian full name" value="<?php echo htmlspecialchars($student_data['parent_name']); ?>">
                        </div>
                    </div>

                    <!-- Parent Phone -->
                    <div class="form-group-modern">
                        <label for="parent_phone">Parent Phone Contact</label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" id="parent_phone" name="parent_phone" placeholder="e.g., +91-XXXXXXXXXX" value="<?php echo htmlspecialchars($student_data['parent_phone']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row-modern">
                    <!-- Scholarship Discount -->
                    <div class="form-group-modern">
                        <label for="discount_percent">Scholarship Discount (%)</label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-percent"></i>
                            <input type="number" id="discount_percent" name="discount_percent" placeholder="e.g. 10" value="<?php echo htmlspecialchars($student_data['discount_percent']); ?>" min="0" max="100">
                        </div>
                    </div>

                    <!-- Status Field -->
                    <div class="form-group-modern">
                        <label for="status">Enrollment Status</label>
                        <div class="input-wrapper-modern">
                            <i class="fa-solid fa-circle-question"></i>
                            <select id="status" name="status" class="select-modern">
                                <option value="Active" <?php echo ($student_data['status'] === 'Active') ? 'selected' : ''; ?>>Active (Enrolled)</option>
                                <option value="Inactive" <?php echo ($student_data['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive (Withdrawn/Suspended)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="form-buttons-modern">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
                    <a href="students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
// Include Footer
include 'footer.php';
?>

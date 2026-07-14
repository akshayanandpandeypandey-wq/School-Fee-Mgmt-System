<?php
/**
 * Add Student Page
 * ================
 * Registers a new student account inside the school directory.
 */

// Include DB connection
include 'db_connection.php';

// Enforce authentication
check_auth();

$page_title = "Register Student";
$success = false;
$error = '';

$student_name = '';
$class = '';
$roll_no = '';
$email = '';
$parent_name = '';
$parent_phone = '';
$discount_percent = 0;
$status = 'Active';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        // Check if unique class + roll_no exists
        $dup_stmt = $conn->prepare("SELECT id FROM students WHERE class = ? AND roll_no = ?");
        $dup_stmt->bind_param("si", $class, $roll_no);
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
                // Insert student
                $stmt = $conn->prepare("INSERT INTO students (student_name, class, roll_no, email, parent_name, parent_phone, discount_percent, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssisssis", $student_name, $class, $roll_no, $email, $parent_name, $parent_phone, $discount_percent, $status);
                    if ($stmt->execute()) {
                        $success = true;
                        $_SESSION['flash_success'] = "🎉 Student registered successfully!";
                        header("Location: students.php");
                        exit;
                    } else {
                        $error = "❌ Database execution error: " . $stmt->error;
                    }
                    $stmt->close();
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
        <h2>Register New Student</h2>
        <p>Enroll a new scholar by inputting their profile, grade class, and guardian contact details.</p>
    </div>
    
    <div class="quick-nav-actions">
        <a href="students.php" class="btn-action-secondary"><i class="fa-solid fa-arrow-left"></i> Student Directory</a>
    </div>
</div>

<div class="form-outer-wrapper">
    <div class="form-container glass-card">
        <h3><i class="fa-solid fa-user-plus text-primary"></i> Registration Profile</h3>
        <p class="form-intro-text">Fill in the fields below. Required values are marked with <span class="required">*</span>.</p>
        
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
                        <input type="text" id="student_name" name="student_name" placeholder="Enter student's full name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                    </div>
                </div>

                <!-- Email Field -->
                <div class="form-group-modern">
                    <label for="email">Student Email Address</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="student@example.com" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                </div>
            </div>

            <div class="form-row-modern">
                <!-- Class Field -->
                <div class="form-group-modern">
                    <label for="class">Class / Grade <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-school"></i>
                        <input type="text" id="class" name="class" placeholder="e.g., 10-A, 9-B" value="<?php echo htmlspecialchars($class); ?>" required>
                    </div>
                    <small class="field-hint">Specify the grade and section (e.g. 10-A, 9-B)</small>
                </div>

                <!-- Roll Number Field -->
                <div class="form-group-modern">
                    <label for="roll_no">Roll Number <span class="required">*</span></label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-arrow-down-1-9"></i>
                        <input type="number" id="roll_no" name="roll_no" placeholder="e.g., 12" value="<?php echo htmlspecialchars($roll_no); ?>" min="1" required>
                    </div>
                </div>
            </div>

            <div class="form-row-modern">
                <!-- Parent Name -->
                <div class="form-group-modern">
                    <label for="parent_name">Parent / Guardian Name</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-person-breastfeeding"></i>
                        <input type="text" id="parent_name" name="parent_name" placeholder="Parent or guardian full name" value="<?php echo htmlspecialchars($parent_name); ?>">
                    </div>
                </div>

                <!-- Parent Phone -->
                <div class="form-group-modern">
                    <label for="parent_phone">Parent Phone Contact</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" id="parent_phone" name="parent_phone" placeholder="e.g., +91-XXXXXXXXXX" value="<?php echo htmlspecialchars($parent_phone); ?>">
                    </div>
                </div>
            </div>

            <div class="form-row-modern">
                <!-- Scholarship Discount -->
                <div class="form-group-modern">
                    <label for="discount_percent">Scholarship Discount (%)</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-percent"></i>
                        <input type="number" id="discount_percent" name="discount_percent" placeholder="e.g. 10" value="<?php echo htmlspecialchars($discount_percent); ?>" min="0" max="100">
                    </div>
                </div>

                <!-- Status Field -->
                <div class="form-group-modern">
                    <label for="status">Enrollment Status</label>
                    <div class="input-wrapper-modern">
                        <i class="fa-solid fa-circle-question"></i>
                        <select id="status" name="status" class="select-modern">
                            <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active (Enrolled)</option>
                            <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive (Withdrawn/Suspended)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="form-buttons-modern">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Register Scholar</button>
                <a href="students.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Include Footer
include 'footer.php';
?>

<?php
/**
 * User Login Page
 * ================
 * Authenticates users (Admins & Cashiers) using password verification.
 */

// Include database connection (which starts session)
include 'db_connection.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please fill in both fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password hash
                if (password_verify($password, $user['password'])) {
                    // Start Session and save user data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $error = "Database query error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Fee Management</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a, #1e293b, #334155);
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --input-bg: rgba(15, 23, 42, 0.6);
            --error-color: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-color);
            overflow: hidden;
            position: relative;
        }

        /* Ambient background glow */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            z-index: 1;
        }
        body::after {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            bottom: -150px;
            left: -150px;
            z-index: 1;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            z-index: 10;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .logo-area {
            margin-bottom: 30px;
        }

        .logo-icon {
            font-size: 40px;
            background: linear-gradient(135deg, #3b82f6, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .logo-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-color);
            font-family: inherit;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border-left: 4px solid var(--error-color);
            padding: 12px 15px;
            border-radius: 8px;
            color: #fca5a5;
            font-size: 14px;
            margin-bottom: 25px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border: none;
            border-radius: 12px;
            color: white;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-note {
            margin-top: 25px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-note a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-area">
            <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <h1 class="logo-title">Greenwood</h1>
            <p class="logo-subtitle">School Fee Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <p class="footer-note">
            Default credentials: Admin (<code>admin</code>/<code>admin123</code>)
        </p>
    </div>
</div>

</body>
</html>

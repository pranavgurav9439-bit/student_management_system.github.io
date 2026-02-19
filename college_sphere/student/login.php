<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_number = sanitize_input($_POST['roll_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($roll_number) || empty($password)) {
        $error = 'Please enter both Roll Number and Password';
    } else {
        // Fetch student details
        $query = "
            SELECT s.*, c.class_name, c.section, d.dept_name
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.class_id
            LEFT JOIN departments d ON s.dept_id = d.dept_id
            WHERE s.roll_number = '{$roll_number}'
            AND s.status = 'Active'
            LIMIT 1
        ";
        
        $student = get_row($query);
        
        if ($student && password_verify($password, $student['password'])) {
            // Successful login
            $_SESSION['student_id'] = $student['student_id'];
            $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['student_email'] = $student['email'];
            $_SESSION['student_roll'] = $student['roll_number'];
            $_SESSION['class_id'] = $student['class_id'];
            $_SESSION['dept_id'] = $student['dept_id'];
            $_SESSION['class_name'] = $student['class_name'] . ' - ' . $student['section'];
            
            // Update last login
            $update_login = "UPDATE students SET last_login = NOW() WHERE student_id = {$student['student_id']}";
            execute_query($update_login);
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid Roll Number or Password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - CollegeSphere</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --student-primary: #3b82f6;
            --student-primary-dark: #2563eb;
            --student-primary-light: #60a5fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-dark) 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 28px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control {
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--student-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #64748b;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #94a3b8;
            font-size: 13px;
        }
        
        .signup-link, .back-to-home {
            text-align: center;
            margin-top: 15px;
        }
        
        .signup-link a, .back-to-home a {
            color: var(--student-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .signup-link a:hover, .back-to-home a:hover {
            color: var(--student-primary-dark);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            z-index: 10;
        }
        
        .demo-info {
            background: #f0f9ff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid var(--student-primary);
        }
        
        .demo-info h6 {
            color: var(--student-primary-dark);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .demo-info p {
            margin: 5px 0;
            font-size: 13px;
            color: #475569;
        }
        
        .demo-info code {
            background: white;
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--student-primary-dark);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-graduate"></i>
                <h2>Student Portal</h2>
                <p>Sign in to access your dashboard</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="roll_number" class="form-label">
                            <i class="fas fa-id-card me-1"></i> Roll Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="roll_number" 
                                name="roll_number" 
                                placeholder="Enter your Roll Number"
                                value="<?php echo htmlspecialchars($_POST['roll_number'] ?? ''); ?>"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Password
                        </label>
                        <div class="input-group position-relative">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>
                
                <!-- Demo Credentials Info -->
                <div class="demo-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Demo Login</h6>
                    <p><strong>Roll Number:</strong> <code>BCA2024001</code></p>
                    <p><strong>Password:</strong> <code>student123</code></p>
                    <p style="font-size: 12px; margin-top: 8px; color: #64748b;">
                        <i class="fas fa-lock me-1"></i> Change your password after first login
                    </p>
                </div>
                
                <div class="divider">
                    <span>New Student?</span>
                </div>
                
                <div class="signup-link">
                    <a href="signup.php">
                        <i class="fas fa-user-plus me-1"></i> Create Account
                    </a>
                </div>
                
                <div class="back-to-home">
                    <a href="../index.html">
                        <i class="fas fa-home me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small style="color: rgba(255,255,255,0.8);">
                © 2026 CollegeSphere. All rights reserved.
            </small>
        </div>
    </div>

    <script>
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>
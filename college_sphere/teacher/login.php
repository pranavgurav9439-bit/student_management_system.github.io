<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['teacher_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = sanitize_input($_POST['employee_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($employee_id) || empty($password)) {
        $error = 'Please enter both Employee ID and Password';
    } else {
        // Fetch teacher details
        $query = "
            SELECT teacher_id, first_name, last_name, email, password, dept_id, employee_id
            FROM teachers 
            WHERE employee_id = '{$employee_id}'
            AND status = 'Active'
            LIMIT 1
        ";
        
        $teacher = get_row($query);
        
        if ($teacher && password_verify($password, $teacher['password'])) {
            // Successful login
            $_SESSION['teacher_id'] = $teacher['teacher_id'];
            $_SESSION['teacher_name'] = $teacher['first_name'] . ' ' . $teacher['last_name'];
            $_SESSION['teacher_email'] = $teacher['email'];
            $_SESSION['dept_id'] = $teacher['dept_id'];
            $_SESSION['employee_id'] = $teacher['employee_id'];
            
            // Update last login
            $update_login = "UPDATE teachers SET last_login = NOW() WHERE teacher_id = {$teacher['teacher_id']}";
            execute_query($update_login);
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid Employee ID or Password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login - CollegeSphere</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --teacher-primary: #10b981;
            --teacher-primary-dark: #059669;
            --teacher-primary-light: #34d399;
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
            background: linear-gradient(135deg, var(--teacher-primary) 0%, var(--teacher-primary-dark) 100%);
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
            border-color: var(--teacher-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
            background: linear-gradient(135deg, var(--teacher-primary) 0%, var(--teacher-primary-dark) 100%);
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
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
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
        
        .back-to-admin {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-admin a {
            color: var(--teacher-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-to-admin a:hover {
            color: var(--teacher-primary-dark);
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
        
        .remember-me {
            font-size: 14px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-chalkboard-teacher"></i>
                <h2>Teacher Portal</h2>
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
                        <label for="employee_id" class="form-label">
                            <i class="fas fa-id-badge me-1"></i> Employee ID
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="employee_id" 
                                name="employee_id" 
                                placeholder="Enter your Employee ID"
                                value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>"
                                required
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
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label remember-me" for="remember">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>
                
                <div class="divider">
                    <span>or</span>
                </div>
                
                <div class="back-to-admin">
                    <a href="../admin/login.php">
                        <i class="fas fa-arrow-left me-1"></i> Back to Admin Login
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small style="color: rgba(255,255,255,0.8);">
                © 2024 CollegeSphere. All rights reserved.
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
        
        // Auto-focus on employee_id field
        document.getElementById('employee_id').focus();
    </script>

</body>
</html>
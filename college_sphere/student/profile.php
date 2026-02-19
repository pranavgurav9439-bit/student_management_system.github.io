<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit; }
$student_id = $_SESSION['student_id'];
$page_title = "My Profile";
$page_subtitle = "View and update your personal information";
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $pincode = sanitize_input($_POST['pincode']);
    
    $update = "UPDATE students SET phone='$phone', address='$address', city='$city', 
                state='$state', pincode='$pincode' WHERE student_id=$student_id";
    if (execute_query($update)) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $student = get_row("SELECT password FROM students WHERE student_id=$student_id");
    
    if (!password_verify($current_password, $student['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        if (execute_query("UPDATE students SET password='$hashed' WHERE student_id=$student_id")) {
            $success = "Password changed successfully!";
        }
    }
}

$student = get_row("SELECT s.*, c.class_name, c.section, d.dept_name FROM students s 
    LEFT JOIN classes c ON s.class_id=c.class_id 
    LEFT JOIN departments d ON s.dept_id=d.dept_id 
    WHERE s.student_id=$student_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f8fafc; }
        .main-wrapper { display:flex; min-height:100vh; }
        .content-wrapper { flex:1; margin-left:280px; }
        .main-content { padding:30px; }
        .card { background:white; border-radius:15px; padding:30px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .card-title { font-size:20px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .profile-header { text-align:center; padding:30px; background:linear-gradient(135deg,#3b82f6,#2563eb); color:white; border-radius:15px; margin-bottom:30px; }
        .avatar-large { width:100px; height:100px; background:white; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:48px; color:#3b82f6; margin-bottom:15px; }
        .form-label { font-weight:600; color:#334155; margin-bottom:8px; font-size:14px; }
        .form-control { padding:12px 16px; border-radius:10px; border:2px solid #e2e8f0; }
        .btn-save { background:linear-gradient(135deg,#3b82f6,#2563eb); color:white; border:none; padding:12px 30px; border-radius:10px; font-weight:600; }
        @media (max-width:768px) { .content-wrapper { margin-left:0; } }
    </style>
</head>
<body>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        <main class="main-content">
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="profile-header">
                <div class="avatar-large"><i class="fas fa-user-graduate"></i></div>
                <h2><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></h2>
                <p><?php echo htmlspecialchars($student['roll_number']); ?></p>
                <p><?php echo htmlspecialchars($student['class_name'].' - '.$student['section']); ?></p>
            </div>
            
            <div class="card">
                <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['first_name']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['last_name']); ?>" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']??''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address']??''); ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($student['city']??''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($student['state']??''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars($student['pincode']??''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-save">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h3 class="card-title"><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-save">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
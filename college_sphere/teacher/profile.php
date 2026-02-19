<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "My Profile";
$page_subtitle = "Manage your personal information and settings";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $pincode = sanitize_input($_POST['pincode']);
    
    $update_query = "
        UPDATE teachers 
        SET phone = '{$phone}',
            address = '{$address}',
            city = '{$city}',
            state = '{$state}',
            pincode = '{$pincode}',
            updated_at = NOW()
        WHERE teacher_id = {$teacher_id}
    ";
    
    if (execute_query($update_query)) {
        $success_message = "Profile updated successfully!";
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Fetch current password
    $password_query = "SELECT password FROM teachers WHERE teacher_id = {$teacher_id}";
    $teacher_data = get_row($password_query);
    
    if ($teacher_data && password_verify($current_password, $teacher_data['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_query = "
                    UPDATE teachers 
                    SET password = '{$hashed_password}',
                        updated_at = NOW()
                    WHERE teacher_id = {$teacher_id}
                ";
                
                if (execute_query($update_password_query)) {
                    $password_success = "Password changed successfully!";
                } else {
                    $password_error = "Failed to update password. Please try again.";
                }
            } else {
                $password_error = "New password must be at least 6 characters long.";
            }
        } else {
            $password_error = "New passwords do not match.";
        }
    } else {
        $password_error = "Current password is incorrect.";
    }
}

// Fetch teacher profile details
$profile_query = "
    SELECT 
        t.teacher_id,
        t.employee_id,
        t.first_name,
        t.last_name,
        t.email,
        t.phone,
        t.date_of_birth,
        t.gender,
        t.address,
        t.city,
        t.state,
        t.pincode,
        t.designation,
        t.qualification,
        t.experience_years,
        t.joining_date,
        t.status,
        d.dept_name,
        d.dept_code
    FROM teachers t
    LEFT JOIN departments d ON t.dept_id = d.dept_id
    WHERE t.teacher_id = {$teacher_id}
";
$profile = get_row($profile_query);

// Fetch assigned classes and subjects
$assignments_query = "
    SELECT 
        c.class_name,
        c.section,
        s.subject_name,
        s.subject_code,
        tca.is_class_teacher
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.class_id
    JOIN subjects s ON tca.subject_id = s.subject_id
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
    ORDER BY c.class_name, c.section
";
$assignments = get_all($assignments_query);

// Calculate teaching statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT tca.class_id) as total_classes,
        COUNT(DISTINCT tca.subject_id) as total_subjects,
        COUNT(DISTINCT s.student_id) as total_students
    FROM teacher_class_assignments tca
    LEFT JOIN students s ON tca.class_id = s.class_id AND s.status = 'Active'
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
";
$stats = get_row($stats_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: var(--teacher-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            color: #1f2937;
            font-weight: 500;
        }
        
        .stat-box-mini {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e5e7eb;
        }
        
        .stat-box-mini h4 {
            font-size: 32px;
            font-weight: 700;
            color: var(--teacher-primary);
            margin: 8px 0;
        }
        
        .stat-box-mini p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .assignment-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .class-teacher-badge {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper">
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($password_success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $password_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($password_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $password_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <div class="profile-avatar mx-auto">
                            <?php echo strtoupper(substr($profile['first_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h2 class="mb-2">
                            <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </h2>
                        <p class="mb-1">
                            <i class="fas fa-id-badge me-2"></i>
                            Employee ID: <?php echo htmlspecialchars($profile['employee_id']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-briefcase me-2"></i>
                            <?php echo htmlspecialchars($profile['designation']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-building me-2"></i>
                            <?php echo htmlspecialchars($profile['dept_name']); ?>
                        </p>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-<?php echo $profile['status'] == 'Active' ? 'success' : 'secondary'; ?> fs-6 px-3 py-2">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            <?php echo htmlspecialchars($profile['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-box-mini">
                        <i class="fas fa-chalkboard-teacher fa-2x text-success mb-2"></i>
                        <h4><?php echo $stats['total_classes']; ?></h4>
                        <p>Assigned Classes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box-mini">
                        <i class="fas fa-book fa-2x text-primary mb-2"></i>
                        <h4><?php echo $stats['total_subjects']; ?></h4>
                        <p>Teaching Subjects</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box-mini">
                        <i class="fas fa-user-graduate fa-2x text-warning mb-2"></i>
                        <h4><?php echo $stats['total_students']; ?></h4>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-6">
                    
                    <!-- Personal Information -->
                    <div class="profile-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary me-2"></i>
                                Personal Information
                            </h5>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['gender']); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value">
                                <?php echo $profile['date_of_birth'] ? date('M d, Y', strtotime($profile['date_of_birth'])) : 'Not provided'; ?>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['address'] ?? 'Not provided'); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">City, State</span>
                            <span class="info-value">
                                <?php echo htmlspecialchars(($profile['city'] ?? '') . ', ' . ($profile['state'] ?? 'Not provided')); ?>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Pincode</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['pincode'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Professional Information -->
                    <div class="profile-card">
                        <h5 class="mb-3">
                            <i class="fas fa-briefcase text-success me-2"></i>
                            Professional Information
                        </h5>
                        
                        <div class="info-row">
                            <span class="info-label">Qualification</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['qualification'] ?? 'Not provided'); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Experience</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['experience_years']); ?> years</span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Joining Date</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($profile['joining_date'])); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="info-label">Department</span>
                            <span class="info-value"><?php echo htmlspecialchars($profile['dept_name']); ?></span>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-6">
                    
                    <!-- Class Assignments -->
                    <div class="profile-card">
                        <h5 class="mb-3">
                            <i class="fas fa-chalkboard text-warning me-2"></i>
                            Current Assignments (2024-2025)
                        </h5>
                        
                        <?php if (count($assignments) > 0): ?>
                            <?php foreach ($assignments as $assignment): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($assignment['class_name'] . '-' . $assignment['section']); ?>
                                        </h6>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-book me-1"></i>
                                            <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                            <small>(<?php echo htmlspecialchars($assignment['subject_code']); ?>)</small>
                                        </p>
                                    </div>
                                    <?php if ($assignment['is_class_teacher']): ?>
                                    <span class="assignment-badge class-teacher-badge">
                                        <i class="fas fa-star me-1"></i>
                                        Class Teacher
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No assignments for current academic year.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Security Settings -->
                    <div class="profile-card">
                        <h5 class="mb-3">
                            <i class="fas fa-shield-alt text-danger me-2"></i>
                            Security Settings
                        </h5>
                        
                        <p class="text-muted mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Keep your account secure by using a strong password.
                        </p>
                        
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>
                            Change Password
                        </button>
                    </div>
                    
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>
    
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You can only update contact and address information. Contact admin for other changes.
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"
                                       placeholder="Enter phone number">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Pincode</label>
                                <input type="text" class="form-control" name="pincode" 
                                       value="<?php echo htmlspecialchars($profile['pincode'] ?? ''); ?>"
                                       placeholder="Enter pincode" maxlength="10">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Address</label>
                                <textarea class="form-control" name="address" rows="3" 
                                          placeholder="Enter your address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">City</label>
                                <input type="text" class="form-control" name="city" 
                                       value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>"
                                       placeholder="Enter city">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">State</label>
                                <input type="text" class="form-control" name="state" 
                                       value="<?php echo htmlspecialchars($profile['state'] ?? ''); ?>"
                                       placeholder="Enter state">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Password must be at least 6 characters long.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="current_password" 
                                   placeholder="Enter current password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="new_password" 
                                   placeholder="Enter new password" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" 
                                   placeholder="Re-enter new password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="change_password" class="btn btn-danger">
                            <i class="fas fa-shield-alt me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>

</body>
</html>
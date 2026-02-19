<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'update_setting':
            $setting_key = sanitize_input($_POST['setting_key']);
            $setting_value = sanitize_input($_POST['setting_value']);
            
            // Check if setting exists
            $check_query = "SELECT setting_id FROM settings WHERE setting_key = '$setting_key'";
            $existing = get_row($check_query);
            
            if ($existing) {
                $query = "UPDATE settings SET setting_value = '$setting_value', updated_at = NOW() WHERE setting_key = '$setting_key'";
            } else {
                $query = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('$setting_key', '$setting_value', 'text')";
            }
            
            if (execute_query($query)) {
                echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update setting']);
            }
            exit;

        case 'update_bulk_settings':
            $settings = json_decode($_POST['settings'], true);
            $success_count = 0;
            
            foreach ($settings as $key => $value) {
                $key = sanitize_input($key);
                $value = sanitize_input($value);
                
                $check_query = "SELECT setting_id FROM settings WHERE setting_key = '$key'";
                $existing = get_row($check_query);
                
                if ($existing) {
                    $query = "UPDATE settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
                } else {
                    $query = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('$key', '$value', 'text')";
                }
                
                if (execute_query($query)) {
                    $success_count++;
                }
            }
            
            echo json_encode(['success' => true, 'message' => "$success_count settings updated successfully"]);
            exit;

        case 'change_password':
            $admin_id = $_SESSION['admin_id'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
                exit;
            }
            
            // Verify current password
            $admin_query = "SELECT password FROM admins WHERE admin_id = $admin_id";
            $admin = get_row($admin_query);
            
            if (!password_verify($current_password, $admin['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_query = "UPDATE admins SET password = '$hashed_password' WHERE admin_id = $admin_id";
            
            if (modify_data($update_query)) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to change password']);
            }
            exit;

        case 'backup_database':
            // This would require additional server permissions
            echo json_encode(['success' => false, 'message' => 'Database backup feature requires server configuration. Please use phpMyAdmin or command line.']);
            exit;
    }
}

// Fetch all settings
$settings_query = "SELECT * FROM settings ORDER BY setting_key";
$all_settings = get_all($settings_query);

// Organize settings into array
$settings = [];
foreach ($all_settings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get admin info
$admin_query = "SELECT * FROM admins WHERE admin_id = {$_SESSION['admin_id']}";
$admin_info = get_row($admin_query);

// Get system statistics
$system_stats = [
    'total_students' => get_row("SELECT COUNT(*) as count FROM students")['count'],
    'total_teachers' => get_row("SELECT COUNT(*) as count FROM teachers")['count'],
    'total_classes' => get_row("SELECT COUNT(*) as count FROM classes")['count'],
    'total_subjects' => get_row("SELECT COUNT(*) as count FROM subjects")['count'],
    'total_departments' => get_row("SELECT COUNT(*) as count FROM departments")['count'],
    'database_size' => 'N/A' // Would need SHOW TABLE STATUS query
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - CollegeSphere</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
    .settings-nav {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        position: sticky;
        top: 100px;
    }
    .settings-nav .nav-link {
        padding: 12px 16px;
        border-radius: 8px;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 4px;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    .settings-nav .nav-link:hover {
        background: #f8fafc;
        color: #6366f1;
        border-left-color: #6366f1;
    }
    .settings-nav .nav-link.active {
        background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        color: white;
        border-left-color: #4f46e5;
    }
    .settings-nav .nav-link i {
        width: 20px;
        margin-right: 12px;
    }
    .settings-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 24px;
    }
    .settings-section h4 {
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    .setting-item {
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .setting-item:last-child {
        border-bottom: none;
    }
    .setting-label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 8px;
    }
    .setting-description {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    .stat-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(99, 102, 241, 0.1);
    }
    .stat-card .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #6366f1;
        margin: 12px 0 8px 0;
    }
    .stat-card .stat-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }
    .admin-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: white;
        margin: 0 auto 20px;
    }
    .success-message {
        background: #d1fae5;
        color: #065f46;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
    }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
   <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">

       <?php include 'includes/navbar.php'; ?>

        <div class="dashboard-container">
            
            <div class="row">
                <!-- Settings Navigation -->
                <div class="col-md-3">
                    <div class="settings-nav">
                        <h5 class="mb-3">Settings</h5>
                        <div class="nav flex-column">
                            <a class="nav-link active" href="#general" data-bs-toggle="tab">
                                <i class="fas fa-building"></i>College Information
                            </a>
                            <a class="nav-link" href="#academic" data-bs-toggle="tab">
                                <i class="fas fa-graduation-cap"></i>Academic Settings
                            </a>
                            <a class="nav-link" href="#system" data-bs-toggle="tab">
                                <i class="fas fa-server"></i>System Information
                            </a>
                            <a class="nav-link" href="#security" data-bs-toggle="tab">
                                <i class="fas fa-shield-alt"></i>Security
                            </a>
                            <a class="nav-link" href="#profile" data-bs-toggle="tab">
                                <i class="fas fa-user-circle"></i>Admin Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-md-9">
                    <div id="successMessage" class="success-message">
                        <i class="fas fa-check-circle me-2"></i><span id="successText"></span>
                    </div>

                    <div class="tab-content">
                        
                        <!-- College Information Tab -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="settings-section">
                                <h4><i class="fas fa-building me-2 text-primary"></i>College Information</h4>
                                <form id="collegeInfoForm">
                                    <div class="setting-item">
                                        <label class="setting-label">College Name</label>
                                        <p class="setting-description">Official name of the institution</p>
                                        <input type="text" name="college_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['college_name'] ?? 'CollegeSphere Institute'); ?>">
                                    </div>
                                    
                                    <div class="setting-item">
                                        <label class="setting-label">College Address</label>
                                        <p class="setting-description">Full postal address</p>
                                        <textarea name="college_address" class="form-control" rows="2"><?php echo htmlspecialchars($settings['college_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Email Address</label>
                                                <input type="email" name="college_email" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['college_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Phone Number</label>
                                                <input type="text" name="college_phone" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['college_phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Website URL</label>
                                                <input type="url" name="college_website" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['college_website'] ?? ''); ?>" 
                                                       placeholder="https://www.example.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Established Year</label>
                                                <input type="number" name="established_year" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['established_year'] ?? ''); ?>" 
                                                       min="1900" max="<?php echo date('Y'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Save College Information
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Academic Settings Tab -->
                        <div class="tab-pane fade" id="academic">
                            <div class="settings-section">
                                <h4><i class="fas fa-graduation-cap me-2 text-success"></i>Academic Settings</h4>
                                <form id="academicSettingsForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Current Academic Year</label>
                                                <p class="setting-description">e.g., 2025-2026</p>
                                                <input type="text" name="academic_year" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['academic_year'] ?? '2025-2026'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Current Semester</label>
                                                <p class="setting-description">e.g., Spring 2026</p>
                                                <input type="text" name="semester" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['semester'] ?? 'Spring 2026'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="setting-item">
                                                <label class="setting-label">Minimum Attendance (%)</label>
                                                <p class="setting-description">Required attendance percentage</p>
                                                <input type="number" name="attendance_percentage_required" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['attendance_percentage_required'] ?? '75'); ?>" 
                                                       min="0" max="100">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="setting-item">
                                                <label class="setting-label">Passing Marks (%)</label>
                                                <p class="setting-description">Minimum percentage to pass</p>
                                                <input type="number" name="passing_marks_percentage" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['passing_marks_percentage'] ?? '40'); ?>" 
                                                       min="0" max="100">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="setting-item">
                                                <label class="setting-label">Late Fee Amount (₹)</label>
                                                <p class="setting-description">Late payment penalty</p>
                                                <input type="number" name="late_fee_amount" class="form-control" 
                                                       value="<?php echo htmlspecialchars($settings['late_fee_amount'] ?? '500'); ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save me-2"></i>Save Academic Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- System Information Tab -->
                        <div class="tab-pane fade" id="system">
                            <div class="settings-section">
                                <h4><i class="fas fa-server me-2 text-info"></i>System Information</h4>
                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-user-graduate fa-2x text-primary"></i>
                                            <div class="stat-value"><?php echo number_format($system_stats['total_students']); ?></div>
                                            <div class="stat-label">Total Students</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-chalkboard-teacher fa-2x text-success"></i>
                                            <div class="stat-value"><?php echo number_format($system_stats['total_teachers']); ?></div>
                                            <div class="stat-label">Total Teachers</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-building fa-2x text-warning"></i>
                                            <div class="stat-value"><?php echo number_format($system_stats['total_departments']); ?></div>
                                            <div class="stat-label">Departments</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-book fa-2x text-info"></i>
                                            <div class="stat-value"><?php echo number_format($system_stats['total_subjects']); ?></div>
                                            <div class="stat-label">Total Subjects</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-door-open fa-2x text-danger"></i>
                                            <div class="stat-value"><?php echo number_format($system_stats['total_classes']); ?></div>
                                            <div class="stat-label">Total Classes</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <i class="fas fa-database fa-2x text-secondary"></i>
                                            <div class="stat-value"><?php echo $system_stats['database_size']; ?></div>
                                            <div class="stat-label">Database Size</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">System Version</th>
                                            <td>CollegeSphere v1.0.0</td>
                                        </tr>
                                        <tr>
                                            <th>PHP Version</th>
                                            <td><?php echo phpversion(); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Server Software</th>
                                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Database</th>
                                            <td>MySQL</td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td><?php echo date('F d, Y h:i A'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Database Backup:</strong> For database backup, please use phpMyAdmin or contact your system administrator.
                                </div>
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security">
                            <div class="settings-section">
                                <h4><i class="fas fa-shield-alt me-2 text-danger"></i>Security Settings</h4>
                                
                                <h5 class="mt-4 mb-3">Change Password</h5>
                                <form id="changePasswordForm">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="setting-item">
                                                <label class="setting-label">Current Password</label>
                                                <input type="password" name="current_password" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">New Password</label>
                                                <input type="password" name="new_password" class="form-control" required minlength="6">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="setting-label">Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-danger btn-lg">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5 class="mb-3">Security Information</h5>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Password Encryption:</strong> All passwords are encrypted using bcrypt hashing algorithm.
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Session Management:</strong> Sessions expire after inactivity. Always logout when finished.
                                </div>
                            </div>
                        </div>

                        <!-- Admin Profile Tab -->
                        <div class="tab-pane fade" id="profile">
                            <div class="settings-section">
                                <h4><i class="fas fa-user-circle me-2 text-primary"></i>Admin Profile</h4>
                                
                                <div class="text-center mb-4">
                                    <div class="admin-avatar">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($admin_info['full_name'] ?? 'Admin User'); ?></h3>
                                    <p class="text-muted">System Administrator</p>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table">
                                        <tr>
                                            <th width="30%">Username</th>
                                            <td><?php echo htmlspecialchars($admin_info['username'] ?? 'admin'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Full Name</th>
                                            <td><?php echo htmlspecialchars($admin_info['full_name'] ?? 'Admin User'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo htmlspecialchars($admin_info['email'] ?? 'admin@college.com'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Role</th>
                                            <td><span class="badge bg-primary">Administrator</span></td>
                                        </tr>
                                        <tr>
                                            <th>Account Created</th>
                                            <td><?php echo date('F d, Y', strtotime($admin_info['created_at'] ?? 'now')); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Last Login</th>
                                            <td><?php echo date('F d, Y h:i A'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    
    <script>
    function showSuccess(message) {
        document.getElementById('successText').textContent = message;
        document.getElementById('successMessage').style.display = 'block';
        setTimeout(() => {
            document.getElementById('successMessage').style.display = 'none';
        }, 5000);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Save College Information
    document.getElementById('collegeInfoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const settings = {};
        formData.forEach((value, key) => settings[key] = value);
        
        const submitData = new FormData();
        submitData.append('action', 'update_bulk_settings');
        submitData.append('settings', JSON.stringify(settings));
        
        fetch('settings.php', { method: 'POST', body: submitData })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                showSuccess(d.message);
            } else {
                alert(d.message);
            }
        });
    });

    // Save Academic Settings
    document.getElementById('academicSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const settings = {};
        formData.forEach((value, key) => settings[key] = value);
        
        const submitData = new FormData();
        submitData.append('action', 'update_bulk_settings');
        submitData.append('settings', JSON.stringify(settings));
        
        fetch('settings.php', { method: 'POST', body: submitData })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                showSuccess(d.message);
            } else {
                alert(d.message);
            }
        });
    });

    // Change Password
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'change_password');
        
        fetch('settings.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                showSuccess(d.message);
                this.reset();
            } else {
                alert(d.message);
            }
        });
    });
    </script>

</body>
</html>
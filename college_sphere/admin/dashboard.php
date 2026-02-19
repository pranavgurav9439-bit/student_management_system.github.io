<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Set page title and subtitle for navbar
$page_title = "Dashboard Overview";
$page_subtitle = "Welcome back, Admin! Here's what's happening today.";

// Fetch dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
        (SELECT COUNT(*) FROM students WHERE status = 'Inactive') as inactive_students,
        (SELECT COUNT(*) FROM teachers WHERE status = 'Active') as total_teachers,
        (SELECT COUNT(*) FROM teachers WHERE status != 'Active') as inactive_teachers,
        (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
        (SELECT COUNT(*) FROM departments) as total_departments,
        (SELECT COUNT(*) FROM classes) as total_classes,
        (SELECT COUNT(*) FROM subjects) as total_subjects,
        (SELECT SUM(amount) FROM fees WHERE payment_status = 'Paid') as fees_collected,
        (SELECT SUM(amount) FROM fees WHERE payment_status = 'Pending') as fees_pending,
        (SELECT SUM(amount) FROM fees WHERE payment_status = 'Overdue') as fees_overdue,
        (SELECT COUNT(*) FROM notices WHERE expiry_date >= CURDATE()) as active_notices
";
$stats = get_row($stats_query);

// Fetch recent students (last 6)
$recent_students_query = "
    SELECT s.*, c.class_name, c.section, d.dept_code
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN departments d ON s.dept_id = d.dept_id
    WHERE s.status = 'Active'
    ORDER BY s.created_at DESC
    LIMIT 6
";
$recent_students = get_all($recent_students_query);

// Fetch recent teachers (last 6)
$recent_teachers_query = "
    SELECT t.*, d.dept_code
    FROM teachers t
    LEFT JOIN departments d ON t.dept_id = d.dept_id
    WHERE t.status = 'Active'
    ORDER BY t.created_at DESC
    LIMIT 6
";
$recent_teachers = get_all($recent_teachers_query);

// Fetch today's attendance summary
$today_attendance_query = "
    SELECT 
        COUNT(*) as total_marked,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance
    WHERE attendance_date = CURDATE()
";
$today_attendance = get_row($today_attendance_query);

// Fetch recent fee payments (last 5)
$recent_payments_query = "
    SELECT f.*, s.roll_number, s.first_name, s.last_name
    FROM fees f
    JOIN students s ON f.student_id = s.student_id
    WHERE f.payment_status = 'Paid'
    ORDER BY f.paid_date DESC
    LIMIT 5
";
$recent_payments = get_all($recent_payments_query);

// Fetch department distribution
$dept_distribution_query = "
    SELECT d.dept_name, d.dept_code, COUNT(s.student_id) as student_count
    FROM departments d
    LEFT JOIN students s ON d.dept_id = s.dept_id AND s.status = 'Active'
    GROUP BY d.dept_id
    ORDER BY student_count DESC
";
$departments = get_all($dept_distribution_query);

// Calculate percentages
$total_fees = ($stats['fees_collected'] ?? 0) + ($stats['fees_pending'] ?? 0) + ($stats['fees_overdue'] ?? 0);
$collection_rate = $total_fees > 0 ? round(($stats['fees_collected'] / $total_fees) * 100, 1) : 0;

$attendance_percentage = $today_attendance['total_marked'] > 0 ? 
    round(($today_attendance['present'] / $today_attendance['total_marked']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
    .quick-stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s;
        border-left: 4px solid transparent;
    }
    .quick-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .attendance-stat { border-left-color: #10b981; }
    .pending-stat { border-left-color: #f59e0b; }
    .overdue-stat { border-left-color: #ef4444; }
    .notice-stat { border-left-color: #6366f1; }
    
    .quick-stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .attendance-stat .quick-stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .pending-stat .quick-stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .overdue-stat .quick-stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .notice-stat .quick-stat-icon { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
    
    .quick-stat-info h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: #1e293b;
    }
    .quick-stat-info p {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        margin: 0 0 2px 0;
    }
    .quick-stat-info small {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .department-box {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
    }
    .department-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        border-color: #6366f1;
    }
    .dept-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 20px;
    }
    .dept-info h4 {
        font-size: 32px;
        font-weight: 700;
        color: #6366f1;
        margin: 0 0 4px 0;
    }
    .dept-info p {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 4px 0;
    }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>

        <div class="dashboard-container">

            <!-- MAIN STATS CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value"><?php echo number_format($stats['total_students'] ?? 0); ?></div>
                            <div class="stat-footer">
                                <span class="stat-trend positive">
                                    <i class="fas fa-arrow-up"></i>
                                    <?php echo ($stats['total_students'] ?? 0) - ($stats['inactive_students'] ?? 0); ?> Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card stat-card-success">
                        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Total Teachers</div>
                            <div class="stat-value"><?php echo number_format($stats['total_teachers'] ?? 0); ?></div>
                            <div class="stat-footer">
                                <span class="stat-trend positive">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo ($stats['total_teachers'] ?? 0) - ($stats['inactive_teachers'] ?? 0); ?> Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-icon"><i class="fas fa-building"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Departments</div>
                            <div class="stat-value"><?php echo number_format($stats['total_departments'] ?? 0); ?></div>
                            <div class="stat-footer">
                                <span class="stat-trend stable">
                                    <i class="fas fa-book"></i>
                                    <?php echo number_format($stats['total_subjects'] ?? 0); ?> Subjects
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card stat-card-info">
                        <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                        <div class="stat-content">
                            <div class="stat-label">Fees Collected</div>
                            <div class="stat-value">₹<?php echo number_format($stats['fees_collected'] ?? 0); ?></div>
                            <div class="stat-footer">
                                <span class="stat-trend <?php echo $collection_rate >= 70 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-percentage"></i>
                                    <?php echo $collection_rate; ?>% Collected
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK STATS -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-6">
                    <div class="quick-stat-card attendance-stat">
                        <div class="quick-stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="quick-stat-info">
                            <h3><?php echo $attendance_percentage; ?>%</h3>
                            <p>Today's Attendance</p>
                            <small><?php echo $today_attendance['present'] ?? 0; ?>/<?php echo $today_attendance['total_marked'] ?? 0; ?> Present</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="quick-stat-card pending-stat">
                        <div class="quick-stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="quick-stat-info">
                            <h3>₹<?php echo number_format($stats['fees_pending'] ?? 0); ?></h3>
                            <p>Pending Fees</p>
                            <small>To be collected</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="quick-stat-card overdue-stat">
                        <div class="quick-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="quick-stat-info">
                            <h3>₹<?php echo number_format($stats['fees_overdue'] ?? 0); ?></h3>
                            <p>Overdue Fees</p>
                            <small>Requires attention</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="quick-stat-card notice-stat">
                        <div class="quick-stat-icon"><i class="fas fa-bell"></i></div>
                        <div class="quick-stat-info">
                            <h3><?php echo number_format($stats['active_notices'] ?? 0); ?></h3>
                            <p>Active Notices</p>
                            <small>Currently published</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DEPARTMENT DISTRIBUTION -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-building text-primary me-2"></i>Department-wise Student Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($departments as $dept): ?>
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <div class="department-box">
                                        <div class="dept-icon"><i class="fas fa-graduation-cap"></i></div>
                                        <div class="dept-info">
                                            <h4><?php echo $dept['student_count']; ?></h4>
                                            <p><?php echo htmlspecialchars($dept['dept_code']); ?></p>
                                            <small class="text-muted"><?php echo htmlspecialchars($dept['dept_name']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLES -->
            <div class="row g-4 mb-4">
                
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-graduate text-primary me-2"></i>Recent Enrollments</h5>
                            <a href="students.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Dept</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_students as $student): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['class_name'] . '-' . $student['section']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($student['dept_code'] ?? 'N/A'); ?></span></td>
                                            <td><span class="badge bg-success">Active</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chalkboard-teacher text-success me-2"></i>Faculty Members</h5>
                            <a href="teachers.php" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Dept</th>
                                            <th>Designation</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_teachers as $teacher): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-success text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($teacher['employee_id']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($teacher['dept_code'] ?? 'N/A'); ?></span></td>
                                            <td><?php echo htmlspecialchars($teacher['designation']); ?></td>
                                            <td><span class="badge bg-success">Active</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RECENT PAYMENTS -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave text-success me-2"></i>Recent Fee Payments</h5>
                            <a href="finance.php" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Roll Number</th>
                                            <th>Fee Type</th>
                                            <th>Amount</th>
                                            <th>Payment Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recent_payments) > 0): ?>
                                            <?php foreach ($recent_payments as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['fee_type']); ?></td>
                                                <td><strong>₹<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                                <td><?php echo date('M d, Y', strtotime($payment['paid_date'])); ?></td>
                                                <td><span class="badge bg-success">Paid</span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3 text-muted">No recent payments</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

</body>
</html>
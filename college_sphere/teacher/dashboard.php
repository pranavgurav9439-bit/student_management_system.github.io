<?php
session_start();
require_once '../config/db.php';

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Set page title
$page_title = "Teacher Dashboard";
$page_subtitle = "Welcome back, " . explode(' ', $teacher_name)[0] . "! Here's your overview.";

// Fetch dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(DISTINCT class_id) 
         FROM teacher_class_assignments 
         WHERE teacher_id = {$teacher_id} AND academic_year = '2024-2025') as assigned_classes,
        
        (SELECT COUNT(DISTINCT subject_id) 
         FROM teacher_class_assignments 
         WHERE teacher_id = {$teacher_id} AND academic_year = '2024-2025') as assigned_subjects,
        
        (SELECT COUNT(DISTINCT s.student_id) 
         FROM teacher_class_assignments tca
         JOIN students s ON tca.class_id = s.class_id
         WHERE tca.teacher_id = {$teacher_id}
         AND tca.academic_year = '2024-2025'
         AND s.status = 'Active') as total_students,
        
        (SELECT COUNT(DISTINCT a.student_id)
         FROM teacher_class_assignments tca
         JOIN students s ON tca.class_id = s.class_id
         LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = CURDATE()
         WHERE tca.teacher_id = {$teacher_id}
         AND tca.academic_year = '2024-2025'
         AND s.status = 'Active'
         AND a.status = 'Present') as today_present,
        
        (SELECT COUNT(DISTINCT a.student_id)
         FROM teacher_class_assignments tca
         JOIN students s ON tca.class_id = s.class_id
         LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = CURDATE()
         WHERE tca.teacher_id = {$teacher_id}
         AND tca.academic_year = '2024-2025'
         AND s.status = 'Active'
         AND a.status = 'Absent') as today_absent,
        
        (SELECT COUNT(*)
         FROM teacher_notices
         WHERE teacher_id = {$teacher_id}
         AND (expiry_date >= CURDATE() OR expiry_date IS NULL)) as my_notices
";

$stats = get_row($stats_query);

// Calculate attendance percentage
$total_marked = $stats['today_present'] + $stats['today_absent'];
$attendance_percentage = $total_marked > 0 ? round(($stats['today_present'] / $total_marked) * 100, 1) : 0;

// Fetch assigned classes with details
$classes_query = "
    SELECT 
        c.class_id,
        c.class_name,
        c.section,
        s.subject_name,
        s.subject_code,
        tca.is_class_teacher,
        COUNT(DISTINCT st.student_id) as student_count
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.class_id
    JOIN subjects s ON tca.subject_id = s.subject_id
    LEFT JOIN students st ON c.class_id = st.class_id AND st.status = 'Active'
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
    GROUP BY tca.assignment_id
    ORDER BY c.class_name, c.section
";

$assigned_classes = get_all($classes_query);

// Fetch recent attendance (last 7 days)
$attendance_trend_query = "
    SELECT 
        DATE(a.attendance_date) as date,
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late
    FROM teacher_class_assignments tca
    JOIN students s ON tca.class_id = s.class_id
    LEFT JOIN attendance a ON s.student_id = a.student_id
    WHERE tca.teacher_id = {$teacher_id}
    AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    AND a.attendance_date <= CURDATE()
    GROUP BY DATE(a.attendance_date)
    ORDER BY date ASC
";

$attendance_trend = get_all($attendance_trend_query);

// Fetch recent notices (last 5)
$notices_query = "
    SELECT title, description as content, created_at, 'General' as notice_type, priority
    FROM notices
    WHERE expiry_date >= CURDATE() OR expiry_date IS NULL
    ORDER BY created_at DESC
    LIMIT 5
";
$recent_notices = get_all($notices_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper">
            
            <!-- STATISTICS CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card green">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="stat-details">
                            <h3 class="stat-value" data-target="<?php echo $stats['assigned_classes']; ?>">0</h3>
                            <p class="stat-label">Assigned Classes</p>
                            <small class="stat-change text-success">
                                <i class="fas fa-arrow-up me-1"></i> Active
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card blue">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-details">
                            <h3 class="stat-value" data-target="<?php echo $stats['assigned_subjects']; ?>">0</h3>
                            <p class="stat-label">Assigned Subjects</p>
                            <small class="stat-change text-info">
                                <i class="fas fa-book-open me-1"></i> Teaching
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card purple">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-details">
                            <h3 class="stat-value" data-target="<?php echo $stats['total_students']; ?>">0</h3>
                            <p class="stat-label">Total Students</p>
                            <small class="stat-change text-muted">
                                <i class="fas fa-users me-1"></i> All classes
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="stat-card orange">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-details">
                            <h3 class="stat-value"><?php echo $attendance_percentage; ?>%</h3>
                            <p class="stat-label">Today's Attendance</p>
                            <small class="stat-change">
                                <span class="text-success"><?php echo $stats['today_present']; ?> Present</span> / 
                                <span class="text-danger"><?php echo $stats['today_absent']; ?> Absent</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <!-- ASSIGNED CLASSES -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-chalkboard text-success me-2"></i>
                                My Assigned Classes
                            </h5>
                            <a href="students.php" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-eye me-1"></i> View Students
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (count($assigned_classes) > 0): ?>
                                <div class="row">
                                    <?php foreach ($assigned_classes as $class): ?>
                                    <div class="col-md-6">
                                        <div class="class-card">
                                            <h5>
                                                <?php echo htmlspecialchars($class['class_name'] . '-' . $class['section']); ?>
                                                <?php if ($class['is_class_teacher']): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Class Teacher</span>
                                                <?php endif; ?>
                                            </h5>
                                            <div class="subject-info">
                                                <i class="fas fa-book me-1"></i>
                                                <?php echo htmlspecialchars($class['subject_name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($class['subject_code']); ?>)</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="student-count">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $class['student_count']; ?> Students
                                                </span>
                                                <div>
                                                    <a href="attendance.php?class_id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-success me-1"
                                                       title="Mark Attendance">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="marks.php?class_id=<?php echo $class['class_id']; ?>" 
                                                       class="btn btn-sm btn-primary"
                                                       title="Add Marks">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No classes assigned yet. Contact admin for assignments.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- RECENT NOTICES -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-bell text-warning me-2"></i>
                                Recent Notices
                            </h5>
                            <a href="notices.php" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <?php if (count($recent_notices) > 0): ?>
                                <?php foreach ($recent_notices as $notice): ?>
                                <div class="notice-card priority-<?php echo strtolower($notice['priority']); ?>">
                                    <div class="notice-title">
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                        <span class="badge bg-secondary float-end">
                                            <?php echo htmlspecialchars($notice['notice_type']); ?>
                                        </span>
                                    </div>
                                    <div class="notice-content">
                                        <?php echo htmlspecialchars(substr($notice['content'], 0, 100)); ?>
                                        <?php if (strlen($notice['content']) > 100) echo '...'; ?>
                                    </div>
                                    <div class="notice-meta">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M d, Y', strtotime($notice['created_at'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No recent notices</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CHARTS -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line text-info me-2"></i>
                                Attendance Trend (Last 7 Days)
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceTrendChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie text-success me-2"></i>
                                Quick Stats
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-clipboard-check text-success me-2"></i>
                                    <span>Classes Today</span>
                                </div>
                                <strong class="text-success"><?php echo $stats['assigned_classes']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-bell text-warning me-2"></i>
                                    <span>My Notices</span>
                                </div>
                                <strong class="text-warning"><?php echo $stats['my_notices']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <span>Total Students</span>
                                </div>
                                <strong class="text-primary"><?php echo $stats['total_students']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        // Attendance Trend Chart
        const attendanceTrendData = <?php echo json_encode($attendance_trend); ?>;
        
        const labels = attendanceTrendData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        });
        
        const presentData = attendanceTrendData.map(d => parseInt(d.present));
        const absentData = attendanceTrendData.map(d => parseInt(d.absent));
        
        const ctx = document.getElementById('attendanceTrendChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' }
                    }
                }
            }
        });
    </script>

</body>
</html>
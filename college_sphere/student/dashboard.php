<?php
session_start();
require_once '../config/db.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Set page title and subtitle
$page_title = "Dashboard";
$page_subtitle = "Welcome back, " . $_SESSION['student_name'] . "! Here's your overview.";

// Fetch student details
$student_query = "
    SELECT s.*, c.class_name, c.section, d.dept_name
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN departments d ON s.dept_id = d.dept_id
    WHERE s.student_id = $student_id
";
$student = get_row($student_query);

// Fetch attendance summary
$attendance_query = "
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days
    FROM attendance
    WHERE student_id = $student_id
";
$attendance = get_row($attendance_query);
$attendance_percentage = $attendance['total_days'] > 0 ? 
    round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) : 0;

// Fetch marks summary
$marks_query = "
    SELECT 
        COUNT(DISTINCT subject_id) as subjects_count,
        AVG((marks_obtained / total_marks) * 100) as average_percentage,
        COUNT(*) as total_exams
    FROM marks
    WHERE student_id = $student_id
";
$marks = get_row($marks_query);
$average_percentage = round($marks['average_percentage'] ?? 0, 1);

// Fetch fees summary
$fees_query = "
    SELECT 
        SUM(amount) as total_amount,
        SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN payment_status = 'Overdue' THEN amount ELSE 0 END) as overdue_amount
    FROM fees
    WHERE student_id = $student_id
";
$fees = get_row($fees_query);

// Fetch recent notices
$notices_query = "
    SELECT * FROM notices 
    WHERE target_audience IN ('All', 'Students')
    AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ORDER BY notice_date DESC, created_at DESC
    LIMIT 5
";
$notices = get_all($notices_query);

// Fetch recent marks (last 5)
$recent_marks_query = "
    SELECT m.*, sub.subject_name, sub.subject_code
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.subject_id
    WHERE m.student_id = $student_id
    ORDER BY m.exam_date DESC
    LIMIT 5
";
$recent_marks = get_all($recent_marks_query);

// Fetch today's timetable
// Check if class_id is set to avoid SQL error
$timetable = [];
if (isset($_SESSION['class_id']) && !empty($_SESSION['class_id'])) {
    $class_id = (int)$_SESSION['class_id'];
    $today_day = date('l'); // Gets current day (Monday, Tuesday, etc.)
    
    $timetable_query = "
        SELECT t.*, s.subject_name, s.subject_code, 
               CONCAT(te.first_name, ' ', te.last_name) as teacher_name
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.subject_id
        LEFT JOIN teachers te ON t.teacher_id = te.teacher_id
        WHERE t.class_id = {$class_id}
        AND t.day_of_week = '{$today_day}'
        ORDER BY t.start_time
    ";
    $timetable = get_all($timetable_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-all {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .view-all:hover {
            color: #2563eb;
        }
        
        .notice-item {
            padding: 15px;
            border-left: 4px solid #3b82f6;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .notice-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .notice-date {
            font-size: 12px;
            color: #64748b;
        }
        
        .notice-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #dbeafe; color: #1e40af; }
        
        .marks-table {
            width: 100%;
        }
        
        .marks-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }
        
        .marks-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        
        .marks-table tr:last-child td {
            border-bottom: none;
        }
        
        .progress-bar-custom {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            border-radius: 10px;
            transition: width 0.5s;
        }
        
        .timetable-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #3b82f6;
        }
        
        .time-badge {
            display: inline-block;
            padding: 4px 10px;
            background: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #3b82f6;
        }
        
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        
        <main class="main-content">
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                            <div class="stat-label">Overall Attendance</div>
                            <span class="stat-badge <?php echo $attendance_percentage >= 75 ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $attendance['present_days'] . '/' . $attendance['total_days'] . ' days'; ?>
                            </span>
                        </div>
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?php echo $attendance_percentage; ?>%"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $average_percentage; ?>%</div>
                            <div class="stat-label">Average Marks</div>
                            <span class="stat-badge <?php echo $average_percentage >= 60 ? 'badge-success' : ($average_percentage >= 40 ? 'badge-warning' : 'badge-danger'); ?>">
                                <?php echo $marks['total_exams'] ?? 0; ?> exams taken
                            </span>
                        </div>
                        <div class="stat-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width: <?php echo min($average_percentage, 100); ?>%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value">₹<?php echo number_format($fees['paid_amount'] ?? 0); ?></div>
                            <div class="stat-label">Fees Paid</div>
                            <span class="stat-badge <?php echo ($fees['pending_amount'] ?? 0) == 0 ? 'badge-success' : 'badge-warning'; ?>">
                                ₹<?php echo number_format($fees['pending_amount'] ?? 0); ?> pending
                            </span>
                        </div>
                        <div class="stat-icon yellow">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $marks['subjects_count'] ?? 0; ?></div>
                            <div class="stat-label">Enrolled Subjects</div>
                            <span class="stat-badge badge-success">
                                <?php echo $student['class_name'] . ' - ' . $student['section']; ?>
                            </span>
                        </div>
                        <div class="stat-icon red">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Recent Marks -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Recent Marks
                        </h3>
                        <a href="marks.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if (count($recent_marks) > 0): ?>
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_marks as $mark): 
                                    $percentage = ($mark['marks_obtained'] / $mark['total_marks']) * 100;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($mark['subject_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mark['exam_type']); ?></td>
                                        <td><?php echo $mark['marks_obtained'] . '/' . $mark['total_marks']; ?></td>
                                        <td>
                                            <span class="stat-badge <?php echo $percentage >= 60 ? 'badge-success' : ($percentage >= 40 ? 'badge-warning' : 'badge-danger'); ?>">
                                                <?php echo round($percentage, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted">No marks available yet.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Today's Classes -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock"></i>
                            Today's Classes
                        </h3>
                    </div>
                    
                    <?php if (count($today_classes) > 0): ?>
                        <?php foreach ($today_classes as $class): ?>
                            <div class="timetable-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong><?php echo htmlspecialchars($class['subject_name']); ?></strong>
                                    <span class="time-badge">
                                        <?php echo date('g:i A', strtotime($class['start_time'])); ?>
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: #64748b;">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($class['teacher_name'] ?? 'TBA'); ?>
                                    <br>
                                    <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No classes today.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Notices -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bullhorn"></i>
                        Recent Notices
                    </h3>
                    <a href="notices.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <?php if (count($notices) > 0): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="notice-item">
                            <div class="notice-title">
                                <?php echo htmlspecialchars($notice['title']); ?>
                                <span class="notice-priority priority-<?php echo strtolower($notice['priority']); ?>">
                                    <?php echo $notice['priority']; ?>
                                </span>
                            </div>
                            <div><?php echo htmlspecialchars(substr($notice['description'], 0, 150)) . '...'; ?></div>
                            <div class="notice-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M d, Y', strtotime($notice['notice_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No notices available.</p>
                <?php endif; ?>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
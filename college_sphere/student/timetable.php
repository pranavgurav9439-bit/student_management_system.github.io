<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

// Get student data
$student = get_row("SELECT * FROM students WHERE student_id = " . $_SESSION['student_id']);

// Page setup
$page_title = "Timetable";
$page_subtitle = "Your class schedule for the week";
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Initialize variables
$has_class = false;
$timetable = [];

// Check if student has a class assigned
if (isset($student['class_id']) && !empty($student['class_id'])) {
    $has_class = true;
    $class_id = (int)$student['class_id'];
    
    // Fetch timetable for each day
    foreach ($days as $day) {
        $query = "SELECT t.*, sub.subject_name, sub.subject_code, 
                         CONCAT(te.first_name, ' ', te.last_name) as teacher
                  FROM timetable t 
                  JOIN subjects sub ON t.subject_id = sub.subject_id
                  LEFT JOIN teachers te ON t.teacher_id = te.teacher_id
                  WHERE t.class_id = {$class_id} AND t.day_of_week = '$day'
                  ORDER BY t.start_time";
        $timetable[$day] = get_all($query);
    }
} else {
    // No class assigned - initialize empty timetable
    foreach ($days as $day) {
        $timetable[$day] = [];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Timetable - CollegeSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f8fafc; }
        .main-wrapper { display:flex; min-height:100vh; }
        .content-wrapper { flex:1; margin-left:280px; }
        .main-content { padding:30px; }
        .day-card { background:white; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .day-header { font-size:18px; font-weight:700; color:#1e293b; margin-bottom:15px; padding-bottom:10px; border-bottom:2px solid #e2e8f0; }
        .class-item { padding:15px; background:#f8fafc; border-radius:8px; margin-bottom:10px; border-left:4px solid #3b82f6; }
        .time-badge { background:#3b82f6; color:white; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; }
        @media (max-width:768px) { .content-wrapper { margin-left:0; } }
        .alert { margin-bottom:20px; border-radius:12px; }
    </style>
</head>
<body>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        <main class="main-content">
            
            <?php if (!$has_class): ?>
                <!-- No Class Warning -->
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Class Not Assigned</h5>
                    <p class="mb-0">You haven't been assigned to a class yet. Please contact the administrator to assign you to a class.</p>
                </div>
                
                <div class="day-card text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Timetable Available</h5>
                    <p class="text-muted">Your timetable will appear here once you're assigned to a class.</p>
                </div>
                
            <?php else: ?>
                <!-- Display Timetable -->
                <?php 
                $total_classes = 0;
                foreach ($timetable as $day_classes) {
                    $total_classes += count($day_classes);
                }
                ?>
                
                <?php if ($total_classes === 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No timetable entries have been created for your class yet. Please check back later.
                    </div>
                <?php endif; ?>
                
                <?php foreach ($days as $day): ?>
                    <div class="day-card">
                        <h3 class="day-header">
                            <i class="fas fa-calendar-day me-2"></i><?php echo $day; ?>
                        </h3>
                        
                        <?php if (count($timetable[$day]) > 0): ?>
                            <?php foreach ($timetable[$day] as $class): ?>
                                <div class="class-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong style="font-size:16px;">
                                                <?php echo htmlspecialchars($class['subject_name']); ?>
                                            </strong>
                                            <div style="color:#64748b; font-size:13px; margin-top:5px;">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($class['teacher'] ?? 'TBA'); ?>
                                                &nbsp;•&nbsp;
                                                <i class="fas fa-door-open me-1"></i>
                                                <?php echo htmlspecialchars($class['room_number'] ?? 'TBA'); ?>
                                            </div>
                                        </div>
                                        <span class="time-badge">
                                            <?php echo date('g:i A', strtotime($class['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($class['end_time'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>No classes scheduled for this day
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
            <?php endif; ?>
            
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$page_title = "My Attendance";
$page_subtitle = "View your attendance records and statistics";

// Get filter parameters
$month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
$subject_filter = isset($_GET['subject']) ? sanitize_input($_GET['subject']) : '';

// Fetch overall attendance summary
$overall_query = "
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) as excused_days
    FROM attendance
    WHERE student_id = $student_id
";
$overall = get_row($overall_query);
$overall_percentage = $overall['total_days'] > 0 ? 
    round(($overall['present_days'] / $overall['total_days']) * 100, 1) : 0;

// Fetch attendance records for selected month
$attendance_query = "
    SELECT a.*, sub.subject_name, sub.subject_code
    FROM attendance a
    LEFT JOIN subjects sub ON a.class_id = (
        SELECT class_id FROM students WHERE student_id = $student_id
    )
    WHERE a.student_id = $student_id
    AND DATE_FORMAT(a.attendance_date, '%Y-%m') = '$month'
    ORDER BY a.attendance_date DESC
";
$attendance_records = get_all($attendance_query);

// Get subjects for filter
$subjects_query = "
    SELECT DISTINCT sub.subject_id, sub.subject_name, sub.subject_code
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.subject_id
    WHERE m.student_id = $student_id
    ORDER BY sub.subject_name
";
$subjects = get_all($subjects_query);

// Calculate monthly attendance
$monthly_query = "
    SELECT 
        DATE_FORMAT(attendance_date, '%Y-%m') as month,
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days
    FROM attendance
    WHERE student_id = $student_id
    GROUP BY DATE_FORMAT(attendance_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
";
$monthly_stats = get_all($monthly_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-wrapper { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .main-content { flex: 1; padding: 30px; }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-box .label {
            font-size: 14px;
            color: #64748b;
        }
        
        .stat-box.present .value { color: #10b981; }
        .stat-box.absent .value { color: #ef4444; }
        .stat-box.late .value { color: #f59e0b; }
        .stat-box.total .value { color: #3b82f6; }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendance-table thead {
            background: #f8fafc;
        }
        
        .attendance-table th {
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .attendance-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-present { background: #dcfce7; color: #166534; }
        .status-absent { background: #fee2e2; color: #991b1b; }
        .status-late { background: #fef3c7; color: #92400e; }
        .status-excused { background: #dbeafe; color: #1e40af; }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .content-wrapper { margin-left: 0; }
            .main-content { padding: 15px; }
            .filters { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        
        <main class="main-content">
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-box total">
                    <div class="label">Total Days</div>
                    <div class="value"><?php echo $overall['total_days']; ?></div>
                </div>
                <div class="stat-box present">
                    <div class="label">Present</div>
                    <div class="value"><?php echo $overall['present_days']; ?></div>
                    <div class="label"><?php echo $overall_percentage; ?>%</div>
                </div>
                <div class="stat-box absent">
                    <div class="label">Absent</div>
                    <div class="value"><?php echo $overall['absent_days']; ?></div>
                </div>
                <div class="stat-box late">
                    <div class="label">Late</div>
                    <div class="value"><?php echo $overall['late_days']; ?></div>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i>
                        Attendance Overview
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
            
            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        Attendance Records
                    </h3>
                </div>
                
                <!-- Filters -->
                <form method="GET" class="filters mb-4">
                    <div class="filter-group">
                        <label>Month</label>
                        <input type="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
                    </div>
                </form>
                
                <!-- Table -->
                <?php if (count($attendance_records) > 0): ?>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Status</th>
                                    <th>Marked By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                        <td>Teacher</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No attendance records for selected month.</p>
                <?php endif; ?>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>

<script>
// Pie Chart
const ctx = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent', 'Late', 'Excused'],
        datasets: [{
            data: [
                <?php echo $overall['present_days']; ?>,
                <?php echo $overall['absent_days']; ?>,
                <?php echo $overall['late_days']; ?>,
                <?php echo $overall['excused_days']; ?>
            ],
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#3b82f6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
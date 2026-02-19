<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$page_title = "Marks & Performance";
$page_subtitle = "View your exam results and academic performance";

// Fetch all marks
$marks_query = "
    SELECT m.*, sub.subject_name, sub.subject_code
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.subject_id
    WHERE m.student_id = $student_id
    ORDER BY m.exam_date DESC
";
$all_marks = get_all($marks_query);

// Calculate subject-wise performance
$subject_performance = "
    SELECT 
        sub.subject_name,
        sub.subject_code,
        COUNT(m.mark_id) as total_exams,
        AVG((m.marks_obtained / m.total_marks) * 100) as avg_percentage,
        SUM(m.marks_obtained) as total_obtained,
        SUM(m.total_marks) as total_marks
    FROM marks m
    JOIN subjects sub ON m.subject_id = sub.subject_id
    WHERE m.student_id = $student_id
    GROUP BY sub.subject_id
    ORDER BY avg_percentage DESC
";
$subject_stats = get_all($subject_performance);

// Overall statistics
$overall_avg = 0;
$total_exams = 0;
if (count($all_marks) > 0) {
    $total_percentage = 0;
    foreach ($all_marks as $mark) {
        $total_percentage += ($mark['marks_obtained'] / $mark['total_marks']) * 100;
    }
    $overall_avg = round($total_percentage / count($all_marks), 2);
    $total_exams = count($all_marks);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks & Performance - Student Portal</title>
    
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
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .summary-card h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .marks-table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .marks-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .grade-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .grade-a { background: #dcfce7; color: #166534; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; }
        .grade-d { background: #fee2e2; color: #991b1b; }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .content-wrapper { margin-left: 0; }
            .main-content { padding: 15px; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        
        <main class="main-content">
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Overall Average</h3>
                    <div class="value"><?php echo $overall_avg; ?>%</div>
                </div>
                <div class="summary-card">
                    <h3>Total Exams</h3>
                    <div class="value"><?php echo $total_exams; ?></div>
                </div>
                <div class="summary-card">
                    <h3>Subjects</h3>
                    <div class="value"><?php echo count($subject_stats); ?></div>
                </div>
            </div>
            
            <div class="card">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Subject-wise Performance
                </h3>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <h3 class="card-title">
                    <i class="fas fa-list-alt"></i>
                    All Marks
                </h3>
                
                <?php if (count($all_marks) > 0): ?>
                    <div class="table-responsive">
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Exam Type</th>
                                    <th>Marks Obtained</th>
                                    <th>Total Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_marks as $mark): 
                                    $percentage = ($mark['marks_obtained'] / $mark['total_marks']) * 100;
                                    $grade_class = $percentage >= 75 ? 'grade-a' : ($percentage >= 60 ? 'grade-b' : ($percentage >= 40 ? 'grade-c' : 'grade-d'));
                                    $grade = $percentage >= 75 ? 'A' : ($percentage >= 60 ? 'B' : ($percentage >= 40 ? 'C' : 'F'));
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($mark['exam_date'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($mark['subject_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($mark['exam_type']); ?></td>
                                        <td><?php echo $mark['marks_obtained']; ?></td>
                                        <td><?php echo $mark['total_marks']; ?></td>
                                        <td><?php echo round($percentage, 1); ?>%</td>
                                        <td>
                                            <span class="grade-badge <?php echo $grade_class; ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No marks available yet.</p>
                <?php endif; ?>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>

<script>
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($subject_stats as $s) echo "'" . addslashes($s['subject_code']) . "',"; ?>],
        datasets: [{
            label: 'Average Percentage',
            data: [<?php foreach ($subject_stats as $s) echo round($s['avg_percentage'], 1) . ","; ?>],
            backgroundColor: '#3b82f6',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
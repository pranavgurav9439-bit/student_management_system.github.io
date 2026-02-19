<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Performance Reports";
$page_subtitle = "Analyze student performance and view analytics";

// Fetch teacher's assigned classes and subjects
$classes_query = "
    SELECT DISTINCT
        c.class_id,
        c.class_name,
        c.section,
        s.subject_id,
        s.subject_name,
        s.subject_code
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.class_id
    JOIN subjects s ON tca.subject_id = s.subject_id
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
    ORDER BY c.class_name, c.section
";
$assigned_classes = get_all($classes_query);

// Get selected filters
$selected_class = $_GET['class_id'] ?? ($assigned_classes[0]['class_id'] ?? null);
$selected_subject = $_GET['subject_id'] ?? null;

// Get subjects for selected class
$subjects = [];
if ($selected_class) {
    $subjects_query = "
        SELECT DISTINCT s.subject_id, s.subject_name, s.subject_code
        FROM teacher_class_assignments tca
        JOIN subjects s ON tca.subject_id = s.subject_id
        WHERE tca.teacher_id = {$teacher_id}
        AND tca.class_id = {$selected_class}
        AND tca.academic_year = '2024-2025'
    ";
    $subjects = get_all($subjects_query);
    
    if (!$selected_subject && count($subjects) > 0) {
        $selected_subject = $subjects[0]['subject_id'];
    }
}

// Fetch performance data
$performance_data = [];
$class_stats = null;
$top_performers = [];
$needs_attention = [];

if ($selected_class && $selected_subject) {
    // Overall class statistics
    $class_stats_query = "
        SELECT 
            COUNT(DISTINCT s.student_id) as total_students,
            ROUND(AVG((m.marks_obtained / m.total_marks) * 100), 2) as average_percentage,
            ROUND(MAX((m.marks_obtained / m.total_marks) * 100), 2) as highest_percentage,
            ROUND(MIN((m.marks_obtained / m.total_marks) * 100), 2) as lowest_percentage,
            COUNT(DISTINCT CASE WHEN (m.marks_obtained / m.total_marks) * 100 >= 40 THEN m.student_id END) as passed,
            COUNT(DISTINCT CASE WHEN (m.marks_obtained / m.total_marks) * 100 < 40 THEN m.student_id END) as failed
        FROM students s
        LEFT JOIN marks m ON s.student_id = m.student_id AND m.subject_id = {$selected_subject}
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
    ";
    $class_stats = get_row($class_stats_query);
    
    // Student-wise performance
    $performance_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            ROUND(AVG((m.marks_obtained / m.total_marks) * 100), 2) as average_percentage,
            COUNT(m.mark_id) as total_exams,
            SUM(m.marks_obtained) as total_marks_obtained,
            SUM(m.total_marks) as total_marks_possible
        FROM students s
        LEFT JOIN marks m ON s.student_id = m.student_id AND m.subject_id = {$selected_subject}
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
        GROUP BY s.student_id
        ORDER BY average_percentage DESC
    ";
    $performance_data = get_all($performance_query);
    
    // Top 5 performers
    $top_performers_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            ROUND(AVG((m.marks_obtained / m.total_marks) * 100), 2) as average_percentage
        FROM students s
        JOIN marks m ON s.student_id = m.student_id AND m.subject_id = {$selected_subject}
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
        GROUP BY s.student_id
        HAVING average_percentage IS NOT NULL
        ORDER BY average_percentage DESC
        LIMIT 5
    ";
    $top_performers = get_all($top_performers_query);
    
    // Students needing attention (below 40%)
    $needs_attention_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            ROUND(AVG((m.marks_obtained / m.total_marks) * 100), 2) as average_percentage
        FROM students s
        JOIN marks m ON s.student_id = m.student_id AND m.subject_id = {$selected_subject}
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
        GROUP BY s.student_id
        HAVING average_percentage < 40
        ORDER BY average_percentage ASC
        LIMIT 5
    ";
    $needs_attention = get_all($needs_attention_query);
}

// Prepare chart data for grade distribution
$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
foreach ($performance_data as $student) {
    $percentage = $student['average_percentage'] ?? 0;
    if ($percentage >= 80) $grade_distribution['A']++;
    elseif ($percentage >= 70) $grade_distribution['B']++;
    elseif ($percentage >= 60) $grade_distribution['C']++;
    elseif ($percentage >= 40) $grade_distribution['D']++;
    else $grade_distribution['F']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Reports - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .performance-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-box.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .stat-box.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .stat-box.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .stat-box.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .stat-box h3 {
            font-size: 36px;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-box p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .student-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--teacher-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .student-rank.gold {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        }
        
        .student-rank.silver {
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
        }
        
        .student-rank.bronze {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .percentage-bar {
            height: 10px;
            border-radius: 5px;
            background: #e5e7eb;
            overflow: hidden;
        }
        
        .percentage-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s;
        }
        
        .percentage-fill.low {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
        }
        
        .percentage-fill.medium {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper">
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Select Class</label>
                            <select class="form-select" name="class_id" required onchange="this.form.submit()">
                                <option value="">Choose a class...</option>
                                <?php foreach ($assigned_classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" 
                                        <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name'] . '-' . $class['section']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Select Subject</label>
                            <select class="form-select" name="subject_id" required onchange="this.form.submit()">
                                <option value="">Choose a subject...</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" 
                                        <?php echo $selected_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-chart-bar me-2"></i>View Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if ($class_stats && $selected_class && $selected_subject): ?>
            
            <!-- Overall Statistics -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box green">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo $class_stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box blue">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h3><?php echo $class_stats['average_percentage'] ?? '0'; ?>%</h3>
                        <p>Class Average</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box orange">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $class_stats['passed'] ?? '0'; ?></h3>
                        <p>Students Passed</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box red">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h3><?php echo $class_stats['failed'] ?? '0'; ?></h3>
                        <p>Students Failed</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <!-- Grade Distribution Chart -->
                <div class="col-lg-6">
                    <div class="performance-card">
                        <h5 class="mb-4">
                            <i class="fas fa-chart-pie text-primary me-2"></i>
                            Grade Distribution
                        </h5>
                        <canvas id="gradeDistributionChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Top & Bottom Performers -->
                <div class="col-lg-6">
                    <div class="performance-card">
                        <h5 class="mb-3">
                            <i class="fas fa-trophy text-warning me-2"></i>
                            Top 5 Performers
                        </h5>
                        <?php if (count($top_performers) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($top_performers as $index => $student): ?>
                                <div class="list-group-item d-flex align-items-center border-0 px-0">
                                    <div class="student-rank <?php 
                                        echo $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')); 
                                    ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex-fill ms-3">
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">Roll: <?php echo htmlspecialchars($student['roll_number']); ?></small>
                                    </div>
                                    <span class="badge bg-success fs-6"><?php echo $student['average_percentage']; ?>%</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">No data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Performance Table -->
            <div class="performance-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="fas fa-table text-info me-2"></i>
                        Detailed Student Performance
                    </h5>
                    <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i> Export to Excel
                    </button>
                </div>
                
                <?php if (count($performance_data) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="performanceTable">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Roll No.</th>
                                <th>Student Name</th>
                                <th>Total Exams</th>
                                <th>Marks Obtained</th>
                                <th>Total Marks</th>
                                <th>Average %</th>
                                <th>Performance</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($performance_data as $student): 
                                $percentage = $student['average_percentage'] ?? 0;
                                $grade = $percentage >= 80 ? 'A' : ($percentage >= 70 ? 'B' : ($percentage >= 60 ? 'C' : ($percentage >= 40 ? 'D' : 'F')));
                                $grade_class = $percentage >= 80 ? 'grade-a' : ($percentage >= 70 ? 'grade-b' : ($percentage >= 60 ? 'grade-c' : ($percentage >= 40 ? 'grade-d' : 'grade-f')));
                                $bar_class = $percentage >= 70 ? '' : ($percentage >= 40 ? 'medium' : 'low');
                            ?>
                            <tr>
                                <td><strong><?php echo $rank++; ?></strong></td>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo $student['total_exams'] ?? 0; ?></td>
                                <td><?php echo $student['total_marks_obtained'] ?? 0; ?></td>
                                <td><?php echo $student['total_marks_possible'] ?? 0; ?></td>
                                <td><strong><?php echo number_format($percentage, 2); ?>%</strong></td>
                                <td style="width: 150px;">
                                    <div class="percentage-bar">
                                        <div class="percentage-fill <?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="grade-badge <?php echo $grade_class; ?>"><?php echo $grade; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Performance Data Available</h5>
                    <p class="text-muted">Add marks to view performance reports.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Students Needing Attention -->
            <?php if (count($needs_attention) > 0): ?>
            <div class="performance-card mt-4">
                <h5 class="mb-3">
                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                    Students Needing Attention (Below 40%)
                </h5>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    The following students are performing below the passing threshold and may need additional support.
                </div>
                <div class="list-group">
                    <?php foreach ($needs_attention as $student): ?>
                    <div class="list-group-item d-flex align-items-center">
                        <div class="flex-fill">
                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                            <br>
                            <small class="text-muted">Roll: <?php echo htmlspecialchars($student['roll_number']); ?></small>
                        </div>
                        <span class="badge bg-danger fs-6"><?php echo $student['average_percentage']; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Select Class and Subject</h4>
                <p class="text-muted">Choose a class and subject from the filter above to view performance reports.</p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        // Grade Distribution Chart
        const gradeData = <?php echo json_encode(array_values($grade_distribution)); ?>;
        const gradeLabels = <?php echo json_encode(array_keys($grade_distribution)); ?>;
        
        const ctx = document.getElementById('gradeDistributionChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: gradeLabels,
                    datasets: [{
                        data: gradeData,
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',   // A - Green
                            'rgba(59, 130, 246, 0.8)',   // B - Blue
                            'rgba(245, 158, 11, 0.8)',   // C - Orange
                            'rgba(251, 146, 60, 0.8)',   // D - Light Orange
                            'rgba(239, 68, 68, 0.8)'     // F - Red
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': ' + value + ' students';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Export to Excel function (basic implementation)
        function exportToExcel() {
            alert('Export functionality coming soon! You can manually copy the table data for now.');
        }
    </script>

</body>
</html>
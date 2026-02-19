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
        case 'add':
            $data = [
                'student_id' => (int)$_POST['student_id'],
                'subject_id' => (int)$_POST['subject_id'],
                'exam_type' => sanitize_input($_POST['exam_type']),
                'exam_name' => sanitize_input($_POST['exam_name']),
                'marks_obtained' => floatval($_POST['marks_obtained']),
                'total_marks' => floatval($_POST['total_marks']),
                'exam_date' => sanitize_input($_POST['exam_date']),
                'remarks' => sanitize_input($_POST['remarks'])
            ];

            $query = "INSERT INTO marks (student_id, subject_id, exam_type, exam_name, marks_obtained, total_marks, exam_date, remarks) 
                     VALUES ({$data['student_id']}, {$data['subject_id']}, '{$data['exam_type']}', '{$data['exam_name']}', 
                     {$data['marks_obtained']}, {$data['total_marks']}, '{$data['exam_date']}', '{$data['remarks']}')";

            if (insert_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Marks added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add marks']);
            }
            exit;

        case 'update':
            $mark_id = (int)$_POST['mark_id'];
            $data = [
                'marks_obtained' => floatval($_POST['marks_obtained']),
                'total_marks' => floatval($_POST['total_marks']),
                'exam_date' => sanitize_input($_POST['exam_date']),
                'remarks' => sanitize_input($_POST['remarks'])
            ];

            $query = "UPDATE marks SET
                marks_obtained = {$data['marks_obtained']},
                total_marks = {$data['total_marks']},
                exam_date = '{$data['exam_date']}',
                remarks = '{$data['remarks']}'
            WHERE mark_id = $mark_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Marks updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update marks']);
            }
            exit;

        case 'delete':
            $mark_id = (int)$_POST['mark_id'];
            $query = "DELETE FROM marks WHERE mark_id = $mark_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Marks deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete marks']);
            }
            exit;

        case 'get':
            $mark_id = (int)$_POST['mark_id'];
            $query = "SELECT * FROM marks WHERE mark_id = $mark_id";
            $mark = get_row($query);

            if ($mark) {
                echo json_encode(['success' => true, 'data' => $mark]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mark not found']);
            }
            exit;

        case 'get_student_report':
            $student_id = (int)$_POST['student_id'];
            $query = "
                SELECT m.*, s.subject_name, s.subject_code,
                       ROUND((m.marks_obtained / m.total_marks) * 100, 2) as percentage
                FROM marks m
                JOIN subjects s ON m.subject_id = s.subject_id
                WHERE m.student_id = $student_id
                ORDER BY m.exam_date DESC
            ";
            $marks = get_all($query);

            // Get student info
            $student_query = "SELECT s.*, c.class_name, c.section, d.dept_code 
                             FROM students s 
                             LEFT JOIN classes c ON s.class_id = c.class_id
                             LEFT JOIN departments d ON s.dept_id = d.dept_id
                             WHERE s.student_id = $student_id";
            $student = get_row($student_query);

            echo json_encode(['success' => true, 'marks' => $marks, 'student' => $student]);
            exit;
    }
}

// Fetch students, subjects, classes
$students = get_all("SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.status = 'Active' ORDER BY s.roll_number");
$subjects = get_all("SELECT * FROM subjects ORDER BY subject_name");
$classes = get_all("SELECT * FROM classes ORDER BY class_name, section");

// Build filter query
$where_conditions = ["1=1"];
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : '';
$subject_filter = isset($_GET['subject']) ? (int)$_GET['subject'] : '';
$exam_filter = isset($_GET['exam']) ? sanitize_input($_GET['exam']) : '';

if (!empty($class_filter)) {
    $where_conditions[] = "s.class_id = $class_filter";
}
if (!empty($subject_filter)) {
    $where_conditions[] = "m.subject_id = $subject_filter";
}
if (!empty($exam_filter)) {
    $where_conditions[] = "m.exam_type = '$exam_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch marks records
$marks_query = "
    SELECT m.*, 
           s.roll_number, s.first_name, s.last_name,
           sub.subject_name, sub.subject_code,
           c.class_name, c.section,
           ROUND((m.marks_obtained / m.total_marks) * 100, 2) as percentage
    FROM marks m
    JOIN students s ON m.student_id = s.student_id
    JOIN subjects sub ON m.subject_id = sub.subject_id
    LEFT JOIN classes c ON s.class_id = c.class_id
    WHERE $where_clause
    ORDER BY m.exam_date DESC, s.roll_number
    LIMIT 50
";
$marks = get_all($marks_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT student_id) as total_students,
        ROUND(AVG((marks_obtained / total_marks) * 100), 2) as avg_percentage,
        COUNT(CASE WHEN (marks_obtained / total_marks) * 100 >= 90 THEN 1 END) as excellent_count,
        COUNT(CASE WHEN (marks_obtained / total_marks) * 100 >= 60 AND (marks_obtained / total_marks) * 100 < 90 THEN 1 END) as good_count,
        COUNT(CASE WHEN (marks_obtained / total_marks) * 100 < 60 THEN 1 END) as needs_improvement
    FROM marks
";
$stats = get_row($stats_query);

// Grade calculation function
function calculateGrade($percentage)
{
    if ($percentage >= 90) return ['grade' => 'A+', 'class' => 'success'];
    if ($percentage >= 80) return ['grade' => 'A', 'class' => 'success'];
    if ($percentage >= 70) return ['grade' => 'B+', 'class' => 'info'];
    if ($percentage >= 60) return ['grade' => 'B', 'class' => 'info'];
    if ($percentage >= 50) return ['grade' => 'C', 'class' => 'warning'];
    if ($percentage >= 40) return ['grade' => 'D', 'class' => 'warning'];
    return ['grade' => 'F', 'class' => 'danger'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks & Grades - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .grade-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }

        .percentage-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .percentage-fill {
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .percentage-fill.excellent {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .percentage-fill.good {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .percentage-fill.average {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .percentage-fill.poor {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 20px;
        }

        .report-header {
            border-bottom: 3px solid #6366f1;
            padding-bottom: 16px;
            margin-bottom: 20px;
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

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Examination Records</h2>
                    <p class="text-muted mb-0">Enter and manage student marks</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarksModal">
                        <i class="fas fa-plus me-2"></i>Add Marks
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reportCardModal">
                        <i class="fas fa-file-alt me-2"></i>Student Report
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-clipboard-list fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Records</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_records']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3">
                                        <i class="fas fa-award fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Excellent (90%+)</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['excellent_count']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-info bg-opacity-10 text-info rounded p-3">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Average %</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['avg_percentage'], 1); ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded p-3">
                                        <i class="fas fa-user-graduate fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Students Evaluated</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <select name="class" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo $class_filter == $class['class_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="subject" class="form-select">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>" <?php echo $subject_filter == $subject['subject_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="exam" class="form-select">
                                    <option value="">All Exams</option>
                                    <option value="Mid-Term" <?php echo $exam_filter == 'Mid-Term' ? 'selected' : ''; ?>>Mid-Term</option>
                                    <option value="Final" <?php echo $exam_filter == 'Final' ? 'selected' : ''; ?>>Final</option>
                                    <option value="Quiz" <?php echo $exam_filter == 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                                    <option value="Assignment" <?php echo $exam_filter == 'Assignment' ? 'selected' : ''; ?>>Assignment</option>
                                    <option value="Practical" <?php echo $exam_filter == 'Practical' ? 'selected' : ''; ?>>Practical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="marks.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Marks Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($marks) > 0): ?>
                                    <?php foreach ($marks as $mark):
                                        $grade = calculateGrade($mark['percentage']);
                                        $perf_class = $mark['percentage'] >= 90 ? 'excellent' : ($mark['percentage'] >= 60 ? 'good' : ($mark['percentage'] >= 40 ? 'average' : 'poor'));
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($mark['roll_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($mark['first_name'] . ' ' . $mark['last_name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($mark['class_name'] . '-' . $mark['section']); ?></span></td>
                                            <td><?php echo htmlspecialchars($mark['subject_code']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $mark['exam_type']; ?></span></td>
                                            <td><strong><?php echo $mark['marks_obtained']; ?>/<?php echo $mark['total_marks']; ?></strong></td>
                                            <td>
                                                <div class="percentage-bar mb-1">
                                                    <div class="percentage-fill <?php echo $perf_class; ?>" style="width: <?php echo $mark['percentage']; ?>%"></div>
                                                </div>
                                                <small><strong><?php echo $mark['percentage']; ?>%</strong></small>
                                            </td>
                                            <td>
                                                <span class="grade-badge bg-<?php echo $grade['class']; ?> text-white">
                                                    <?php echo $grade['grade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($mark['exam_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editMark(<?php echo $mark['mark_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteMark(<?php echo $mark['mark_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No marks records found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Add Marks Modal -->
    <div class="modal fade" id="addMarksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Exam Marks</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMarksForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Student *</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- Select Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['student_id']; ?>">
                                            <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['class_name'] . '-' . $student['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Subject *</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Exam Type *</label>
                                <select name="exam_type" class="form-select" required>
                                    <option value="Mid-Term">Mid-Term Exam</option>
                                    <option value="Final">Final Exam</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Assignment">Assignment</option>
                                    <option value="Practical">Practical Exam</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Exam Name *</label>
                                <input type="text" name="exam_name" class="form-control" placeholder="e.g., Mid-Term Exam 2026" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marks Obtained *</label>
                                <input type="number" name="marks_obtained" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" name="total_marks" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Exam Date *</label>
                                <input type="date" name="exam_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks about performance"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Marks Modal -->
    <div class="modal fade" id="editMarksModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Marks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMarksForm">
                    <input type="hidden" name="mark_id" id="edit_mark_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Marks Obtained *</label>
                                <input type="number" name="marks_obtained" id="edit_marks_obtained" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" name="total_marks" id="edit_total_marks" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Exam Date *</label>
                                <input type="date" name="exam_date" id="edit_exam_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Report Card Modal -->
    <div class="modal fade" id="reportCardModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Student Report Card</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <select id="reportStudentSelect" class="form-select" onchange="loadReport()">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['student_id']; ?>">
                                    <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="reportContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        // Add Marks
        document.getElementById('addMarksForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');

            fetch('marks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else {
                        alert(d.message);
                    }
                });
        });

        // Edit Mark
        function editMark(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('mark_id', id);

            fetch('marks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const m = d.data;
                        document.getElementById('edit_mark_id').value = m.mark_id;
                        document.getElementById('edit_marks_obtained').value = m.marks_obtained;
                        document.getElementById('edit_total_marks').value = m.total_marks;
                        document.getElementById('edit_exam_date').value = m.exam_date;
                        document.getElementById('edit_remarks').value = m.remarks;
                        new bootstrap.Modal(document.getElementById('editMarksModal')).show();
                    }
                });
        }

        // Update Marks
        document.getElementById('editMarksForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('marks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else {
                        alert(d.message);
                    }
                });
        });

        // Delete Mark
        function deleteMark(id) {
            if (confirm('Delete this mark record?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('mark_id', id);

                fetch('marks.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            alert(d.message);
                            location.reload();
                        } else {
                            alert(d.message);
                        }
                    });
            }
        }

        // Load Student Report
        function loadReport() {
            const studentId = document.getElementById('reportStudentSelect').value;
            if (!studentId) {
                document.getElementById('reportContent').innerHTML = '';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'get_student_report');
            formData.append('student_id', studentId);

            fetch('marks.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        displayReport(d.student, d.marks);
                    }
                });
        }

        function calculateGrade(percentage) {
            if (percentage >= 90) return {
                grade: 'A+',
                class: 'success'
            };
            if (percentage >= 80) return {
                grade: 'A',
                class: 'success'
            };
            if (percentage >= 70) return {
                grade: 'B+',
                class: 'info'
            };
            if (percentage >= 60) return {
                grade: 'B',
                class: 'info'
            };
            if (percentage >= 50) return {
                grade: 'C',
                class: 'warning'
            };
            if (percentage >= 40) return {
                grade: 'D',
                class: 'warning'
            };
            return {
                grade: 'F',
                class: 'danger'
            };
        }

        function displayReport(student, marks) {
            let html = `
            <div class="report-card">
                <div class="report-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="mb-2">${student.first_name} ${student.last_name}</h3>
                            <p class="mb-1"><strong>Roll Number:</strong> ${student.roll_number}</p>
                            <p class="mb-0"><strong>Class:</strong> ${student.class_name} - ${student.section} (${student.dept_code})</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Exam Type</th>
                                <th>Exam Name</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

            let totalPercentage = 0;
            marks.forEach(m => {
                const grade = calculateGrade(m.percentage);
                totalPercentage += parseFloat(m.percentage);
                html += `
                <tr>
                    <td><strong>${m.subject_name}</strong></td>
                    <td><span class="badge bg-secondary">${m.exam_type}</span></td>
                    <td>${m.exam_name}</td>
                    <td>${m.marks_obtained}/${m.total_marks}</td>
                    <td><strong>${m.percentage}%</strong></td>
                    <td><span class="grade-badge bg-${grade.class} text-white">${grade.grade}</span></td>
                    <td>${new Date(m.exam_date).toLocaleDateString()}</td>
                </tr>
            `;
            });

            const avgPercentage = marks.length > 0 ? (totalPercentage / marks.length).toFixed(2) : 0;
            const avgGrade = calculateGrade(avgPercentage);

            html += `
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Overall Average:</th>
                                <th>${avgPercentage}%</th>
                                <th><span class="grade-badge bg-${avgGrade.class} text-white">${avgGrade.grade}</span></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        `;

            document.getElementById('reportContent').innerHTML = html;
        }
    </script>

</body>

</html>
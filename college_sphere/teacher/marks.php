<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Marks & Grades";
$page_subtitle = "Add and manage student marks and grades";

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
$exam_type = $_GET['exam_type'] ?? 'Mid-Term';

// Get subject for selected class
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

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $exam_date = sanitize_input($_POST['exam_date']);
    $exam_name = sanitize_input($_POST['exam_name']);
    $total_marks = (float)$_POST['total_marks'];
    $marks_data = $_POST['marks'] ?? [];
    
    $success_count = 0;
    
    foreach ($marks_data as $student_id => $obtained_marks) {
        $student_id = (int)$student_id;
        $obtained_marks = (float)$obtained_marks;
        $remarks = sanitize_input($_POST['remarks'][$student_id] ?? '');
        
        if ($obtained_marks >= 0) {
            // Check if mark already exists
            $check_query = "
                SELECT mark_id FROM marks 
                WHERE student_id = {$student_id} 
                AND subject_id = {$selected_subject}
                AND exam_type = '{$exam_type}'
                AND exam_name = '{$exam_name}'
            ";
            $existing = get_row($check_query);
            
            if ($existing) {
                // Update existing
                $update_query = "
                    UPDATE marks 
                    SET marks_obtained = {$obtained_marks},
                        total_marks = {$total_marks},
                        exam_date = '{$exam_date}',
                        remarks = '{$remarks}'
                    WHERE mark_id = {$existing['mark_id']}
                ";
                execute_query($update_query);
            } else {
                // Insert new
                $insert_query = "
                    INSERT INTO marks (student_id, subject_id, exam_type, exam_name, marks_obtained, total_marks, exam_date, remarks)
                    VALUES ({$student_id}, {$selected_subject}, '{$exam_type}', '{$exam_name}', {$obtained_marks}, {$total_marks}, '{$exam_date}', '{$remarks}')
                ";
                execute_query($insert_query);
            }
            $success_count++;
        }
    }
    
    header("Location: marks.php?class_id={$selected_class}&subject_id={$selected_subject}&exam_type={$exam_type}&success=1");
    exit;
}

// Fetch students for selected class
$students = [];
if ($selected_class && $selected_subject) {
    $students_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            m.marks_obtained,
            m.total_marks,
            m.remarks
        FROM students s
        LEFT JOIN marks m ON s.student_id = m.student_id 
            AND m.subject_id = {$selected_subject}
            AND m.exam_type = '{$exam_type}'
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
        ORDER BY s.roll_number
    ";
    $students = get_all($students_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marks & Grades - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .marks-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .marks-input {
            width: 100px;
            text-align: center;
            font-weight: 600;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 8px;
        }
        
        .marks-input:focus {
            border-color: var(--teacher-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .grade-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .grade-a { background: #dcfce7; color: #166534; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef9c3; color: #854d0e; }
        .grade-d { background: #fed7aa; color: #9a3412; }
        .grade-f { background: #fecaca; color: #991b1b; }
        
        .filter-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .exam-info {
            background: linear-gradient(135deg, var(--teacher-primary) 0%, var(--teacher-primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper">
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Marks saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Select Class</label>
                            <select class="form-select" name="class_id" required onchange="this.form.submit()">
                                <option value="">Choose class...</option>
                                <?php foreach ($assigned_classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" 
                                        <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name'] . '-' . $class['section']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Select Subject</label>
                            <select class="form-select" name="subject_id" required onchange="this.form.submit()">
                                <option value="">Choose subject...</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" 
                                        <?php echo $selected_subject == $subject['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Exam Type</label>
                            <select class="form-select" name="exam_type" required onchange="this.form.submit()">
                                <option value="Quiz" <?php echo $exam_type == 'Quiz' ? 'selected' : ''; ?>>Quiz</option>
                                <option value="Mid-Term" <?php echo $exam_type == 'Mid-Term' ? 'selected' : ''; ?>>Mid-Term</option>
                                <option value="Final" <?php echo $exam_type == 'Final' ? 'selected' : ''; ?>>Final Exam</option>
                                <option value="Assignment" <?php echo $exam_type == 'Assignment' ? 'selected' : ''; ?>>Assignment</option>
                                <option value="Project" <?php echo $exam_type == 'Project' ? 'selected' : ''; ?>>Project</option>
                                <option value="Practical" <?php echo $exam_type == 'Practical' ? 'selected' : ''; ?>>Practical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (count($students) > 0): ?>
            
            <!-- Exam Info -->
            <div class="exam-info">
                <form method="POST" action="" id="marksForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Exam Name</label>
                            <input type="text" class="form-control" name="exam_name" 
                                   placeholder="e.g., Mid-Term Exam 2025" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exam Date</label>
                            <input type="date" class="form-control" name="exam_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Total Marks</label>
                            <input type="number" class="form-control" name="total_marks" 
                                   value="100" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="save_marks" class="btn btn-warning w-100">
                                <i class="fas fa-save me-2"></i>Save All Marks
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Marks Table -->
                <div class="marks-table mt-4">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Roll No.</th>
                                <th>Student Name</th>
                                <th style="width: 150px;">Marks Obtained</th>
                                <th style="width: 100px;">Grade</th>
                                <th style="width: 200px;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" 
                                           class="marks-input form-control" 
                                           name="marks[<?php echo $student['student_id']; ?>]"
                                           min="0" 
                                           step="0.01"
                                           value="<?php echo $student['marks_obtained'] ?? ''; ?>"
                                           placeholder="0"
                                           onchange="calculateGrade(this)">
                                </td>
                                <td>
                                    <span class="grade-display"></span>
                                </td>
                                <td>
                                    <input type="text" 
                                           class="form-control form-control-sm" 
                                           name="remarks[<?php echo $student['student_id']; ?>]"
                                           value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>"
                                           placeholder="Optional remarks">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Students Found</h4>
                <p class="text-muted">Please select a class and subject to add marks.</p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        // Calculate grade based on marks
        function calculateGrade(input) {
            const marks = parseFloat(input.value) || 0;
            const totalMarks = parseFloat(document.querySelector('input[name="total_marks"]').value) || 100;
            const percentage = (marks / totalMarks) * 100;
            
            const gradeDisplay = input.closest('tr').querySelector('.grade-display');
            
            let grade = '';
            let className = '';
            
            if (percentage >= 90) {
                grade = 'A+';
                className = 'grade-a';
            } else if (percentage >= 80) {
                grade = 'A';
                className = 'grade-a';
            } else if (percentage >= 70) {
                grade = 'B';
                className = 'grade-b';
            } else if (percentage >= 60) {
                grade = 'C';
                className = 'grade-c';
            } else if (percentage >= 40) {
                grade = 'D';
                className = 'grade-d';
            } else {
                grade = 'F';
                className = 'grade-f';
            }
            
            gradeDisplay.textContent = grade;
            gradeDisplay.className = 'grade-badge ' + className;
        }
        
        // Calculate grades on page load for existing marks
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.marks-input').forEach(input => {
                if (input.value) {
                    calculateGrade(input);
                }
            });
        });
        
        // Auto-save warning
        document.getElementById('marksForm')?.addEventListener('submit', (e) => {
            const emptyFields = Array.from(document.querySelectorAll('.marks-input'))
                .filter(input => !input.value || input.value === '0');
            
            if (emptyFields.length > 0) {
                if (!confirm(`${emptyFields.length} student(s) have no marks entered. Do you want to continue?`)) {
                    e.preventDefault();
                }
            }
        });
    </script>

</body>
</html>
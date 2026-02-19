<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Mark Attendance";
$page_subtitle = "Track student attendance for your classes";

// Fetch teacher's assigned classes
$classes_query = "
    SELECT DISTINCT
        c.class_id,
        c.class_name,
        c.section,
        s.subject_id,
        s.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.class_id
    JOIN subjects s ON tca.subject_id = s.subject_id
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
    ORDER BY c.class_name, c.section
";

$assigned_classes = get_all($classes_query);

// Get selected class and date
$selected_class = $_GET['class_id'] ?? ($assigned_classes[0]['class_id'] ?? null);
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch students for selected class
$students = [];
if ($selected_class) {
    $students_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            s.email,
            a.status as attendance_status,
            '' as remarks
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = '{$selected_date}'
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
        ORDER BY s.roll_number
    ";
    
    $students = get_all($students_query);
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $attendance_data = $_POST['attendance'] ?? [];
    $success_count = 0;
    
    foreach ($attendance_data as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = sanitize_input($status);
        $remarks = sanitize_input($_POST['remarks'][$student_id] ?? '');
        
        // Check if attendance already exists
        $check_query = "SELECT attendance_id FROM attendance WHERE student_id = {$student_id} AND attendance_date = '{$selected_date}'";
        $existing = get_row($check_query);
        
        if ($existing) {
            // Update existing record
            $update_query = "
                UPDATE attendance 
                SET status = '{$status}', updated_at = NOW()
                WHERE student_id = {$student_id} AND attendance_date = '{$selected_date}'
            ";
            execute_query($update_query);
        } else {
            // Insert new record
            $insert_query = "
                INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by)
                VALUES ({$student_id}, {$selected_class}, '{$selected_date}', '{$status}', {$teacher_id})
            ";
            execute_query($insert_query);
        }
        $success_count++;
    }
    
    // Refresh page with success message
    header("Location: attendance.php?class_id={$selected_class}&date={$selected_date}&success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        :root {
            --teacher-primary: #10b981;
        }
        
        .attendance-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .student-row {
            transition: all 0.3s;
        }
        
        .student-row:hover {
            background: #f8fafc;
        }
        
        .status-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 13px;
        }
        
        .status-btn.present {
            border-color: var(--teacher-primary);
            background: var(--teacher-primary);
            color: white;
        }
        
        .status-btn.absent {
            border-color: #ef4444;
            background: #ef4444;
            color: white;
        }
        
        .status-btn.late {
            border-color: #f59e0b;
            background: #f59e0b;
            color: white;
        }
        
        .bulk-actions {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .date-selector {
            max-width: 200px;
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
                Attendance marked successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="bulk-actions">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Select Class</label>
                        <select class="form-select" id="classSelector" onchange="changeClass(this.value)">
                            <option value="">Choose a class...</option>
                            <?php foreach ($assigned_classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>" 
                                    <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name'] . '-' . $class['section'] . ' (' . $class['subject_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Select Date</label>
                        <input type="date" 
                               class="form-control date-selector" 
                               id="dateSelector" 
                               value="<?php echo $selected_date; ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               onchange="changeDate(this.value)">
                    </div>
                    
                    <div class="col-md-5 text-end">
                        <button type="button" class="btn btn-success me-2" onclick="markAllPresent()">
                            <i class="fas fa-check-double me-1"></i> Mark All Present
                        </button>
                        <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                            <i class="fas fa-times-circle me-1"></i> Mark All Absent
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (count($students) > 0): ?>
            <form method="POST" action="">
                <div class="attendance-table">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Roll No.</th>
                                <th>Student Name</th>
                                <th style="width: 300px;">Attendance Status</th>
                                <th style="width: 250px;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="student-row">
                                <td>
                                    <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="attendance[<?php echo $student['student_id']; ?>]" 
                                               id="present_<?php echo $student['student_id']; ?>" 
                                               value="Present"
                                               <?php echo $student['attendance_status'] == 'Present' ? 'checked' : ''; ?>>
                                        <label class="status-btn <?php echo $student['attendance_status'] == 'Present' ? 'present' : ''; ?>" 
                                               for="present_<?php echo $student['student_id']; ?>">
                                            <i class="fas fa-check me-1"></i> Present
                                        </label>
                                        
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="attendance[<?php echo $student['student_id']; ?>]" 
                                               id="absent_<?php echo $student['student_id']; ?>" 
                                               value="Absent"
                                               <?php echo $student['attendance_status'] == 'Absent' ? 'checked' : ''; ?>>
                                        <label class="status-btn <?php echo $student['attendance_status'] == 'Absent' ? 'absent' : ''; ?>" 
                                               for="absent_<?php echo $student['student_id']; ?>">
                                            <i class="fas fa-times me-1"></i> Absent
                                        </label>
                                        
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="attendance[<?php echo $student['student_id']; ?>]" 
                                               id="late_<?php echo $student['student_id']; ?>" 
                                               value="Late"
                                               <?php echo $student['attendance_status'] == 'Late' ? 'checked' : ''; ?>>
                                        <label class="status-btn <?php echo $student['attendance_status'] == 'Late' ? 'late' : ''; ?>" 
                                               for="late_<?php echo $student['student_id']; ?>">
                                            <i class="fas fa-clock me-1"></i> Late
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" 
                                           class="form-control form-control-sm" 
                                           name="remarks[<?php echo $student['student_id']; ?>]" 
                                           placeholder="Optional remarks"
                                           value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" name="submit_attendance" class="btn btn-success btn-lg px-5">
                        <i class="fas fa-save me-2"></i> Save Attendance
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Students Found</h4>
                <p>Please select a class to mark attendance.</p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        function changeClass(classId) {
            if (classId) {
                const date = document.getElementById('dateSelector').value;
                window.location.href = `attendance.php?class_id=${classId}&date=${date}`;
            }
        }
        
        function changeDate(date) {
            const classId = document.getElementById('classSelector').value;
            if (classId) {
                window.location.href = `attendance.php?class_id=${classId}&date=${date}`;
            }
        }
        
        function markAllPresent() {
            document.querySelectorAll('input[value="Present"]').forEach(radio => {
                radio.checked = true;
                radio.labels[0].classList.add('present');
                // Remove other classes
                const studentId = radio.id.split('_')[1];
                document.querySelector(`label[for="absent_${studentId}"]`).classList.remove('absent');
                document.querySelector(`label[for="late_${studentId}"]`).classList.remove('late');
            });
        }
        
        function markAllAbsent() {
            document.querySelectorAll('input[value="Absent"]').forEach(radio => {
                radio.checked = true;
                radio.labels[0].classList.add('absent');
                // Remove other classes
                const studentId = radio.id.split('_')[1];
                document.querySelector(`label[for="present_${studentId}"]`).classList.remove('present');
                document.querySelector(`label[for="late_${studentId}"]`).classList.remove('late');
            });
        }
        
        // Update button styling on click
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling || this.nextElementSibling;
                const studentId = input.id.split('_')[1];
                
                // Remove all status classes for this student
                document.querySelector(`label[for="present_${studentId}"]`).classList.remove('present');
                document.querySelector(`label[for="absent_${studentId}"]`).classList.remove('absent');
                document.querySelector(`label[for="late_${studentId}"]`).classList.remove('late');
                
                // Add appropriate class
                if (input.value === 'Present') {
                    this.classList.add('present');
                } else if (input.value === 'Absent') {
                    this.classList.add('absent');
                } else if (input.value === 'Late') {
                    this.classList.add('late');
                }
            });
        });
    </script>

</body>
</html>
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
        case 'mark_attendance':
            $class_id = (int)$_POST['class_id'];
            $attendance_date = sanitize_input($_POST['attendance_date']);
            $attendance_data = json_decode($_POST['attendance_data'], true);

            $success_count = 0;
            foreach ($attendance_data as $student_id => $status) {
                $student_id = (int)$student_id;
                $status = sanitize_input($status);

                // Check if already marked
                $check_query = "SELECT attendance_id FROM attendance 
                               WHERE student_id = $student_id AND attendance_date = '$attendance_date'";
                $existing = get_row($check_query);

                if ($existing) {
                    // Update existing
                    $query = "UPDATE attendance SET status = '$status' 
                             WHERE student_id = $student_id AND attendance_date = '$attendance_date'";
                } else {
                    // Insert new
                    $query = "INSERT INTO attendance (student_id, class_id, attendance_date, status) 
                             VALUES ($student_id, $class_id, '$attendance_date', '$status')";
                }

                if (execute_query($query)) {
                    $success_count++;
                }
            }

            echo json_encode(['success' => true, 'message' => "Attendance marked for $success_count students"]);
            exit;

        case 'get_attendance':
            $class_id = (int)$_POST['class_id'];
            $attendance_date = sanitize_input($_POST['attendance_date']);

            $query = "SELECT s.student_id, s.roll_number, s.first_name, s.last_name, 
                            COALESCE(a.status, 'Not Marked') as status
                     FROM students s
                     LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = '$attendance_date'
                     WHERE s.class_id = $class_id AND s.status = 'Active'
                     ORDER BY s.roll_number";

            $students = get_all($query);
            echo json_encode(['success' => true, 'data' => $students]);
            exit;
    }
}

// Fetch classes
$classes = get_all("SELECT c.*, d.dept_code FROM classes c LEFT JOIN departments d ON c.dept_id = d.dept_id ORDER BY c.class_name, c.section");

// Get today's date
$today = date('Y-m-d');

// Get attendance statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT student_id) as total_students_marked,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE attendance_date = '$today'
";
$stats = get_row($stats_query);

// Get recent attendance records
$recent_query = "
    SELECT a.*, s.first_name, s.last_name, s.roll_number, c.class_name, c.section
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    JOIN classes c ON a.class_id = c.class_id
    WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.attendance_date DESC, s.roll_number
    LIMIT 50
";
$recent_attendance = get_all($recent_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .attendance-row {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .attendance-row:hover {
            background: #f8fafc;
        }

        .status-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
        }

        .status-btn.present {
            background: rgba(16, 185, 129, 0.1);
            border-color: #10b981;
            color: #10b981;
        }

        .status-btn.absent {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #ef4444;
        }

        .status-btn.late {
            background: rgba(245, 158, 11, 0.1);
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .status-btn.active {
            font-weight: 600;
            transform: scale(1.05);
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

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Today's Marked</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_students_marked'] ?? 0); ?></h3>
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
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Present</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['present_count'] ?? 0); ?></h3>
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
                                    <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded p-3">
                                        <i class="fas fa-times fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Absent</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['absent_count'] ?? 0); ?></h3>
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
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Late</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['late_count'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mark Attendance Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Select Class *</label>
                            <select id="classSelect" class="form-select">
                                <option value="">-- Select Class --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Select Date *</label>
                            <input type="date" id="attendanceDate" class="form-control" value="<?php echo $today; ?>" max="<?php echo $today; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadStudents()">
                                <i class="fas fa-search me-2"></i>Load Students
                            </button>
                        </div>
                    </div>

                    <div id="studentsList"></div>

                    <div id="submitSection" style="display: none;">
                        <button class="btn btn-success btn-lg w-100 mt-3" onclick="submitAttendance()">
                            <i class="fas fa-save me-2"></i>Save Attendance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance Records -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Attendance Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['class_name'] . ' - ' . $record['section']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                                    echo $record['status'] == 'Present' ? 'success' : ($record['status'] == 'Absent' ? 'danger' : 'warning');
                                                                    ?>">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        let attendanceData = {};

        function loadStudents() {
            const classId = document.getElementById('classSelect').value;
            const date = document.getElementById('attendanceDate').value;

            if (!classId || !date) {
                alert('Please select both class and date');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'get_attendance');
            formData.append('class_id', classId);
            formData.append('attendance_date', date);

            fetch('attendance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudents(data.data);
                    }
                });
        }

        function displayStudents(students) {
            const container = document.getElementById('studentsList');
            if (students.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No students found in this class</p>';
                return;
            }

            let html = '<div class="row g-3">';
            students.forEach(student => {
                const defaultStatus = student.status !== 'Not Marked' ? student.status : 'Present';
                attendanceData[student.student_id] = defaultStatus;

                html += `
                <div class="col-12">
                    <div class="attendance-row d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${student.roll_number}</strong> - ${student.first_name} ${student.last_name}
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="status-btn present ${defaultStatus === 'Present' ? 'active' : ''}" 
                                    onclick="markStatus(${student.student_id}, 'Present', this)">
                                <i class="fas fa-check"></i> Present
                            </button>
                            <button type="button" class="status-btn absent ${defaultStatus === 'Absent' ? 'active' : ''}" 
                                    onclick="markStatus(${student.student_id}, 'Absent', this)">
                                <i class="fas fa-times"></i> Absent
                            </button>
                            <button type="button" class="status-btn late ${defaultStatus === 'Late' ? 'active' : ''}" 
                                    onclick="markStatus(${student.student_id}, 'Late', this)">
                                <i class="fas fa-clock"></i> Late
                            </button>
                        </div>
                    </div>
                </div>
            `;
            });
            html += '</div>';
            container.innerHTML = html;
            document.getElementById('submitSection').style.display = 'block';
        }

        function markStatus(studentId, status, button) {
            attendanceData[studentId] = status;

            // Remove active class from siblings
            const siblings = button.parentElement.querySelectorAll('.status-btn');
            siblings.forEach(btn => btn.classList.remove('active'));

            // Add active class to clicked button
            button.classList.add('active');
        }

        function submitAttendance() {
            const classId = document.getElementById('classSelect').value;
            const date = document.getElementById('attendanceDate').value;

            const formData = new FormData();
            formData.append('action', 'mark_attendance');
            formData.append('class_id', classId);
            formData.append('attendance_date', date);
            formData.append('attendance_data', JSON.stringify(attendanceData));

            fetch('attendance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        }
    </script>

</body>

</html>
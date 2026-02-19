<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "My Students";
$page_subtitle = "View and manage students in your classes";

// Fetch teacher's assigned classes for filter
$classes_query = "
    SELECT DISTINCT
        c.class_id,
        c.class_name,
        c.section
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.class_id
    WHERE tca.teacher_id = {$teacher_id}
    AND tca.academic_year = '2024-2025'
    ORDER BY c.class_name, c.section
";
$assigned_classes = get_all($classes_query);

// Get selected class (default to first class)
$selected_class = $_GET['class_id'] ?? ($assigned_classes[0]['class_id'] ?? null);
$search = $_GET['search'] ?? '';

// Fetch students for selected class
$students = [];
$class_info = null;

if ($selected_class) {
    // Get class information
    $class_info_query = "
        SELECT c.class_name, c.section, c.capacity,
               COUNT(s.student_id) as student_count
        FROM classes c
        LEFT JOIN students s ON c.class_id = s.class_id AND s.status = 'Active'
        WHERE c.class_id = {$selected_class}
        GROUP BY c.class_id
    ";
    $class_info = get_row($class_info_query);
    
    // Build students query with search
    $students_query = "
        SELECT 
            s.student_id,
            s.roll_number,
            s.first_name,
            s.last_name,
            s.email,
            s.phone,
            s.gender,
            s.date_of_birth,
            s.guardian_name,
            s.guardian_phone,
            d.dept_name,
            d.dept_code
        FROM students s
        LEFT JOIN departments d ON s.dept_id = d.dept_id
        WHERE s.class_id = {$selected_class}
        AND s.status = 'Active'
    ";
    
    if (!empty($search)) {
        $search_safe = sanitize_input($search);
        $students_query .= " AND (
            s.roll_number LIKE '%{$search_safe}%' OR
            s.first_name LIKE '%{$search_safe}%' OR
            s.last_name LIKE '%{$search_safe}%' OR
            s.email LIKE '%{$search_safe}%'
        )";
    }
    
    $students_query .= " ORDER BY s.roll_number";
    $students = get_all($students_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .student-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid var(--teacher-primary);
        }
        
        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teacher-primary) 0%, var(--teacher-primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }
        
        .student-info h5 {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 4px 0;
        }
        
        .student-info .roll-number {
            color: var(--teacher-primary);
            font-weight: 600;
            font-size: 14px;
        }
        
        .student-detail {
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 4px;
        }
        
        .student-detail i {
            width: 18px;
            color: var(--gray-400);
        }
        
        .class-stats {
            background: linear-gradient(135deg, var(--teacher-primary) 0%, var(--teacher-primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        
        .class-stats h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        
        .class-stats p {
            margin: 0;
            opacity: 0.9;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper">
            
            <!-- Class Stats -->
            <?php if ($class_info): ?>
            <div class="class-stats">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3><?php echo htmlspecialchars($class_info['class_name'] . '-' . $class_info['section']); ?></h3>
                        <p>Total Students: <?php echo $class_info['student_count']; ?> / <?php echo $class_info['capacity']; ?> (Capacity)</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                            <a href="attendance.php?class_id=<?php echo $selected_class; ?>" class="btn btn-light">
                                <i class="fas fa-clipboard-check me-2"></i>Mark Attendance
                            </a>
                            <a href="marks.php?class_id=<?php echo $selected_class; ?>" class="btn btn-warning">
                                <i class="fas fa-star me-2"></i>Add Marks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Select Class</label>
                            <select class="form-select" name="class_id" onchange="this.form.submit()">
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
                            <label class="form-label fw-bold">Search Students</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Search by name, roll number, email..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Students List -->
            <?php if (count($students) > 0): ?>
            <div class="row g-4">
                <?php foreach ($students as $student): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="student-card">
                        <div class="d-flex gap-3 mb-3">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                            </div>
                            <div class="student-info flex-fill">
                                <h5><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                <span class="roll-number">
                                    <i class="fas fa-id-card me-1"></i>
                                    <?php echo htmlspecialchars($student['roll_number']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="student-details">
                            <div class="student-detail">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($student['email']); ?>
                            </div>
                            <div class="student-detail">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?>
                            </div>
                            <div class="student-detail">
                                <i class="fas fa-venus-mars"></i>
                                <?php echo htmlspecialchars($student['gender']); ?>
                            </div>
                            <div class="student-detail">
                                <i class="fas fa-birthday-cake"></i>
                                <?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?>
                            </div>
                            <?php if ($student['guardian_name']): ?>
                            <div class="student-detail mt-2 pt-2 border-top">
                                <i class="fas fa-user-shield"></i>
                                <strong>Guardian:</strong> <?php echo htmlspecialchars($student['guardian_name']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary flex-fill" 
                                    onclick="viewStudent(<?php echo $student['student_id']; ?>)">
                                <i class="fas fa-eye me-1"></i> View Details
                            </button>
                            <a href="attendance.php?class_id=<?php echo $selected_class; ?>&student_id=<?php echo $student['student_id']; ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($selected_class): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Students Found</h4>
                <p class="text-muted">
                    <?php echo !empty($search) ? 'Try adjusting your search criteria.' : 'No students in this class yet.'; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Select a Class</h4>
                <p class="text-muted">Choose a class from the dropdown to view students.</p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        function viewStudent(studentId) {
            alert('View student details - Student ID: ' + studentId);
            // Implement student details modal or page
        }
    </script>

</body>
</html>
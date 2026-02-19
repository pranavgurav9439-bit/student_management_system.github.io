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
        // REMOVED: case 'add' - Students register through signup page

        case 'update':
            $student_id = (int)$_POST['student_id'];

            if ($student_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
                exit;
            }

            // Use prepared statement for security
            $stmt = $conn->prepare("UPDATE students SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                date_of_birth = ?,
                gender = ?,
                address = ?,
                city = ?,
                state = ?,
                pincode = ?,
                class_id = ?,
                dept_id = ?,
                guardian_name = ?,
                guardian_phone = ?,
                status = ?
            WHERE student_id = ?");

            if ($stmt) {
                $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;

                $stmt->bind_param(
                    "ssssssssssiiissi",
                    sanitize_input($_POST['first_name']),
                    sanitize_input($_POST['last_name']),
                    sanitize_input($_POST['email']),
                    sanitize_input($_POST['phone']),
                    sanitize_input($_POST['date_of_birth']),
                    sanitize_input($_POST['gender']),
                    sanitize_input($_POST['address']),
                    sanitize_input($_POST['city']),
                    sanitize_input($_POST['state']),
                    sanitize_input($_POST['pincode']),
                    $class_id,
                    (int)$_POST['dept_id'],
                    sanitize_input($_POST['guardian_name']),
                    sanitize_input($_POST['guardian_phone']),
                    sanitize_input($_POST['status']),
                    $student_id
                );

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
            }
            exit;

        case 'delete':
            $student_id = (int)$_POST['student_id'];
            $query = "DELETE FROM students WHERE student_id = $student_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
            }
            exit;

        case 'get':
            $student_id = (int)$_POST['student_id'];
            $query = "SELECT * FROM students WHERE student_id = $student_id";
            $student = get_row($query);

            if ($student) {
                echo json_encode(['success' => true, 'data' => $student]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
            }
            exit;

        case 'upload_document':
            $student_id = (int)$_POST['student_id'];
            $document_type = sanitize_input($_POST['document_type']);

            if (!isset($_FILES['document']) || $_FILES['document']['error'] != 0) {
                echo json_encode(['success' => false, 'message' => 'Please select a file']);
                exit;
            }

            // Get student roll number for folder creation
            $student = get_row("SELECT roll_number FROM students WHERE student_id = {$student_id}");
            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                exit;
            }

            $upload_dir = '../uploads/student_documents/' . $student['roll_number'] . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES['document']['name'];
            $file_size = $_FILES['document']['size'];
            $file_tmp = $_FILES['document']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, PNG allowed.']);
                exit;
            }

            if ($file_size > $max_file_size) {
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
                exit;
            }

            // Delete old document of same type if exists
            $old_doc = get_row("SELECT document_id FROM student_documents 
                                WHERE student_id = {$student_id} 
                                AND document_type = '{$document_type}' 
                                AND status = 'Active'");
            if ($old_doc) {
                execute_query("UPDATE student_documents SET status = 'Deleted' 
                               WHERE document_id = {$old_doc['document_id']}");
            }

            // Upload new file
            $new_file_name = $document_type . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $file_path = 'uploads/student_documents/' . $student['roll_number'] . '/' . $new_file_name;
                $doc_name = ucfirst(str_replace('_', ' ', $document_type));

                $query = "INSERT INTO student_documents 
                          (student_id, document_type, document_name, file_path, file_size, uploaded_by, uploaded_date) 
                          VALUES ({$student_id}, '{$document_type}', '{$doc_name}', '{$file_path}', {$file_size}, {$_SESSION['admin_id']}, NOW())";

                if (execute_query($query)) {
                    echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save document info']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            }
            exit;

        case 'get_documents':
            $student_id = (int)$_POST['student_id'];

            $query = "SELECT document_id, document_type, document_name, file_path, 
                      DATE_FORMAT(uploaded_date, '%b %d, %Y') as upload_date
                      FROM student_documents 
                      WHERE student_id = {$student_id} AND status = 'Active'
                      ORDER BY uploaded_date DESC";

            $documents = get_all($query);
            echo json_encode(['success' => true, 'documents' => $documents]);
            exit;

        case 'delete_document':
            $document_id = (int)$_POST['document_id'];

            $query = "UPDATE student_documents SET status = 'Deleted' WHERE document_id = {$document_id}";

            if (execute_query($query)) {
                echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete document']);
            }
            exit;
    }
}

// Fetch departments for dropdown
$departments = get_all("SELECT * FROM departments ORDER BY dept_name");

// Fetch classes for dropdown
$classes = get_all("SELECT c.*, d.dept_code FROM classes c LEFT JOIN departments d ON c.dept_id = d.dept_id ORDER BY c.class_name, c.section");

// Build search and filter query
$where_conditions = ["1=1"];
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

if (!empty($search)) {
    $where_conditions[] = "(s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR s.roll_number LIKE '%$search%' OR s.email LIKE '%$search%')";
}
if (!empty($class_filter)) {
    $where_conditions[] = "s.class_id = $class_filter";
}
if (!empty($dept_filter)) {
    $where_conditions[] = "s.dept_id = $dept_filter";
}
if (!empty($status_filter)) {
    $where_conditions[] = "s.status = '$status_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM students s WHERE $where_clause";
$total_result = get_row($count_query);
$total_students = $total_result['total'];
$total_pages = ceil($total_students / $per_page);

// Fetch students
$students_query = "
    SELECT s.*, 
           c.class_name, c.section,
           d.dept_name, d.dept_code
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN departments d ON s.dept_id = d.dept_id
    WHERE $where_clause
    ORDER BY s.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$students = get_all($students_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive
    FROM students
";
$stats = get_row($stats_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .action-buttons .btn {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-status.active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .badge-status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .modal-header {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Document Management Styles */
        .document-item {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .document-item:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .doc-icon {
            font-size: 40px;
            color: #0d6efd;
        }

        .nav-tabs .nav-link {
            color: #6c757d;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }

        .badge {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Navigation Menu -->

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">

        <?php include 'includes/navbar.php'; ?>

        <!-- TOP HEADER -->

        <!-- PAGE CONTENT -->
        <div class="dashboard-container">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Student Management</h2>
                    <p class="text-muted mb-0">Manage all student records and information</p>
                </div>
                <!-- ADD STUDENT BUTTON REMOVED -->
                <div class="alert alert-info mb-0" style="max-width: 400px;">
                    <i class="fas fa-info-circle me-2"></i>
                    Students register through <a href="../student/signup.php" target="_blank" class="alert-link">Signup Page</a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Students</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Active Students</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['active']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded p-3">
                                        <i class="fas fa-user-times fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Inactive Students</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['inactive']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Search students..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="dept" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>"
                                        <?php echo $dept_filter == $dept['dept_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="class" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>"
                                        <?php echo $class_filter == $class['class_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="students.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Class</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) > 0): ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                            <td><?php echo htmlspecialchars(($student['class_name'] ?? 'N/A') . ' - ' . ($student['section'] ?? '')); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($student['dept_code']); ?></span></td>
                                            <td>
                                                <span class="badge-status <?php echo strtolower($student['status']); ?>">
                                                    <?php echo $student['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-warning" onclick="editStudent(<?php echo $student['student_id']; ?>)" title="Edit & Manage Documents">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['student_id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No students found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>

    <!-- ADD STUDENT MODAL REMOVED - Students register through signup page -->

    <!-- Edit Student Modal with Document Management -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit Student
                        <span id="student_name_display" class="text-muted"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- Nav Tabs -->
                <ul class="nav nav-tabs px-3 pt-3" id="editStudentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                            data-bs-target="#details" type="button" role="tab">
                            <i class="fas fa-info-circle me-1"></i>Student Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab"
                            data-bs-target="#documents" type="button" role="tab">
                            <i class="fas fa-file-upload me-1"></i>Documents
                            <span class="badge bg-primary" id="doc_count">0</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="editStudentTabContent">
                    <!-- Student Details Tab -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <form id="editStudentForm">
                            <input type="hidden" name="student_id" id="edit_student_id">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Roll Number</label>
                                        <input type="text" id="edit_roll_number" class="form-control" readonly style="background: #f8f9fa;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status *</label>
                                        <select name="status" id="edit_status" class="form-select" required>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Suspended">Suspended</option>
                                            <option value="Graduated">Graduated</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" id="edit_email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" id="edit_phone" class="form-control" pattern="[0-9]{10}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gender *</label>
                                        <select name="gender" id="edit_gender" class="form-select" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" id="edit_city" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" id="edit_state" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" name="pincode" id="edit_pincode" class="form-control" pattern="[0-9]{6}">
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6><i class="fas fa-user-shield me-2"></i>Guardian Information</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Guardian Name</label>
                                        <input type="text" name="guardian_name" id="edit_guardian_name" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Guardian Phone</label>
                                        <input type="tel" name="guardian_phone" id="edit_guardian_phone" class="form-control" pattern="[0-9]{10}">
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Department *</label>
                                        <select name="dept_id" id="edit_dept_id" class="form-select" required>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept['dept_id']; ?>">
                                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Class</label>
                                        <select name="class_id" id="edit_class_id" class="form-select">
                                            <option value="">Not Assigned</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class['class_id']; ?>">
                                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Student
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="documents" role="tabpanel">
                        <div class="modal-body">
                            <!-- Upload Document Section -->
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload New Document
                                </div>
                                <div class="card-body">
                                    <form id="uploadDocumentForm">
                                        <input type="hidden" name="student_id" id="doc_student_id">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Document Type *</label>
                                                <select name="document_type" class="form-select" required>
                                                    <option value="">Select Type</option>
                                                    <option value="aadhar">Aadhar Card</option>
                                                    <option value="tenth_marksheet">10th Marksheet</option>
                                                    <option value="twelfth_marksheet">12th Marksheet</option>
                                                    <option value="other_documents">Other Documents</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Choose File *</label>
                                                <input type="file" name="document" class="form-control"
                                                    accept=".pdf,.jpg,.jpeg,.png" required>
                                                <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-upload me-2"></i>Upload Document
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Existing Documents Section -->
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-folder-open me-2"></i>Uploaded Documents
                                </div>
                                <div class="card-body">
                                    <div id="documents_list">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                                            <p class="mt-2">Loading documents...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        // REMOVED: Add Student Form Handler

        // Load documents when Documents tab is clicked
        document.getElementById('documents-tab').addEventListener('click', function() {
            loadStudentDocuments();
        });

        // Load Student Documents
        function loadStudentDocuments() {
            const studentId = document.getElementById('edit_student_id').value;

            if (!studentId) return;

            const formData = new FormData();
            formData.append('action', 'get_documents');
            formData.append('student_id', studentId);

            fetch('students.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDocuments(data.documents);
                        document.getElementById('doc_count').textContent = data.documents.length;
                    } else {
                        document.getElementById('documents_list').innerHTML =
                            '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No documents uploaded yet.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('documents_list').innerHTML =
                        '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Error loading documents.</div>';
                });
        }

        // Display Documents in List
        function displayDocuments(documents) {
            const listContainer = document.getElementById('documents_list');

            if (documents.length === 0) {
                listContainer.innerHTML =
                    '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No documents uploaded yet.</div>';
                return;
            }

            let html = '';
            documents.forEach(doc => {
                const iconClass = doc.file_path.endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file-image';
                const iconColor = doc.file_path.endsWith('.pdf') ? 'text-danger' : 'text-primary';

                html += `
                    <div class="document-item">
                        <div class="d-flex align-items-center">
                            <div class="doc-icon ${iconColor} me-3">
                                <i class="fas ${iconClass}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${doc.document_name}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>Uploaded: ${doc.upload_date}
                                </small>
                            </div>
                            <div>
                                <a href="../${doc.file_path}" target="_blank" class="btn btn-sm btn-outline-primary me-2" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="deleteDocument(${doc.document_id})" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            listContainer.innerHTML = html;
        }

        // Upload Document
        document.getElementById('uploadDocumentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'upload_document');

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            submitBtn.disabled = true;

            fetch('students.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        this.reset();
                        loadStudentDocuments(); // Reload documents list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Upload failed. Please try again.');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Delete Document
        function deleteDocument(documentId) {
            if (!confirm('Are you sure you want to delete this document?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_document');
            formData.append('document_id', documentId);

            fetch('students.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadStudentDocuments(); // Reload documents list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Delete failed. Please try again.');
                });
        }

        // Edit Student
        function editStudent(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('student_id', id);

            fetch('students.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const student = data.data;

                        // Set all form fields
                        document.getElementById('edit_student_id').value = student.student_id;
                        document.getElementById('doc_student_id').value = student.student_id; // For document upload
                        document.getElementById('edit_roll_number').value = student.roll_number;
                        document.getElementById('edit_first_name').value = student.first_name;
                        document.getElementById('edit_last_name').value = student.last_name;
                        document.getElementById('edit_email').value = student.email;
                        document.getElementById('edit_phone').value = student.phone || '';
                        document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
                        document.getElementById('edit_gender').value = student.gender;
                        document.getElementById('edit_dept_id').value = student.dept_id;
                        document.getElementById('edit_class_id').value = student.class_id || '';
                        document.getElementById('edit_address').value = student.address || '';
                        document.getElementById('edit_city').value = student.city || '';
                        document.getElementById('edit_state').value = student.state || '';
                        document.getElementById('edit_pincode').value = student.pincode || '';
                        document.getElementById('edit_guardian_name').value = student.guardian_name || '';
                        document.getElementById('edit_guardian_phone').value = student.guardian_phone || '';
                        document.getElementById('edit_status').value = student.status;

                        // Set student name in modal header
                        document.getElementById('student_name_display').textContent =
                            `(${student.roll_number} - ${student.first_name} ${student.last_name})`;

                        // Load documents
                        loadStudentDocuments();

                        // Show modal
                        new bootstrap.Modal(document.getElementById('editStudentModal')).show();
                    }
                });
        }

        // Update Student
        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('students.php', {
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
        });

        // Delete Student
        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('student_id', id);

                fetch('students.php', {
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
        }
    </script>

</body>

</html>
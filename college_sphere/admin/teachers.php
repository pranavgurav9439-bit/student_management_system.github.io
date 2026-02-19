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
                'employee_id' => sanitize_input($_POST['employee_id']),
                'first_name' => sanitize_input($_POST['first_name']),
                'last_name' => sanitize_input($_POST['last_name']),
                'email' => sanitize_input($_POST['email']),
                'phone' => sanitize_input($_POST['phone']),
                'date_of_birth' => sanitize_input($_POST['date_of_birth']),
                'gender' => sanitize_input($_POST['gender']),
                'address' => sanitize_input($_POST['address']),
                'city' => sanitize_input($_POST['city']),
                'state' => sanitize_input($_POST['state']),
                'pincode' => sanitize_input($_POST['pincode']),
                'dept_id' => (int)$_POST['dept_id'],
                'designation' => sanitize_input($_POST['designation']),
                'qualification' => sanitize_input($_POST['qualification']),
                'experience_years' => (int)$_POST['experience_years'],
                'joining_date' => sanitize_input($_POST['joining_date']),
                'salary' => floatval($_POST['salary']),
                'status' => sanitize_input($_POST['status'])
            ];

            $query = "INSERT INTO teachers (
                employee_id, first_name, last_name, email, phone, date_of_birth, gender,
                address, city, state, pincode, dept_id, designation, qualification,
                experience_years, joining_date, salary, status
            ) VALUES (
                '{$data['employee_id']}', '{$data['first_name']}', '{$data['last_name']}',
                '{$data['email']}', '{$data['phone']}', '{$data['date_of_birth']}', '{$data['gender']}',
                '{$data['address']}', '{$data['city']}', '{$data['state']}', '{$data['pincode']}',
                {$data['dept_id']}, '{$data['designation']}', '{$data['qualification']}',
                {$data['experience_years']}, '{$data['joining_date']}', {$data['salary']}, '{$data['status']}'
            )";

            $teacher_id = insert_data($query);
            if ($teacher_id) {
                echo json_encode(['success' => true, 'message' => 'Teacher added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add teacher']);
            }
            exit;

        case 'update':
            $teacher_id = (int)$_POST['teacher_id'];
            $data = [
                'first_name' => sanitize_input($_POST['first_name']),
                'last_name' => sanitize_input($_POST['last_name']),
                'email' => sanitize_input($_POST['email']),
                'phone' => sanitize_input($_POST['phone']),
                'date_of_birth' => sanitize_input($_POST['date_of_birth']),
                'gender' => sanitize_input($_POST['gender']),
                'address' => sanitize_input($_POST['address']),
                'city' => sanitize_input($_POST['city']),
                'state' => sanitize_input($_POST['state']),
                'pincode' => sanitize_input($_POST['pincode']),
                'dept_id' => (int)$_POST['dept_id'],
                'designation' => sanitize_input($_POST['designation']),
                'qualification' => sanitize_input($_POST['qualification']),
                'experience_years' => (int)$_POST['experience_years'],
                'salary' => floatval($_POST['salary']),
                'status' => sanitize_input($_POST['status'])
            ];

            $query = "UPDATE teachers SET
                first_name = '{$data['first_name']}',
                last_name = '{$data['last_name']}',
                email = '{$data['email']}',
                phone = '{$data['phone']}',
                date_of_birth = '{$data['date_of_birth']}',
                gender = '{$data['gender']}',
                address = '{$data['address']}',
                city = '{$data['city']}',
                state = '{$data['state']}',
                pincode = '{$data['pincode']}',
                dept_id = {$data['dept_id']},
                designation = '{$data['designation']}',
                qualification = '{$data['qualification']}',
                experience_years = {$data['experience_years']},
                salary = {$data['salary']},
                status = '{$data['status']}'
            WHERE teacher_id = $teacher_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Teacher updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update teacher']);
            }
            exit;

        case 'delete':
            $teacher_id = (int)$_POST['teacher_id'];
            $query = "DELETE FROM teachers WHERE teacher_id = $teacher_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete teacher']);
            }
            exit;

        case 'get':
            $teacher_id = (int)$_POST['teacher_id'];
            $query = "SELECT * FROM teachers WHERE teacher_id = $teacher_id";
            $teacher = get_row($query);

            if ($teacher) {
                echo json_encode(['success' => true, 'data' => $teacher]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Teacher not found']);
            }
            exit;
    }
}

// Fetch departments and subjects
$departments = get_all("SELECT * FROM departments ORDER BY dept_name");
$subjects = get_all("SELECT s.*, d.dept_code FROM subjects s LEFT JOIN departments d ON s.dept_id = d.dept_id ORDER BY s.subject_name");
$classes = get_all("SELECT c.*, d.dept_code FROM classes c LEFT JOIN departments d ON c.dept_id = d.dept_id ORDER BY c.class_name, c.section");

// Build search and filter query
$where_conditions = ["1=1"];
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

if (!empty($search)) {
    $where_conditions[] = "(t.first_name LIKE '%$search%' OR t.last_name LIKE '%$search%' OR t.employee_id LIKE '%$search%' OR t.email LIKE '%$search%')";
}
if (!empty($dept_filter)) {
    $where_conditions[] = "t.dept_id = $dept_filter";
}
if (!empty($status_filter)) {
    $where_conditions[] = "t.status = '$status_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM teachers t WHERE $where_clause";
$total_result = get_row($count_query);
$total_teachers = $total_result['total'];
$total_pages = ceil($total_teachers / $per_page);

// Fetch teachers
$teachers_query = "
    SELECT t.*, 
           d.dept_name, d.dept_code,
           GROUP_CONCAT(DISTINCT s.subject_code SEPARATOR ', ') as subjects
    FROM teachers t
    LEFT JOIN departments d ON t.dept_id = d.dept_id
    LEFT JOIN teacher_subjects ts ON t.teacher_id = ts.teacher_id
    LEFT JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE $where_clause
    GROUP BY t.teacher_id
    ORDER BY t.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$teachers = get_all($teachers_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status != 'Active' THEN 1 ELSE 0 END) as inactive
    FROM teachers
";
$stats = get_row($stats_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management - CollegeSphere</title>

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

        .badge-status.inactive,
        .badge-status.on.leave {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .subject-tag {
            display: inline-block;
            padding: 4px 10px;
            background: #f1f5f9;
            border-radius: 12px;
            font-size: 11px;
            margin: 2px;
        }

        .modal-header {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">

     <?php include 'includes/navbar.php'; ?>


        <!-- PAGE CONTENT -->
        <div class="dashboard-container">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Teacher Management</h2>
                    <p class="text-muted mb-0">Manage faculty members and subject assignments</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                    <i class="fas fa-plus me-2"></i>Add New Teacher
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3">
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Teachers</h6>
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
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Active Faculty</h6>
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
                                        <i class="fas fa-user-clock fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">On Leave/Inactive</h6>
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
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search teachers..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
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
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="On Leave" <?php echo $status_filter == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="teachers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Teachers Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Teacher Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Subjects</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($teachers) > 0): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($teacher['employee_id']); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-success text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <span><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($teacher['dept_code'] ?? 'N/A'); ?></span></td>
                                            <td><?php echo htmlspecialchars($teacher['designation']); ?></td>
                                            <td>
                                                <?php if (!empty($teacher['subjects'])): ?>
                                                    <?php foreach (explode(', ', $teacher['subjects']) as $subject): ?>
                                                        <span class="subject-tag"><?php echo htmlspecialchars($subject); ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No subjects assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-status <?php echo strtolower(str_replace(' ', '.', $teacher['status'])); ?>">
                                                    <?php echo $teacher['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-warning" onclick="editTeacher(<?php echo $teacher['teacher_id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?php echo $teacher['teacher_id']; ?>)" title="Delete">
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
                                            <p class="text-muted">No teachers found</p>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&dept=<?php echo $dept_filter; ?>&status=<?php echo $status_filter; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addTeacherForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee ID *</label>
                                <input type="text" name="employee_id" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select name="dept_id" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['dept_id']; ?>">
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation *</label>
                                <input type="text" name="designation" class="form-control" required placeholder="e.g., Professor, Lecturer">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" placeholder="e.g., PhD, M.Tech">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" name="experience_years" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Joining Date *</label>
                                <input type="date" name="joining_date" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary</label>
                                <input type="number" name="salary" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="On Leave">On Leave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editTeacherForm">
                    <input type="hidden" name="teacher_id" id="edit_teacher_id">
                    <div class="modal-body">
                        <div class="row g-3">
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
                                <input type="tel" name="phone" id="edit_phone" class="form-control">
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
                                <label class="form-label">Designation *</label>
                                <input type="text" name="designation" id="edit_designation" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" id="edit_qualification" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" name="experience_years" id="edit_experience_years" class="form-control" min="0">
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
                                <input type="text" name="pincode" id="edit_pincode" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Salary</label>
                                <input type="number" name="salary" id="edit_salary" class="form-control" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="On Leave">On Leave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        // Add Teacher
        document.getElementById('addTeacherForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');

            fetch('teachers.php', {
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
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });

        // Edit Teacher
        function editTeacher(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('teacher_id', id);

            fetch('teachers.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const teacher = data.data;
                        document.getElementById('edit_teacher_id').value = teacher.teacher_id;
                        document.getElementById('edit_first_name').value = teacher.first_name;
                        document.getElementById('edit_last_name').value = teacher.last_name;
                        document.getElementById('edit_email').value = teacher.email;
                        document.getElementById('edit_phone').value = teacher.phone;
                        document.getElementById('edit_date_of_birth').value = teacher.date_of_birth;
                        document.getElementById('edit_gender').value = teacher.gender;
                        document.getElementById('edit_dept_id').value = teacher.dept_id;
                        document.getElementById('edit_designation').value = teacher.designation;
                        document.getElementById('edit_qualification').value = teacher.qualification;
                        document.getElementById('edit_experience_years').value = teacher.experience_years;
                        document.getElementById('edit_address').value = teacher.address;
                        document.getElementById('edit_city').value = teacher.city;
                        document.getElementById('edit_state').value = teacher.state;
                        document.getElementById('edit_pincode').value = teacher.pincode;
                        document.getElementById('edit_salary').value = teacher.salary;
                        document.getElementById('edit_status').value = teacher.status;

                        new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }

        // Update Teacher
        document.getElementById('editTeacherForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('teachers.php', {
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
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });

        // Delete Teacher
        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('teacher_id', id);

                fetch('teachers.php', {
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
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            }
        }
    </script>

</body>

</html>
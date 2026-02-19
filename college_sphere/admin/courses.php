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
                'subject_name' => sanitize_input($_POST['subject_name']),
                'subject_code' => sanitize_input($_POST['subject_code']),
                'dept_id' => (int)$_POST['dept_id'],
                'credits' => (int)$_POST['credits'],
                'description' => sanitize_input($_POST['description'])
            ];

            $query = "INSERT INTO subjects (subject_name, subject_code, dept_id, credits, description) 
                     VALUES ('{$data['subject_name']}', '{$data['subject_code']}', {$data['dept_id']}, 
                             {$data['credits']}, '{$data['description']}')";

            if (insert_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Subject added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add subject']);
            }
            exit;

        case 'update':
            $subject_id = (int)$_POST['subject_id'];
            $data = [
                'subject_name' => sanitize_input($_POST['subject_name']),
                'subject_code' => sanitize_input($_POST['subject_code']),
                'dept_id' => (int)$_POST['dept_id'],
                'credits' => (int)$_POST['credits'],
                'description' => sanitize_input($_POST['description'])
            ];

            $query = "UPDATE subjects SET
                subject_name = '{$data['subject_name']}',
                subject_code = '{$data['subject_code']}',
                dept_id = {$data['dept_id']},
                credits = {$data['credits']},
                description = '{$data['description']}'
            WHERE subject_id = $subject_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update subject']);
            }
            exit;

        case 'delete':
            $subject_id = (int)$_POST['subject_id'];
            $query = "DELETE FROM subjects WHERE subject_id = $subject_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete subject']);
            }
            exit;

        case 'get':
            $subject_id = (int)$_POST['subject_id'];
            $query = "SELECT * FROM subjects WHERE subject_id = $subject_id";
            $subject = get_row($query);

            if ($subject) {
                echo json_encode(['success' => true, 'data' => $subject]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Subject not found']);
            }
            exit;
    }
}

// Fetch departments
$departments = get_all("SELECT * FROM departments ORDER BY dept_name");

// Build search and filter query
$where_conditions = ["1=1"];
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$dept_filter = isset($_GET['dept']) ? (int)$_GET['dept'] : '';

if (!empty($search)) {
    $where_conditions[] = "(s.subject_name LIKE '%$search%' OR s.subject_code LIKE '%$search%')";
}
if (!empty($dept_filter)) {
    $where_conditions[] = "s.dept_id = $dept_filter";
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch subjects
$subjects_query = "
    SELECT s.*, 
           d.dept_name, d.dept_code,
           COUNT(DISTINCT ts.teacher_id) as teacher_count
    FROM subjects s
    LEFT JOIN departments d ON s.dept_id = d.dept_id
    LEFT JOIN teacher_subjects ts ON s.subject_id = ts.subject_id
    WHERE $where_clause
    GROUP BY s.subject_id
    ORDER BY s.created_at DESC
";
$subjects = get_all($subjects_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_subjects,
        COUNT(DISTINCT dept_id) as total_departments,
        SUM(credits) as total_credits
    FROM subjects
";
$stats = get_row($stats_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses & Subjects - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">

        <!-- TOP HEADER -->
        <?php include 'includes/navbar.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="dashboard-container">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Subject Management</h2>
                    <p class="text-muted mb-0">Manage all subjects and courses</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="fas fa-plus me-2"></i>Add New Subject
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-book fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Subjects</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_subjects']); ?></h3>
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
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Departments</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_departments']); ?></h3>
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
                                        <i class="fas fa-award fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Credits</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_credits']); ?></h3>
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
                            <div class="col-md-5">
                                <input type="text" name="search" class="form-control" placeholder="Search subjects..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="dept" class="form-select">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['dept_id']; ?>"
                                            <?php echo $dept_filter == $dept['dept_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="courses.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subjects Grid -->
            <div class="row g-4">
                <?php if (count($subjects) > 0): ?>
                    <?php foreach ($subjects as $subject): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                                            <p class="text-muted mb-0">
                                                <small><i class="fas fa-code"></i> <?php echo htmlspecialchars($subject['subject_code']); ?></small>
                                            </p>
                                        </div>
                                        <span class="badge bg-primary"><?php echo $subject['credits']; ?> Credits</span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="badge bg-info me-2">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($subject['dept_code']); ?>
                                        </span>
                                        <span class="badge bg-success">
                                            <i class="fas fa-user"></i> <?php echo $subject['teacher_count']; ?> Teachers
                                        </span>
                                    </div>

                                    <?php if (!empty($subject['description'])): ?>
                                        <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($subject['description'], 0, 100)); ?>...</p>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-warning flex-fill" onclick="editSubject(<?php echo $subject['subject_id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSubject(<?php echo $subject['subject_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No subjects found</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addSubjectForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject Name *</label>
                                <input type="text" name="subject_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject Code *</label>
                                <input type="text" name="subject_code" class="form-control" required>
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
                                <label class="form-label">Credits *</label>
                                <input type="number" name="credits" class="form-control" min="1" max="10" value="3" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Subject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSubjectForm">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject Name *</label>
                                <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject Code *</label>
                                <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
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
                                <label class="form-label">Credits *</label>
                                <input type="number" name="credits" id="edit_credits" class="form-control" min="1" max="10" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Subject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        // Add Subject
        document.getElementById('addSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');

            fetch('courses.php', {
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

        // Edit Subject
        function editSubject(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('subject_id', id);

            fetch('courses.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const subject = data.data;
                        document.getElementById('edit_subject_id').value = subject.subject_id;
                        document.getElementById('edit_subject_name').value = subject.subject_name;
                        document.getElementById('edit_subject_code').value = subject.subject_code;
                        document.getElementById('edit_dept_id').value = subject.dept_id;
                        document.getElementById('edit_credits').value = subject.credits;
                        document.getElementById('edit_description').value = subject.description;

                        new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
                    }
                });
        }

        // Update Subject
        document.getElementById('editSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('courses.php', {
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

        // Delete Subject
        function deleteSubject(id) {
            if (confirm('Are you sure you want to delete this subject?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('subject_id', id);

                fetch('courses.php', {
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
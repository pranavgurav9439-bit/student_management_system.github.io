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
            $dept_name = sanitize_input($_POST['dept_name']);
            $dept_code = strtoupper(sanitize_input($_POST['dept_code']));

            $check = get_row("SELECT dept_id FROM departments WHERE dept_code = '$dept_code'");
            if ($check) {
                echo json_encode(['success' => false, 'message' => 'Department code already exists. Please use a unique code.']);
                exit;
            }

            $query = "INSERT INTO departments (dept_name, dept_code) VALUES ('$dept_name', '$dept_code')";
            if (insert_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Department added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add department. Please try again.']);
            }
            exit;

        case 'update':
            $dept_id   = (int)$_POST['dept_id'];
            $dept_name = sanitize_input($_POST['dept_name']);
            $dept_code = strtoupper(sanitize_input($_POST['dept_code']));

            $check = get_row("SELECT dept_id FROM departments WHERE dept_code = '$dept_code' AND dept_id != $dept_id");
            if ($check) {
                echo json_encode(['success' => false, 'message' => 'Department code already in use by another department.']);
                exit;
            }

            $query = "UPDATE departments SET dept_name = '$dept_name', dept_code = '$dept_code' WHERE dept_id = $dept_id";
            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Department updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update department. Please try again.']);
            }
            exit;

        case 'delete':
            $dept_id = (int)$_POST['dept_id'];

            $students = get_row("SELECT COUNT(*) as cnt FROM students WHERE dept_id = $dept_id");
            $teachers = get_row("SELECT COUNT(*) as cnt FROM teachers WHERE dept_id = $dept_id");
            $subjects = get_row("SELECT COUNT(*) as cnt FROM subjects WHERE dept_id = $dept_id");

            if ($students['cnt'] > 0 || $teachers['cnt'] > 0 || $subjects['cnt'] > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete: this department has {$students['cnt']} student(s), {$teachers['cnt']} teacher(s), and {$subjects['cnt']} subject(s) linked to it."
                ]);
                exit;
            }

            $query = "DELETE FROM departments WHERE dept_id = $dept_id";
            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Department deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete department. Please try again.']);
            }
            exit;

        case 'get':
            $dept_id = (int)$_POST['dept_id'];
            $dept = get_row("SELECT * FROM departments WHERE dept_id = $dept_id");
            if ($dept) {
                echo json_encode(['success' => true, 'data' => $dept]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Department not found.']);
            }
            exit;
    }
}

// Search / filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where  = "1=1";
if (!empty($search)) {
    $where .= " AND (d.dept_name LIKE '%$search%' OR d.dept_code LIKE '%$search%')";
}

// Fetch departments with counts
$departments = get_all("
    SELECT d.*,
           COUNT(DISTINCT s.student_id)   AS student_count,
           COUNT(DISTINCT t.teacher_id)   AS teacher_count,
           COUNT(DISTINCT sub.subject_id) AS subject_count
    FROM departments d
    LEFT JOIN students  s   ON d.dept_id = s.dept_id   AND s.status = 'Active'
    LEFT JOIN teachers  t   ON d.dept_id = t.dept_id   AND t.status = 'Active'
    LEFT JOIN subjects  sub ON d.dept_id = sub.dept_id
    WHERE $where
    GROUP BY d.dept_id
    ORDER BY d.dept_name ASC
");

// Summary stats
$stats = get_row("
    SELECT
        COUNT(*)                                                    AS total_departments,
        (SELECT COUNT(*) FROM students WHERE status = 'Active')    AS total_students,
        (SELECT COUNT(*) FROM teachers WHERE status = 'Active')    AS total_teachers,
        (SELECT COUNT(*) FROM subjects)                            AS total_subjects
    FROM departments
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - CollegeSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .dept-card {
            transition: transform .2s, box-shadow .2s;
            border-left: 4px solid transparent;
        }
        .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
        }
        .dept-card.color-0 { border-left-color: #4f46e5; }
        .dept-card.color-1 { border-left-color: #0ea5e9; }
        .dept-card.color-2 { border-left-color: #10b981; }
        .dept-card.color-3 { border-left-color: #f59e0b; }
        .dept-card.color-4 { border-left-color: #ef4444; }
        .dept-card.color-5 { border-left-color: #8b5cf6; }
        .dept-card.color-6 { border-left-color: #ec4899; }
        .dept-card.color-7 { border-left-color: #14b8a6; }
        .dept-badge { font-size: .75rem; letter-spacing: .5px; padding: .35em .7em; }
        .stat-pill { display: flex; align-items: center; gap: .4rem; font-size: .82rem; color: #64748b; }
        .stat-pill i { width: 14px; text-align: center; }
        .empty-state { padding: 60px 20px; }
        .toast-container { z-index: 9999; }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" id="mainContent">

        <?php include 'includes/navbar.php'; ?>

        <div class="dashboard-container">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Department Management</h2>
                    <p class="text-muted mb-0">Create and manage college departments</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                    <i class="fas fa-plus me-2"></i>Add Department
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                                    <i class="fas fa-building fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Departments</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_departments']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3 me-3">
                                    <i class="fas fa-user-graduate fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Students</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_students']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Teachers</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_teachers']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info rounded p-3 me-3">
                                    <i class="fas fa-book-open fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Subjects</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_subjects']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="card shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Search by name or code..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="departments.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto ms-auto text-muted small">
                            <?php echo count($departments); ?> department(s) found
                        </div>
                    </form>
                </div>
            </div>

            <!-- Departments Grid -->
            <?php if (count($departments) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($departments as $i => $dept): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm dept-card color-<?php echo $i % 8; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1 fw-semibold">
                                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                                            </h5>
                                            <span class="badge bg-primary dept-badge">
                                                <?php echo htmlspecialchars($dept['dept_code']); ?>
                                            </span>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center"
                                            style="width:42px;height:42px;flex-shrink:0">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-3 mb-3 mt-2">
                                        <div class="stat-pill">
                                            <i class="fas fa-user-graduate text-success"></i>
                                            <span><?php echo $dept['student_count']; ?> Students</span>
                                        </div>
                                        <div class="stat-pill">
                                            <i class="fas fa-chalkboard-teacher text-warning"></i>
                                            <span><?php echo $dept['teacher_count']; ?> Teachers</span>
                                        </div>
                                        <div class="stat-pill">
                                            <i class="fas fa-book text-info"></i>
                                            <span><?php echo $dept['subject_count']; ?> Subjects</span>
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Created: <?php echo date('d M Y', strtotime($dept['created_at'])); ?>
                                    </p>

                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-warning flex-fill"
                                            onclick="editDept(<?php echo $dept['dept_id']; ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger"
                                            onclick="deleteDept(<?php echo $dept['dept_id']; ?>, '<?php echo htmlspecialchars($dept['dept_name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center empty-state">
                        <i class="fas fa-building fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">
                            <?php echo !empty($search) ? 'No departments match your search' : 'No Departments Yet'; ?>
                        </h4>
                        <p class="text-muted mb-4">
                            <?php echo !empty($search) ? 'Try a different search term.' : 'Get started by adding your first department.'; ?>
                        </p>
                        <?php if (empty($search)): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                                <i class="fas fa-plus me-2"></i>Add First Department
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addDeptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addDeptForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="dept_name" class="form-control" placeholder="e.g. Computer Science" required>
                            <div class="form-text">Full name of the department.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="dept_code" class="form-control text-uppercase"
                                placeholder="e.g. CS" maxlength="20" required
                                oninput="this.value = this.value.toUpperCase()">
                            <div class="form-text">Short unique code (e.g. CS, IT, MECH). Must be unique.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editDeptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editDeptForm">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="dept_name" id="edit_dept_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Department Code <span class="text-danger">*</span></label>
                            <input type="text" name="dept_code" id="edit_dept_code" class="form-control text-uppercase"
                                maxlength="20" required oninput="this.value = this.value.toUpperCase()">
                            <div class="form-text">Changing the code may affect linked records.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRM MODAL -->
    <div class="modal fade" id="deleteDeptModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_dept_name"></strong>?</p>
                    <p class="text-muted small mb-0">Departments with linked students, teachers, or subjects cannot be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="notifToast" class="toast align-items-center border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script>
        const toastEl  = document.getElementById('notifToast');
        const toastMsg = document.getElementById('toastMsg');
        const bsToast  = new bootstrap.Toast(toastEl, { delay: 3500 });

        function showToast(message, type = 'success') {
            toastEl.className = `toast align-items-center border-0 text-white bg-${type === 'success' ? 'success' : 'danger'}`;
            toastMsg.textContent = message;
            bsToast.show();
        }

        // ADD
        document.getElementById('addDeptForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'add');
            fetch('departments.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addDeptModal')).hide();
                        setTimeout(() => location.reload(), 900);
                    }
                });
        });

        // EDIT (open)
        function editDept(id) {
            const fd = new FormData();
            fd.append('action', 'get');
            fd.append('dept_id', id);
            fetch('departments.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('edit_dept_id').value   = res.data.dept_id;
                        document.getElementById('edit_dept_name').value = res.data.dept_name;
                        document.getElementById('edit_dept_code').value = res.data.dept_code;
                        new bootstrap.Modal(document.getElementById('editDeptModal')).show();
                    } else {
                        showToast(res.message, 'error');
                    }
                });
        }

        // EDIT (submit)
        document.getElementById('editDeptForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('action', 'update');
            fetch('departments.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editDeptModal')).hide();
                        setTimeout(() => location.reload(), 900);
                    }
                });
        });

        // DELETE
        let pendingDeleteId = null;

        function deleteDept(id, name) {
            pendingDeleteId = id;
            document.getElementById('delete_dept_name').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteDeptModal')).show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (!pendingDeleteId) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('dept_id', pendingDeleteId);
            fetch('departments.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    bootstrap.Modal.getInstance(document.getElementById('deleteDeptModal')).hide();
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) setTimeout(() => location.reload(), 900);
                    pendingDeleteId = null;
                });
        });
    </script>

</body>
</html>
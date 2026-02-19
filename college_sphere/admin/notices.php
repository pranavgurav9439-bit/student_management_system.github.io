<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add':
            $query = "INSERT INTO notices (title, description, notice_date, expiry_date, target_audience, priority, created_by) 
                     VALUES ('" . sanitize_input($_POST['title']) . "', '" . sanitize_input($_POST['description']) . "', 
                     '" . sanitize_input($_POST['notice_date']) . "', '" . sanitize_input($_POST['expiry_date']) . "', 
                     '" . sanitize_input($_POST['target_audience']) . "', '" . sanitize_input($_POST['priority']) . "', 
                     {$_SESSION['admin_id']})";
            echo json_encode(['success' => insert_data($query), 'message' => 'Notice added']);
            exit;

        case 'update':
            $query = "UPDATE notices SET 
                     title = '" . sanitize_input($_POST['title']) . "',
                     description = '" . sanitize_input($_POST['description']) . "',
                     notice_date = '" . sanitize_input($_POST['notice_date']) . "',
                     expiry_date = '" . sanitize_input($_POST['expiry_date']) . "',
                     target_audience = '" . sanitize_input($_POST['target_audience']) . "',
                     priority = '" . sanitize_input($_POST['priority']) . "'
                     WHERE notice_id = " . (int)$_POST['notice_id'];
            echo json_encode(['success' => modify_data($query), 'message' => 'Notice updated']);
            exit;

        case 'delete':
            $query = "DELETE FROM notices WHERE notice_id = " . (int)$_POST['notice_id'];
            echo json_encode(['success' => modify_data($query), 'message' => 'Notice deleted']);
            exit;

        case 'get':
            $query = "SELECT * FROM notices WHERE notice_id = " . (int)$_POST['notice_id'];
            echo json_encode(['success' => true, 'data' => get_row($query)]);
            exit;
    }
}

$notices = get_all("SELECT * FROM notices WHERE expiry_date >= CURDATE() ORDER BY priority DESC, notice_date DESC");
$stats = get_row("SELECT COUNT(*) as total, 
                 COUNT(CASE WHEN target_audience='All' THEN 1 END) as all_count,
                 COUNT(CASE WHEN priority='Urgent' THEN 1 END) as urgent_count 
                 FROM notices WHERE expiry_date >= CURDATE()");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Notices - CollegeSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .notice-card {
            border-left: 4px solid #6366f1;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .notice-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notice-card.urgent {
            border-left-color: #ef4444;
        }

        .notice-card.high {
            border-left-color: #f59e0b;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .priority-urgent {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .priority-high {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .priority-medium {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
    </style>
</head>

<body>
    <!-- SIDEBAR (same as finance.php) -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" id="mainContent">

        <?php include 'includes/navbar.php'; ?>

        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Active Notices</h2>
                    <p class="text-muted mb-0">Post and manage important announcements</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
                    <i class="fas fa-plus me-2"></i>Post New Notice
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                    <i class="fas fa-bell fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Active Notices</h6>
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">General Notices</h6>
                                    <h3 class="mb-0"><?php echo $stats['all_count']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded p-3">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Urgent Notices</h6>
                                    <h3 class="mb-0"><?php echo $stats['urgent_count']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notices List -->
            <div class="row">
                <?php foreach ($notices as $notice): ?>
                    <div class="col-12">
                        <div class="card notice-card <?php echo strtolower($notice['priority']); ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h5 class="mb-0 me-3"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                            <span class="priority-badge priority-<?php echo strtolower($notice['priority']); ?>">
                                                <?php echo $notice['priority']; ?>
                                            </span>
                                            <span class="badge bg-info ms-2"><?php echo $notice['target_audience']; ?></span>
                                        </div>
                                        <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($notice['description'])); ?></p>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($notice['notice_date'])); ?>
                                            <span class="ms-3"><i class="fas fa-clock"></i> Expires: <?php echo date('M d, Y', strtotime($notice['expiry_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <button class="btn btn-sm btn-warning mb-2" onclick="editNotice(<?php echo $notice['notice_id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteNotice(<?php echo $notice['notice_id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Notice Modal -->
    <div class="modal fade" id="addNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Post New Notice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addNoticeForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Notice Title *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notice Date *</label>
                                <input type="date" name="notice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date *</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Target Audience *</label>
                                <select name="target_audience" class="form-select" required>
                                    <option value="All">All</option>
                                    <option value="Students">Students</option>
                                    <option value="Teachers">Teachers</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority *</label>
                                <select name="priority" class="form-select" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Post Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Notice Modal (Similar structure) -->
    <div class="modal fade" id="editNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editNoticeForm">
                    <input type="hidden" name="notice_id" id="edit_notice_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Notice Title *</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description *</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notice Date *</label>
                                <input type="date" name="notice_date" id="edit_notice_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date *</label>
                                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Target Audience *</label>
                                <select name="target_audience" id="edit_target_audience" class="form-select" required>
                                    <option value="All">All</option>
                                    <option value="Students">Students</option>
                                    <option value="Teachers">Teachers</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority *</label>
                                <select name="priority" id="edit_priority" class="form-select" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Update Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    <script>
        document.getElementById('addNoticeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');
            fetch('notices.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else alert(d.message);
                });
        });

        function editNotice(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('notice_id', id);
            fetch('notices.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const n = d.data;
                        document.getElementById('edit_notice_id').value = n.notice_id;
                        document.getElementById('edit_title').value = n.title;
                        document.getElementById('edit_description').value = n.description;
                        document.getElementById('edit_notice_date').value = n.notice_date;
                        document.getElementById('edit_expiry_date').value = n.expiry_date;
                        document.getElementById('edit_target_audience').value = n.target_audience;
                        document.getElementById('edit_priority').value = n.priority;
                        new bootstrap.Modal(document.getElementById('editNoticeModal')).show();
                    }
                });
        }

        document.getElementById('editNoticeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');
            fetch('notices.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else alert(d.message);
                });
        });

        function deleteNotice(id) {
            if (confirm('Delete this notice?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('notice_id', id);
                fetch('notices.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            alert(d.message);
                            location.reload();
                        } else alert(d.message);
                    });
            }
        }
    </script>
</body>

</html>
<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in (adjust based on your admin authentication)
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$page_title = "Teacher Leave Management";
$page_subtitle = "Review and manage teacher leave requests";

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave'])) {
    $leave_id = (int)$_POST['leave_id'];
    $action = sanitize_input($_POST['action']);
    $admin_remarks = sanitize_input($_POST['admin_remarks']);
    
    $new_status = ($action == 'approve') ? 'Approved' : 'Rejected';
    
    $update_query = "
        UPDATE leave_requests 
        SET status = '{$new_status}',
            admin_remarks = '{$admin_remarks}',
            approved_by = {$admin_id},
            approved_at = NOW()
        WHERE leave_id = {$leave_id}
    ";
    
    if (execute_query($update_query)) {
        $success = "Leave request has been {$new_status} successfully!";
    } else {
        $error = "Failed to update leave request. Please try again.";
    }
}

// Fetch filter parameters
$filter = $_GET['filter'] ?? 'pending';
$search = $_GET['search'] ?? '';

// Fetch leave requests with teacher details
$leaves_query = "
    SELECT 
        lr.*,
        t.employee_id,
        t.first_name,
        t.last_name,
        t.email,
        t.phone,
        t.designation,
        d.dept_name,
        a.full_name as approved_by_name
    FROM leave_requests lr
    JOIN teachers t ON lr.teacher_id = t.teacher_id
    LEFT JOIN departments d ON t.dept_id = d.dept_id
    LEFT JOIN admins a ON lr.approved_by = a.admin_id
    WHERE 1=1
";

// Apply status filter
if ($filter == 'pending') {
    $leaves_query .= " AND lr.status = 'Pending'";
} elseif ($filter == 'approved') {
    $leaves_query .= " AND lr.status = 'Approved'";
} elseif ($filter == 'rejected') {
    $leaves_query .= " AND lr.status = 'Rejected'";
}

// Apply search filter
if (!empty($search)) {
    $search_safe = sanitize_input($search);
    $leaves_query .= " AND (
        t.first_name LIKE '%{$search_safe}%' OR
        t.last_name LIKE '%{$search_safe}%' OR
        t.employee_id LIKE '%{$search_safe}%' OR
        lr.leave_type LIKE '%{$search_safe}%'
    )";
}

$leaves_query .= " ORDER BY 
    CASE lr.status 
        WHEN 'Pending' THEN 1 
        WHEN 'Approved' THEN 2 
        WHEN 'Rejected' THEN 3 
    END,
    lr.created_at DESC
";

$leave_requests = get_all($leaves_query);

// Fetch statistics
$stats_query = "
    SELECT 
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected_count,
        COUNT(*) as total_count
    FROM leave_requests
";
$stats = get_row($stats_query);

// Fetch upcoming leaves (approved leaves starting within next 7 days)
$upcoming_query = "
    SELECT 
        lr.*,
        t.first_name,
        t.last_name,
        t.employee_id
    FROM leave_requests lr
    JOIN teachers t ON lr.teacher_id = t.teacher_id
    WHERE lr.status = 'Approved'
    AND lr.start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY lr.start_date ASC
    LIMIT 5
";
$upcoming_leaves = get_all($upcoming_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Leave Management - Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root {
            --admin-primary: #6366f1;
            --admin-primary-dark: #4f46e5;
        }
        
        .leave-request-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .leave-request-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .leave-request-card.pending {
            border-left-color: #f59e0b;
        }
        
        .leave-request-card.approved {
            border-left-color: #10b981;
        }
        
        .leave-request-card.rejected {
            border-left-color: #ef4444;
        }
        
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .teacher-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
        
        .stat-box.pending .icon {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .stat-box.approved .icon {
            background: #d1fae5;
            color: #10b981;
        }
        
        .stat-box.rejected .icon {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .stat-box.total .icon {
            background: #e0e7ff;
            color: #6366f1;
        }
        
        .stat-box h3 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #1f2937;
        }
        
        .stat-box p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .upcoming-leave-item {
            background: #f9fafb;
            border-left: 3px solid #10b981;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
        }
        
        .quick-action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quick-action-btn.approve {
            background: #d1fae5;
            color: #065f46;
        }
        
        .quick-action-btn.approve:hover {
            background: #10b981;
            color: white;
        }
        
        .quick-action-btn.reject {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .quick-action-btn.reject:hover {
            background: #ef4444;
            color: white;
        }
    </style>
</head>
<body>

    <!-- Include Admin Sidebar (adjust path as needed) -->
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content" id="mainContent">
        
        <!-- Include Admin Navbar (adjust path as needed) -->
        <?php include 'includes/navbar.php'; ?>
        
        <div class="content-wrapper" style="padding: 30px;">
            
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="mb-2"><?php echo $page_title; ?></h1>
                <p class="text-muted"><?php echo $page_subtitle; ?></p>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box pending">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box approved">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $stats['approved_count'] ?? 0; ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box rejected">
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3><?php echo $stats['rejected_count'] ?? 0; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box total">
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3><?php echo $stats['total_count'] ?? 0; ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8">
                    
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form method="GET" action="">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Filter by Status</label>
                                    <select class="form-select" name="filter" onchange="this.form.submit()">
                                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Requests</option>
                                        <option value="pending" <?php echo $filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Search</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="Search by teacher name, employee ID..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Leave Requests List -->
                    <?php if (count($leave_requests) > 0): ?>
                        <?php foreach ($leave_requests as $leave): ?>
                        <div class="leave-request-card <?php echo strtolower($leave['status']); ?>">
                            <div class="teacher-info">
                                <div class="teacher-avatar">
                                    <?php echo strtoupper(substr($leave['first_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>
                                    </h6>
                                    <p class="mb-0 text-muted small">
                                        <?php echo htmlspecialchars($leave['employee_id']); ?> | 
                                        <?php echo htmlspecialchars($leave['designation']); ?> | 
                                        <?php echo htmlspecialchars($leave['dept_name']); ?>
                                    </p>
                                </div>
                                <span class="status-badge <?php echo strtolower($leave['status']); ?>">
                                    <?php echo htmlspecialchars($leave['status']); ?>
                                </span>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Leave Type:</small>
                                        <span class="badge bg-primary ms-1">
                                            <?php echo htmlspecialchars($leave['leave_type']); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Duration:</small>
                                        <strong class="ms-1">
                                            <?php echo date('M d', strtotime($leave['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                        </strong>
                                        <span class="badge bg-secondary ms-1">
                                            <?php echo $leave['total_days']; ?> 
                                            <?php echo $leave['total_days'] > 1 ? 'days' : 'day'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted">Contact:</small>
                                        <span class="ms-1"><?php echo htmlspecialchars($leave['phone'] ?? 'N/A'); ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Applied:</small>
                                        <span class="ms-1">
                                            <?php echo date('M d, Y h:i A', strtotime($leave['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Reason:</strong>
                                <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($leave['reason'])); ?></p>
                            </div>
                            
                            <?php if ($leave['admin_remarks']): ?>
                            <div class="alert alert-<?php echo $leave['status'] == 'Approved' ? 'success' : 'danger'; ?> mb-3 py-2">
                                <strong>Admin Remarks:</strong> <?php echo htmlspecialchars($leave['admin_remarks']); ?>
                                <?php if ($leave['approved_by_name']): ?>
                                <br><small>By: <?php echo htmlspecialchars($leave['approved_by_name']); ?> on 
                                <?php echo date('M d, Y h:i A', strtotime($leave['approved_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($leave['status'] == 'Pending'): ?>
                            <div class="d-flex gap-2">
                                <button class="quick-action-btn approve" 
                                        onclick="openActionModal(<?php echo $leave['leave_id']; ?>, 'approve', '<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>')">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                                <button class="quick-action-btn reject" 
                                        onclick="openActionModal(<?php echo $leave['leave_id']; ?>, 'reject', '<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>')">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Leave Requests Found</h4>
                        <p class="text-muted">
                            <?php echo !empty($search) ? 'Try adjusting your search criteria.' : 'No leave requests to display.'; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    
                    <!-- Upcoming Leaves -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-check text-success me-2"></i>
                                Upcoming Leaves (Next 7 Days)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($upcoming_leaves) > 0): ?>
                                <?php foreach ($upcoming_leaves as $upcoming): ?>
                                <div class="upcoming-leave-item">
                                    <strong><?php echo htmlspecialchars($upcoming['first_name'] . ' ' . $upcoming['last_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($upcoming['employee_id']); ?>
                                    </small>
                                    <div class="mt-1">
                                        <i class="fas fa-calendar fa-sm me-1"></i>
                                        <small>
                                            <?php echo date('M d', strtotime($upcoming['start_date'])); ?> - 
                                            <?php echo date('M d', strtotime($upcoming['end_date'])); ?>
                                        </small>
                                        <span class="badge bg-primary ms-1" style="font-size: 10px;">
                                            <?php echo $upcoming['total_days']; ?> days
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <p class="text-muted text-center py-3 mb-0">No upcoming leaves</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-pie text-primary me-2"></i>
                                Quick Statistics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    <span>Pending Review</span>
                                </div>
                                <strong class="text-warning"><?php echo $stats['pending_count']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span>Approved</span>
                                </div>
                                <strong class="text-success"><?php echo $stats['approved_count']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                    <span>Rejected</span>
                                </div>
                                <strong class="text-danger"><?php echo $stats['rejected_count']; ?></strong>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

        </div>

    </main>
    
    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="leave_id" id="modalLeaveId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="modal-body">
                        <div class="alert alert-info" id="modalInfo"></div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Admin Remarks</label>
                            <textarea class="form-control" name="admin_remarks" rows="4" 
                                      placeholder="Add your remarks here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="update_leave" class="btn" id="modalSubmitBtn">
                            <i class="fas fa-check me-1"></i> <span id="modalSubmitText"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let actionModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
        });
        
        function openActionModal(leaveId, action, teacherName) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('modalAction').value = action;
            
            const modalTitle = document.getElementById('actionModalTitle');
            const modalInfo = document.getElementById('modalInfo');
            const submitBtn = document.getElementById('modalSubmitBtn');
            const submitText = document.getElementById('modalSubmitText');
            
            if (action === 'approve') {
                modalTitle.textContent = 'Approve Leave Request';
                modalInfo.innerHTML = '<i class="fas fa-check-circle me-2"></i>You are about to <strong>approve</strong> the leave request for <strong>' + teacherName + '</strong>.';
                submitBtn.className = 'btn btn-success';
                submitText.textContent = 'Approve Leave';
            } else {
                modalTitle.textContent = 'Reject Leave Request';
                modalInfo.innerHTML = '<i class="fas fa-times-circle me-2"></i>You are about to <strong>reject</strong> the leave request for <strong>' + teacherName + '</strong>.';
                modalInfo.className = 'alert alert-danger';
                submitBtn.className = 'btn btn-danger';
                submitText.textContent = 'Reject Leave';
            }
            
            actionModal.show();
        }
    </script>

</body>
</html>
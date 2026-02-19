<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Leave Management";
$page_subtitle = "Apply for leave and track your leave requests";

// Handle leave application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $leave_type = sanitize_input($_POST['leave_type']);
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = sanitize_input($_POST['end_date']);
    $reason = sanitize_input($_POST['reason']);
    
    // Calculate total days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $total_days = $interval->days + 1; // +1 to include both start and end dates
    
    // Validate dates
    if (strtotime($start_date) > strtotime($end_date)) {
        $error = "End date cannot be before start date.";
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = "Start date cannot be in the past.";
    } else {
        // Check for overlapping leaves
        $overlap_check = "
            SELECT leave_id FROM leave_requests 
            WHERE teacher_id = {$teacher_id}
            AND status != 'Rejected' AND status != 'Cancelled'
            AND (
                (start_date <= '{$end_date}' AND end_date >= '{$start_date}')
            )
        ";
        $overlap = get_row($overlap_check);
        
        if ($overlap) {
            $error = "You already have a leave request for overlapping dates.";
        } else {
            // Insert leave request
            $insert_query = "
                INSERT INTO leave_requests (teacher_id, leave_type, start_date, end_date, total_days, reason, status)
                VALUES ({$teacher_id}, '{$leave_type}', '{$start_date}', '{$end_date}', {$total_days}, '{$reason}', 'Pending')
            ";
            
            if (execute_query($insert_query)) {
                header("Location: leave.php?success=1");
                exit;
            } else {
                $error = "Failed to submit leave request. Please try again.";
            }
        }
    }
}

// Handle leave cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $leave_id = (int)$_GET['cancel'];
    
    // Verify this leave belongs to the teacher and is pending
    $verify_query = "
        SELECT leave_id FROM leave_requests 
        WHERE leave_id = {$leave_id} 
        AND teacher_id = {$teacher_id} 
        AND status = 'Pending'
    ";
    $verify = get_row($verify_query);
    
    if ($verify) {
        $cancel_query = "UPDATE leave_requests SET status = 'Cancelled' WHERE leave_id = {$leave_id}";
        if (execute_query($cancel_query)) {
            header("Location: leave.php?cancelled=1");
            exit;
        }
    }
}

// Fetch leave statistics for current year
$stats_query = "
    SELECT 
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved_count,
        COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected_count,
        COALESCE(SUM(CASE WHEN status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE()) THEN total_days END), 0) as total_days_this_year
    FROM leave_requests
    WHERE teacher_id = {$teacher_id}
";
$stats = get_row($stats_query);

// Fetch all leave requests
$filter = $_GET['filter'] ?? 'all';
$leaves_query = "
    SELECT 
        lr.*,
        a.full_name as approved_by_name
    FROM leave_requests lr
    LEFT JOIN admins a ON lr.approved_by = a.admin_id
    WHERE lr.teacher_id = {$teacher_id}
";

if ($filter == 'pending') {
    $leaves_query .= " AND lr.status = 'Pending'";
} elseif ($filter == 'approved') {
    $leaves_query .= " AND lr.status = 'Approved'";
} elseif ($filter == 'rejected') {
    $leaves_query .= " AND lr.status = 'Rejected'";
}

$leaves_query .= " ORDER BY lr.created_at DESC";
$leave_requests = get_all($leaves_query);

// Calculate leave balance (assuming 20 days annual leave)
$annual_leave_quota = 20;
$leave_balance = $annual_leave_quota - ($stats['total_days_this_year'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .leave-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .leave-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .leave-card.pending {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .leave-card.approved {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .leave-card.rejected {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .leave-card.cancelled {
            border-left-color: #6b7280;
            background: #f9fafb;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            height: 100%;
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
        
        .stat-card.pending .icon {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .stat-card.approved .icon {
            background: #d1fae5;
            color: #10b981;
        }
        
        .stat-card.rejected .icon {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .stat-card.balance .icon {
            background: #dbeafe;
            color: #3b82f6;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        
        .stat-card p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .leave-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .leave-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .leave-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .leave-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .leave-badge.cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .leave-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .apply-leave-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .apply-leave-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }
        
        .date-range {
            font-size: 14px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tabs {
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
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Leave request submitted successfully! Waiting for admin approval.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Leave request cancelled successfully!
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
                    <div class="stat-card pending">
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card approved">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $stats['approved_count'] ?? 0; ?></h3>
                        <p>Approved Leaves</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card rejected">
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3><?php echo $stats['rejected_count'] ?? 0; ?></h3>
                        <p>Rejected Requests</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card balance">
                        <div class="icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3><?php echo max(0, $leave_balance); ?></h3>
                        <p>Leave Balance (2026)</p>
                        <small class="text-muted"><?php echo $stats['total_days_this_year'] ?? 0; ?> / <?php echo $annual_leave_quota; ?> days used</small>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group" role="group">
                        <a href="leave.php?filter=all" 
                           class="btn btn-<?php echo $filter == 'all' ? 'primary' : 'outline-primary'; ?>">
                            <i class="fas fa-list me-1"></i> All Requests
                        </a>
                        <a href="leave.php?filter=pending" 
                           class="btn btn-<?php echo $filter == 'pending' ? 'warning' : 'outline-warning'; ?>">
                            <i class="fas fa-clock me-1"></i> Pending
                        </a>
                        <a href="leave.php?filter=approved" 
                           class="btn btn-<?php echo $filter == 'approved' ? 'success' : 'outline-success'; ?>">
                            <i class="fas fa-check-circle me-1"></i> Approved
                        </a>
                        <a href="leave.php?filter=rejected" 
                           class="btn btn-<?php echo $filter == 'rejected' ? 'danger' : 'outline-danger'; ?>">
                            <i class="fas fa-times-circle me-1"></i> Rejected
                        </a>
                    </div>
                    
                    <span class="badge bg-primary fs-6">
                        Total: <?php echo count($leave_requests); ?> Requests
                    </span>
                </div>
            </div>
            
            <!-- Leave Requests List -->
            <?php if (count($leave_requests) > 0): ?>
                <?php foreach ($leave_requests as $leave): ?>
                <div class="leave-card <?php echo strtolower($leave['status']); ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="flex-fill">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="leave-type-badge">
                                            <?php echo htmlspecialchars($leave['leave_type']); ?>
                                        </span>
                                        <span class="leave-badge <?php echo strtolower($leave['status']); ?>">
                                            <?php echo htmlspecialchars($leave['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="date-range mb-2">
                                        <i class="fas fa-calendar"></i>
                                        <strong>
                                            <?php echo date('M d, Y', strtotime($leave['start_date'])); ?>
                                        </strong>
                                        <i class="fas fa-arrow-right"></i>
                                        <strong>
                                            <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                        </strong>
                                        <span class="badge bg-secondary">
                                            <?php echo $leave['total_days']; ?> 
                                            <?php echo $leave['total_days'] > 1 ? 'days' : 'day'; ?>
                                        </span>
                                    </div>
                                    
                                    <p class="mb-2">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($leave['reason']); ?>
                                    </p>
                                    
                                    <?php if ($leave['admin_remarks']): ?>
                                    <div class="alert alert-<?php echo $leave['status'] == 'Approved' ? 'success' : 'danger'; ?> mb-0 py-2">
                                        <strong>Admin Remarks:</strong> <?php echo htmlspecialchars($leave['admin_remarks']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Applied on: <?php echo date('M d, Y h:i A', strtotime($leave['created_at'])); ?>
                                <?php if ($leave['approved_at']): ?>
                                | 
                                <i class="fas fa-<?php echo $leave['status'] == 'Approved' ? 'check' : 'times'; ?> me-1"></i>
                                <?php echo ucfirst(strtolower($leave['status'])); ?> on: 
                                <?php echo date('M d, Y h:i A', strtotime($leave['approved_at'])); ?>
                                <?php if ($leave['approved_by_name']): ?>
                                by <?php echo htmlspecialchars($leave['approved_by_name']); ?>
                                <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <?php if ($leave['status'] == 'Pending'): ?>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="cancelLeave(<?php echo $leave['leave_id']; ?>)">
                                <i class="fas fa-times me-1"></i> Cancel Request
                            </button>
                            <?php elseif ($leave['status'] == 'Approved'): ?>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                            <?php elseif ($leave['status'] == 'Rejected'): ?>
                            <div class="text-danger">
                                <i class="fas fa-times-circle fa-3x"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Leave Requests Found</h4>
                <p class="text-muted">
                    <?php if ($filter != 'all'): ?>
                        No <?php echo $filter; ?> leave requests to display.
                    <?php else: ?>
                        Click the "+" button to apply for leave.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>
    
    <!-- Apply Leave Button -->
    <button class="apply-leave-btn" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Apply Leave Modal -->
    <div class="modal fade" id="applyLeaveModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Apply for Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You have <strong><?php echo max(0, $leave_balance); ?> days</strong> of leave balance remaining for 2026.
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Leave Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="leave_type" required>
                                    <option value="">Choose leave type...</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Casual Leave">Casual Leave</option>
                                    <option value="Emergency Leave">Emergency Leave</option>
                                    <option value="Maternity Leave">Maternity Leave</option>
                                    <option value="Paternity Leave">Paternity Leave</option>
                                    <option value="Unpaid Leave">Unpaid Leave</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Days</label>
                                <input type="text" class="form-control" id="totalDays" readonly 
                                       placeholder="Auto-calculated" style="background: #f9fafb;">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" 
                                       id="startDate" min="<?php echo date('Y-m-d'); ?>" 
                                       required onchange="calculateDays()">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" 
                                       id="endDate" min="<?php echo date('Y-m-d'); ?>" 
                                       required onchange="calculateDays()">
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Reason for Leave <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="reason" rows="4" 
                                          placeholder="Please provide a detailed reason for your leave request..." 
                                          required maxlength="500"></textarea>
                                <small class="text-muted">Maximum 500 characters</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="apply_leave" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        function calculateDays() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end >= start) {
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    document.getElementById('totalDays').value = diffDays + ' day' + (diffDays > 1 ? 's' : '');
                } else {
                    document.getElementById('totalDays').value = '';
                    alert('End date must be greater than or equal to start date.');
                }
            }
        }
        
        function cancelLeave(leaveId) {
            if (confirm('Are you sure you want to cancel this leave request?')) {
                window.location.href = 'leave.php?cancel=' + leaveId;
            }
        }
        
        // Update end date min value when start date changes
        document.getElementById('startDate')?.addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
        });
    </script>

</body>
</html>
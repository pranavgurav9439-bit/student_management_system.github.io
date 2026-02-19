<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$page_title = "Notices";
$page_subtitle = "View announcements and post class notices";

// Handle notice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notice'])) {
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $class_id = $_POST['class_id'] == 'all' ? null : (int)$_POST['class_id'];
    $notice_type = sanitize_input($_POST['notice_type']);
    $priority = sanitize_input($_POST['priority']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null;
    
    $expiry_sql = $expiry_date ? "'{$expiry_date}'" : "NULL";
    $class_sql = $class_id ? $class_id : "NULL";
    
    $insert_query = "
        INSERT INTO teacher_notices (teacher_id, class_id, title, content, notice_type, priority, expiry_date)
        VALUES ({$teacher_id}, {$class_sql}, '{$title}', '{$content}', '{$notice_type}', '{$priority}', {$expiry_sql})
    ";
    
    if (execute_query($insert_query)) {
        header("Location: notices.php?success=1");
        exit;
    } else {
        $error = "Failed to create notice. Please try again.";
    }
}

// Handle notice deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notice_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM teacher_notices WHERE notice_id = {$notice_id} AND teacher_id = {$teacher_id}";
    
    if (execute_query($delete_query)) {
        header("Location: notices.php?deleted=1");
        exit;
    }
}

// Fetch teacher's assigned classes for dropdown
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

// Fetch all notices (Admin + Teacher's own)
$filter = $_GET['filter'] ?? 'all';

// Fetch admin notices
$admin_notices_query = "
    SELECT 
        notice_id,
        title,
        description as content,
        notice_date as created_at,
        expiry_date,
        target_audience,
        priority,
        'admin' as source,
        NULL as class_id,
        NULL as notice_type
    FROM notices
    WHERE (target_audience = 'All' OR target_audience = 'Teachers')
    AND (expiry_date >= CURDATE() OR expiry_date IS NULL)
    ORDER BY created_at DESC
";
$admin_notices = get_all($admin_notices_query);

// Fetch teacher's own notices
$teacher_notices_query = "
    SELECT 
        tn.notice_id,
        tn.title,
        tn.content,
        tn.created_at,
        tn.expiry_date,
        tn.notice_type,
        tn.priority,
        'teacher' as source,
        tn.class_id,
        CONCAT(c.class_name, '-', c.section) as class_name
    FROM teacher_notices tn
    LEFT JOIN classes c ON tn.class_id = c.class_id
    WHERE tn.teacher_id = {$teacher_id}
";

if ($filter == 'active') {
    $teacher_notices_query .= " AND (tn.expiry_date >= CURDATE() OR tn.expiry_date IS NULL)";
} elseif ($filter == 'expired') {
    $teacher_notices_query .= " AND tn.expiry_date < CURDATE()";
}

$teacher_notices_query .= " ORDER BY tn.created_at DESC";
$teacher_notices = get_all($teacher_notices_query);

// Combine all notices
$all_notices = array_merge($admin_notices, $teacher_notices);

// Sort by date
usort($all_notices, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices - Teacher Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    
    <style>
        .notice-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .notice-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .notice-card.priority-high {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .notice-card.priority-urgent {
            border-left-color: #dc2626;
            background: #fee2e2;
        }
        
        .notice-card.priority-medium {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        
        .notice-card.priority-low {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .notice-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        
        .notice-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .notice-content {
            color: #4b5563;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        
        .notice-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #6b7280;
            flex-wrap: wrap;
        }
        
        .notice-meta i {
            margin-right: 4px;
        }
        
        .filter-tabs {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .create-notice-btn {
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
        
        .create-notice-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }
        
        .source-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .source-badge.admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .source-badge.teacher {
            background: #dcfce7;
            color: #166534;
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
                Notice created successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-trash me-2"></i>
                Notice deleted successfully!
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
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group" role="group">
                        <a href="notices.php?filter=all" 
                           class="btn btn-<?php echo $filter == 'all' ? 'primary' : 'outline-primary'; ?>">
                            <i class="fas fa-list me-1"></i> All Notices
                        </a>
                        <a href="notices.php?filter=active" 
                           class="btn btn-<?php echo $filter == 'active' ? 'success' : 'outline-success'; ?>">
                            <i class="fas fa-check-circle me-1"></i> Active
                        </a>
                        <a href="notices.php?filter=expired" 
                           class="btn btn-<?php echo $filter == 'expired' ? 'secondary' : 'outline-secondary'; ?>">
                            <i class="fas fa-history me-1"></i> Expired
                        </a>
                    </div>
                    
                    <span class="badge bg-primary fs-6">
                        Total: <?php echo count($all_notices); ?> Notices
                    </span>
                </div>
            </div>
            
            <!-- Notices List -->
            <?php if (count($all_notices) > 0): ?>
                <?php foreach ($all_notices as $notice): ?>
                <div class="notice-card priority-<?php echo strtolower($notice['priority'] ?? 'medium'); ?>">
                    <div class="notice-header">
                        <h5 class="notice-title">
                            <?php echo htmlspecialchars($notice['title']); ?>
                        </h5>
                        <div class="notice-badges">
                            <span class="source-badge <?php echo $notice['source']; ?>">
                                <i class="fas fa-<?php echo $notice['source'] == 'admin' ? 'user-shield' : 'chalkboard-teacher'; ?> me-1"></i>
                                <?php echo ucfirst($notice['source']); ?>
                            </span>
                            <span class="badge bg-<?php 
                                echo $notice['priority'] == 'Urgent' ? 'danger' : 
                                    ($notice['priority'] == 'High' ? 'warning' : 
                                    ($notice['priority'] == 'Medium' ? 'info' : 'secondary')); 
                            ?>">
                                <?php echo htmlspecialchars($notice['priority'] ?? 'Medium'); ?>
                            </span>
                            <?php if (isset($notice['notice_type']) && $notice['notice_type']): ?>
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($notice['notice_type']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (isset($notice['class_name']) && $notice['class_name']): ?>
                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($notice['class_name']); ?>
                            </span>
                            <?php elseif (isset($notice['target_audience']) && $notice['target_audience']): ?>
                            <span class="badge bg-info">
                                <?php echo htmlspecialchars($notice['target_audience']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="notice-content">
                        <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                    </div>
                    
                    <div class="notice-meta">
                        <span>
                            <i class="fas fa-calendar"></i>
                            Posted: <?php echo date('M d, Y', strtotime($notice['created_at'])); ?>
                        </span>
                        <?php if ($notice['expiry_date']): ?>
                        <span>
                            <i class="fas fa-clock"></i>
                            Expires: <?php echo date('M d, Y', strtotime($notice['expiry_date'])); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($notice['source'] == 'teacher'): ?>
                        <span class="ms-auto">
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteNotice(<?php echo $notice['notice_id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Notices Found</h4>
                <p class="text-muted">
                    <?php if ($filter == 'expired'): ?>
                        No expired notices to display.
                    <?php else: ?>
                        Start by creating a new notice for your students.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <?php include 'includes/footer.php'; ?>

        </div>

    </main>
    
    <!-- Create Notice Button -->
    <button class="create-notice-btn" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Create Notice Modal -->
    <div class="modal fade" id="createNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bell me-2"></i>
                        Create New Notice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Notice Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" 
                                       placeholder="Enter notice title" required maxlength="200">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Target Class <span class="text-danger">*</span></label>
                                <select class="form-select" name="class_id" required>
                                    <option value="all">All My Classes</option>
                                    <?php foreach ($assigned_classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name'] . '-' . $class['section']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Notice Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="notice_type" required>
                                    <option value="General">General</option>
                                    <option value="Assignment">Assignment</option>
                                    <option value="Exam">Exam</option>
                                    <option value="Event">Event</option>
                                    <option value="Reminder">Reminder</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Expiry Date (Optional)</label>
                                <input type="date" class="form-control" name="expiry_date" 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Leave empty for no expiry</small>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Notice Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" rows="6" 
                                          placeholder="Enter the notice content here..." required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="create_notice" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Post Notice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/teacher.js"></script>
    
    <script>
        function deleteNotice(noticeId) {
            if (confirm('Are you sure you want to delete this notice? This action cannot be undone.')) {
                window.location.href = 'notices.php?delete=' + noticeId;
            }
        }
    </script>

</body>
</html>
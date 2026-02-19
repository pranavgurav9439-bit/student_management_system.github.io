<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit; }
$page_title = "Notices";
$page_subtitle = "Important announcements and updates";
$notices = get_all("SELECT * FROM notices WHERE target_audience IN ('All','Students') 
    AND (expiry_date IS NULL OR expiry_date >= CURDATE()) 
    ORDER BY priority DESC, notice_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-wrapper { flex: 1; margin-left: 280px; }
        .main-content { padding: 30px; }
        .notice-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #3b82f6; }
        .notice-header { display: flex; justify-content: between; align-items: start; margin-bottom: 15px; }
        .notice-title { font-size: 20px; font-weight: 700; color: #1e293b; flex: 1; }
        .priority-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .priority-urgent { background: #fee2e2; color: #991b1b; }
        .priority-high { background: #fef3c7; color: #92400e; }
        .priority-medium { background: #dbeafe; color: #1e40af; }
        .priority-low { background: #f3f4f6; color: #4b5563; }
        @media (max-width: 768px) { .content-wrapper { margin-left: 0; } }
    </style>
</head>
<body>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        <main class="main-content">
            <?php foreach ($notices as $notice): ?>
                <div class="notice-card">
                    <div class="notice-header">
                        <h3 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h3>
                        <span class="priority-badge priority-<?php echo strtolower($notice['priority']); ?>">
                            <?php echo $notice['priority']; ?>
                        </span>
                    </div>
                    <p style="color: #475569; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($notice['description'])); ?></p>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 13px;">
                        <i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($notice['notice_date'])); ?>
                        <?php if($notice['expiry_date']): ?>
                            &nbsp;•&nbsp; <i class="fas fa-clock"></i> Valid until <?php echo date('F d, Y', strtotime($notice['expiry_date'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
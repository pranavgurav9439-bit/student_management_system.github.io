<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['student_id'])) { header("Location: login.php"); exit; }
$student_id = $_SESSION['student_id'];
$page_title = "Fees Status";
$page_subtitle = "View your fee payments and pending dues";
$fees_query = "SELECT * FROM fees WHERE student_id = $student_id ORDER BY due_date DESC";
$fees = get_all($fees_query);
$summary = get_row("SELECT 
    SUM(amount) as total, 
    SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END) as paid,
    SUM(CASE WHEN payment_status='Pending' THEN amount ELSE 0 END) as pending 
    FROM fees WHERE student_id=$student_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .main-wrapper { display: flex; min-height: 100vh; }
        .content-wrapper { flex: 1; margin-left: 280px; }
        .main-content { padding: 30px; }
        .card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-box h3 { font-size: 14px; color: #64748b; margin-bottom: 10px; }
        .stat-box .value { font-size: 32px; font-weight: 700; }
        .fee-item { padding: 20px; border-left: 4px solid #3b82f6; background: #f8fafc; border-radius: 8px; margin-bottom: 15px; }
        .status-paid { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-overdue { background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        @media (max-width: 768px) { .content-wrapper { margin-left: 0; } .stat-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="content-wrapper">
        <?php include 'includes/navbar.php'; ?>
        <main class="main-content">
            <div class="stat-row">
                <div class="stat-box">
                    <h3>Total Fees</h3>
                    <div class="value">₹<?php echo number_format($summary['total']??0); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Paid</h3>
                    <div class="value" style="color: #10b981;">₹<?php echo number_format($summary['paid']??0); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Pending</h3>
                    <div class="value" style="color: #f59e0b;">₹<?php echo number_format($summary['pending']??0); ?></div>
                </div>
            </div>
            <div class="card">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-receipt"></i> Fee Records</h3>
                <?php foreach ($fees as $fee): ?>
                    <div class="fee-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5><?php echo htmlspecialchars($fee['fee_type']); ?></h5>
                                <p style="color: #64748b; margin: 5px 0;">Due: <?php echo date('M d, Y', strtotime($fee['due_date'])); ?></p>
                                <?php if($fee['paid_date']): ?>
                                    <p style="color: #10b981; margin: 5px 0;"><i class="fas fa-check-circle"></i> Paid on <?php echo date('M d, Y', strtotime($fee['paid_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <h4>₹<?php echo number_format($fee['amount']); ?></h4>
                                <span class="status-<?php echo strtolower($fee['payment_status']); ?>">
                                    <?php echo $fee['payment_status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php include 'includes/footer.php'; ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
        case 'add_payment':
            $data = [
                'student_id' => (int)$_POST['student_id'],
                'amount' => floatval($_POST['amount']),
                'fee_type' => sanitize_input($_POST['fee_type']),
                'due_date' => sanitize_input($_POST['due_date']),
                'payment_status' => sanitize_input($_POST['payment_status']),
                'payment_method' => sanitize_input($_POST['payment_method']),
                'paid_date' => $_POST['payment_status'] == 'Paid' ? sanitize_input($_POST['paid_date']) : 'NULL',
                'transaction_id' => sanitize_input($_POST['transaction_id'])
            ];

            $paid_date_value = $data['paid_date'] === 'NULL' ? 'NULL' : "'{$data['paid_date']}'";
            
            $query = "INSERT INTO fees (student_id, amount, fee_type, due_date, payment_status, 
                      payment_method, paid_date, transaction_id) 
                      VALUES ({$data['student_id']}, {$data['amount']}, '{$data['fee_type']}', 
                      '{$data['due_date']}', '{$data['payment_status']}', '{$data['payment_method']}', 
                      $paid_date_value, '{$data['transaction_id']}')";

            if (insert_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Fee record added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add fee record']);
            }
            exit;

        case 'update_payment':
            $fee_id = (int)$_POST['fee_id'];
            $data = [
                'amount' => floatval($_POST['amount']),
                'fee_type' => sanitize_input($_POST['fee_type']),
                'due_date' => sanitize_input($_POST['due_date']),
                'payment_status' => sanitize_input($_POST['payment_status']),
                'payment_method' => sanitize_input($_POST['payment_method']),
                'paid_date' => $_POST['payment_status'] == 'Paid' ? sanitize_input($_POST['paid_date']) : 'NULL',
                'transaction_id' => sanitize_input($_POST['transaction_id'])
            ];

            $paid_date_value = $data['paid_date'] === 'NULL' ? 'NULL' : "'{$data['paid_date']}'";

            $query = "UPDATE fees SET
                amount = {$data['amount']},
                fee_type = '{$data['fee_type']}',
                due_date = '{$data['due_date']}',
                payment_status = '{$data['payment_status']}',
                payment_method = '{$data['payment_method']}',
                paid_date = $paid_date_value,
                transaction_id = '{$data['transaction_id']}'
            WHERE fee_id = $fee_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment']);
            }
            exit;

        case 'delete_payment':
            $fee_id = (int)$_POST['fee_id'];
            $query = "DELETE FROM fees WHERE fee_id = $fee_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Fee record deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete fee record']);
            }
            exit;

        case 'get_payment':
            $fee_id = (int)$_POST['fee_id'];
            $query = "SELECT * FROM fees WHERE fee_id = $fee_id";
            $fee = get_row($query);

            if ($fee) {
                echo json_encode(['success' => true, 'data' => $fee]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Fee record not found']);
            }
            exit;
    }
}

// Fetch students for dropdown
$students = get_all("SELECT student_id, roll_number, first_name, last_name FROM students WHERE status = 'Active' ORDER BY roll_number");

// Build search and filter query
$where_conditions = ["1=1"];
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$class_filter = isset($_GET['class']) ? (int)$_GET['class'] : '';

if (!empty($search)) {
    $where_conditions[] = "(s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR s.roll_number LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_conditions[] = "f.payment_status = '$status_filter'";
}
if (!empty($class_filter)) {
    $where_conditions[] = "s.class_id = $class_filter";
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM fees f 
                JOIN students s ON f.student_id = s.student_id 
                WHERE $where_clause";
$total_result = get_row($count_query);
$total_fees = $total_result['total'];
$total_pages = ceil($total_fees / $per_page);

// Fetch fee records
$fees_query = "
    SELECT f.*, 
           s.roll_number, s.first_name, s.last_name,
           c.class_name, c.section, d.dept_code
    FROM fees f
    JOIN students s ON f.student_id = s.student_id
    LEFT JOIN classes c ON s.class_id = c.class_id
    LEFT JOIN departments d ON s.dept_id = d.dept_id
    WHERE $where_clause
    ORDER BY f.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$fees = get_all($fees_query);

// Fetch classes
$classes = get_all("SELECT * FROM classes ORDER BY class_name, section");

// Get statistics
$stats_query = "
    SELECT 
        SUM(amount) as total_amount,
        SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as total_collected,
        SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN payment_status = 'Overdue' THEN amount ELSE 0 END) as total_overdue,
        COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) as paid_count,
        COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN payment_status = 'Overdue' THEN 1 END) as overdue_count
    FROM fees
";
$stats = get_row($stats_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management - CollegeSphere</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
    .payment-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .payment-badge.paid {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    .payment-badge.pending {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    .payment-badge.overdue {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    .currency-symbol {
        font-weight: 600;
        color: #6366f1;
    }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
     <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">


        <?php include 'includes/navbar.php'; ?>

        <div class="dashboard-container">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Fee Management</h2>
                    <p class="text-muted mb-0">Manage student fee payments and records</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-plus me-2"></i>Add Payment Record
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-wallet fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Amount</h6>
                                    <h3 class="mb-0 currency-symbol">₹<?php echo number_format($stats['total_amount'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-success bg-opacity-10 text-success rounded p-3">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Collected (<?php echo $stats['paid_count']; ?>)</h6>
                                    <h3 class="mb-0 text-success">₹<?php echo number_format($stats['total_collected'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded p-3">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Pending (<?php echo $stats['pending_count']; ?>)</h6>
                                    <h3 class="mb-0 text-warning">₹<?php echo number_format($stats['total_pending'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-danger bg-opacity-10 text-danger rounded p-3">
                                        <i class="fas fa-exclamation-circle fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Overdue (<?php echo $stats['overdue_count']; ?>)</h6>
                                    <h3 class="mb-0 text-danger">₹<?php echo number_format($stats['total_overdue'] ?? 0); ?></h3>
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
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search student..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
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
                                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Overdue" <?php echo $status_filter == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="finance.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Fee Records Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fees) > 0): ?>
                                    <?php foreach ($fees as $fee): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($fee['created_at'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($fee['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($fee['class_name'] . '-' . $fee['section']); ?></span></td>
                                        <td><?php echo htmlspecialchars($fee['fee_type']); ?></td>
                                        <td><strong class="currency-symbol">₹<?php echo number_format($fee['amount'], 2); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($fee['due_date'])); ?></td>
                                        <td>
                                            <span class="payment-badge <?php echo strtolower($fee['payment_status']); ?>">
                                                <?php echo $fee['payment_status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($fee['payment_method'] ?? '-'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editPayment(<?php echo $fee['fee_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePayment(<?php echo $fee['fee_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No fee records found</p>
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo $class_filter; ?>&status=<?php echo $status_filter; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Payment Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPaymentForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Student *</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- Select Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['student_id']; ?>">
                                            <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fee Type *</label>
                                <select name="fee_type" class="form-select" required>
                                    <option value="Tuition Fee">Tuition Fee</option>
                                    <option value="Semester Fee">Semester Fee</option>
                                    <option value="Exam Fee">Exam Fee</option>
                                    <option value="Library Fee">Library Fee</option>
                                    <option value="Lab Fee">Lab Fee</option>
                                    <option value="Sports Fee">Sports Fee</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount (₹) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date *</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Status *</label>
                                <select name="payment_status" class="form-select" required onchange="togglePaymentFields(this.value)">
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Partial">Partial</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="payment_method_div" style="display:none;">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Card">Card</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="paid_date_div" style="display:none;">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="paid_date" class="form-control">
                            </div>
                            <div class="col-md-6" id="transaction_id_div" style="display:none;">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Payment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPaymentForm">
                    <input type="hidden" name="fee_id" id="edit_fee_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fee Type *</label>
                                <select name="fee_type" id="edit_fee_type" class="form-select" required>
                                    <option value="Tuition Fee">Tuition Fee</option>
                                    <option value="Semester Fee">Semester Fee</option>
                                    <option value="Exam Fee">Exam Fee</option>
                                    <option value="Library Fee">Library Fee</option>
                                    <option value="Lab Fee">Lab Fee</option>
                                    <option value="Sports Fee">Sports Fee</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount (₹) *</label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Due Date *</label>
                                <input type="date" name="due_date" id="edit_due_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Status *</label>
                                <select name="payment_status" id="edit_payment_status" class="form-select" required onchange="toggleEditPaymentFields(this.value)">
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Partial">Partial</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="edit_payment_method_div">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" id="edit_payment_method" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Card">Card</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="edit_paid_date_div">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="paid_date" id="edit_paid_date" class="form-control">
                            </div>
                            <div class="col-md-6" id="edit_transaction_id_div">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="transaction_id" id="edit_transaction_id" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>
    
    <script>
    function togglePaymentFields(status) {
        const methodDiv = document.getElementById('payment_method_div');
        const dateDiv = document.getElementById('paid_date_div');
        const transDiv = document.getElementById('transaction_id_div');
        
        if (status === 'Paid') {
            methodDiv.style.display = 'block';
            dateDiv.style.display = 'block';
            transDiv.style.display = 'block';
        } else {
            methodDiv.style.display = 'none';
            dateDiv.style.display = 'none';
            transDiv.style.display = 'none';
        }
    }

    function toggleEditPaymentFields(status) {
        const methodDiv = document.getElementById('edit_payment_method_div');
        const dateDiv = document.getElementById('edit_paid_date_div');
        const transDiv = document.getElementById('edit_transaction_id_div');
        
        if (status === 'Paid') {
            methodDiv.style.display = 'block';
            dateDiv.style.display = 'block';
            transDiv.style.display = 'block';
        } else {
            methodDiv.style.display = 'none';
            dateDiv.style.display = 'none';
            transDiv.style.display = 'none';
        }
    }

    // Add Payment
    document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add_payment');
        
        fetch('finance.php', {
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
    
    // Edit Payment
    function editPayment(id) {
        const formData = new FormData();
        formData.append('action', 'get_payment');
        formData.append('fee_id', id);
        
        fetch('finance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fee = data.data;
                document.getElementById('edit_fee_id').value = fee.fee_id;
                document.getElementById('edit_fee_type').value = fee.fee_type;
                document.getElementById('edit_amount').value = fee.amount;
                document.getElementById('edit_due_date').value = fee.due_date;
                document.getElementById('edit_payment_status').value = fee.payment_status;
                document.getElementById('edit_payment_method').value = fee.payment_method || '';
                document.getElementById('edit_paid_date').value = fee.paid_date || '';
                document.getElementById('edit_transaction_id').value = fee.transaction_id || '';
                
                toggleEditPaymentFields(fee.payment_status);
                
                new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
            }
        });
    }
    
    // Update Payment
    document.getElementById('editPaymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_payment');
        
        fetch('finance.php', {
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
    
    // Delete Payment
    function deletePayment(id) {
        if (confirm('Are you sure you want to delete this fee record?')) {
            const formData = new FormData();
            formData.append('action', 'delete_payment');
            formData.append('fee_id', id);
            
            fetch('finance.php', {
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
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
                'class_id' => (int)$_POST['class_id'],
                'subject_id' => (int)$_POST['subject_id'],
                'teacher_id' => !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 'NULL',
                'day_of_week' => sanitize_input($_POST['day_of_week']),
                'start_time' => sanitize_input($_POST['start_time']),
                'end_time' => sanitize_input($_POST['end_time']),
                'room_number' => sanitize_input($_POST['room_number'])
            ];

            $teacher_value = $data['teacher_id'] === 'NULL' ? 'NULL' : $data['teacher_id'];

            $query = "INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room_number) 
                     VALUES ({$data['class_id']}, {$data['subject_id']}, $teacher_value, '{$data['day_of_week']}', 
                     '{$data['start_time']}', '{$data['end_time']}', '{$data['room_number']}')";

            if (insert_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Timetable entry added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add timetable entry. Check for conflicts.']);
            }
            exit;

        case 'update':
            $timetable_id = (int)$_POST['timetable_id'];
            $data = [
                'subject_id' => (int)$_POST['subject_id'],
                'teacher_id' => !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 'NULL',
                'start_time' => sanitize_input($_POST['start_time']),
                'end_time' => sanitize_input($_POST['end_time']),
                'room_number' => sanitize_input($_POST['room_number'])
            ];

            $teacher_value = $data['teacher_id'] === 'NULL' ? 'NULL' : $data['teacher_id'];

            $query = "UPDATE timetable SET
                subject_id = {$data['subject_id']},
                teacher_id = $teacher_value,
                start_time = '{$data['start_time']}',
                end_time = '{$data['end_time']}',
                room_number = '{$data['room_number']}'
            WHERE timetable_id = $timetable_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Timetable updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update timetable']);
            }
            exit;

        case 'delete':
            $timetable_id = (int)$_POST['timetable_id'];
            $query = "DELETE FROM timetable WHERE timetable_id = $timetable_id";

            if (modify_data($query)) {
                echo json_encode(['success' => true, 'message' => 'Timetable entry deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete entry']);
            }
            exit;

        case 'get':
            $timetable_id = (int)$_POST['timetable_id'];
            $query = "SELECT * FROM timetable WHERE timetable_id = $timetable_id";
            $entry = get_row($query);

            if ($entry) {
                echo json_encode(['success' => true, 'data' => $entry]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Entry not found']);
            }
            exit;

        case 'get_class_timetable':
            $class_id = (int)$_POST['class_id'];
            $query = "
                SELECT t.*, 
                       s.subject_name, s.subject_code,
                       CONCAT(te.first_name, ' ', te.last_name) as teacher_name
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.subject_id
                LEFT JOIN teachers te ON t.teacher_id = te.teacher_id
                WHERE t.class_id = $class_id
                ORDER BY 
                    FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    t.start_time
            ";
            $timetable = get_all($query);
            echo json_encode(['success' => true, 'data' => $timetable]);
            exit;
    }
}

// Fetch data for dropdowns
$classes = get_all("SELECT c.*, d.dept_code FROM classes c LEFT JOIN departments d ON c.dept_id = d.dept_id ORDER BY c.class_name, c.section");
$subjects = get_all("SELECT * FROM subjects ORDER BY subject_name");
$teachers = get_all("SELECT teacher_id, first_name, last_name FROM teachers WHERE status = 'Active' ORDER BY first_name, last_name");

// Get selected class for viewing
$selected_class = isset($_GET['class']) ? (int)$_GET['class'] : (count($classes) > 0 ? $classes[0]['class_id'] : 0);

// Fetch timetable for selected class
$timetable_data = [];
if ($selected_class > 0) {
    $timetable_query = "
        SELECT t.*, 
               s.subject_name, s.subject_code,
               CONCAT(te.first_name, ' ', te.last_name) as teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.subject_id
        LEFT JOIN teachers te ON t.teacher_id = te.teacher_id
        WHERE t.class_id = $selected_class
        ORDER BY 
            FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
            t.start_time
    ";
    $timetable_data = get_all($timetable_query);
}

// Get class info
$class_info = null;
if ($selected_class > 0) {
    $class_info = get_row("SELECT c.*, d.dept_name FROM classes c LEFT JOIN departments d ON c.dept_id = d.dept_id WHERE c.class_id = $selected_class");
}

// Statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_entries,
        COUNT(DISTINCT class_id) as total_classes,
        COUNT(DISTINCT teacher_id) as teachers_assigned,
        COUNT(DISTINCT subject_id) as subjects_scheduled
    FROM timetable
";
$stats = get_row($stats_query);

// Days of week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Time slots (common periods)
$time_slots = [
    ['start' => '08:00:00', 'end' => '09:00:00', 'label' => '8:00 AM - 9:00 AM'],
    ['start' => '09:00:00', 'end' => '10:00:00', 'label' => '9:00 AM - 10:00 AM'],
    ['start' => '10:00:00', 'end' => '11:00:00', 'label' => '10:00 AM - 11:00 AM'],
    ['start' => '11:00:00', 'end' => '12:00:00', 'label' => '11:00 AM - 12:00 PM'],
    ['start' => '12:00:00', 'end' => '13:00:00', 'label' => '12:00 PM - 1:00 PM'],
    ['start' => '13:00:00', 'end' => '14:00:00', 'label' => '1:00 PM - 2:00 PM'],
    ['start' => '14:00:00', 'end' => '15:00:00', 'label' => '2:00 PM - 3:00 PM'],
    ['start' => '15:00:00', 'end' => '16:00:00', 'label' => '3:00 PM - 4:00 PM'],
];

// Organize timetable in grid format
$timetable_grid = [];
foreach ($days as $day) {
    $timetable_grid[$day] = [];
    foreach ($time_slots as $slot) {
        $timetable_grid[$day][$slot['start']] = null;
    }
}

// Fill the grid
foreach ($timetable_data as $entry) {
    $timetable_grid[$entry['day_of_week']][$entry['start_time']] = $entry;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management - CollegeSphere</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .timetable-grid {
            overflow-x: auto;
        }

        .timetable-table {
            min-width: 1200px;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .timetable-table th {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            color: white;
            padding: 12px;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .timetable-table td {
            background: white;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-height: 80px;
            vertical-align: top;
        }

        .time-label {
            background: #f8fafc;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            padding: 12px 8px;
            text-align: center;
            border-radius: 8px;
        }

        .period-card {
            padding: 12px;
            height: 100%;
            min-height: 80px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 4px solid #6366f1;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .period-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .period-card .subject-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .period-card .teacher-name {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .period-card .room-number {
            font-size: 11px;
            color: #94a3b8;
        }

        .period-card .period-actions {
            margin-top: 8px;
            display: none;
        }

        .period-card:hover .period-actions {
            display: block;
        }

        .empty-slot {
            padding: 12px;
            text-align: center;
            color: #cbd5e1;
            cursor: pointer;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .empty-slot:hover {
            border-color: #6366f1;
            background: #f8fafc;
            color: #6366f1;
        }

        .class-selector {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .timetable-table {
                border-spacing: 2px;
            }
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

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded p-3">
                                        <i class="fas fa-calendar-week fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Periods</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_entries']); ?></h3>
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
                                        <i class="fas fa-chalkboard fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Classes Scheduled</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_classes']); ?></h3>
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
                                    <div class="stat-icon bg-info bg-opacity-10 text-info rounded p-3">
                                        <i class="fas fa-user-tie fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Teachers Assigned</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['teachers_assigned']); ?></h3>
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
                                        <i class="fas fa-book fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Subjects Active</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['subjects_scheduled']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Selector -->
            <div class="class-selector no-print">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-primary"></i>View Timetable
                        </h5>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" onchange="window.location.href='timetable.php?class='+this.value">
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section'] . ' (' . $class['dept_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-success me-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Timetable
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                            <i class="fas fa-plus me-2"></i>Add Period
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($selected_class > 0 && $class_info): ?>
                <!-- Timetable Header -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1"><?php echo htmlspecialchars($class_info['class_name'] . ' - Section ' . $class_info['section']); ?></h4>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($class_info['dept_name']); ?>
                                    <span class="ms-3"><i class="fas fa-users me-2"></i>Capacity: <?php echo $class_info['capacity']; ?> students</span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-primary p-2">
                                    <i class="fas fa-calendar-alt me-2"></i><?php echo count($timetable_data); ?> Periods Scheduled
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timetable Grid -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="timetable-grid p-3">
                            <table class="timetable-table w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Time</th>
                                        <?php foreach ($days as $day): ?>
                                            <th><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $slot): ?>
                                        <tr>
                                            <td class="time-label">
                                                <?php
                                                echo date('g:i A', strtotime($slot['start'])) . '<br>' .
                                                    date('g:i A', strtotime($slot['end']));
                                                ?>
                                            </td>
                                            <?php foreach ($days as $day): ?>
                                                <td>
                                                    <?php
                                                    $period = $timetable_grid[$day][$slot['start']];
                                                    if ($period):
                                                    ?>
                                                        <div class="period-card" onclick="editPeriod(<?php echo $period['timetable_id']; ?>)">
                                                            <div class="subject-name">
                                                                <?php echo htmlspecialchars($period['subject_code']); ?>
                                                            </div>
                                                            <div class="teacher-name">
                                                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($period['teacher_name'] ?? 'No teacher'); ?>
                                                            </div>
                                                            <div class="room-number">
                                                                <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($period['room_number']); ?>
                                                            </div>
                                                            <div class="period-actions">
                                                                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); editPeriod(<?php echo $period['timetable_id']; ?>)">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deletePeriod(<?php echo $period['timetable_id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="empty-slot" onclick="addPeriodQuick('<?php echo $day; ?>', '<?php echo $slot['start']; ?>', '<?php echo $slot['end']; ?>')">
                                                            <span><i class="fas fa-plus-circle"></i> Add</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="legend no-print">
                    <div class="legend-item">
                        <div class="legend-color" style="background: linear-gradient(135deg, #6366f1, #818cf8);"></div>
                        <span>Scheduled Period</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="border: 2px dashed #e2e8f0;"></div>
                        <span>Empty Slot</span>
                    </div>
                    <div class="legend-item">
                        <i class="fas fa-info-circle text-primary"></i>
                        <span>Click on any slot to add/edit period</span>
                    </div>
                </div>

            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Please select a class to view timetable</h4>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Add Period Modal -->
    <div class="modal fade" id="addPeriodModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Period to Timetable</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPeriodForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Class *</label>
                                <select name="class_id" id="add_class_id" class="form-select" required>
                                    <option value="">-- Select Class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo $selected_class == $class['class_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Subject *</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assign Teacher</label>
                                <select name="teacher_id" class="form-select">
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['teacher_id']; ?>">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Day of Week *</label>
                                <select name="day_of_week" id="add_day_of_week" class="form-select" required>
                                    <option value="">-- Select Day --</option>
                                    <?php foreach ($days as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Time *</label>
                                <input type="time" name="start_time" id="add_start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time *</label>
                                <input type="time" name="end_time" id="add_end_time" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Room Number</label>
                                <input type="text" name="room_number" class="form-control" placeholder="e.g., Room 101, Lab A">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add to Timetable
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Period Modal -->
    <div class="modal fade" id="editPeriodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPeriodForm">
                    <input type="hidden" name="timetable_id" id="edit_timetable_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Subject *</label>
                                <select name="subject_id" id="edit_subject_id" class="form-select" required>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['subject_id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Teacher</label>
                                <select name="teacher_id" id="edit_teacher_id" class="form-select">
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['teacher_id']; ?>">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Time *</label>
                                <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time *</label>
                                <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Room Number</label>
                                <input type="text" name="room_number" id="edit_room_number" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin-dashboard.js"></script>

    <script>
        // Add Period
        document.getElementById('addPeriodForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'add');

            fetch('timetable.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else {
                        alert(d.message);
                    }
                });
        });

        // Quick add with pre-filled data
        function addPeriodQuick(day, startTime, endTime) {
            document.getElementById('add_day_of_week').value = day;
            document.getElementById('add_start_time').value = startTime;
            document.getElementById('add_end_time').value = endTime;
            new bootstrap.Modal(document.getElementById('addPeriodModal')).show();
        }

        // Edit Period
        function editPeriod(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('timetable_id', id);

            fetch('timetable.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const p = d.data;
                        document.getElementById('edit_timetable_id').value = p.timetable_id;
                        document.getElementById('edit_subject_id').value = p.subject_id;
                        document.getElementById('edit_teacher_id').value = p.teacher_id || '';
                        document.getElementById('edit_start_time').value = p.start_time;
                        document.getElementById('edit_end_time').value = p.end_time;
                        document.getElementById('edit_room_number').value = p.room_number;
                        new bootstrap.Modal(document.getElementById('editPeriodModal')).show();
                    }
                });
        }

        // Update Period
        document.getElementById('editPeriodForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update');

            fetch('timetable.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert(d.message);
                        location.reload();
                    } else {
                        alert(d.message);
                    }
                });
        });

        // Delete Period
        function deletePeriod(id) {
            if (confirm('Delete this period from timetable?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('timetable_id', id);

                fetch('timetable.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            alert(d.message);
                            location.reload();
                        } else {
                            alert(d.message);
                        }
                    });
            }
        }
    </script>

</body>

</html>
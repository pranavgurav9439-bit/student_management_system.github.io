<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$stream_id = isset($_GET['stream_id']) ? (int)$_GET['stream_id'] : 0;

if ($stream_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid stream ID']);
    exit;
}

// Fetch subjects for the selected stream
$query = "
    SELECT subject_id, subject_name, subject_code, credits, description
    FROM subjects
    WHERE stream_id = {$stream_id}
    ORDER BY subject_name
";

$subjects = get_all($query);

if ($subjects) {
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No subjects found',
        'subjects' => []
    ]);
}
?>
<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
$generated_roll_number = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $stream_id = (int)$_POST['stream_id'];
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    $gender = sanitize_input($_POST['gender']);
    
    // New fields
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $pincode = sanitize_input($_POST['pincode']);
    $guardian_name = sanitize_input($_POST['guardian_name']);
    $guardian_phone = sanitize_input($_POST['guardian_phone']);
    
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || 
        empty($password) || empty($stream_id) || empty($gender) ||
        empty($address) || empty($city) || empty($state) || empty($pincode) ||
        empty($guardian_name) || empty($guardian_phone)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $error = "Pincode must be 6 digits";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be 10 digits";
    } elseif (!preg_match('/^[0-9]{10}$/', $guardian_phone)) {
        $error = "Guardian phone number must be 10 digits";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Generate roll number
            $roll_number = generateRollNumber($stream_id);
            
            if (!$roll_number) {
                $error = "Failed to generate roll number. Please try again.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Get department ID from stream
                $stmt = $conn->prepare("SELECT dept_id FROM streams WHERE stream_id = ?");
                $stmt->bind_param("i", $stream_id);
                $stmt->execute();
                $stream_result = $stmt->get_result();
                $stream_data = $stream_result->fetch_assoc();
                $dept_id = $stream_data['dept_id'] ?? null;
                
                // Handle document uploads
                $upload_dir = '../uploads/student_documents/' . $roll_number . '/';
                $document_paths = [];
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $max_file_size = 5 * 1024 * 1024; // 5MB
                
                // Process each document type
                $document_types = ['aadhar', 'tenth_marksheet', 'twelfth_marksheet', 'other_documents'];
                
                foreach ($document_types as $doc_type) {
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] == 0) {
                        $file_name = $_FILES[$doc_type]['name'];
                        $file_size = $_FILES[$doc_type]['size'];
                        $file_tmp = $_FILES[$doc_type]['tmp_name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        if (!in_array($file_ext, $allowed_extensions)) {
                            $error = "Invalid file type for $doc_type. Only PDF, JPG, JPEG, PNG allowed.";
                            break;
                        }
                        
                        if ($file_size > $max_file_size) {
                            $error = "File size for $doc_type exceeds 5MB limit.";
                            break;
                        }
                        
                        $new_file_name = $doc_type . '_' . time() . '.' . $file_ext;
                        $destination = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $destination)) {
                            $document_paths[$doc_type] = 'uploads/student_documents/' . $roll_number . '/' . $new_file_name;
                        }
                    }
                }
                
                if (empty($error)) {
                    // Insert new student using prepared statement
                    $stmt = $conn->prepare("
                        INSERT INTO students 
                        (roll_number, first_name, last_name, email, password, phone, 
                         date_of_birth, gender, address, city, state, pincode,
                         guardian_name, guardian_phone, stream_id, dept_id, 
                         enrollment_date, status) 
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Active')
                    ");
                    
                    $stmt->bind_param("ssssssssssssssii", 
                        $roll_number, $first_name, $last_name, $email, $hashed_password, 
                        $phone, $date_of_birth, $gender, $address, $city, $state, $pincode,
                        $guardian_name, $guardian_phone, $stream_id, $dept_id
                    );
                    
                    if ($stmt->execute()) {
                        $student_id = $stmt->insert_id;
                        
                        // Insert document records
                        if (!empty($document_paths)) {
                            $doc_stmt = $conn->prepare("
                                INSERT INTO student_documents 
                                (student_id, document_type, document_name, file_path, uploaded_date) 
                                VALUES (?, ?, ?, ?, NOW())
                            ");
                            
                            foreach ($document_paths as $doc_type => $file_path) {
                                $doc_name = ucfirst(str_replace('_', ' ', $doc_type));
                                $doc_stmt->bind_param("isss", $student_id, $doc_type, $doc_name, $file_path);
                                $doc_stmt->execute();
                            }
                            $doc_stmt->close();
                        }
                        
                        // Insert selected subjects
                        if (!empty($subjects)) {
                            $subject_stmt = $conn->prepare("
                                INSERT INTO student_subjects (student_id, subject_id, enrollment_date, status) 
                                VALUES (?, ?, CURDATE(), 'Active')
                            ");
                            
                            foreach ($subjects as $subject_id) {
                                $subject_id = (int)$subject_id;
                                $subject_stmt->bind_param("ii", $student_id, $subject_id);
                                $subject_stmt->execute();
                            }
                            $subject_stmt->close();
                        }
                        
                        $generated_roll_number = $roll_number;
                        $success = "Registration successful! Your Roll Number is <strong>$roll_number</strong>. Please login with your credentials.";
                        // Redirect to login after 4 seconds
                        header("refresh:4;url=login.php");
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

/**
 * Generate unique roll number for student based on stream
 * Format: STREAMCODE + YEAR + 3-digit sequential number (e.g., BCA2026001)
 */
function generateRollNumber($stream_id) {
    global $conn;
    
    // Get stream code
    $stmt = $conn->prepare("SELECT stream_code FROM streams WHERE stream_id = ? AND is_active = 1");
    $stmt->bind_param("i", $stream_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $stream = $result->fetch_assoc();
    $stream_code = $stream['stream_code'];
    $stmt->close();
    
    $current_year = date('Y');
    
    // Count existing students in this stream for current year
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM students 
        WHERE stream_id = ? AND YEAR(enrollment_date) = ?
    ");
    $stmt->bind_param("ii", $stream_id, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $count_data = $result->fetch_assoc();
    $next_number = $count_data['count'] + 1;
    $stmt->close();
    
    // Generate roll number with zero padding (3 digits)
    $roll_number = $stream_code . $current_year . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    
    // Check if roll number already exists (safety check)
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // If exists, try next number
        $next_number++;
        $roll_number = $stream_code . $current_year . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    }
    $stmt->close();
    
    return $roll_number;
}

// Fetch streams for dropdown
$streams = get_all("SELECT stream_id, stream_name, stream_code FROM streams WHERE is_active = 1 ORDER BY stream_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - CollegeSphere</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --primary-dark: #5568d3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .signup-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .signup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .signup-header {
            background: var(--primary-gradient);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .signup-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .signup-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 28px;
        }
        
        .signup-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .signup-body {
            padding: 40px 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .required {
            color: #ef4444;
        }
        
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-signup {
            width: 100%;
            padding: 14px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #059669;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        
        .login-link a:hover {
            color: var(--primary-dark);
        }
        
        .password-strength {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .subject-checkbox {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .subject-checkbox:hover {
            border-color: var(--primary-color);
            background: #f1f5f9;
        }
        
        .subject-checkbox.selected {
            border-color: var(--primary-color);
            background: #ede9fe;
        }
        
        .subject-checkbox input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .loading-subjects {
            text-align: center;
            padding: 20px;
            color: #64748b;
        }
        
        #subjectsContainer {
            display: none;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            color: #64748b;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: #f1f5f9;
        }
        
        .file-upload-label i {
            margin-right: 8px;
        }
        
        .file-name {
            font-size: 12px;
            color: #059669;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .document-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-message i {
            font-size: 80px;
            color: #10b981;
            margin-bottom: 20px;
        }
        
        .success-message h3 {
            color: #059669;
            margin-bottom: 15px;
        }
        
        .roll-number-display {
            background: #d1fae5;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
            color: #059669;
        }
    </style>
</head>
<body>

    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <i class="fas fa-graduation-cap"></i>
                <h2>Student Registration</h2>
                <p>Join CollegeSphere - Your Journey Begins Here</p>
            </div>
            
            <div class="signup-body">
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Registration Successful!</h3>
                        <div class="roll-number-display">
                            Your Roll Number: <?php echo htmlspecialchars($generated_roll_number); ?>
                        </div>
                        <p>Please save your roll number for future reference.<br>
                        Redirecting to login page...</p>
                    </div>
                <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="signupForm" enctype="multipart/form-data">
                    <!-- Basic Information -->
                    <div class="section-title">
                        <i class="fas fa-user me-2"></i>Basic Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">
                                First Name <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   placeholder="Enter first name" required
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">
                                Last Name <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   placeholder="Enter last name" required
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="your.email@example.com" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">
                                Phone Number <span class="required">*</span>
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="e.g., 9876543210" pattern="[0-9]{10}" required
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">
                                Date of Birth <span class="required">*</span>
                            </label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required
                                   value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">
                                Gender <span class="required">*</span>
                            </label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Address Information -->
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt me-2"></i>Address Information
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            Address <span class="required">*</span>
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="2" 
                                  placeholder="Enter complete address" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="city" class="form-label">
                                City <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   placeholder="Enter city" required
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="state" class="form-label">
                                State <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   placeholder="Enter state" required
                                   value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="pincode" class="form-label">
                                Pincode <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="pincode" name="pincode" 
                                   placeholder="e.g., 400001" pattern="[0-9]{6}" required
                                   value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Guardian Information -->
                    <div class="section-title">
                        <i class="fas fa-user-shield me-2"></i>Guardian Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="guardian_name" class="form-label">
                                Guardian Name <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                                   placeholder="Enter guardian's full name" required
                                   value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="guardian_phone" class="form-label">
                                Guardian Phone <span class="required">*</span>
                            </label>
                            <input type="tel" class="form-control" id="guardian_phone" name="guardian_phone" 
                                   placeholder="e.g., 9876543210" pattern="[0-9]{10}" required
                                   value="<?php echo htmlspecialchars($_POST['guardian_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="section-title">
                        <i class="fas fa-book me-2"></i>Academic Information
                    </div>
                    
                    <div class="mb-3">
                        <label for="stream_id" class="form-label">
                            Select Stream <span class="required">*</span>
                        </label>
                        <select class="form-select" id="stream_id" name="stream_id" required>
                            <option value="">Choose your stream...</option>
                            <?php foreach ($streams as $stream): ?>
                                <option value="<?php echo $stream['stream_id']; ?>" 
                                        <?php echo (isset($_POST['stream_id']) && $_POST['stream_id'] == $stream['stream_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($stream['stream_name'] . ' (' . $stream['stream_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="subjectsContainer" class="mb-3">
                        <label class="form-label">
                            Select Subjects <small class="text-muted">(Optional)</small>
                        </label>
                        <div id="subjectsGrid"></div>
                    </div>
                    
                    <!-- Document Upload -->
                    <div class="section-title">
                        <i class="fas fa-file-upload me-2"></i>Document Upload
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Aadhar Card
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="file-upload-input" id="aadhar" name="aadhar" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="displayFileName(this)">
                                <label for="aadhar" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose Aadhar Card</span>
                                </label>
                            </div>
                            <div class="file-name" id="aadhar_name"></div>
                            <div class="document-info">PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                10th Marksheet
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="file-upload-input" id="tenth_marksheet" name="tenth_marksheet" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="displayFileName(this)">
                                <label for="tenth_marksheet" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose 10th Marksheet</span>
                                </label>
                            </div>
                            <div class="file-name" id="tenth_marksheet_name"></div>
                            <div class="document-info">PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                12th Marksheet
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="file-upload-input" id="twelfth_marksheet" name="twelfth_marksheet" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="displayFileName(this)">
                                <label for="twelfth_marksheet" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose 12th Marksheet</span>
                                </label>
                            </div>
                            <div class="file-name" id="twelfth_marksheet_name"></div>
                            <div class="document-info">PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Other Documents
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="file-upload-input" id="other_documents" name="other_documents" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="displayFileName(this)">
                                <label for="other_documents" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Choose Other Documents</span>
                                </label>
                            </div>
                            <div class="file-name" id="other_documents_name"></div>
                            <div class="document-info">PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="section-title">
                        <i class="fas fa-lock me-2"></i>Security
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Password <span class="required">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Minimum 6 characters" required minlength="6">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                Confirm Password <span class="required">*</span>
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Re-enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-signup">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <div class="login-link">
                    Already have an account? 
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login Here
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small style="color: rgba(255,255,255,0.8);">
                © 2026 CollegeSphere. All rights reserved.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Display file name when selected
        function displayFileName(input) {
            const fileName = input.files[0]?.name;
            const nameDisplay = document.getElementById(input.id + '_name');
            if (fileName && nameDisplay) {
                nameDisplay.textContent = '✓ ' + fileName;
            }
        }
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength += 25;
                if (password.length >= 10) strength += 25;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                if (/\d/.test(password)) strength += 15;
                if (/[^a-zA-Z\d]/.test(password)) strength += 10;
                
                strengthBar.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthBar.style.background = '#ef4444';
                } else if (strength < 70) {
                    strengthBar.style.background = '#f59e0b';
                } else {
                    strengthBar.style.background = '#10b981';
                }
            });
        }
        
        // Confirm password validation
        const confirmPassword = document.getElementById('confirm_password');
        const signupForm = document.getElementById('signupForm');
        
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                if (passwordInput.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    confirmPassword.focus();
                    return false;
                }
                
                // Validate file sizes
                const fileInputs = document.querySelectorAll('input[type="file"]');
                for (let input of fileInputs) {
                    if (input.files.length > 0) {
                        const fileSize = input.files[0].size / 1024 / 1024; // in MB
                        if (fileSize > 5) {
                            e.preventDefault();
                            alert('File size for ' + input.id.replace('_', ' ') + ' exceeds 5MB limit!');
                            return false;
                        }
                    }
                }
            });
        }
        
        // Dynamic subject loading based on stream selection
        const streamSelect = document.getElementById('stream_id');
        const subjectsContainer = document.getElementById('subjectsContainer');
        const subjectsGrid = document.getElementById('subjectsGrid');
        
        if (streamSelect) {
            streamSelect.addEventListener('change', function() {
                const streamId = this.value;
                
                if (streamId) {
                    // Show subjects container
                    subjectsContainer.style.display = 'block';
                    subjectsGrid.innerHTML = '<div class="loading-subjects"><i class="fas fa-spinner fa-spin"></i> Loading subjects...</div>';
                    
                    // Fetch subjects via AJAX
                    fetch('get_subjects.php?stream_id=' + streamId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.subjects.length > 0) {
                                let html = '';
                                data.subjects.forEach(subject => {
                                    html += `
                                        <div class="subject-checkbox" onclick="toggleSubject(this)">
                                            <input type="checkbox" 
                                                   name="subjects[]" 
                                                   value="${subject.subject_id}" 
                                                   id="subject_${subject.subject_id}">
                                            <label for="subject_${subject.subject_id}" style="cursor: pointer; margin: 0;">
                                                ${subject.subject_name}
                                                <small class="d-block text-muted">${subject.subject_code}</small>
                                            </label>
                                        </div>
                                    `;
                                });
                                subjectsGrid.innerHTML = html;
                            } else {
                                subjectsGrid.innerHTML = '<div class="loading-subjects">No subjects available for this stream</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            subjectsGrid.innerHTML = '<div class="loading-subjects text-danger">Error loading subjects</div>';
                        });
                } else {
                    subjectsContainer.style.display = 'none';
                }
            });
        }
        
        // Toggle subject checkbox styling
        function toggleSubject(element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            element.classList.toggle('selected', checkbox.checked);
        }
    </script>
</body>
</html>
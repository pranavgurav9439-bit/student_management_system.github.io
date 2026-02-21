-- ============================================================
-- CollegeSphere - Database SQL File
-- Generated for: college_sphere
-- Place this file in: your_project/database/college_sphere.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `college_sphere` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `college_sphere`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- ============================================================
-- TABLE STRUCTURE
-- ============================================================

--
-- Table: admins
--
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: departments
--
CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: classes
--
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(10) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: streams
--
CREATE TABLE `streams` (
  `stream_id` int(11) NOT NULL,
  `stream_name` varchar(100) NOT NULL,
  `stream_code` varchar(10) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: students
--
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `stream_id` int(11) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('Active','Inactive','Suspended','Graduated') DEFAULT 'Active',
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: teachers
--
CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `joining_date` date NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: staff
--
CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `designation` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `joining_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: subjects
--
CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `stream_id` int(11) DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: student_subjects
--
CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT current_timestamp(),
  `status` enum('Active','Dropped','Completed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: teacher_subjects
--
CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: teacher_class_assignments
--
CREATE TABLE `teacher_class_assignments` (
  `assignment_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT '2024-2025',
  `is_class_teacher` tinyint(1) DEFAULT 0 COMMENT 'Is this teacher the class teacher?',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table: attendance
--
CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: marks
--
CREATE TABLE `marks` (
  `mark_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_type` enum('Mid-Term','Final','Assignment','Quiz','Practical') NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `exam_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: fees
--
CREATE TABLE `fees` (
  `fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_status` enum('Paid','Pending','Overdue','Partial') DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: notices
--
CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `notice_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `target_audience` enum('All','Students','Teachers','Staff') DEFAULT 'All',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: teacher_notices
--
CREATE TABLE `teacher_notices` (
  `notice_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL COMMENT 'NULL = all classes, specific class_id = that class only',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `notice_type` enum('General','Assignment','Exam','Event','Reminder') DEFAULT 'General',
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table: timetable
--
CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: leave_requests
--
CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `leave_type` enum('Sick Leave','Casual Leave','Emergency Leave','Maternity Leave','Paternity Leave','Unpaid Leave') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `admin_remarks` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: student_documents
--
CREATE TABLE `student_documents` (
  `document_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL COMMENT 'aadhar, tenth_marksheet, twelfth_marksheet, other_documents',
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'admin_id who uploaded',
  `status` enum('Active','Deleted') DEFAULT 'Active',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: student_notifications
--
CREATE TABLE `student_notifications` (
  `notification_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: student_sessions
--
CREATE TABLE `student_sessions` (
  `session_id` varchar(128) NOT NULL,
  `student_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table: settings
--
CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

--
-- Data: admins
--
INSERT INTO `admins` (`admin_id`, `username`, `password`, `full_name`, `email`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$1hsNDBva1LXhxIShR5lSSOZUNcCuioBZ.l0yYxONkXe1aTH1aTI5O', 'admin', 'admin@college.com', '2026-01-29 14:38:52', '2026-01-29 14:42:01');

--
-- Data: staff
--
INSERT INTO `staff` (`staff_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `designation`, `department`, `joining_date`, `status`, `created_at`) VALUES
(1, 'STAFF001', 'Ramesh', 'Yadav',   'ramesh.yadav@college.edu',   '9234567890', 'Office Clerk',     'Administration', '2015-03-01', 'Active', '2026-01-29 14:26:58'),
(2, 'STAFF002', 'Geeta',  'Nair',    'geeta.nair@college.edu',     '9234567891', 'Librarian',        'Library',        '2016-06-15', 'Active', '2026-01-29 14:26:58'),
(3, 'STAFF003', 'Mohan',  'Jha',     'mohan.jha@college.edu',      '9234567892', 'Lab Assistant',    'Computer Lab',   '2017-08-20', 'Active', '2026-01-29 14:26:58'),
(4, 'STAFF004', 'Lakshmi','Iyer',    'lakshmi.iyer@college.edu',   '9234567893', 'Accountant',       'Finance',        '2014-01-10', 'Active', '2026-01-29 14:26:58'),
(5, 'STAFF005', 'Vijay',  'Mishra',  'vijay.mishra@college.edu',   '9234567894', 'Security Officer', 'Security',       '2018-05-01', 'Active', '2026-01-29 14:26:58');

--
-- Data: teachers
--
INSERT INTO `teachers` (`teacher_id`, `employee_id`, `first_name`, `last_name`, `email`, `password`, `last_login`, `phone`, `date_of_birth`, `gender`, `address`, `city`, `state`, `pincode`, `dept_id`, `designation`, `qualification`, `experience_years`, `joining_date`, `salary`, `status`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'Dr. Rajesh', 'Sharma',  'rajesh.sharma@college.edu',  '$2y$10$TisiO/pB.auLaf8SvuxL4OJga0BdaojVtEpCk3/ka/k9qKcp9ITmC', '2026-02-11 07:08:15', '9123456780', '1980-05-15', 'Male',   '12 Faculty Housing', 'Mumbai',    'Maharashtra', '400001', NULL, 'Professor',           'PhD in Computer Science',        15, '2010-07-01', 85000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-02-11 07:08:15'),
(2, 'EMP002', 'Ms. Kavita', 'Roy',     'kavita.roy@college.edu',     NULL, NULL, '9123456781', '1985-08-22', 'Female', '15 Faculty Housing', 'Mumbai',    'Maharashtra', '400001', NULL, 'Associate Professor', 'M.Tech, PhD (Pursuing)',          10, '2014-08-15', 65000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(3, 'EMP003', 'Mr. Suresh', 'Kumar',   'suresh.kumar@college.edu',   NULL, NULL, '9123456782', '1982-03-10', 'Male',   '18 Faculty Housing', 'Pune',      'Maharashtra', '411001', NULL, 'Professor',           'PhD in Mechanical Engineering',  12, '2012-06-01', 78000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(4, 'EMP004', 'Dr. Priya',  'Singh',   'priya.singh@college.edu',    NULL, NULL, '9123456783', '1983-11-28', 'Female', '21 Faculty Housing', 'Mumbai',    'Maharashtra', '400001', NULL, 'Assistant Professor', 'PhD in AI/ML',                    8, '2016-07-20', 60000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(5, 'EMP005', 'Mr. Anil',   'Patel',   'anil.patel@college.edu',     NULL, NULL, '9123456784', '1978-07-05', 'Male',   '24 Faculty Housing', 'Ahmedabad', 'Gujarat',     '380001', NULL, 'Professor',           'MBA, PhD',                       18, '2008-01-10', 90000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(6, 'EMP006', 'Ms. Anjali', 'Desai',   'anjali.desai@college.edu',   NULL, NULL, '9123456785', '1988-12-12', 'Female', '27 Faculty Housing', 'Mumbai',    'Maharashtra', '400001', NULL, 'Lecturer',            'M.Sc in Computer Science',        6, '2018-08-01', 45000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(7, 'EMP007', 'Dr. Manoj',  'Verma',   'manoj.verma@college.edu',    NULL, NULL, '9123456786', '1981-09-18', 'Male',   '30 Faculty Housing', 'Delhi',     'Delhi',       '110001', NULL, 'Associate Professor', 'PhD in Electronics',             11, '2013-07-15', 72000.00, 'Active',   NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57'),
(8, 'EMP008', 'Ms. Sunita', 'Reddy',   'sunita.reddy@college.edu',   NULL, NULL, '9123456787', '1986-04-25', 'Female', '33 Faculty Housing', 'Hyderabad', 'Telangana',   '500001', NULL, 'Assistant Professor', 'MBA, CFA',                        7, '2017-08-20', 58000.00, 'On Leave', NULL, '2026-01-29 14:26:57', '2026-01-29 14:26:57');

--
-- Data: leave_requests
--
INSERT INTO `leave_requests` (`leave_id`, `teacher_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `admin_remarks`, `approved_by`, `approved_at`, `attachment`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sick Leave',      '2026-02-15', '2026-02-17', 3, 'Suffering from viral fever. Medical certificate attached.', 'Approved',  'Approved. Get well soon!',       1, '2026-02-11 05:00:00', NULL, '2026-02-10 15:09:23', '2026-02-10 15:09:23'),
(2, 1, 'Casual Leave',    '2026-02-20', '2026-02-21', 2, 'Personal work',                                             'Cancelled', NULL,                             NULL, NULL,                NULL, '2026-02-10 15:09:23', '2026-02-10 15:19:20'),
(3, 2, 'Emergency Leave', '2026-02-12', '2026-02-12', 1, 'Family emergency',                                          'Approved',  'Approved as emergency leave.',   1, '2026-02-12 02:30:00', NULL, '2026-02-10 15:09:23', '2026-02-10 15:09:23'),
(4, 3, 'Casual Leave',    '2026-02-25', '2026-02-28', 4, 'Attending family function',                                 'Approved',  'Enjoy',                          1, '2026-02-10 15:45:32', NULL, '2026-02-10 15:09:23', '2026-02-10 15:45:32'),
(5, 1, 'Emergency Leave', '2026-03-13', '2026-03-14', 2, 'Hiiii',                                                     'Rejected',  'No leave',                       1, '2026-02-11 07:07:44', NULL, '2026-02-11 07:07:01', '2026-02-11 07:07:44');

--
-- Data: notices
--
INSERT INTO `notices` (`notice_id`, `title`, `description`, `notice_date`, `expiry_date`, `target_audience`, `priority`, `created_by`, `created_at`) VALUES
(1,  'Mid-Term Examinations', 'Mid-term examinations will be conducted from March 15-25, 2026. Students are advised to prepare accordingly.', '2026-02-01', '2026-03-25', 'Students', 'High',   NULL, '2026-02-07 09:47:55'),
(2,  'Faculty Meeting',       'All faculty members are requested to attend the monthly meeting on February 15, 2026 at 10:00 AM.',            '2026-02-07', '2026-02-15', 'Teachers', 'Medium', NULL, '2026-02-07 09:47:55'),
(3,  'Fee Payment Reminder',  'Semester fees must be paid by February 20, 2026. Late fee will be applicable after the deadline.',             '2026-02-05', '2026-02-20', 'Students', 'Urgent', NULL, '2026-02-07 09:47:55'),
(4,  'Holiday Notice',        'College will remain closed on February 14, 2026 on account of public holiday.',                               '2026-02-10', '2026-02-14', 'All',      'Low',    NULL, '2026-02-07 09:47:55');

--
-- Data: settings
--
INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'college_name',                   'CollegeSphere Institute',                       'text',   'Name of the institution',          '2026-02-07 11:18:24'),
(2, 'college_address',                '123 Education Street, Mumbai, Maharashtra 400001', 'text', 'College address',                  '2026-02-07 11:18:24'),
(3, 'college_email',                  'info@collegesphere.edu',                         'email',  'Official email',                   '2026-02-07 11:18:24'),
(4, 'college_phone',                  '+91 22 1234 5678',                               'text',   'Contact number',                   '2026-02-07 11:18:24'),
(5, 'academic_year',                  '2025-2026',                                      'text',   'Current academic year',            '2026-02-07 11:18:24'),
(6, 'semester',                       'Spring 2026',                                    'text',   'Current semester',                 '2026-02-07 11:18:24'),
(7, 'attendance_percentage_required', '75',                                             'number', 'Minimum attendance percentage',    '2026-02-07 11:18:24'),
(8, 'late_fee_amount',                '500',                                            'number', 'Late fee amount in rupees',        '2026-02-07 11:18:24'),
(9, 'passing_marks_percentage',       '40',                                             'number', 'Minimum passing percentage',       '2026-02-07 11:18:24');


-- ============================================================
-- PRIMARY KEYS & AUTO INCREMENT
-- ============================================================

ALTER TABLE `admins`                  ADD PRIMARY KEY (`admin_id`);
ALTER TABLE `departments`             ADD PRIMARY KEY (`dept_id`);
ALTER TABLE `classes`                 ADD PRIMARY KEY (`class_id`);
ALTER TABLE `streams`                 ADD PRIMARY KEY (`stream_id`);
ALTER TABLE `students`                ADD PRIMARY KEY (`student_id`);
ALTER TABLE `teachers`                ADD PRIMARY KEY (`teacher_id`);
ALTER TABLE `staff`                   ADD PRIMARY KEY (`staff_id`);
ALTER TABLE `subjects`                ADD PRIMARY KEY (`subject_id`);
ALTER TABLE `student_subjects`        ADD PRIMARY KEY (`id`);
ALTER TABLE `teacher_subjects`        ADD PRIMARY KEY (`id`);
ALTER TABLE `teacher_class_assignments` ADD PRIMARY KEY (`assignment_id`);
ALTER TABLE `attendance`              ADD PRIMARY KEY (`attendance_id`);
ALTER TABLE `marks`                   ADD PRIMARY KEY (`mark_id`);
ALTER TABLE `fees`                    ADD PRIMARY KEY (`fee_id`);
ALTER TABLE `notices`                 ADD PRIMARY KEY (`notice_id`);
ALTER TABLE `teacher_notices`         ADD PRIMARY KEY (`notice_id`);
ALTER TABLE `timetable`               ADD PRIMARY KEY (`timetable_id`);
ALTER TABLE `leave_requests`          ADD PRIMARY KEY (`leave_id`);
ALTER TABLE `student_documents`       ADD PRIMARY KEY (`document_id`);
ALTER TABLE `student_notifications`   ADD PRIMARY KEY (`notification_id`);
ALTER TABLE `student_sessions`        ADD PRIMARY KEY (`session_id`);
ALTER TABLE `settings`                ADD PRIMARY KEY (`setting_id`);


-- ============================================================
-- UNIQUE KEYS & INDEXES
-- ============================================================

ALTER TABLE `admins`        ADD UNIQUE KEY `username` (`username`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `departments`   ADD UNIQUE KEY `dept_code` (`dept_code`);
ALTER TABLE `classes`       ADD UNIQUE KEY `unique_class_section` (`class_name`, `section`), ADD KEY `dept_id` (`dept_id`);
ALTER TABLE `streams`       ADD UNIQUE KEY `stream_code` (`stream_code`), ADD KEY `idx_stream_code` (`stream_code`), ADD KEY `idx_is_active` (`is_active`), ADD KEY `fk_stream_dept` (`dept_id`);
ALTER TABLE `students`      ADD UNIQUE KEY `roll_number` (`roll_number`), ADD UNIQUE KEY `email` (`email`), ADD KEY `idx_status` (`status`), ADD KEY `idx_class` (`class_id`), ADD KEY `idx_dept` (`dept_id`), ADD KEY `idx_stream_id` (`stream_id`);
ALTER TABLE `teachers`      ADD UNIQUE KEY `employee_id` (`employee_id`), ADD UNIQUE KEY `email` (`email`), ADD KEY `idx_status` (`status`), ADD KEY `idx_dept` (`dept_id`);
ALTER TABLE `staff`         ADD UNIQUE KEY `employee_id` (`employee_id`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `subjects`      ADD UNIQUE KEY `subject_code` (`subject_code`), ADD KEY `dept_id` (`dept_id`), ADD KEY `idx_stream_id` (`stream_id`);
ALTER TABLE `student_subjects` ADD UNIQUE KEY `unique_student_subject` (`student_id`, `subject_id`), ADD KEY `idx_student_id` (`student_id`), ADD KEY `idx_subject_id` (`subject_id`);
ALTER TABLE `teacher_subjects` ADD UNIQUE KEY `unique_teacher_subject_class` (`teacher_id`, `subject_id`, `class_id`), ADD KEY `subject_id` (`subject_id`), ADD KEY `class_id` (`class_id`);
ALTER TABLE `teacher_class_assignments` ADD UNIQUE KEY `unique_assignment` (`teacher_id`, `class_id`, `subject_id`, `academic_year`), ADD KEY `class_id` (`class_id`), ADD KEY `subject_id` (`subject_id`);
ALTER TABLE `attendance`    ADD UNIQUE KEY `unique_student_date` (`student_id`, `attendance_date`), ADD KEY `marked_by` (`marked_by`), ADD KEY `idx_date` (`attendance_date`), ADD KEY `idx_class` (`class_id`);
ALTER TABLE `marks`         ADD KEY `idx_student` (`student_id`), ADD KEY `idx_subject` (`subject_id`), ADD KEY `idx_exam` (`exam_type`);
ALTER TABLE `fees`          ADD KEY `idx_status` (`payment_status`), ADD KEY `idx_student` (`student_id`);
ALTER TABLE `notices`       ADD KEY `created_by` (`created_by`), ADD KEY `idx_date` (`notice_date`), ADD KEY `idx_target` (`target_audience`);
ALTER TABLE `teacher_notices` ADD KEY `class_id` (`class_id`), ADD KEY `idx_expiry` (`expiry_date`), ADD KEY `idx_teacher` (`teacher_id`);
ALTER TABLE `timetable`     ADD UNIQUE KEY `unique_schedule` (`class_id`, `day_of_week`, `start_time`), ADD KEY `subject_id` (`subject_id`), ADD KEY `teacher_id` (`teacher_id`);
ALTER TABLE `leave_requests` ADD KEY `teacher_id` (`teacher_id`), ADD KEY `approved_by` (`approved_by`), ADD KEY `idx_status` (`status`), ADD KEY `idx_dates` (`start_date`, `end_date`);
ALTER TABLE `student_documents` ADD KEY `idx_student_id` (`student_id`), ADD KEY `idx_document_type` (`document_type`), ADD KEY `idx_status` (`status`);
ALTER TABLE `student_notifications` ADD KEY `idx_student_read` (`student_id`, `is_read`), ADD KEY `idx_created` (`created_at`);
ALTER TABLE `student_sessions` ADD KEY `idx_student_id` (`student_id`), ADD KEY `idx_last_activity` (`last_activity`);
ALTER TABLE `settings`      ADD UNIQUE KEY `setting_key` (`setting_key`);


-- ============================================================
-- AUTO INCREMENT VALUES
-- ============================================================

ALTER TABLE `admins`                    MODIFY `admin_id`        int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `departments`               MODIFY `dept_id`         int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
ALTER TABLE `classes`                   MODIFY `class_id`        int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
ALTER TABLE `streams`                   MODIFY `stream_id`       int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `students`                  MODIFY `student_id`      int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
ALTER TABLE `teachers`                  MODIFY `teacher_id`      int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `staff`                     MODIFY `staff_id`        int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `subjects`                  MODIFY `subject_id`      int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
ALTER TABLE `student_subjects`          MODIFY `id`              int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
ALTER TABLE `teacher_subjects`          MODIFY `id`              int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `teacher_class_assignments` MODIFY `assignment_id`   int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `attendance`                MODIFY `attendance_id`   int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
ALTER TABLE `marks`                     MODIFY `mark_id`         int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
ALTER TABLE `fees`                      MODIFY `fee_id`          int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `notices`                   MODIFY `notice_id`       int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
ALTER TABLE `teacher_notices`           MODIFY `notice_id`       int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `timetable`                 MODIFY `timetable_id`    int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
ALTER TABLE `leave_requests`            MODIFY `leave_id`        int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `student_documents`         MODIFY `document_id`     int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `student_notifications`     MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
ALTER TABLE `settings`                  MODIFY `setting_id`      int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;


-- ============================================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================================

ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

ALTER TABLE `streams`
  ADD CONSTRAINT `fk_stream_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`stream_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_1`   FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_2`   FOREIGN KEY (`dept_id`)  REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subject_stream` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`stream_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_1`   FOREIGN KEY (`dept_id`)   REFERENCES `departments` (`dept_id`) ON DELETE SET NULL;

ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_3` FOREIGN KEY (`class_id`)   REFERENCES `classes` (`class_id`) ON DELETE SET NULL;

ALTER TABLE `teacher_class_assignments`
  ADD CONSTRAINT `teacher_class_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_class_assignments_ibfk_2` FOREIGN KEY (`class_id`)   REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_class_assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`)   REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`)  REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

ALTER TABLE `teacher_notices`
  ADD CONSTRAINT `teacher_notices_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_notices_ibfk_2` FOREIGN KEY (`class_id`)   REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`)   REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE SET NULL;

ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`teacher_id`)  REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

ALTER TABLE `student_documents`
  ADD CONSTRAINT `fk_student_documents_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

ALTER TABLE `student_sessions`
  ADD CONSTRAINT `student_sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;


-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

CREATE TRIGGER `before_student_insert` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count FROM students WHERE roll_number = NEW.roll_number;
    IF v_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Roll number already exists';
    END IF;
END$$

DELIMITER ;


-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER $$

CREATE PROCEDURE `generate_roll_number` (IN `p_stream_id` INT, OUT `p_roll_number` VARCHAR(50))
BEGIN
    DECLARE v_stream_code VARCHAR(10);
    DECLARE v_next_number INT;
    DECLARE v_roll_number VARCHAR(50);

    START TRANSACTION;

    SELECT stream_code INTO v_stream_code
    FROM streams WHERE stream_id = p_stream_id FOR UPDATE;

    SELECT COALESCE(MAX(CAST(SUBSTRING(roll_number, LENGTH(v_stream_code) + 1) AS UNSIGNED)), 0) + 1
    INTO v_next_number
    FROM students
    WHERE stream_id = p_stream_id AND roll_number LIKE CONCAT(v_stream_code, '%');

    SET v_roll_number = CONCAT(v_stream_code, LPAD(v_next_number, 3, '0'));
    SET p_roll_number = v_roll_number;

    COMMIT;
END$$

CREATE PROCEDURE `get_student_documents` (IN `p_student_id` INT)
BEGIN
    SELECT
        sd.document_id,
        sd.student_id,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        sd.document_type,
        sd.document_name,
        sd.file_path,
        sd.file_size,
        sd.uploaded_date,
        sd.status,
        sd.remarks
    FROM student_documents sd
    INNER JOIN students s ON sd.student_id = s.student_id
    WHERE sd.student_id = p_student_id AND sd.status = 'Active'
    ORDER BY sd.uploaded_date DESC;
END$$

DELIMITER ;


-- ============================================================
-- FUNCTIONS
-- ============================================================

DELIMITER $$

CREATE FUNCTION `get_stream_code` (`p_stream_id` INT)
RETURNS VARCHAR(10) CHARSET utf8mb4 COLLATE utf8mb4_general_ci
DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v_stream_code VARCHAR(10);
    SELECT stream_code INTO v_stream_code FROM streams WHERE stream_id = p_stream_id;
    RETURN v_stream_code;
END$$

CREATE FUNCTION `has_all_documents` (`p_student_id` INT)
RETURNS TINYINT(1)
DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE doc_count INT;
    DECLARE required_docs INT DEFAULT 3;
    SELECT COUNT(DISTINCT document_type) INTO doc_count
    FROM student_documents
    WHERE student_id = p_student_id
      AND document_type IN ('aadhar', 'tenth_marksheet', 'twelfth_marksheet')
      AND status = 'Active';
    RETURN doc_count >= required_docs;
END$$

DELIMITER ;


-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW `department_stats` AS
SELECT d.dept_name, d.dept_code,
       COUNT(DISTINCT s.student_id) AS student_count,
       COUNT(DISTINCT t.teacher_id) AS teacher_count
FROM departments d
LEFT JOIN students s ON d.dept_id = s.dept_id
LEFT JOIN teachers t ON d.dept_id = t.dept_id
GROUP BY d.dept_id, d.dept_name, d.dept_code;

CREATE OR REPLACE VIEW `fee_stats` AS
SELECT
    SUM(CASE WHEN payment_status = 'Paid'                    THEN amount ELSE 0 END) AS total_collected,
    SUM(CASE WHEN payment_status IN ('Pending','Overdue')    THEN amount ELSE 0 END) AS total_pending,
    COUNT(CASE WHEN payment_status = 'Paid'    THEN 1 END) AS paid_count,
    COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) AS pending_count,
    COUNT(CASE WHEN payment_status = 'Overdue' THEN 1 END) AS overdue_count,
    COUNT(*) AS total_fees
FROM fees;

CREATE OR REPLACE VIEW `student_stats` AS
SELECT
    COUNT(*) AS total_students,
    SUM(CASE WHEN status = 'Active'   THEN 1 ELSE 0 END) AS active_students,
    SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS inactive_students
FROM students;

CREATE OR REPLACE VIEW `teacher_stats` AS
SELECT
    COUNT(*) AS total_teachers,
    SUM(CASE WHEN status = 'Active'                      THEN 1 ELSE 0 END) AS active_teachers,
    SUM(CASE WHEN status IN ('Inactive','On Leave')      THEN 1 ELSE 0 END) AS inactive_teachers
FROM teachers;

CREATE OR REPLACE VIEW `student_attendance_summary` AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name,
       COUNT(a.attendance_id) AS total_days,
       SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_days,
       SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_days,
       SUM(CASE WHEN a.status = 'Late'    THEN 1 ELSE 0 END) AS late_days,
       ROUND(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(a.attendance_id) * 100, 2) AS attendance_percentage
FROM students s
LEFT JOIN attendance a ON s.student_id = a.student_id
WHERE s.status = 'Active'
GROUP BY s.student_id;

CREATE OR REPLACE VIEW `student_fee_summary` AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name,
       COUNT(f.fee_id) AS total_fees,
       SUM(CASE WHEN f.payment_status = 'Paid'    THEN f.amount ELSE 0 END) AS total_paid,
       SUM(CASE WHEN f.payment_status = 'Pending' THEN f.amount ELSE 0 END) AS total_pending,
       SUM(CASE WHEN f.payment_status = 'Overdue' THEN f.amount ELSE 0 END) AS total_overdue,
       SUM(f.amount) AS total_amount
FROM students s
LEFT JOIN fees f ON s.student_id = f.student_id
WHERE s.status = 'Active'
GROUP BY s.student_id;

CREATE OR REPLACE VIEW `student_performance_summary` AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name,
       COUNT(DISTINCT m.subject_id) AS subjects_enrolled,
       ROUND(AVG(m.marks_obtained / m.total_marks * 100), 2) AS overall_percentage,
       COUNT(m.mark_id) AS total_exams,
       SUM(CASE WHEN m.marks_obtained / m.total_marks * 100 >= 40 THEN 1 ELSE 0 END) AS passed_exams
FROM students s
LEFT JOIN marks m ON s.student_id = m.student_id
WHERE s.status = 'Active'
GROUP BY s.student_id;

CREATE OR REPLACE VIEW `student_enrollment_summary` AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name, s.email,
       st.stream_name, st.stream_code,
       COUNT(DISTINCT ss.subject_id) AS enrolled_subjects,
       GROUP_CONCAT(DISTINCT sub.subject_name SEPARATOR ', ') AS subject_list,
       s.enrollment_date, s.status
FROM students s
LEFT JOIN streams st       ON s.stream_id    = st.stream_id
LEFT JOIN student_subjects ss ON s.student_id = ss.student_id AND ss.status = 'Active'
LEFT JOIN subjects sub     ON ss.subject_id  = sub.subject_id
GROUP BY s.student_id;

CREATE OR REPLACE VIEW `student_complete_profile` AS
SELECT s.student_id, s.roll_number, s.first_name, s.last_name,
       CONCAT(s.first_name,' ',s.last_name) AS full_name,
       s.email, s.phone, s.date_of_birth, s.gender,
       s.address, s.city, s.state, s.pincode,
       s.guardian_name, s.guardian_phone,
       s.enrollment_date, s.status,
       st.stream_name, st.stream_code,
       d.dept_name, d.dept_code,
       COUNT(DISTINCT sd.document_id) AS documents_uploaded,
       COUNT(DISTINCT ss.subject_id)  AS subjects_enrolled,
       s.created_at, s.updated_at
FROM students s
LEFT JOIN streams          st ON s.stream_id  = st.stream_id
LEFT JOIN departments      d  ON s.dept_id    = d.dept_id
LEFT JOIN student_documents sd ON s.student_id = sd.student_id AND sd.status = 'Active'
LEFT JOIN student_subjects  ss ON s.student_id = ss.student_id AND ss.status = 'Active'
GROUP BY s.student_id;

CREATE OR REPLACE VIEW `student_document_status` AS
SELECT s.student_id, s.roll_number,
       CONCAT(s.first_name,' ',s.last_name) AS student_name,
       s.email, s.stream_id, st.stream_name,
       COUNT(DISTINCT sd.document_id) AS total_documents,
       SUM(CASE WHEN sd.document_type = 'aadhar'            THEN 1 ELSE 0 END) AS has_aadhar,
       SUM(CASE WHEN sd.document_type = 'tenth_marksheet'   THEN 1 ELSE 0 END) AS has_tenth,
       SUM(CASE WHEN sd.document_type = 'twelfth_marksheet' THEN 1 ELSE 0 END) AS has_twelfth,
       SUM(CASE WHEN sd.document_type = 'other_documents'   THEN 1 ELSE 0 END) AS has_other,
       has_all_documents(s.student_id) AS documents_complete
FROM students s
LEFT JOIN streams          st ON s.stream_id  = st.stream_id
LEFT JOIN student_documents sd ON s.student_id = sd.student_id AND sd.status = 'Active'
WHERE s.status = 'Active'
GROUP BY s.student_id
ORDER BY s.enrollment_date DESC;

CREATE OR REPLACE VIEW `teacher_dashboard_stats` AS
SELECT t.teacher_id, t.employee_id, t.first_name, t.last_name,
       COUNT(DISTINCT tca.class_id)   AS assigned_classes,
       COUNT(DISTINCT tca.subject_id) AS assigned_subjects,
       COUNT(DISTINCT s.student_id)   AS total_students
FROM teachers t
LEFT JOIN teacher_class_assignments tca ON t.teacher_id = tca.teacher_id AND tca.academic_year = '2024-2025'
LEFT JOIN students s ON tca.class_id = s.class_id AND s.status = 'Active'
GROUP BY t.teacher_id;

CREATE OR REPLACE VIEW `teacher_leave_stats` AS
SELECT t.teacher_id, t.employee_id, t.first_name, t.last_name,
       COUNT(CASE WHEN lr.status = 'Pending'  THEN 1 END) AS pending_leaves,
       COUNT(CASE WHEN lr.status = 'Approved' THEN 1 END) AS approved_leaves,
       COUNT(CASE WHEN lr.status = 'Rejected' THEN 1 END) AS rejected_leaves,
       COALESCE(SUM(CASE WHEN lr.status = 'Approved' AND YEAR(lr.start_date) = YEAR(CURDATE()) THEN lr.total_days END), 0) AS total_leaves_taken_this_year
FROM teachers t
LEFT JOIN leave_requests lr ON t.teacher_id = lr.teacher_id
GROUP BY t.teacher_id;

CREATE OR REPLACE VIEW `teacher_today_attendance` AS
SELECT tca.teacher_id, c.class_id, c.class_name, c.section,
       COUNT(DISTINCT st.student_id) AS total_students,
       COUNT(DISTINCT CASE WHEN a.status = 'Present' THEN a.student_id END) AS present,
       COUNT(DISTINCT CASE WHEN a.status = 'Absent'  THEN a.student_id END) AS absent,
       COUNT(DISTINCT CASE WHEN a.status = 'Late'    THEN a.student_id END) AS late
FROM teacher_class_assignments tca
JOIN classes  c  ON tca.class_id   = c.class_id
JOIN students st ON c.class_id     = st.class_id AND st.status = 'Active'
LEFT JOIN attendance a ON st.student_id = a.student_id AND a.attendance_date = CURDATE()
WHERE tca.academic_year = '2024-2025'
GROUP BY tca.teacher_id, c.class_id;


-- ============================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

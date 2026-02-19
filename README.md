# 🎓 CollegeSphere — College Management System

A comprehensive, role-based college management system built with **PHP**, **MySQL**, and **Bootstrap 5**. It provides three dedicated portals for Admins, Teachers, and Students to manage day-to-day college operations from a single unified platform.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Requirements](#-requirements)
- [Installation & Setup](#-installation--setup)
- [Login Credentials](#-login-credentials)
- [Portal Overview](#-portal-overview)
- [Database Overview](#-database-overview)
- [Configuration](#-configuration)
- [Troubleshooting](#-troubleshooting)

---

## ✨ Features

### Admin Portal
- Dashboard with live statistics
- Department Management (Create, Edit, Delete)
- Student Management (Enroll, Edit, View Documents)
- Teacher Management (Add, Edit, Assign)
- Attendance Tracking & Reports
- Fee / Finance Management
- Marks & Examination Management
- Timetable Management
- Notice Board
- Leave Management for Teachers
- System Settings (College name, address, email, etc.)

### Teacher Portal
- Personal Dashboard
- View Assigned Classes & Subjects
- Mark Student Attendance
- Enter & View Student Marks
- Post Notices to Students
- Apply for Leave
- View Student Performance Reports

### Student Portal
- Personal Dashboard
- View Attendance Summary
- View Marks & Exam Results
- Check Fee Payment Status
- View Timetable
- Read Notices & Announcements
- Manage Profile & Documents
- Self Registration (Signup)

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5.3, Font Awesome 6.5 |
| Fonts | Google Fonts (Inter, Poppins) |
| Server | Apache (via XAMPP / WAMP / Laragon) |
| Auth | PHP Sessions + bcrypt password hashing |

---

## 📁 Project Structure

```
college_sphere/
│
├── index.php                    # Public landing page (dynamic)
│
├── config/
│   └── db.php                   # Database connection & helper functions
│
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── students.php
│   ├── teachers.php
│   ├── departments.php          # Department management module
│   ├── courses.php              # Subjects management
│   ├── attendance.php
│   ├── finance.php
│   ├── marks.php
│   ├── timetable.php
│   ├── notices.php
│   ├── leave_management.php
│   ├── settings.php
│   ├── logout.php
│   └── includes/
│       ├── sidebar.php
│       ├── navbar.php
│       └── footer.php
│
├── teacher/
│   ├── login.php
│   ├── dashboard.php
│   ├── attendance.php
│   ├── marks.php
│   ├── students.php
│   ├── notices.php
│   ├── performance.php
│   ├── leave.php
│   ├── profile.php
│   ├── logout.php
│   └── includes/
│       ├── sidebar.php
│       ├── navbar.php
│       └── footer.php
│
├── student/
│   ├── login.php
│   ├── signup.php
│   ├── dashboard.php
│   ├── attendance.php
│   ├── marks.php
│   ├── fees.php
│   ├── timetable.php
│   ├── notices.php
│   ├── profile.php
│   ├── logout.php
│   └── includes/
│       ├── sidebar.php
│       ├── navbar.php
│       └── footer.php
│
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── teacher.css
│   │   └── style.css
│   └── js/
│       ├── admin-dashboard.js
│       ├── teacher.js
│       └── registration.js
│
└── uploads/
    └── student_documents/       # Uploaded student files stored here
```

---

## ⚙️ Requirements

Before running this project, make sure you have the following installed:

- **XAMPP** (recommended) or **WAMP** or **Laragon**
  - Apache 2.4+
  - PHP 8.0 or higher
  - MySQL 5.7+ / MariaDB 10.4+
- A modern web browser (Chrome, Firefox, Edge)

---

## 🚀 Installation & Setup

Follow these steps carefully to get CollegeSphere running on your local machine.

### Step 1 — Install XAMPP

Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org).

Start both **Apache** and **MySQL** from the XAMPP Control Panel.

### Step 2 — Copy Project Files

Copy the entire `college_sphere` folder into your XAMPP web root directory:

```
C:\xampp\htdocs\college_sphere
```

Your folder structure should look like this:

```
C:\xampp\htdocs\college_sphere\
    ├── index.php
    ├── config\
    ├── admin\
    ├── teacher\
    ├── student\
    ├── assets\
    └── uploads\
```

### Step 3 — Create the Database

1. Open your browser and go to:
   ```
   http://localhost/phpmyadmin
   ```

2. Click **"New"** in the left sidebar

3. Enter the database name:
   ```
   college_sphere
   ```
   and click **Create**

4. Select the newly created `college_sphere` database

5. Click the **"Import"** tab at the top

6. Click **"Choose File"** and select the SQL file:
   ```
   college_sphere.sql
   ```

7. Click **"Go"** to import — you should see a success message

### Step 4 — Configure Database Connection

Open the file:
```
college_sphere/config/db.php
```

Update the credentials if needed (default XAMPP settings work out of the box):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password (blank for XAMPP default)
define('DB_NAME', 'college_sphere');
```

### Step 5 — Set Up Upload Folder Permissions

Make sure the uploads folder exists and is writable. If it doesn't exist, create it:

```
college_sphere/uploads/student_documents/
```

On Windows this works automatically. On Linux/Mac, run:

```bash
chmod -R 775 uploads/
```

### Step 6 — Run the Project

Open your browser and go to:

```
http://localhost/college_sphere/
```

You should see the CollegeSphere landing page. 🎉

---

## 🔐 Login Credentials

### Admin Login
| Field | Value |
|---|---|
| URL | `http://localhost/college_sphere/admin/login.php` |
| Username | `admin` |
| Password | `admin123` |

### Teacher Login
| Field | Value |
|---|---|
| URL | `http://localhost/college_sphere/teacher/login.php` |
| Email | `rajesh.sharma@college.edu` |
| Password | Contact admin to set password |

> **Note:** Teacher and Student passwords are set by the Admin or through the reset flow. The sample data includes teacher records but passwords must be assigned via the Admin panel.

### Student Login / Signup
| Field | Value |
|---|---|
| Signup URL | `http://localhost/college_sphere/student/signup.php` |
| Login URL | `http://localhost/college_sphere/student/login.php` |

> Students register themselves via the Signup page. The admin can then manage their records.

---

## 🖥 Portal Overview

| Portal | URL | Access |
|---|---|---|
| Landing Page | `http://localhost/college_sphere/` | Public |
| Admin Panel | `http://localhost/college_sphere/admin/login.php` | Admin only |
| Teacher Panel | `http://localhost/college_sphere/teacher/login.php` | Teachers only |
| Student Panel | `http://localhost/college_sphere/student/login.php` | Students only |
| Student Signup | `http://localhost/college_sphere/student/signup.php` | Public |

---

## 🗄 Database Overview

The database `college_sphere` contains the following key tables:

| Table | Purpose |
|---|---|
| `admins` | Admin user accounts |
| `departments` | College departments (CS, IT, MECH, etc.) |
| `teachers` | Teacher profiles & credentials |
| `students` | Student profiles & enrollment data |
| `streams` | Academic streams/programs |
| `subjects` | Subjects offered per department |
| `classes` | Class sections |
| `attendance` | Daily attendance records |
| `marks` | Exam marks per student per subject |
| `fees` | Fee records & payment status |
| `notices` | Announcements for students/teachers |
| `leave_requests` | Teacher leave applications |
| `timetable` | Class schedules |
| `student_documents` | Uploaded documents (Aadhar, marksheets) |
| `settings` | College info (name, address, email, phone) |

### Key Views (auto-generated reports)
- `student_stats` — Total, active, inactive students
- `teacher_stats` — Total, active teachers
- `fee_stats` — Collected vs pending fees
- `student_attendance_summary` — Per-student attendance percentage
- `student_performance_summary` — Overall exam performance
- `department_stats` — Students & teachers per department

---

## ⚙️ Configuration

You can configure college-wide settings directly from the Admin Panel:

**Admin → Settings**

| Setting | Description |
|---|---|
| College Name | Displayed across all pages and the landing page |
| College Address | Shown in the footer contact section |
| College Email | Public contact email |
| College Phone | Public contact number |
| Academic Year | e.g. 2025-2026 |
| Semester | e.g. Spring 2026 |
| Attendance Required % | Minimum attendance threshold (default 75%) |
| Late Fee Amount | Late fee in rupees (default ₹500) |
| Passing Marks % | Minimum passing percentage (default 40%) |

> All of these settings are dynamically reflected on the public `index.php` landing page.

---

## 🔧 Troubleshooting

**Blank page or "Connection failed" error**
- Make sure Apache and MySQL are both running in XAMPP
- Double-check credentials in `config/db.php`
- Ensure the database name is exactly `college_sphere`

**"Table not found" SQL error**
- The SQL file was not imported correctly
- Re-import `college_sphere.sql` via phpMyAdmin

**Images or CSS not loading**
- Make sure you placed the project in `htdocs/college_sphere/` (not a subfolder inside a subfolder)
- Access via `http://localhost/college_sphere/` not by opening the file directly

**File upload not working**
- Ensure the folder `uploads/student_documents/` exists inside the project
- On Linux/Mac, run `chmod -R 775 uploads/`
- Check that `file_uploads = On` in your `php.ini`

**Landing page showing static data**
- Make sure `index.html` has been renamed to `index.php`
- The dynamic version requires the PHP file extension to connect to the database

---

## 👨‍💻 Development Notes

- All AJAX requests use `fetch()` with `FormData` — no jQuery dependency
- Passwords are hashed using PHP's `password_hash()` with `bcrypt`
- The `sanitize_input()` function in `db.php` handles SQL injection prevention
- Session-based authentication — sessions expire on browser close
- The `generate_roll_number` stored procedure auto-generates roll numbers per stream

---

*Built with ❤️ by the CollegeSphere Team — Academic Year 2025-2026*

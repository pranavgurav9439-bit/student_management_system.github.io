<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <!-- Logo Section -->
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="logo-text">CollegeSphere</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                <a href="students.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : ''; ?>">
                <a href="teachers.php" class="nav-link">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teachers</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
                <a href="courses.php" class="nav-link">
                    <i class="fas fa-book-open"></i>
                    <span>Courses</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
                <a href="departments.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                <a href="attendance.php" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : ''; ?>">
                <a href="finance.php" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Finance</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
                <a href="timetable.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Time Table</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>">
                <a href="marks.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Marks</span>
                </a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'notices.php' ? 'active' : ''; ?>">
                <a href="notices.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span>Notices</span>
                </a>
            </li>

            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'leave_management.php' ? 'active' : ''; ?>">
                <a href="leave_management.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>leave_manage</span>
                </a>
            </li>

            <!-- Divider -->
            <li class="nav-divider"></li>

            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-info">
                <div class="user-name">Admin User</div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>
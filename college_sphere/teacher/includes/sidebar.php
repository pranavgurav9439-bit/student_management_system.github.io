<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="logo-text">
                <h4>CollegeSphere</h4>
                <small>Teacher Portal</small>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span class="nav-text">My Students</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="attendance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span class="nav-text">Attendance</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="marks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span class="nav-text">Marks & Grades</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="performance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'performance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-text">Performance</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="leave.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'leave.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-text">Leave</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="notices.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notices.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span class="nav-text">Notices</span>
                </a>
            </li>

            <li class="nav-divider"></li>

            <li class="nav-item">
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">My Profile</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['teacher_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h6><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></h6>
                <small><?php echo htmlspecialchars($_SESSION['employee_id']); ?></small>
            </div>
        </div>
    </div>
</div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <i class="fas fa-graduation-cap"></i>
            <span>Student Portal</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="attendance.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>My Attendance</span>
        </a>
        
        <a href="marks.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Marks & Performance</span>
        </a>
        
        <a href="fees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'fees.php' ? 'active' : ''; ?>">
            <i class="fas fa-rupee-sign"></i>
            <span>Fees Status</span>
        </a>
        
        <a href="notices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'notices.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Notices</span>
        </a>
        
        <a href="timetable.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'timetable.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Timetable</span>
        </a>
        
        <div class="menu-divider"></div>
        
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="details">
                <strong><?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></strong>
                <small><?php echo htmlspecialchars($_SESSION['student_roll'] ?? 'N/A'); ?></small>
            </div>
        </div>
    </div>
</aside>

<style>
.sidebar {
    width: 280px;
    height: 100vh;
    background: white;
    position: fixed;
    left: 0;
    top: 0;
    box-shadow: 4px 0 15px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 25px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
    color: #3b82f6;
}

.brand i {
    font-size: 28px;
}

.sidebar-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    color: #64748b;
    cursor: pointer;
}

.sidebar-menu {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 14px 20px;
    color: #64748b;
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 500;
    margin: 0 10px 5px;
    border-radius: 10px;
}

.menu-item i {
    width: 24px;
    text-align: center;
    font-size: 18px;
}

.menu-item:hover {
    background: #f1f5f9;
    color: #3b82f6;
}

.menu-item.active {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.menu-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 20px 20px;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid #e2e8f0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.details {
    flex: 1;
}

.details strong {
    display: block;
    font-size: 14px;
    color: #1e293b;
}

.details small {
    font-size: 12px;
    color: #64748b;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: block;
    }
}
</style>
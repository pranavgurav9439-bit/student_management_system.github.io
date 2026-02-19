<header class="navbar" id="navbar">
    <div class="navbar-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-header">
            <h1 class="page-title"><?php echo $page_title ?? 'Teacher Panel'; ?></h1>
            <p class="page-subtitle"><?php echo $page_subtitle ?? 'Manage your classes and students'; ?></p>
        </div>
    </div>

    <div class="navbar-right">
        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search students, classes...">
        </div>

        <!-- Notifications -->
        <div class="notification-icon">
            <a href="notices.php"></a>
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
        </div>

        <!-- User Profile -->
        <div class="user-dropdown">
            <div class="user-avatar-small">
                <?php echo strtoupper(substr($_SESSION['teacher_name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['teacher_name'])[0]); ?></span>
                <small class="user-role">Teacher</small>
            </div>
            <i class="fas fa-chevron-down ms-2"></i>
        </div>
    </div>
</header>
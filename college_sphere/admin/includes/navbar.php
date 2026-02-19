<!-- TOP HEADER -->
<header class="top-header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-title">
            <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
            <p class="subtitle"><?php echo $page_subtitle ?? 'Welcome back, Admin!'; ?></p>
        </div>
    </div>

    <div class="header-right">
        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search..." class="search-input">
        </div>

        <!-- Notifications -->
        <div class="header-icon notification-icon">
            <i class="fas fa-bell"></i>
            <span class="notification-dot"></span>
        </div>

        <!-- User Menu -->
        <div class="user-menu">
            <div class="user-avatar-small">
                <i class="fas fa-user-shield"></i>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</header>
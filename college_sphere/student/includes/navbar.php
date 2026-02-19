<nav class="top-navbar">
    <div class="navbar-left">
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-info">
            <h1><?php echo $page_title ?? 'Student Portal'; ?></h1>
            <?php if (isset($page_subtitle)): ?>
                <p><?php echo $page_subtitle; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="navbar-right">
        <div class="navbar-item">
            <button class="icon-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </button>
        </div>
        
        <div class="navbar-item user-menu">
            <div class="user-avatar">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="user-details">
                <strong><?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?></strong>
                <small><?php echo htmlspecialchars($_SESSION['class_name'] ?? 'N/A'); ?></small>
            </div>
            <div class="dropdown">
                <button class="dropdown-toggle">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.top-navbar {
    height: 70px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.mobile-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    color: #64748b;
    cursor: pointer;
}

.page-info h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.page-info p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.navbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.icon-btn {
    position: relative;
    width: 42px;
    height: 42px;
    background: #f8fafc;
    border: none;
    border-radius: 10px;
    color: #64748b;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s;
}

.icon-btn:hover {
    background: #3b82f6;
    color: white;
}

.icon-btn .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 10px;
    transition: all 0.3s;
}

.user-menu:hover {
    background: #f8fafc;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.user-details strong {
    display: block;
    font-size: 14px;
    color: #1e293b;
}

.user-details small {
    font-size: 12px;
    color: #64748b;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 12px;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    min-width: 200px;
    padding: 10px;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: #64748b;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.dropdown-menu a:hover {
    background: #f8fafc;
    color: #3b82f6;
}

@media (max-width: 768px) {
    .mobile-toggle {
        display: block;
    }
    
    .page-info h1 {
        font-size: 18px;
    }
    
    .page-info p {
        display: none;
    }
    
    .user-details {
        display: none;
    }
    
    .top-navbar {
        padding: 0 15px;
    }
}
</style>

<script>
// Mobile menu toggle
document.getElementById('mobileToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar')?.classList.toggle('active');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.getElementById('mobileToggle');
    
    if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && e.target !== mobileToggle) {
        sidebar.classList.remove('active');
    }
});
</script>
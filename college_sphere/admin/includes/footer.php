<!-- FOOTER -->
<footer class="dashboard-footer">
    <div class="footer-content">
        <div class="footer-left">
            <p>&copy; <?php echo date('Y'); ?> <strong>CollegeSphere</strong>. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <span>Version 1.0.0</span>
            <span class="footer-divider">|</span>
            <a href="settings.php">System Settings</a>
            <span class="footer-divider">|</span>
            <a href="#" onclick="window.print(); return false;">Print</a>
        </div>
    </div>
</footer>

<style>
.dashboard-footer {
    background: white;
    border-top: 1px solid #e2e8f0;
    padding: 20px 30px;
    margin-top: 40px;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 100%;
}

.footer-left p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #64748b;
}

.footer-right a {
    color: #6366f1;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-right a:hover {
    color: #4f46e5;
    text-decoration: underline;
}

.footer-divider {
    color: #cbd5e1;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .footer-right {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
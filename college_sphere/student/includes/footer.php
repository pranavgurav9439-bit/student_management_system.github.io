<footer class="content-footer">
    <div class="footer-content">
        <p>&copy; 2026 CollegeSphere Student Portal. All rights reserved.</p>
        <div class="footer-links">
            <a href="#"><i class="fas fa-question-circle"></i> Help</a>
            <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
            <a href="#"><i class="fas fa-file-alt"></i> Terms</a>
        </div>
    </div>
</footer>

<style>
.content-footer {
    margin-top: auto;
    padding: 20px 0;
    border-top: 1px solid #e2e8f0;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-content p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.footer-links {
    display: flex;
    gap: 20px;
}

.footer-links a {
    color: #64748b;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.footer-links a:hover {
    color: #3b82f6;
}

.footer-links a i {
    margin-right: 5px;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>
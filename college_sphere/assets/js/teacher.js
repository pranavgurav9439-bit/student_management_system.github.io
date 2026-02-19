/* ============================================
   TEACHER PANEL - JAVASCRIPT
   Clean, Maintainable, Well-Commented Code
   ============================================ */

'use strict';

// ===== SIDEBAR TOGGLE =====
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        
        // For mobile
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('active');
        }
    });
}

// Close sidebar on mobile when clicking outside
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && sidebar) {
        if (!sidebar.contains(e.target) && menuToggle && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// ===== SMOOTH COUNT-UP ANIMATION =====
/**
 * Animates numbers from 0 to target value
 * @param {string} selector - CSS selector for elements to animate
 * @param {number} duration - Animation duration in ms
 */
function animateCounters(selector, duration = 2000) {
    const counters = document.querySelectorAll(selector);
    
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.target || counter.textContent);
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(counter);
    });
}

// Initialize counters
if (document.querySelector('.stat-value')) {
    animateCounters('.stat-value');
}

// ===== SEARCH FUNCTIONALITY =====
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    searchInput.addEventListener('input', debounce(function(e) {
        const searchTerm = e.target.value.toLowerCase();
        console.log('Searching for:', searchTerm);
        // Implement search logic here
    }, 300));
}

/**
 * Debounce function to limit how often a function is called
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== RESPONSIVE HANDLING =====
function handleResize() {
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('collapsed');
    }
}

window.addEventListener('resize', debounce(handleResize, 250));

// ===== NOTIFICATION HANDLING =====
const notificationIcon = document.querySelector('.notification-icon');
if (notificationIcon) {
    notificationIcon.addEventListener('click', function() {
        console.log('Show notifications');
        // Implement notification panel logic here
    });
}

// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ Teacher Dashboard initialized successfully');
    
    // Add smooth transitions after page load
    setTimeout(() => {
        if (document.body) {
            document.body.style.opacity = '1';
        }
    }, 100);
});

// ===== ERROR HANDLING =====
window.addEventListener('error', (e) => {
    console.error('Dashboard Error:', e.error);
});

// ===== UTILITY FUNCTIONS =====

/**
 * Animate a single value change
 * @param {string} elementId - ID of element to update
 * @param {number} target - Target value
 */
function animateValue(elementId, target) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const current = parseInt(element.textContent) || 0;
    const increment = (target - current) / 30;
    let value = current;
    
    const timer = setInterval(() => {
        value += increment;
        if ((increment > 0 && value >= target) || (increment < 0 && value <= target)) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(value);
        }
    }, 16);
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        animateCounters,
        animateValue,
        debounce
    };
}
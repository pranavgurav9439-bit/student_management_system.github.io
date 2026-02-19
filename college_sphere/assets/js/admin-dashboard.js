/* ============================================
   MODERN ADMIN DASHBOARD - JAVASCRIPT
   Clean, Maintainable, Well-Commented Code
   ============================================ */

'use strict';

// ===== SIDEBAR TOGGLE =====
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    
    // For mobile
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('active');
    }
});

// Close sidebar on mobile when clicking outside
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
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
        const target = parseInt(counter.dataset.target);
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
animateCounters('.stat-value');

// ===== CHART CONFIGURATION =====
// Default chart options for consistency
const defaultChartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            padding: 12,
            cornerRadius: 8,
            titleFont: {
                size: 13,
                weight: '600'
            },
            bodyFont: {
                size: 12
            }
        }
    }
};

// ===== ATTENDANCE CHART =====
const attendanceData = {
    class1: {
        present: [30, 28, 29, 27, 26, 25, 24],
        absent: [5, 7, 6, 8, 9, 10, 11]
    },
    class2: {
        present: [32, 31, 30, 29, 28, 27, 26],
        absent: [3, 4, 5, 6, 7, 8, 9]
    },
    class3: {
        present: [25, 26, 27, 28, 29, 30, 28],
        absent: [10, 9, 8, 7, 6, 5, 7]
    }
};

const attendanceCtx = document.getElementById('attendanceChart');
const attendanceChart = new Chart(attendanceCtx, {
    type: 'bar',
    data: {
        labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [
            {
                label: 'Present',
                data: attendanceData.class1.present,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Absent',
                data: attendanceData.class1.absent,
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        ...defaultChartOptions,
        plugins: {
            ...defaultChartOptions.plugins,
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11,
                        weight: '500'
                    }
                }
            },
            y: {
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});

// Attendance class selector
document.getElementById('attendanceClassSelector').addEventListener('change', function() {
    const selectedClass = this.value;
    attendanceChart.data.datasets[0].data = attendanceData[selectedClass].present;
    attendanceChart.data.datasets[1].data = attendanceData[selectedClass].absent;
    attendanceChart.update('active');
});

// ===== PERFORMANCE CHART =====
const performanceData = {
    class1: {
        top: 40,
        average: 15,
        below: 5
    },
    class2: {
        top: 45,
        average: 11,
        below: 2
    },
    class3: {
        top: 35,
        average: 18,
        below: 7
    }
};

const performanceCtx = document.getElementById('performanceChart');
const performanceChart = new Chart(performanceCtx, {
    type: 'doughnut',
    data: {
        labels: ['Excellence', 'Average', 'Needs Help'],
        datasets: [{
            data: [40, 15, 5],
            backgroundColor: [
                '#6366f1',
                '#f59e0b',
                '#ef4444'
            ],
            borderWidth: 0,
            spacing: 2
        }]
    },
    options: {
        ...defaultChartOptions,
        cutout: '75%',
        plugins: {
            ...defaultChartOptions.plugins,
            tooltip: {
                ...defaultChartOptions.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((context.parsed / total) * 100);
                        return `${context.label}: ${context.parsed} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Update performance stats
function updatePerformanceStats(classKey) {
    const data = performanceData[classKey];
    
    // Update chart
    performanceChart.data.datasets[0].data = [data.top, data.average, data.below];
    performanceChart.update('active');
    
    // Update stat cards with animation
    animateValue('topCount', data.top);
    animateValue('avgCount', data.average);
    animateValue('belowCount', data.below);
    
    // Update center text
    const total = data.top + data.average + data.below;
    document.querySelector('.center-value').textContent = total;
}

// Performance class selector
document.getElementById('performanceClassSelector').addEventListener('change', function() {
    updatePerformanceStats(this.value);
});

// ===== FEE PAYMENT CHART =====
const feeCtx = document.getElementById('feeChart');
const feeChart = new Chart(feeCtx, {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Pending', 'Overdue'],
        datasets: [{
            data: [68, 20, 12],
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#ef4444'
            ],
            borderWidth: 0,
            spacing: 2
        }]
    },
    options: {
        ...defaultChartOptions,
        cutout: '70%',
        plugins: {
            ...defaultChartOptions.plugins,
            tooltip: {
                ...defaultChartOptions.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return `${context.label}: ${context.parsed}%`;
                    }
                }
            }
        }
    }
});

// ===== DEPARTMENT DISTRIBUTION CHART =====
const departmentCtx = document.getElementById('departmentChart');
const departmentChart = new Chart(departmentCtx, {
    type: 'doughnut',
    data: {
        labels: ['BCA', 'B.Tech', 'MBA'],
        datasets: [{
            data: [400, 500, 350],
            backgroundColor: [
                '#8b5cf6',
                '#14b8a6',
                '#f59e0b'
            ],
            borderWidth: 0,
            spacing: 2
        }]
    },
    options: {
        ...defaultChartOptions,
        cutout: '65%',
        plugins: {
            ...defaultChartOptions.plugins,
            tooltip: {
                ...defaultChartOptions.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((context.parsed / total) * 100);
                        return `${context.label}: ${context.parsed} students (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// ===== STAR STUDENT CAROUSEL =====
const starStudents = [
    {
        name: 'Michael Johnson',
        class: 'Class XII, Section B',
        image: 'https://i.imgur.com/8Km9tLL.png',
        achievement: '98.5% Average'
    },
    {
        name: 'Sophia Williams',
        class: 'Class XI, Section A',
        image: 'https://i.imgur.com/7k12EPD.png',
        achievement: '96.8% Average'
    },
    {
        name: 'Daniel Martinez',
        class: 'Class X, Section C',
        image: 'https://i.imgur.com/Z9qZ1Zg.png',
        achievement: '95.2% Average'
    }
];

let currentStarIndex = 0;

function updateStarStudent(index) {
    const student = starStudents[index];
    
    // Fade out
    const starCard = document.querySelector('.star-content');
    starCard.style.opacity = '0';
    starCard.style.transform = 'translateY(10px)';
    
    setTimeout(() => {
        // Update content
        document.getElementById('starName').textContent = student.name;
        document.getElementById('starClass').textContent = student.class;
        document.getElementById('starImage').src = student.image;
        
        // Update dots
        document.querySelectorAll('.star-dots .dot').forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        
        // Fade in
        starCard.style.opacity = '1';
        starCard.style.transform = 'translateY(0)';
    }, 300);
}

function nextStudent() {
    currentStarIndex = (currentStarIndex + 1) % starStudents.length;
    updateStarStudent(currentStarIndex);
}

function prevStudent() {
    currentStarIndex = (currentStarIndex - 1 + starStudents.length) % starStudents.length;
    updateStarStudent(currentStarIndex);
}

// Auto-rotate star students every 5 seconds
setInterval(nextStudent, 5000);

// ===== UTILITY FUNCTIONS =====

/**
 * Animate a single value change
 * @param {string} elementId - ID of element to update
 * @param {number} target - Target value
 */
function animateValue(elementId, target) {
    const element = document.getElementById(elementId);
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

// ===== PROGRESS BAR ANIMATION =====
// Animate progress bars when they come into view
const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const fill = entry.target;
            fill.style.width = fill.getAttribute('style').match(/width:\s*(\d+%)/)[1];
            progressObserver.unobserve(entry.target);
        }
    });
});

document.querySelectorAll('.progress-fill').forEach(fill => {
    const targetWidth = fill.style.width;
    fill.style.width = '0%';
    setTimeout(() => {
        fill.style.width = targetWidth;
    }, 100);
});

// ===== SEARCH FUNCTIONALITY =====
const searchInput = document.querySelector('.search-input');
searchInput.addEventListener('input', debounce(function(e) {
    const searchTerm = e.target.value.toLowerCase();
    // Implement search logic here
    console.log('Searching for:', searchTerm);
}, 300));

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
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('collapsed');
    }
}

window.addEventListener('resize', debounce(handleResize, 250));

// ===== NOTIFICATION HANDLING =====
document.querySelector('.notification-icon').addEventListener('click', function() {
    // Toggle notification panel
    console.log('Show notifications');
    // Implement notification panel logic here
});

// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ Dashboard initialized successfully');
    
    // Add smooth transitions after page load
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
    
    // Initialize tooltips if needed
    // initializeTooltips();
});

// ===== ERROR HANDLING =====
window.addEventListener('error', (e) => {
    console.error('Dashboard Error:', e.error);
});

// Log when charts are loaded
console.log('📊 Charts loaded:', {
    attendance: attendanceChart ? '✓' : '✗',
    performance: performanceChart ? '✓' : '✗',
    fee: feeChart ? '✓' : '✗',
    department: departmentChart ? '✓' : '✗'
});
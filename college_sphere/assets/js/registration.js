/**
 * Student Registration - JavaScript
 * 
 * Handles dynamic subject loading via AJAX
 * Form validation and submission
 * 
 * @author CollegeSphere Development Team
 * @version 2.0
 */

(function() {
    'use strict';
    
    // DOM Elements
    const streamSelect = document.getElementById('stream_id');
    const subjectsContainer = document.getElementById('subjects-container');
    const subjectsLoading = document.getElementById('subjects-loading');
    const registrationForm = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('submitBtn');
    const emailInput = document.getElementById('email');
    
    /**
     * Initialize event listeners
     */
    function init() {
        // Stream change event
        if (streamSelect) {
            streamSelect.addEventListener('change', handleStreamChange);
        }
        
        // Form submission
        if (registrationForm) {
            registrationForm.addEventListener('submit', handleFormSubmit);
        }
        
        // Email validation on blur
        if (emailInput) {
            emailInput.addEventListener('blur', checkEmailAvailability);
        }
        
        // Password confirmation validation
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        // Password strength indicator
        if (passwordInput) {
            passwordInput.addEventListener('input', updatePasswordStrength);
        }
    }
    
    /**
     * Handle stream selection change
     * Loads subjects via AJAX
     */
    function handleStreamChange() {
        const streamId = this.value;
        
        if (!streamId) {
            subjectsContainer.innerHTML = '';
            subjectsContainer.style.display = 'none';
            return;
        }
        
        // Show loading state
        subjectsLoading.style.display = 'block';
        subjectsContainer.innerHTML = '';
        subjectsContainer.style.display = 'none';
        
        // Fetch subjects via AJAX
        fetchSubjects(streamId);
    }
    
    /**
     * Fetch subjects from server via AJAX
     * 
     * @param {number} streamId Stream ID
     */
    function fetchSubjects(streamId) {
        const formData = new FormData();
        formData.append('action', 'get_subjects');
        formData.append('stream_id', streamId);
        
        fetch('../controllers/StudentRegistrationController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            subjectsLoading.style.display = 'none';
            
            if (data.success) {
                displaySubjects(data.subjects);
            } else {
                showError('Failed to load subjects: ' + data.message);
            }
        })
        .catch(error => {
            subjectsLoading.style.display = 'none';
            showError('Error loading subjects. Please try again.');
            console.error('Error:', error);
        });
    }
    
    /**
     * Display subjects as checkboxes
     * 
     * @param {Array} subjects Array of subject objects
     */
    function displaySubjects(subjects) {
        if (!subjects || subjects.length === 0) {
            subjectsContainer.innerHTML = '<p class="text-muted">No subjects available for this stream.</p>';
            subjectsContainer.style.display = 'block';
            return;
        }
        
        let html = '<div class="row">';
        html += '<div class="col-12"><h6 class="mb-3">Select Subjects:</h6></div>';
        
        subjects.forEach(subject => {
            html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check subject-checkbox">
                        <input class="form-check-input" type="checkbox" 
                               name="subjects[]" value="${subject.subject_id}" 
                               id="subject_${subject.subject_id}">
                        <label class="form-check-label" for="subject_${subject.subject_id}">
                            <strong>${subject.subject_name}</strong>
                            <small class="text-muted d-block">${subject.subject_code} - ${subject.credits} Credits</small>
                            ${subject.description ? `<small class="text-muted">${subject.description}</small>` : ''}
                        </label>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        subjectsContainer.innerHTML = html;
        subjectsContainer.style.display = 'block';
    }
    
    /**
     * Check email availability
     */
    function checkEmailAvailability() {
        const email = this.value;
        
        if (!email || !validateEmail(email)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'check_email');
        formData.append('email', email);
        
        fetch('../controllers/StudentRegistrationController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.exists) {
                emailInput.setCustomValidity('This email is already registered');
                showEmailFeedback('Email already registered', 'danger');
            } else {
                emailInput.setCustomValidity('');
                showEmailFeedback('Email available', 'success');
            }
        })
        .catch(error => {
            console.error('Error checking email:', error);
        });
    }
    
    /**
     * Show email availability feedback
     * 
     * @param {string} message Feedback message
     * @param {string} type Message type (success/danger)
     */
    function showEmailFeedback(message, type) {
        let feedbackDiv = document.getElementById('email-feedback');
        
        if (!feedbackDiv) {
            feedbackDiv = document.createElement('div');
            feedbackDiv.id = 'email-feedback';
            feedbackDiv.className = 'form-text';
            emailInput.parentElement.appendChild(feedbackDiv);
        }
        
        feedbackDiv.className = `form-text text-${type}`;
        feedbackDiv.textContent = message;
        
        // Remove after 3 seconds
        setTimeout(() => {
            feedbackDiv.textContent = '';
        }, 3000);
    }
    
    /**
     * Handle form submission
     * 
     * @param {Event} e Submit event
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        // Validate form
        if (!registrationForm.checkValidity()) {
            registrationForm.classList.add('was-validated');
            return;
        }
        
        // Check if at least one subject is selected
        const selectedSubjects = registrationForm.querySelectorAll('input[name="subjects[]"]:checked');
        if (selectedSubjects.length === 0) {
            showError('Please select at least one subject');
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        
        // Prepare form data
        const formData = new FormData(registrationForm);
        formData.append('action', 'register_student');
        
        // Submit form via AJAX
        fetch('../controllers/StudentRegistrationController.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            
            if (data.success) {
                showSuccess(data);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            showError('Registration failed. Please try again.');
            console.error('Error:', error);
        });
    }
    
    /**
     * Show success message and redirect
     * 
     * @param {Object} data Response data
     */
    function showSuccess(data) {
        const successModal = `
            <div class="modal fade show" id="successModal" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle"></i> Registration Successful!
                            </h5>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-graduation-cap" style="font-size: 64px; color: #10b981;"></i>
                            </div>
                            <h4>Welcome to CollegeSphere!</h4>
                            <p class="mb-3">${data.message}</p>
                            <div class="alert alert-info">
                                <h5 class="mb-2">Your Roll Number:</h5>
                                <h2 class="mb-0" style="color: #3b82f6; font-weight: 700;">${data.roll_number}</h2>
                            </div>
                            <p class="text-muted">
                                <small>Please save your roll number for future reference.</small>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <a href="login.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-sign-in-alt"></i> Proceed to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', successModal);
        
        // Redirect after 5 seconds
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 5000);
    }
    
    /**
     * Show error message
     * 
     * @param {string} message Error message
     */
    function showError(message) {
        const alertDiv = document.getElementById('alert-container');
        
        if (alertDiv) {
            alertDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error!</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Scroll to alert
            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            alert(message);
        }
    }
    
    /**
     * Validate email format
     * 
     * @param {string} email Email address
     * @return {boolean} Valid or not
     */
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Update password strength indicator
     */
    function updatePasswordStrength() {
        const password = this.value;
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';
        
        if (password.length >= 6) strength += 25;
        if (password.length >= 10) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/\d/.test(password)) strength += 15;
        if (/[^a-zA-Z\d]/.test(password)) strength += 10;
        
        if (strength < 40) {
            strengthText = 'Weak';
            strengthClass = 'danger';
        } else if (strength < 70) {
            strengthText = 'Medium';
            strengthClass = 'warning';
        } else {
            strengthText = 'Strong';
            strengthClass = 'success';
        }
        
        let strengthIndicator = document.getElementById('password-strength');
        
        if (!strengthIndicator) {
            strengthIndicator = document.createElement('div');
            strengthIndicator.id = 'password-strength';
            strengthIndicator.className = 'mt-2';
            this.parentElement.appendChild(strengthIndicator);
        }
        
        strengthIndicator.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-${strengthClass}" style="width: ${strength}%"></div>
            </div>
            <small class="text-${strengthClass}">Password Strength: ${strengthText}</small>
        `;
    }
    
    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
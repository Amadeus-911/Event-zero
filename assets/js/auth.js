

document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const formAlert = document.getElementById('formAlert');

    if (registrationForm) {
        // Real-time password validation
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');

        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);

        registrationForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Reset previous error messages
            clearErrors();

            // Validate all fields
            if (!validateForm()) {
                return;
            }

            // Collect form data
            const formData = {
                full_name: document.getElementById('fullName').value.trim(),
                username: document.getElementById('username').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: passwordInput.value
            };

            try {
                const response = await fetch(`${ACTIVE_CONFIG.BASE_URL}/api/endpoints/user_reg.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    registrationForm.reset();
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again later.', 'danger');
            }
        });
    }

    // Validation Functions
    function validateForm() {
        let isValid = true;

        const fullName = document.getElementById('fullName').value.trim();
        if (fullName.length < 2) {
            showFieldError('fullName', 'Full name must be at least 2 characters long');
            isValid = false;
        }

        const username = document.getElementById('username').value.trim();
        if (username.length < 3) {
            showFieldError('username', 'Username must be at least 3 characters long');
            isValid = false;
        }
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            showFieldError('username', 'Username can only contain letters, numbers, and underscores');
            isValid = false;
        }

        const email = document.getElementById('email').value.trim();
        if (!isValidEmail(email)) {
            showFieldError('email', 'Please enter a valid email address');
            isValid = false;
        }

        if (!validatePassword()) {
            isValid = false;
        }

        if (!validatePasswordMatch()) {
            isValid = false;
        }

        return isValid;
    }

    function validatePassword() {
        const password = document.getElementById('password').value;
        const passwordRegex = /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+){8,}$/;
        
        if (!passwordRegex.test(password)) {
            showFieldError('password', 'Password must be at least 8 characters long and contain at least one number');
            return false;
        }
        
        clearFieldError('password');
        return true;
    }

    function validatePasswordMatch() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            showFieldError('confirmPassword', 'Passwords do not match');
            return false;
        }
        
        clearFieldError('confirmPassword');
        return true;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        
        // Create or update error message
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        errorDiv.textContent = message;
    }

    function clearFieldError(fieldId) {
        const field = document.getElementById(fieldId);
        field.classList.remove('is-invalid');
    }

    function clearErrors() {
        const fields = registrationForm.querySelectorAll('.is-invalid');
        fields.forEach(field => field.classList.remove('is-invalid'));
    }

    function showAlert(message, type) {
        formAlert.className = `alert alert-${type}`;
        formAlert.textContent = message;
        formAlert.classList.remove('d-none');

        if (type === 'success') {
            setTimeout(() => {
                formAlert.classList.add('d-none');
            }, 3000);
        }
    }

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            const formData = {
                identifier: document.getElementById('identifier').value,
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch(`${ACTIVE_CONFIG.BASE_URL}/api/endpoints/login.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('user', JSON.stringify(data.user));
                    localStorage.setItem('auth_token', JSON.stringify(data.token));
                    
                    showAlert(data.message, 'success');
                    
                    // Redirect to dashboard after successful login
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert(error, 'danger');
            }
        });
    }
});
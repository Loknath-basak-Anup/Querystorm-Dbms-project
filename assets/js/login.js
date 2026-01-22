// ============================================
// QuickMart Login Page - JavaScript
// ============================================

// ===== Success Sound Effect =====
function playSuccessSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    // Success sound: pleasant ascending tones
    oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
    oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
    oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2); // G5
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.5);
}

// ===== Animated Particles =====
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    const particleCount = 50;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random positioning
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 20 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
        
        particlesContainer.appendChild(particle);
    }
}

// ===== Image Slider =====
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

// Auto slide every 5 seconds
setInterval(nextSlide, 5000);

// Manual slide control
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
    });
});

// ===== Form Switching =====
const loginForm = document.getElementById('loginForm');
const signupForm = document.getElementById('signupForm');
const showSignupBtn = document.getElementById('showSignup');
const showLoginBtn = document.getElementById('showLogin');

showSignupBtn.addEventListener('click', (e) => {
    e.preventDefault();
    loginForm.classList.remove('active');
    setTimeout(() => {
        signupForm.classList.add('active');
    }, 300);
});

showLoginBtn.addEventListener('click', (e) => {
    e.preventDefault();
    signupForm.classList.remove('active');
    setTimeout(() => {
        loginForm.classList.add('active');
    }, 300);
});

// ===== Role Selection Handler =====
// Login Role Selection
const loginRoleOptions = document.querySelectorAll('.role-option');
loginRoleOptions.forEach(option => {
    option.addEventListener('click', function() {
        loginRoleOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Signup Role Selection
const signupRoleOptions = document.querySelectorAll('.signup-role-option');
signupRoleOptions.forEach(option => {
    option.addEventListener('click', function() {
        signupRoleOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Continue to Signup Button
const continueSignupBtn = document.querySelector('.btn-continue-signup');
if (continueSignupBtn) {
    continueSignupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedRole = document.querySelector('input[name="signupRole"]:checked').value;
        
        // Redirect based on role
        if (selectedRole === 'buyer') {
            showNotification('Redirecting to Buyer signup...', 'info');
            setTimeout(() => {
                window.location.href = '../buyer/signup.php';
            }, 1000);
        } else if (selectedRole === 'seller') {
            showNotification('Redirecting to Seller signup...', 'info');
            setTimeout(() => {
                window.location.href = '../seller/signup.php';
            }, 1000);
        }
    });
}

// ===== Password Visibility Toggle =====
const togglePasswordIcons = document.querySelectorAll('.toggle-password');

togglePasswordIcons.forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.previousElementSibling;
        
        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});

// ===== Password Strength Checker =====
const signupPasswordInput = document.getElementById('signupPassword');

if (signupPasswordInput) {
    signupPasswordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthFill = document.querySelector('.strength-fill');
        const strengthText = document.querySelector('.strength-text');
        
        if (!strengthFill || !strengthText) return;
        
        let strength = 0;
        
        // Check password criteria
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        // Update strength bar
        strengthFill.className = 'strength-fill';
        
        if (strength === 0) {
            strengthFill.style.width = '0%';
            strengthText.textContent = 'Password strength';
        } else if (strength <= 2) {
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#ef4444';
        } else if (strength <= 4) {
            strengthFill.classList.add('medium');
            strengthText.textContent = 'Medium password';
            strengthText.style.color = '#f59e0b';
        } else {
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#10b981';
        }
    });
}

// ===== Form Validation =====
const loginFormElement = document.getElementById('loginFormElement');
const signupFormElement = document.getElementById('signupFormElement');

// Login Form Submit
if (loginFormElement) {
    loginFormElement.addEventListener('submit', function(e) {
        const email = document.getElementById('loginEmail');
        const password = document.getElementById('loginPassword');
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Basic validation (let backend handle the real auth)
        if (!email.value || !password.value) {
            e.preventDefault();
            showNotification('Please fill in all fields', 'error');
            return;
        }
        
        if (!validateEmail(email.value)) {
            e.preventDefault();
            showNotification('Please enter a valid email address', 'error');
            return;
        }
        
        // Disable submit button to prevent double submission
        if (submitBtn) {
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Signing In...</span>';
            
            // Re-enable after 3 seconds in case of redirect failure
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 3000);
        }
        
        // allow form to submit to ../actions/login_action.php
    });
}

// Signup Form Submit
if (signupFormElement) {
    signupFormElement.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const firstName = document.getElementById('signupFirstName').value;
        const lastName = document.getElementById('signupLastName').value;
        const email = document.getElementById('signupEmail').value;
        const phone = document.getElementById('signupPhone').value;
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('signupConfirmPassword').value;
        const acceptTerms = document.getElementById('acceptTerms').checked;
        
        // Validation
        if (!firstName || !lastName || !email || !phone || !password || !confirmPassword) {
            showNotification('Please fill in all fields', 'error');
            return;
        }
        
        if (!validateEmail(email)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }
        
        if (password.length < 8) {
            showNotification('Password must be at least 8 characters', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }
        
        if (!acceptTerms) {
            showNotification('Please accept the terms and conditions', 'error');
            return;
        }
        
        // Simulate signup
        showNotification('Account created successfully! Redirecting...', 'success');
        
        // Redirect after 1.5 seconds
        setTimeout(() => {
            window.location.href = '../index.php';
        }, 1500);
    });
}

// ===== Email Validation =====
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// ===== Notification System =====
function showNotification(message, type = 'info') {
    // Remove existing notification if any
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        animation: slideInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.95rem;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add notification animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-content i {
        font-size: 1.25rem;
    }
`;
document.head.appendChild(style);

// ===== Social Login Handlers =====
const socialButtons = document.querySelectorAll('.social-btn');

socialButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const provider = this.classList.contains('google') ? 'Google' : 'Facebook';
        showNotification(`${provider} login coming soon!`, 'info');
    });
});

// ===== Input Focus Effects =====
const inputs = document.querySelectorAll('input');

inputs.forEach(input => {
    // Ensure inputs are always enabled
    input.disabled = false;
    input.readOnly = false;
    
    input.addEventListener('focus', function() {
        // Always ensure the input is editable when focused
        this.disabled = false;
        this.readOnly = false;
        this.parentElement.style.transform = 'scale(1.01)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
    
    // Prevent any attempts to disable the input
    input.addEventListener('mousedown', function() {
        this.disabled = false;
        this.readOnly = false;
    });
});

// Force enable all inputs on page load
document.addEventListener('DOMContentLoaded', () => {
    // Re-enable all input fields
    document.querySelectorAll('input').forEach(input => {
        input.disabled = false;
        input.readOnly = false;
    });
    
    // Auto-focus on email field
    const emailInput = document.getElementById('loginEmail');
    if (emailInput && loginForm.classList.contains('active')) {
        setTimeout(() => emailInput.focus(), 500);
    }
});

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', () => {
    createParticles();
    showSlide(0);
    
    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
});

// ===== Prevent Form Submission on Enter (except in password fields) =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.type !== 'submit') {
        const form = e.target.closest('form');
        if (form) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.click();
            }
        }
    }
});

// ===== Multi-Step Form Management =====
let currentStep = 1;
const totalSteps = 4;
let formData = {};
let verificationCode = '';

// Initialize multi-step form
function initMultiStepForm() {
    showStep(1);
    attachStepEventListeners();
    initCodeInputs();
}

// Show specific step
function showStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });

    // Show current step
    const activeStep = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
    if (activeStep) {
        activeStep.classList.add('active');
    }

    // Update step indicators
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNum < stepNumber) {
            step.classList.add('completed');
        } else if (stepNum === stepNumber) {
            step.classList.add('active');
        }
    });

    currentStep = stepNumber;
}

// Validate current step
function validateStep(stepNumber) {
    switch(stepNumber) {
        case 1:
            const firstName = document.getElementById('signupFirstName')?.value.trim();
            const lastName = document.getElementById('signupLastName')?.value.trim();
            const email = document.getElementById('signupEmail')?.value.trim();

            if (!firstName || !lastName || !email) {
                showNotification('Please fill in all fields', 'error');
                return false;
            }

            if (!validateEmail(email)) {
                showNotification('Please enter a valid email address', 'error');
                return false;
            }

            formData.firstName = firstName;
            formData.lastName = lastName;
            formData.email = email;
            return true;

        case 2:
            const phone = document.getElementById('signupPhone')?.value.trim();
            const password = document.getElementById('signupPassword')?.value;
            const confirmPassword = document.getElementById('signupConfirmPassword')?.value;

            if (!phone || !password || !confirmPassword) {
                showNotification('Please fill in all fields', 'error');
                return false;
            }

            if (password.length < 8) {
                showNotification('Password must be at least 8 characters', 'error');
                return false;
            }

            if (password !== confirmPassword) {
                showNotification('Passwords do not match', 'error');
                return false;
            }

            formData.phone = phone;
            formData.password = password;
            return true;

        case 3:
            const termsChecked = document.getElementById('acceptTerms')?.checked;

            if (!termsChecked) {
                showNotification('Please agree to the terms and conditions', 'error');
                return false;
            }

            // Generate verification code and display email
            verificationCode = generateVerificationCode();
            document.getElementById('emailDisplay').textContent = formData.email;
            console.log('Verification code:', verificationCode); // For testing
            return true;

        case 4:
            const code = getEnteredCode();

            if (code.length !== 6) {
                showNotification('Please enter the complete 6-digit code', 'error');
                return false;
            }

            if (code !== verificationCode) {
                showNotification('Invalid verification code', 'error');
                document.querySelectorAll('.code-input').forEach(input => {
                    input.classList.add('error');
                    setTimeout(() => input.classList.remove('error'), 300);
                });
                return false;
            }

            return true;

        default:
            return true;
    }
}

// Move to next step
function nextStep() {
    if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }
}

// Move to previous step
function prevStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

// Attach event listeners for step navigation
function attachStepEventListeners() {
    // Next buttons
    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            nextStep();
        });
    });

    // Back buttons
    document.querySelectorAll('.btn-back').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            prevStep();
        });
    });

    // Create Account button
    const createBtn = document.querySelector('.btn-create');
    if (createBtn) {
        createBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (validateStep(4)) {
                handleSignupSubmit();
            }
        });
    }

    // Resend code button
    const resendBtn = document.querySelector('.resend-code');
    if (resendBtn) {
        resendBtn.addEventListener('click', (e) => {
            e.preventDefault();
            handleResendCode(resendBtn);
        });
    }
}

// Initialize code inputs behavior
function initCodeInputs() {
    const codeInputs = document.querySelectorAll('.code-input');
    
    codeInputs.forEach((input, index) => {
        // Auto-advance on input
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            // Only allow numbers
            e.target.value = value.replace(/[^0-9]/g, '');
            
            // Add filled class
            if (e.target.value) {
                e.target.classList.add('filled');
            } else {
                e.target.classList.remove('filled');
            }
            
            // Auto-advance to next input
            if (e.target.value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
        });

        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });

        // Handle paste
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            
            if (pastedData.length === 6) {
                codeInputs.forEach((inp, idx) => {
                    inp.value = pastedData[idx] || '';
                    if (inp.value) {
                        inp.classList.add('filled');
                    }
                });
                codeInputs[5].focus();
            }
        });
    });
}

// Get entered verification code
function getEnteredCode() {
    const codeInputs = document.querySelectorAll('.code-input');
    let code = '';
    codeInputs.forEach(input => {
        code += input.value;
    });
    return code;
}

// Generate random 6-digit verification code
function generateVerificationCode() {
    return Math.floor(100000 + Math.random() * 900000).toString();
}

// Handle resend code
function handleResendCode(button) {
    let countdown = 60;
    button.disabled = true;
    
    const originalText = button.innerHTML;
    
    verificationCode = generateVerificationCode();
    console.log('New verification code:', verificationCode); // For testing
    
    showNotification('Verification code sent!', 'success');
    
    const timer = setInterval(() => {
        button.innerHTML = `<i class="fas fa-clock"></i> Resend in ${countdown}s`;
        countdown--;
        
        if (countdown < 0) {
            clearInterval(timer);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }, 1000);
}

// Handle final signup submission
function handleSignupSubmit() {
    const signupData = {
        ...formData,
        verificationCode: getEnteredCode()
    };
    
    console.log('Signup successful with data:', signupData);
    
    showNotification('Account created successfully! Redirecting...', 'success');
    
    setTimeout(() => {
        // Reset form
        document.querySelectorAll('.code-input').forEach(input => {
            input.value = '';
            input.classList.remove('filled');
        });
        showStep(1);
        formData = {};
        
        // Switch to login form
        const signupForm = document.getElementById('signup-form-container');
        const loginForm = document.getElementById('login-form-container');
        signupForm.classList.remove('active');
        loginForm.classList.add('active');
    }, 2000);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initMultiStepForm();
    // Recreate particles with improved visuals
    const particlesRoot = document.getElementById('particles');
    if (particlesRoot) {
        particlesRoot.innerHTML = '';
        createParticles(true);
    }
});

// Override: Improved colorful bubbles
function createParticles(enhanced = false) {
    const container = document.getElementById('particles');
    if (!container) return;

    const count = enhanced ? 80 : 50;
    const colors = [
        '#60a5fa', // blue-400
        '#a78bfa', // violet-400
        '#34d399', // green-400
        '#f59e0b', // amber-500
        '#f472b6'  // pink-400
    ];

    for (let i = 0; i < count; i++) {
        const bubble = document.createElement('div');
        bubble.className = 'particle';

        // Random size (larger than before)
        const size = enhanced ? Math.floor(Math.random() * 12) + 10 : Math.floor(Math.random() * 8) + 6; // 10-22px or 6-14px
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        // Random position
        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.top = `${Math.random() * 100}%`;

        // Colorful gradient fill
        const color = colors[Math.floor(Math.random() * colors.length)];
        bubble.style.background = `radial-gradient(circle at 30% 30%, ${color}, rgba(255,255,255,0.0))`;
        bubble.style.boxShadow = `0 0 ${Math.max(10, size)}px ${color}40`;

        // Animation timing
        const duration = enhanced ? (8 + Math.random() * 12) : (10 + Math.random() * 10);
        const delay = Math.random() * 5;
        bubble.style.animation = `float-particle ${duration}s ease-in-out ${delay}s infinite`;

        container.appendChild(bubble);
    }
}

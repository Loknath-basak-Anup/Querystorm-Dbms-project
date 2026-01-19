<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Signup | QuickMart</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #60a5fa;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-dark: #0a0e27;
            --bg-darker: #060918;
            --card-bg: #0f1229;
            --input-bg: #1a1f3a;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --border-color: #1e293b;
            --border-light: #334155;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
            min-height: 100vh;
            color: var(--text-primary);
            padding: 2rem 1rem;
        }

        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            width: 120px;
            height: auto;
            filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.3));
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .signup-card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-group label .required {
            color: var(--danger-color);
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1rem;
            pointer-events: none;
        }

        .input-wrapper input,
        .input-wrapper select,
        .input-wrapper textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .input-wrapper textarea {
            min-height: 100px;
            resize: vertical;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus,
        .input-wrapper textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(59, 130, 246, 0.05);
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            cursor: pointer;
            pointer-events: all;
            z-index: 10;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: var(--transition);
        }

        .strength-fill.weak {
            width: 33%;
            background: var(--danger-color);
        }

        .strength-fill.medium {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-fill.strong {
            width: 100%;
            background: var(--success-color);
        }

        .strength-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-top: 0.125rem;
        }

        .checkbox-group label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            cursor: pointer;
        }

        .checkbox-group a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .form-footer p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }

            .signup-card {
                padding: 1.5rem;
                border-radius: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .section-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            .logo-container img {
                width: 90px;
            }

            .signup-card {
                padding: 1.25rem;
            }

            .input-wrapper input,
            .input-wrapper select,
            .input-wrapper textarea {
                padding: 0.75rem 1rem 0.75rem 2.5rem;
                font-size: 0.875rem;
            }

            .btn-primary {
                padding: 0.875rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div id="sidebarContainer"></div>
    <div class="bg-pattern"></div>

    <div class="container">
        <!-- Header -->
        <div class="signup-header">
            <div class="logo-container">
                <img src="../images/qmart_logo2.png" alt="QuickMart Logo" />
            </div>
            <div class="role-badge">
                <i class="fas fa-shopping-bag"></i>
                <span>Buyer Registration</span>
            </div>
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">Create Your Buyer Account</h1>
            <p style="color: var(--text-secondary);">Join QuickMart and start shopping today</p>
        </div>

        <!-- Signup Form -->
        <div class="signup-card">
            <form id="buyerSignupForm" action="../actions/register_action.php" method="POST" novalidate>
                <input type="hidden" name="role" value="buyer" />
                <input type="hidden" id="fullName" name="full_name" />
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Personal Information</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="firstName">First Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="firstName" name="first_name" placeholder="John" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="lastName">Last Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="lastName" name="last_name" placeholder="Doe" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" placeholder="john.doe@example.com" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="phone" name="phone" placeholder="+880 1234-567890" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="dateOfBirth">Date of Birth</label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="dateOfBirth" name="dateOfBirth" />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="gender">Gender</label>
                            <div class="input-wrapper">
                                <i class="fas fa-venus-mars"></i>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        <span>Account Security</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Create a strong password" required />
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <span class="strength-text">Password strength</span>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter password" required />
                                <i class="fas fa-eye toggle-password"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Shipping Address</span>
                    </div>

                    <div class="input-group">
                        <label for="addressLine1">Address Line 1 <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-home"></i>
                            <input type="text" id="addressLine1" name="address_line1" placeholder="Street address, P.O. box" required />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="addressLine2">Address Line 2</label>
                        <div class="input-wrapper">
                            <i class="fas fa-building"></i>
                            <input type="text" id="addressLine2" name="address_line2" placeholder="Apartment, suite, unit, building, floor, etc." />
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="city">City <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-city"></i>
                                <input type="text" id="city" name="city" placeholder="Dhaka" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="state">State/Province</label>
                            <div class="input-wrapper">
                                <i class="fas fa-map"></i>
                                <input type="text" id="state" name="state" placeholder="Dhaka Division" />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="postalCode">Postal Code <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-mail-bulk"></i>
                                <input type="text" id="postalCode" name="postal_code" placeholder="1000" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="country">Country <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-globe"></i>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="BD" selected>Bangladesh</option>
                                    <option value="IN">India</option>
                                    <option value="PK">Pakistan</option>
                                    <option value="US">United States</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="form-section">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required />
                        <label for="terms">
                            I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a> of QuickMart
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary">
                    <span>Create Buyer Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="form-footer">
                <p>Already have an account? <a href="../html/login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(icon => {
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

        // Password strength checker
        const passwordInput = document.getElementById('password');
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthFill = document.querySelector('.strength-fill');
            const strengthText = document.querySelector('.strength-text');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            if (strength === 0) {
                strengthFill.style.width = '0%';
                strengthText.textContent = 'Password strength';
                strengthText.style.color = 'var(--text-secondary)';
            } else if (strength <= 2) {
                strengthFill.classList.add('weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = 'var(--danger-color)';
            } else if (strength <= 4) {
                strengthFill.classList.add('medium');
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#f59e0b';
            } else {
                strengthFill.classList.add('strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = 'var(--success-color)';
            }
        });

        // Form validation & backend submit
        document.getElementById('buyerSignupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 8) {
                showNotification('Password must be at least 8 characters long!', 'error');
                return;
            }
            
            if (!terms) {
                showNotification('Please accept the Terms & Conditions!', 'error');
                return;
            }

            // Compose full name for backend
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            document.getElementById('fullName').value = `${firstName} ${lastName}`.trim();
            
            this.submit();
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
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
                font-size: 0.95rem;
                font-weight: 500;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { opacity: 0; transform: translateX(100px); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes slideOutRight {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100px); }
            }
        `;
        document.head.appendChild(style);
    </script>
    <script src="../assets/js/products_page.js"></script>
</body>
</html>

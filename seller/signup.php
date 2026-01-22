<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Signup | QuickMart</title>

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
            --primary-color: #8b5cf6;
            --primary-dark: #7c3aed;
            --primary-light: #a78bfa;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
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
                radial-gradient(circle at 20% 50%, rgba(139, 92, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1000px;
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
            filter: drop-shadow(0 4px 12px rgba(139, 92, 246, 0.3));
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(139, 92, 246, 0.1);
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
            background: rgba(139, 92, 246, 0.05);
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

        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            background: var(--input-bg);
            border: 2px dashed var(--border-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background: rgba(139, 92, 246, 0.05);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .file-upload-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-align: center;
        }

        .file-upload-text strong {
            color: var(--primary-color);
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
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
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

        .info-box {
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid var(--primary-color);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-box-content {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
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
                <i class="fas fa-store"></i>
                <span>Seller Registration</span>
            </div>
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">Create Your Seller Account</h1>
            <p style="color: var(--text-secondary);">Start your business on QuickMart and reach millions of customers</p>
        </div>

        <!-- Signup Form -->
        <div class="signup-card">
            <form id="sellerSignupForm" action="../actions/register_action.php" method="POST" novalidate>
                <input type="hidden" name="role" value="seller" />
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
                            <label for="nid">National ID Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="nid" name="nid" placeholder="1234567890" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="dateOfBirth" name="dateOfBirth" required />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i>
                        <span>Business Information</span>
                    </div>

                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fas fa-info-circle"></i>
                            <span>Business Verification</span>
                        </div>
                        <div class="info-box-content">
                            Your business information will be verified before you can start selling. Please provide accurate details.
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessName">Business/Shop Name <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-store-alt"></i>
                            <input type="text" id="businessName" name="shop_name" placeholder="Your Shop Name" required />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessType">Business Type <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-building"></i>
                            <select id="businessType" name="businessType" required>
                                <option value="">Select Business Type</option>
                                <option value="individual">Individual/Sole Proprietor</option>
                                <option value="partnership">Partnership</option>
                                <option value="company">Private Limited Company</option>
                                <option value="corporation">Public Limited Company</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="businessCategory">Business Category <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-tags"></i>
                                <select id="businessCategory" name="businessCategory" required>
                                    <option value="">Select Category</option>
                                    <option value="electronics">Electronics</option>
                                    <option value="fashion">Fashion & Apparel</option>
                                    <option value="home">Home & Kitchen</option>
                                    <option value="beauty">Beauty & Personal Care</option>
                                    <option value="sports">Sports & Outdoors</option>
                                    <option value="books">Books & Stationery</option>
                                    <option value="toys">Toys & Games</option>
                                    <option value="groceries">Groceries</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="taxId">Tax ID/TIN Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-receipt"></i>
                                <input type="text" id="taxId" name="taxId" placeholder="123-456-789" />
                            </div>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessDescription">Business Description <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-align-left"></i>
                            <textarea id="businessDescription" name="shop_description" placeholder="Describe your business, products, and services..." required></textarea>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="businessLicense">Business License/Trade License Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-certificate"></i>
                            <input type="text" id="businessLicense" name="businessLicense" placeholder="License number" />
                        </div>
                    </div>
                </div>

                <!-- Business Address -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Business Address</span>
                    </div>

                    <div class="input-group">
                        <label for="addressLine1">Address Line 1 <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-home"></i>
                            <input type="text" id="addressLine1" name="address_line1" placeholder="Street address" required />
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="addressLine2">Address Line 2</label>
                        <div class="input-wrapper">
                            <i class="fas fa-building"></i>
                            <input type="text" id="addressLine2" name="address_line2" placeholder="Building, floor, etc." />
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
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-university"></i>
                        <span>Bank Account Information</span>
                    </div>

                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Payment Information</span>
                        </div>
                        <div class="info-box-content">
                            Your payment information is encrypted and secure. This is required to receive payments from customers.
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="bankName">Bank Name <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-landmark"></i>
                            <input type="text" id="bankName" name="bankName" placeholder="Bank name" required />
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="accountName">Account Holder Name <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" id="accountName" name="accountName" placeholder="Full name" required />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="accountNumber">Account Number <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <i class="fas fa-credit-card"></i>
                                <input type="text" id="accountNumber" name="accountNumber" placeholder="Account number" required />
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="routingNumber">Routing Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-hashtag"></i>
                                <input type="text" id="routingNumber" name="routingNumber" placeholder="Routing number" />
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="branchName">Branch Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-code-branch"></i>
                                <input type="text" id="branchName" name="branchName" placeholder="Branch name" />
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

                <!-- Document Upload -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-file-upload"></i>
                        <span>Document Upload</span>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="idDocument">Government ID (NID/Passport) <span class="required">*</span></label>
                            <div class="file-upload">
                                <input type="file" id="idDocument" name="idDocument" accept=".pdf,.jpg,.jpeg,.png" required />
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    <small>PDF, JPG or PNG (Max 5MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="businessDocument">Business License/Certificate</label>
                            <div class="file-upload">
                                <input type="file" id="businessDocument" name="businessDocument" accept=".pdf,.jpg,.jpeg,.png" />
                                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                                <div class="file-upload-text">
                                    <strong>Click to upload</strong> or drag and drop<br>
                                    <small>PDF, JPG or PNG (Max 5MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="form-section">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required />
                        <label for="terms">
                            I agree to the <a href="#">Seller Terms & Conditions</a>, <a href="#">Privacy Policy</a>, and <a href="#">Commission Structure</a> of QuickMart
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary">
                    <span>Create Seller Account</span>
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
                strengthText.style.color = 'var(--warning-color)';
            } else {
                strengthFill.classList.add('strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = 'var(--success-color)';
            }
        });

        // File upload display
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    const textElement = this.nextElementSibling.nextElementSibling;
                    textElement.innerHTML = `<strong>${fileName}</strong><br><small>Click to change</small>`;
                }
            });
        });

        // Form validation before submit (then allow normal POST to backend)
        document.getElementById('sellerSignupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;

            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('Passwords do not match!', 'error');
                return;
            }

            if (password.length < 8) {
                e.preventDefault();
                showNotification('Password must be at least 8 characters long!', 'error');
                return;
            }

            if (!terms) {
                e.preventDefault();
                showNotification('Please accept the Terms & Conditions!', 'error');
                return;
            }
            // If validation passes, let the form submit normally to register_action.php
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
                max-width: 400px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
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

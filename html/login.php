<?php
$alertMessage = '';
$alertType = '';

if (isset($_GET['err'])) {
  $err = $_GET['err'];
  $alertType = 'error';
  if ($err === 'verify_email') {
    $alertMessage = 'Please verify your email before logging in. Check your inbox for the verification link.';
  } elseif ($err === 'invalid_verification') {
    $alertMessage = 'This verification link is invalid or has already been used. Please request a new verification email.';
  } elseif ($err === 'verification_failed') {
    $alertMessage = 'Email verification failed. Please try again later or contact support.';
  } else {
    $alertMessage = 'Invalid email or password. Please try again.';
  }
} elseif (isset($_GET['info'])) {
  $info = $_GET['info'];
  if ($info === 'verify_email_sent') {
    $alertType = 'info';
    $alertMessage = 'We have sent a verification link to your email. Please check your inbox and verify your account.';
  } elseif ($info === 'verify_email_error') {
    $alertType = 'error';
    $alertMessage = 'Account created, but we could not send the verification email. Please contact support or try again later.';
  } elseif ($info === 'verification_success') {
    $alertType = 'success';
    $alertMessage = 'Your email has been verified successfully. You can now log in.';
  } elseif ($info === 'already_verified') {
    $alertType = 'info';
    $alertMessage = 'Your email is already verified. You can log in now.';
  } elseif ($info === 'seller_pending') {
    $alertType = 'info';
    $alertMessage = 'Your seller account is pending admin approval. You will be able to access the seller dashboard once approved.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | QuickMart</title>

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />

    <!-- Google Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/login.css" />
  </head>
  <body>
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <!-- Animated Particles -->
    <div id="particles"></div>

    <!-- Logo -->
    <div class="logo-container">
      <a href="../index.php" style="text-decoration: none; display: inline-block;">
        <img src="../images/qmart_logo2.png" alt="QuickMart Logo" />
      </a>
    </div>

    <!-- Main Container -->
    <div class="main-container">
      <!-- Left Panel - Image Slider -->
      <div class="left-panel">
        <div class="slider-container">
          <div class="slide active" id="slide1">
            <img src="../images/login.jpeg" alt="Shopping" />
            <div class="slide-overlay">
              <h2>Welcome to QuickMart</h2>
              <p>Your one-stop destination for everything you need</p>
            </div>
          </div>
          <div class="slide" id="slide2">
            <img src="../images/login2.png" alt="Shopping" />
            <div class="slide-overlay">
              <h2>Shop with Confidence</h2>
              <p>Secure payments, fast delivery, and hassle-free returns</p>
            </div>
          </div>
          <div class="slide" id="slide3">
            <img src="../images/login3.png" alt="Shopping" />
            <div class="slide-overlay">
              <h2>Exclusive Deals</h2>
              <p>Get amazing discounts on your favorite products</p>
            </div>
          </div>
        </div>
        <div class="slider-dots">
          <span class="dot active" data-slide="0"></span>
          <span class="dot" data-slide="1"></span>
          <span class="dot" data-slide="2"></span>
        </div>
      </div>

      <!-- Right Panel - Forms -->
      <div class="right-panel">
        <!-- Back to Home Button -->
        <div style="position: absolute; top: 20px; right: 20px; z-index: 10;">
          <a href="../index.php" class="back-home-btn" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: linear-gradient(135deg, #3b82f6, #06b6d4); color: white; border-radius: 0.75rem; font-weight: 600; text-decoration: none; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
          </a>
        </div>

        <div class="form-wrapper">
          <!-- Login Form -->
          <div class="form-container active" id="loginForm">
            <div class="form-header">
              <h1>Welcome Back!</h1>
              <p>Sign in to continue to QuickMart</p>
            </div>

            <form class="auth-form" id="loginFormElement" method="POST" action="../actions/login_action.php">
              <!-- Role Selection -->
              <div class="role-selector">
                <label class="role-label">Login as:</label>
                <div class="role-options">
                  <label class="role-option active">
                    <input type="radio" name="loginRole" value="buyer" checked />
                    <div class="role-card">
                      <i class="fas fa-shopping-bag"></i>
                      <span>Buyer</span>
                    </div>
                  </label>
                  <label class="role-option">
                    <input type="radio" name="loginRole" value="seller" />
                    <div class="role-card">
                      <i class="fas fa-store"></i>
                      <span>Seller</span>
                    </div>
                  </label>
                </div>
              </div>

              <div class="input-group">
                <label for="loginEmail">Email Address</label>
                <div class="input-wrapper">
                  <i class="fas fa-envelope"></i>
                  <input
                    type="email"
                    id="loginEmail"
                    name="email"
                    placeholder="your@email.com"
                    required
                  />
                </div>
              </div>

              <div class="input-group">
                <label for="loginPassword">Password</label>
                <div class="input-wrapper">
                  <i class="fas fa-lock"></i>
                  <input
                    type="password"
                    id="loginPassword"
                    name="password"
                    placeholder="Enter your password"
                    required
                  />
                  <i class="fas fa-eye toggle-password"></i>
                </div>
              </div>
              <?php if ($alertMessage !== ''): ?>
              <?php
                $color = '#3b82f6';
                $bg = 'rgba(59, 130, 246, 0.1)';
                if ($alertType === 'error') {
                  $color = '#ef4444';
                  $bg = 'rgba(239, 68, 68, 0.1)';
                } elseif ($alertType === 'success') {
                  $color = '#10b981';
                  $bg = 'rgba(16, 185, 129, 0.1)';
                }
              ?>
              <div style="color: <?php echo $color; ?>; padding: 0.75rem; background: <?php echo $bg; ?>; border-radius: 0.5rem; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($alertMessage); ?>
              </div>
              <?php endif; ?>

              <div class="form-options">
                <label class="remember-me">
                  <input type="checkbox" id="rememberMe" />
                  <span>Remember me</span>
                </label>

                <a href="#" class="forgot-link">Forgot Password?</a>
              </div>

              <button type="submit" class="btn-primary">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
              </button>

              <div class="divider">
                <span>or continue with</span>
              </div>

              <div class="social-login">
                <button type="button" class="social-btn google">
                  <i class="fab fa-google"></i>
                  <span>Google</span>
                </button>
                <button type="button" class="social-btn facebook">
                  <i class="fab fa-facebook-f"></i>
                  <span>Facebook</span>
                </button>
              </div>

              <div class="form-footer">
                <p>
                  Don't have an account? <a href="#" id="showSignup">Create Account</a>
                </p>
              </div>
            </form>
          </div>

          <!-- Signup Form -->
          <div class="form-container" id="signupForm">
            <div class="form-header">
              <h1>Create Account</h1>
              <p>Choose your account type to get started</p>
            </div>

            <!-- Role Selection for Signup -->
            <div class="signup-role-selector">
              <label class="signup-role-label">I want to:</label>
              <div class="signup-role-options">
                <label class="signup-role-option active">
                  <input type="radio" name="signupRole" value="buyer" checked />
                  <div class="signup-role-card">
                    <div class="role-icon-wrapper">
                      <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3>Buy Products</h3>
                    <p>Browse and shop from thousands of products</p>
                  </div>
                </label>
                <label class="signup-role-option">
                  <input type="radio" name="signupRole" value="seller" />
                  <div class="signup-role-card">
                    <div class="role-icon-wrapper">
                      <i class="fas fa-store"></i>
                    </div>
                    <h3>Sell Products</h3>
                    <p>Start your business and reach customers</p>
                  </div>
                </label>
              </div>
            </div>

            <button type="button" class="btn-primary btn-continue-signup">
              <span>Continue</span>
              <i class="fas fa-arrow-right"></i>
            </button>

            <div class="form-footer">
              <p>
                Already have an account? <a href="#" id="showLogin">Sign In</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom JS -->
    <script src="../assets/js/login.js"></script>
  </body>
</html>

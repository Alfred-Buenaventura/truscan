<?php 
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BPC Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=1.2">
</head>
<body class="login-page">
    
    <div class="card login-card-new">
        
        <div class="login-new-header">
            <div class="login-logo-container">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h2 class="login-title">BPC Attendance System</h2>
            <p class="login-subtitle">Biometric Attendance Monitoring</p>
        </div>

        <div class="login-new-body">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label> 
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-input">
                        <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                            <i id="eyeIcon" class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options-new">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-full-width">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> <span>Sign In</span> 
                </button>
            </form>

            <a href="forgot_password.php" class="login-new-forgot-link">Forgot your password?</a>
        </div>
    </div>
    
    <script>
        function togglePasswordVisibility() {
            const passField = document.getElementById('passwordField');
            const eyeIcon = document.getElementById('eyeIcon');
            const isPassword = passField.type === 'password';
            
            passField.type = isPassword ? 'text' : 'password';
            eyeIcon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    </script>
</body>
</html>
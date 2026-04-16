<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isAdminLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($auth->adminLogin($username, $password)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bihar Food</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-utensils"></i>
                    <h1>Bihar Food Admin</h1>
                </div>
                <p>Sign in to manage your food website</p>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php"><i class="fas fa-home"></i> Back to Website</a>
                <a href="#" style="float:right;"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>
        </div>
    </div>
    
    <style>
    .login-page {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .login-container {
        width: 100%;
        max-width: 400px;
    }
    
    .login-box {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        padding: 40px;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 10px;
    }
    
    .login-logo i {
        font-size: 2.5rem;
        color: #667eea;
    }
    
    .login-logo h1 {
        font-size: 1.8rem;
        color: #333;
    }
    
    .login-header p {
        color: #666;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 500;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .remember-me {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .remember-me input {
        width: auto;
    }
    
    .btn-login {
        width: 100%;
        background: #667eea;
        color: white;
        border: none;
        padding: 15px;
        border-radius: 5px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    
    .btn-login:hover {
        background: #5a67d8;
    }
    
    .login-footer {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .login-footer a {
        color: #667eea;
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .login-footer a:hover {
        text-decoration: underline;
    }
    
    .demo-credentials {
        margin-top: 30px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border-left: 4px solid #667eea;
    }
    
    .demo-credentials h4 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .demo-credentials p {
        margin: 5px 0;
        color: #555;
        font-size: 0.9rem;
    }
    </style>
</body>
</html>
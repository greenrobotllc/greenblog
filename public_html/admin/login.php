<?php
/**
 * GreenBlog Admin Login
 * 
 * This file handles admin login functionality.
 */

// Define base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Define GREENBLOG constant to allow includes
define('GREENBLOG', true);

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Include required files
require_once INCLUDES_DIR . '/db.php';
require_once INCLUDES_DIR . '/functions.php';
require_once INCLUDES_DIR . '/auth.php';

// Start session
startSecureSession();

// Check if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process login form
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            // Attempt to authenticate
            if (authenticateUser($username, $password)) {
                // Redirect to admin dashboard
                redirect('index.php');
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars(SITE_TITLE); ?> Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            color: #333;
            background-color: #f9f9f9;
        }
        h1 {
            color: #2c8c3c;
            text-align: center;
            margin-bottom: 30px;
        }
        .error {
            color: #e74c3c;
            background-color: #fadbd8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #2c8c3c;
            color: white;
            border: none;
            padding: 10px 15px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #1e6e2e;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #2c8c3c;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Admin Login</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <div class="back-link">
        <a href="../index.php">Back to Blog</a>
    </div>
</body>
</html>

<?php
/**
 * GreenBlog Admin Logout
 * 
 * This file handles admin logout functionality.
 */

// Define base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Define GREENBLOG constant to allow includes
define('GREENBLOG', true);

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Include required files
require_once INCLUDES_DIR . '/functions.php';
require_once INCLUDES_DIR . '/auth.php';

// Start session
startSecureSession();

// Logout user
logoutUser();

// Redirect to login page
redirect('login.php');

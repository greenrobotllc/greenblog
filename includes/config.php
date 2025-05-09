<?php
/**
 * GreenBlog Configuration File
 *
 * This file contains the main configuration settings for the GreenBlog system.
 * It is generated during the setup process and should not be edited manually.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

// Database configuration
define('DB_PATH', __DIR__ . '/../data/greenblog.db');
define('DB_TYPE', 'sqlite3');

// Site configuration
define('SITE_TITLE', 'security');
define('SITE_DESCRIPTION', 'test');
define('SITE_URL', 'http://localhost:8001');
define('ADMIN_EMAIL', 'andy@greenrobot.com');

// File paths
define('ROOT_DIR', realpath(__DIR__ . '/..'));
define('PUBLIC_DIR', ROOT_DIR . '/public_html');
define('ADMIN_DIR', PUBLIC_DIR . '/admin');
define('INCLUDES_DIR', ROOT_DIR . '/includes');
define('TEMPLATES_DIR', ROOT_DIR . '/templates');
define('STATIC_DIR', PUBLIC_DIR . '/static');
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads');
define('ASSETS_DIR', PUBLIC_DIR . '/assets');

// Static file generation settings
define('POSTS_PER_PAGE', 10);
define('ENABLE_CACHE', true);
define('CACHE_DURATION', 3600); // 1 hour in seconds

// Security settings
define('HASH_ALGO', 'PASSWORD_BCRYPT');
define('SESSION_DURATION', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes in seconds

// Installation status
define('INSTALLED', true);

// Version
define('VERSION', '1.0.0');

<?php
/**
 * GreenBlog Setup Script
 *
 * This file handles the initial setup of the GreenBlog system.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Define GREENBLOG constant to allow includes
define('GREENBLOG', true);

// Check if already installed
if (file_exists(BASE_PATH . '/data/greenblog.db') && file_exists(BASE_PATH . '/includes/config.php')) {
    // Check if config file indicates installation is complete
    include BASE_PATH . '/includes/config.php';
    if (defined('INSTALLED') && INSTALLED === true) {
        die('GreenBlog is already installed. If you want to reinstall, please delete the data/greenblog.db file and the includes/config.php file.');
    }
}

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $siteName = trim($_POST['site_name'] ?? '');
    $siteDescription = trim($_POST['site_description'] ?? '');
    $siteUrl = trim($_POST['site_url'] ?? '');
    $adminUsername = trim($_POST['admin_username'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminEmail = trim($_POST['admin_email'] ?? '');

    // Validation
    if (empty($siteName)) {
        $errors[] = 'Site name is required';
    }

    if (empty($siteDescription)) {
        $errors[] = 'Site description is required';
    }

    if (empty($siteUrl)) {
        $errors[] = 'Site URL is required';
    } elseif (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Site URL must be a valid URL';
    }

    if (empty($adminUsername)) {
        $errors[] = 'Admin username is required';
    } elseif (strlen($adminUsername) < 3) {
        $errors[] = 'Admin username must be at least 3 characters';
    }

    if (empty($adminPassword)) {
        $errors[] = 'Admin password is required';
    } elseif (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters';
    }

    if (empty($adminEmail)) {
        $errors[] = 'Admin email is required';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email must be a valid email address';
    }

    // If no errors, proceed with installation
    if (empty($errors)) {
        try {
            // Create data directory if it doesn't exist
            if (!file_exists(BASE_PATH . '/data')) {
                if (!mkdir(BASE_PATH . '/data', 0755, true)) {
                    throw new Exception("Failed to create data directory: " . BASE_PATH . '/data');
                }
                echo "<p>Created data directory</p>";
            } else {
                echo "<p>Data directory already exists</p>";
            }

            // Create uploads directory if it doesn't exist
            if (!file_exists(__DIR__ . '/uploads')) {
                if (!mkdir(__DIR__ . '/uploads', 0755, true)) {
                    throw new Exception("Failed to create uploads directory: " . __DIR__ . '/uploads');
                }
                echo "<p>Created uploads directory</p>";
            } else {
                echo "<p>Uploads directory already exists</p>";
            }

            // Create static directory if it doesn't exist
            if (!file_exists(__DIR__ . '/static')) {
                if (!mkdir(__DIR__ . '/static', 0755, true)) {
                    throw new Exception("Failed to create static directory: " . __DIR__ . '/static');
                }
                echo "<p>Created static directory</p>";
            } else {
                echo "<p>Static directory already exists</p>";
            }

            // Include ADODB
            require_once BASE_PATH . '/vendor/adodb/adodb-php/adodb.inc.php';

            echo "<p>ADODB included successfully</p>";

            // Check if SQLite3 extension is loaded
            echo "<p>PHP Version: " . phpversion() . "</p>";
            echo "<p>Loaded Extensions: </p><pre>";
            print_r(get_loaded_extensions());
            echo "</pre>";

            if (!extension_loaded('sqlite3')) {
                throw new Exception("SQLite3 extension is not loaded. Please enable it in your PHP configuration.");
            }
            echo "<p>SQLite3 extension is loaded</p>";

            // Check if PDO SQLite is available as an alternative
            if (extension_loaded('pdo_sqlite')) {
                echo "<p>PDO SQLite extension is also loaded</p>";
            }

            // Create database connection
            echo "<p>Attempting to create database connection with sqlite3 driver</p>";
            $conn = ADONewConnection('sqlite3');
            if (!$conn) {
                throw new Exception("Failed to create ADONewConnection with sqlite3 driver");
            }

            $dbPath = BASE_PATH . '/data/greenblog.db';
            echo "<p>Connecting to database at: " . $dbPath . "</p>";

            if (!$conn->Connect($dbPath)) {
                throw new Exception("Failed to connect to database: " . $conn->ErrorMsg());
            }

            echo "<p>Database connection successful</p>";

            // Create tables
            $conn->Execute("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL UNIQUE,
                    password TEXT NOT NULL,
                    email TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'admin',
                    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME,
                    login_attempts INTEGER DEFAULT 0,
                    last_login_attempt DATETIME
                )
            ");

            $conn->Execute("
                CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    status TEXT NOT NULL DEFAULT 'draft',
                    author_id INTEGER NOT NULL,
                    featured_image TEXT,
                    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    published_date DATETIME,
                    FOREIGN KEY (author_id) REFERENCES users(id)
                )
            ");

            $conn->Execute("
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    description TEXT
                )
            ");

            $conn->Execute("
                CREATE TABLE IF NOT EXISTS post_categories (
                    post_id INTEGER NOT NULL,
                    category_id INTEGER NOT NULL,
                    PRIMARY KEY (post_id, category_id),
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                )
            ");

            $conn->Execute("
                CREATE TABLE IF NOT EXISTS settings (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL
                )
            ");

            // Create admin user
            $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $conn->Execute(
                "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')",
                [$adminUsername, $hashedPassword, $adminEmail]
            );

            // Create default category
            $conn->Execute(
                "INSERT INTO categories (name, slug, description) VALUES ('Uncategorized', 'uncategorized', 'Default category')"
            );

            // Create settings
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('site_title', ?)", [$siteName]);
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('site_description', ?)", [$siteDescription]);
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('site_url', ?)", [$siteUrl]);
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('admin_email', ?)", [$adminEmail]);
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('posts_per_page', '10')");
            $conn->Execute("INSERT INTO settings (key, value) VALUES ('excerpt_length', '150')");

            // Create config file
            echo "<p>Creating config file</p>";
            $configPath = BASE_PATH . '/includes/config.php';

            if (!file_exists($configPath)) {
                throw new Exception("Config template file does not exist: " . $configPath);
            }

            $configTemplate = file_get_contents($configPath);
            if ($configTemplate === false) {
                throw new Exception("Failed to read config template file: " . $configPath);
            }

            echo "<p>Config template loaded successfully</p>";

            $configContent = str_replace('{{SITE_TITLE}}', $siteName, $configTemplate);
            $configContent = str_replace('{{SITE_DESCRIPTION}}', $siteDescription, $configContent);
            $configContent = str_replace('{{SITE_URL}}', $siteUrl, $configContent);
            $configContent = str_replace('{{ADMIN_EMAIL}}', $adminEmail, $configContent);

            echo "<p>Config content prepared</p>";

            if (file_put_contents($configPath, $configContent) === false) {
                throw new Exception("Failed to write config file: " . $configPath);
            }

            echo "<p>Config file written successfully</p>";

            // Installation successful
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenBlog Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c8c3c;
            border-bottom: 2px solid #2c8c3c;
            padding-bottom: 10px;
        }
        .error {
            color: #e74c3c;
            background-color: #fadbd8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
            background-color: #d4efdf;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #2c8c3c;
            color: white;
            border: none;
            padding: 10px 15px;
            margin-top: 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1e6e2e;
        }
    </style>
</head>
<body>
    <h1>GreenBlog Setup</h1>

    <?php if ($success): ?>
        <div class="success">
            <p>Installation successful! Your GreenBlog is now ready to use.</p>
            <p><a href="admin/login.php">Click here to login</a> to your new blog.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <h2>Site Information</h2>

            <label for="site_name">Site Name:</label>
            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? ''); ?>" required>

            <label for="site_description">Site Description:</label>
            <input type="text" id="site_description" name="site_description" value="<?php echo htmlspecialchars($_POST['site_description'] ?? ''); ?>" required>

            <label for="site_url">Site URL:</label>
            <input type="text" id="site_url" name="site_url" value="<?php echo htmlspecialchars($_POST['site_url'] ?? 'http://' . $_SERVER['HTTP_HOST']); ?>" required>

            <h2>Admin Account</h2>

            <label for="admin_username">Username:</label>
            <input type="text" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ''); ?>" required>

            <label for="admin_password">Password:</label>
            <input type="password" id="admin_password" name="admin_password" required>

            <label for="admin_email">Email:</label>
            <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>

            <button type="submit">Install GreenBlog</button>
        </form>
    <?php endif; ?>
</body>
</html>

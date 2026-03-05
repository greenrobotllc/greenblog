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

// Check if Composer dependencies are installed
$composerAutoloadExists = file_exists(BASE_PATH . '/vendor/autoload.php');

// Check if already installed
if ($composerAutoloadExists && file_exists(BASE_PATH . '/data/greenblog.db') && file_exists(BASE_PATH . '/includes/config.php')) {
    // Check if config file indicates installation is complete
    include BASE_PATH . '/includes/config.php';
    if (defined('INSTALLED') && INSTALLED === true) {
        die('GreenBlog is already installed. If you want to reinstall, please delete the data/greenblog.db file and the includes/config.php file.');
    }
}

// Run requirements checks
$requirements = [];

// PHP version
$phpVersionOk = version_compare(PHP_VERSION, '7.4.0', '>=');
$requirements[] = [
    'name' => 'PHP Version',
    'required' => '>= 7.4',
    'current' => PHP_VERSION,
    'passed' => $phpVersionOk,
];

// SQLite3 extension
$sqliteOk = extension_loaded('sqlite3');
$requirements[] = [
    'name' => 'SQLite3 Extension',
    'required' => 'Enabled',
    'current' => $sqliteOk ? 'Enabled' : 'Not installed',
    'passed' => $sqliteOk,
];

// PDO SQLite extension (optional)
$pdoSqliteOk = extension_loaded('pdo_sqlite');
$requirements[] = [
    'name' => 'PDO SQLite Extension',
    'required' => 'Optional',
    'current' => $pdoSqliteOk ? 'Enabled' : 'Not installed',
    'passed' => $pdoSqliteOk,
    'optional' => true,
];

// mbstring extension
$mbstringOk = extension_loaded('mbstring');
$requirements[] = [
    'name' => 'mbstring Extension',
    'required' => 'Enabled',
    'current' => $mbstringOk ? 'Enabled' : 'Not installed',
    'passed' => $mbstringOk,
    'optional' => true,
];

// Composer dependencies
$requirements[] = [
    'name' => 'Composer Dependencies',
    'required' => 'Installed',
    'current' => $composerAutoloadExists ? 'Installed' : 'Not installed — run: composer install',
    'passed' => $composerAutoloadExists,
];

// Helper to check a directory's writability/creatability for the requirements table
$webUser = function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? 'web server') : 'web server';
$fixCommands = [];
function checkDirRequirement($path, $name) {
    global $webUser, $fixCommands;
    $isDir = is_dir($path);
    $blockedByFile = !$isDir && file_exists($path);
    $writable = $isDir && is_writable($path);
    $passed = false;

    if ($blockedByFile) {
        $current = 'A file exists at this path — please remove it';
        $fixCommands[] = 'rm ' . $path;
    } elseif (!$isDir) {
        // Directory doesn't exist — try to create it now
        if (@mkdir($path, 0755, true)) {
            $current = 'Created successfully';
            $passed = true;
        } else {
            $current = 'Could not be created automatically';
            $fixCommands[] = 'sudo mkdir -p ' . $path . ' && sudo chown ' . $webUser . ' ' . $path;
        }
    } else {
        if ($writable) {
            $current = 'Writable';
            $passed = true;
        } else {
            $current = 'Not writable';
            $fixCommands[] = 'sudo chown ' . $webUser . ' ' . $path;
        }
    }

    return [
        'name' => $name,
        'required' => 'Writable',
        'current' => $current,
        'passed' => $passed,
    ];
}

$requirements[] = checkDirRequirement(BASE_PATH . '/data', 'Data Directory Writable');
$requirements[] = checkDirRequirement(BASE_PATH . '/includes', 'Includes Directory Writable');
$requirements[] = checkDirRequirement(__DIR__ . '/uploads', 'Uploads Directory Writable');
$requirements[] = checkDirRequirement(__DIR__ . '/static', 'Static Directory Writable');

// Determine if all required checks pass
$allRequiredPassed = true;
foreach ($requirements as $req) {
    if (empty($req['optional']) && !$req['passed']) {
        $allRequiredPassed = false;
        break;
    }
}

// Process form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-side readiness guard: abort if required checks have not passed
    if (!$allRequiredPassed) {
        http_response_code(400);
        $errors[] = 'Installation prerequisites are not met. Please resolve all failed requirements before installing.';
    }

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
            $dataPath = BASE_PATH . '/data';
            if (file_exists($dataPath) && !is_dir($dataPath)) {
                throw new Exception("Path exists but is not a directory: " . $dataPath);
            }
            if (!is_dir($dataPath)) {
                if (!mkdir($dataPath, 0755, true)) {
                    throw new Exception("Failed to create data directory: " . $dataPath);
                }
            }

            // Ensure data directory is writable
            if (!is_writable($dataPath)) {
                throw new Exception("Data directory is not writable: " . $dataPath . ". Please run: chmod 775 " . $dataPath);
            }

            // Create uploads directory if it doesn't exist
            $uploadsPath = __DIR__ . '/uploads';
            if (file_exists($uploadsPath) && !is_dir($uploadsPath)) {
                throw new Exception("Path exists but is not a directory: " . $uploadsPath);
            }
            if (!is_dir($uploadsPath)) {
                if (!mkdir($uploadsPath, 0755, true)) {
                    throw new Exception("Failed to create uploads directory: " . $uploadsPath);
                }
            }

            // Create static directory if it doesn't exist
            $staticPath = __DIR__ . '/static';
            if (file_exists($staticPath) && !is_dir($staticPath)) {
                throw new Exception("Path exists but is not a directory: " . $staticPath);
            }
            if (!is_dir($staticPath)) {
                if (!mkdir($staticPath, 0755, true)) {
                    throw new Exception("Failed to create static directory: " . $staticPath);
                }
            }

            // Check if SQLite3 extension is loaded
            if (!extension_loaded('sqlite3')) {
                throw new Exception("SQLite3 extension is not loaded. Please enable it in your PHP configuration.");
            }

            // Include ADODB
            require_once BASE_PATH . '/vendor/adodb/adodb-php/adodb.inc.php';

            // Create database connection
            $conn = ADONewConnection('sqlite3');
            if (!$conn) {
                throw new Exception("Failed to create ADONewConnection with sqlite3 driver");
            }

            $dbPath = BASE_PATH . '/data/greenblog.db';

            if (!$conn->Connect($dbPath)) {
                throw new Exception("Unable to open database: " . $conn->ErrorMsg());
            }

            // Helper to execute SQL with error checking
            $execSql = function ($sql, $params = false) use ($conn) {
                $result = $conn->Execute($sql, $params);
                if ($result === false) {
                    throw new Exception("SQL error: " . $conn->ErrorMsg());
                }
                return $result;
            };

            // Run all DB operations inside a transaction
            $conn->BeginTrans();

            try {
                // Create tables
                $execSql("
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

                $execSql("
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

                $execSql("
                    CREATE TABLE IF NOT EXISTS categories (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        slug TEXT NOT NULL UNIQUE,
                        description TEXT
                    )
                ");

                $execSql("
                    CREATE TABLE IF NOT EXISTS post_categories (
                        post_id INTEGER NOT NULL,
                        category_id INTEGER NOT NULL,
                        PRIMARY KEY (post_id, category_id),
                        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                    )
                ");

                $execSql("
                    CREATE TABLE IF NOT EXISTS settings (
                        key TEXT PRIMARY KEY,
                        value TEXT NOT NULL
                    )
                ");

                // Create admin user
                $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $execSql(
                    "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')",
                    [$adminUsername, $hashedPassword, $adminEmail]
                );

                // Create default category
                $execSql(
                    "INSERT INTO categories (name, slug, description) VALUES ('Uncategorized', 'uncategorized', 'Default category')"
                );

                // Create settings
                $execSql("INSERT INTO settings (key, value) VALUES ('site_title', ?)", [$siteName]);
                $execSql("INSERT INTO settings (key, value) VALUES ('site_description', ?)", [$siteDescription]);
                $execSql("INSERT INTO settings (key, value) VALUES ('site_url', ?)", [$siteUrl]);
                $execSql("INSERT INTO settings (key, value) VALUES ('admin_email', ?)", [$adminEmail]);
                $execSql("INSERT INTO settings (key, value) VALUES ('posts_per_page', '10')");
                $execSql("INSERT INTO settings (key, value) VALUES ('excerpt_length', '150')");

                // Ensure includes directory exists
                $includesPath = BASE_PATH . '/includes';
                if (!is_dir($includesPath)) {
                    if (!mkdir($includesPath, 0755, true)) {
                        throw new Exception("Failed to create includes directory: " . $includesPath);
                    }
                }

                // Create config file
                $configPath = $includesPath . '/config.php';
                $escapedSiteTitle = addslashes($siteName);
                $escapedSiteDesc = addslashes($siteDescription);
                $escapedSiteUrl = addslashes($siteUrl);
                $escapedAdminEmail = addslashes($adminEmail);

                $configContent = <<<PHP
<?php
/**
 * GreenBlog Configuration File
 *
 * Generated during setup. Do not edit manually.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

// Database configuration
define('DB_PATH', __DIR__ . '/../data/greenblog.db');
define('DB_TYPE', 'sqlite3');

// Site configuration
define('SITE_TITLE', '{$escapedSiteTitle}');
define('SITE_DESCRIPTION', '{$escapedSiteDesc}');
define('SITE_URL', '{$escapedSiteUrl}');
define('ADMIN_EMAIL', '{$escapedAdminEmail}');

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
define('CACHE_DURATION', 3600);

// Security settings
define('HASH_ALGO', PASSWORD_BCRYPT);
define('SESSION_DURATION', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300);

// Installation status
define('INSTALLED', true);

// Version
define('VERSION', '1.0.0');
PHP;

                if (file_put_contents($configPath, $configContent) === false) {
                    throw new Exception("Failed to write config file: " . $configPath);
                }

                // Only commit after config file is successfully written
                if (!$conn->CommitTrans()) {
                    throw new Exception("Failed to commit setup transaction");
                }
            } catch (Exception $e) {
                $conn->RollbackTrans();
                // Clean up config file if it was written before the error
                if (isset($configPath) && file_exists($configPath)) {
                    unlink($configPath);
                }
                throw $e;
            }

            // Installation successful
            $success = true;
        } catch (Exception $e) {
            error_log('GreenBlog setup failed: ' . $e->__toString());
            $errors[] = 'Installation failed. Please check the server logs or contact support.';
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
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .requirements-table th,
        .requirements-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .requirements-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .status-pass {
            color: #27ae60;
            font-weight: bold;
        }
        .status-fail {
            color: #e74c3c;
            font-weight: bold;
            white-space: nowrap;
        }
        .requirements-summary {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .requirements-ok {
            color: #27ae60;
            background-color: #d4efdf;
        }
        .requirements-fail {
            color: #e74c3c;
            background-color: #fadbd8;
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
        <h2>System Requirements</h2>
        <table class="requirements-table">
            <thead>
                <tr>
                    <th>Requirement</th>
                    <th>Required</th>
                    <th>Current</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requirements as $req): ?>
                <tr>
                    <td><?php echo htmlspecialchars($req['name']); ?></td>
                    <td><?php echo htmlspecialchars($req['required']); ?></td>
                    <td><?php echo htmlspecialchars($req['current']); ?></td>
                    <td>
                        <?php if ($req['passed']): ?>
                            <span class="status-pass">Pass</span>
                        <?php else: ?>
                            <span class="status-fail">Action Required</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$allRequiredPassed): ?>
            <div class="requirements-summary requirements-fail">
                <p>Some checks need attention. Run this command in your terminal, then refresh the page:</p>
                <?php if (!empty($fixCommands)): ?>
                <?php $fixCommand = implode(" && \\\n", $fixCommands); ?>
                <div style="position:relative;margin-top:8px">
                    <pre id="fix-command" style="background:#2d2d2d;color:#f8f8f2;padding:12px 50px 12px 12px;border-radius:4px;overflow-x:auto;font-size:13px;margin:0"><?php echo htmlspecialchars($fixCommand); ?></pre>
                    <button onclick="copyFixCommand()" id="copy-btn" style="position:absolute;top:8px;right:8px;background:#555;color:#fff;border:none;padding:4px 10px;border-radius:3px;cursor:pointer;font-size:12px">Copy</button>
                </div>
                <script>
                function copyFixCommand() {
                    var text = document.getElementById('fix-command').textContent;
                    var btn = document.getElementById('copy-btn');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(onCopied);
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.position = 'fixed';
                        ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        onCopied();
                    }
                    function onCopied() {
                        btn.textContent = 'Copied!';
                        btn.style.background = '#27ae60';
                        setTimeout(function() { btn.textContent = 'Copy'; btn.style.background = '#555'; }, 2000);
                    }
                }
                </script>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="requirements-summary requirements-ok">
                <p>All requirements met. You are ready to install GreenBlog.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($allRequiredPassed): ?>
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
            <input type="password" id="admin_password" name="admin_password" autocomplete="new-password" required>

            <label for="admin_email">Email:</label>
            <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>

            <button type="submit">Install GreenBlog</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

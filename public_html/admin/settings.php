<?php
/**
 * GreenBlog Admin Settings
 * 
 * This file handles site settings management.
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
require_once INCLUDES_DIR . '/static-generator.php';

// Start session
startSecureSession();

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Set page title
$pageTitle = 'Settings';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid form submission', 'error');
        redirect('settings.php');
    }
    
    // Handle settings update
    handleUpdateSettings();
}

// Get current settings
$settings = getSettings();

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';
?>

<div class="content-header">
    <h1>Settings</h1>
</div>

<form method="post" action="settings.php" class="settings-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    
    <div class="settings-section">
        <h2>Site Information</h2>
        
        <div class="form-group">
            <label for="site_title">Site Title:</label>
            <input type="text" id="site_title" name="settings[site_title]" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="site_description">Site Description:</label>
            <textarea id="site_description" name="settings[site_description]" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="site_url">Site URL:</label>
            <input type="url" id="site_url" name="settings[site_url]" value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>" required>
        </div>
    </div>
    
    <div class="settings-section">
        <h2>Content Settings</h2>
        
        <div class="form-group">
            <label for="posts_per_page">Posts Per Page:</label>
            <input type="number" id="posts_per_page" name="settings[posts_per_page]" value="<?php echo htmlspecialchars($settings['posts_per_page'] ?? '10'); ?>" min="1" max="50" required>
        </div>
        
        <div class="form-group">
            <label for="excerpt_length">Excerpt Length:</label>
            <input type="number" id="excerpt_length" name="settings[excerpt_length]" value="<?php echo htmlspecialchars($settings['excerpt_length'] ?? '150'); ?>" min="50" max="500" required>
            <p class="field-help">Maximum number of characters for automatically generated excerpts.</p>
        </div>
    </div>
    
    <div class="settings-section">
        <h2>User Settings</h2>
        
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password">
            <p class="field-help">Required only if you want to change your password.</p>
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        
        <div class="form-group">
            <label for="admin_email">Admin Email:</label>
            <input type="email" id="admin_email" name="settings[admin_email]" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" required>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="button">Save Settings</button>
        <a href="index.php" class="button button-secondary">Cancel</a>
    </div>
</form>

<style>
    .settings-section {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .settings-section h2 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #2c8c3c;
    }
</style>

<?php
// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';

/**
 * Get all settings from database
 * 
 * @return array Settings as key-value pairs
 */
function getSettings() {
    $settings = [];
    
    // Get settings from database
    $rows = getRows("SELECT key, value FROM settings");
    
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }
    
    return $settings;
}

/**
 * Handle updating settings
 */
function handleUpdateSettings() {
    // Get settings from form
    $newSettings = $_POST['settings'] ?? [];
    
    // Validate required settings
    if (empty($newSettings['site_title'])) {
        setFlashMessage('Site title is required', 'error');
        redirect('settings.php');
    }
    
    if (empty($newSettings['site_url']) || !filter_var($newSettings['site_url'], FILTER_VALIDATE_URL)) {
        setFlashMessage('Valid site URL is required', 'error');
        redirect('settings.php');
    }
    
    if (empty($newSettings['admin_email']) || !filter_var($newSettings['admin_email'], FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('Valid admin email is required', 'error');
        redirect('settings.php');
    }
    
    // Validate numeric settings
    $newSettings['posts_per_page'] = max(1, min(50, (int)($newSettings['posts_per_page'] ?? 10)));
    $newSettings['excerpt_length'] = max(50, min(500, (int)($newSettings['excerpt_length'] ?? 150)));
    
    // Get current settings
    $currentSettings = getSettings();
    
    // Update settings in database
    $conn = getDbConnection();
    
    foreach ($newSettings as $key => $value) {
        if (isset($currentSettings[$key])) {
            // Update existing setting
            updateRecord('settings', ['value' => $value], 'key = ?', [$key]);
        } else {
            // Insert new setting
            insertRecord('settings', ['key' => $key, 'value' => $value]);
        }
    }
    
    // Handle password change
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if (!empty($currentPassword) && !empty($newPassword)) {
        // Validate password match
        if ($newPassword !== $confirmPassword) {
            setFlashMessage('New passwords do not match', 'error');
            redirect('settings.php');
        }
        
        // Validate password length
        if (strlen($newPassword) < 8) {
            setFlashMessage('New password must be at least 8 characters', 'error');
            redirect('settings.php');
        }
        
        // Verify current password
        $userId = getCurrentUserId();
        $user = getRow("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !verifyPassword($currentPassword, $user['password'])) {
            setFlashMessage('Current password is incorrect', 'error');
            redirect('settings.php');
        }
        
        // Update password
        $hashedPassword = hashPassword($newPassword);
        updateRecord('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
    }
    
    // Update config file
    updateConfigFile($newSettings);
    
    // Regenerate static files
    generateAllStaticFiles();
    
    setFlashMessage('Settings updated successfully', 'success');
    redirect('settings.php');
}

/**
 * Update config.php file with new settings
 * 
 * @param array $settings New settings
 */
function updateConfigFile($settings) {
    $configFile = INCLUDES_DIR . '/config.php';
    $configContent = file_get_contents($configFile);
    
    // Update site title
    if (isset($settings['site_title'])) {
        $configContent = preg_replace(
            "/define\('SITE_TITLE', '(.*)'\);/",
            "define('SITE_TITLE', '" . addslashes($settings['site_title']) . "');",
            $configContent
        );
    }
    
    // Update site description
    if (isset($settings['site_description'])) {
        $configContent = preg_replace(
            "/define\('SITE_DESCRIPTION', '(.*)'\);/",
            "define('SITE_DESCRIPTION', '" . addslashes($settings['site_description']) . "');",
            $configContent
        );
    }
    
    // Update site URL
    if (isset($settings['site_url'])) {
        $configContent = preg_replace(
            "/define\('SITE_URL', '(.*)'\);/",
            "define('SITE_URL', '" . addslashes($settings['site_url']) . "');",
            $configContent
        );
    }
    
    // Update admin email
    if (isset($settings['admin_email'])) {
        $configContent = preg_replace(
            "/define\('ADMIN_EMAIL', '(.*)'\);/",
            "define('ADMIN_EMAIL', '" . addslashes($settings['admin_email']) . "');",
            $configContent
        );
    }
    
    // Update posts per page
    if (isset($settings['posts_per_page'])) {
        $configContent = preg_replace(
            "/define\('POSTS_PER_PAGE', (\d+)\);/",
            "define('POSTS_PER_PAGE', " . (int)$settings['posts_per_page'] . ");",
            $configContent
        );
    }
    
    // Write updated config file
    file_put_contents($configFile, $configContent);
}

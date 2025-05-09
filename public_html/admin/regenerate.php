<?php
/**
 * GreenBlog Regenerate Static Files
 * 
 * This file handles regeneration of all static files.
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

// Set page title
$pageTitle = 'Regenerate Static Files';

// Process regeneration
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission';
    } else {
        // Regenerate all static files
        if (generateAllStaticFiles()) {
            $success = true;
            setFlashMessage('Static files regenerated successfully', 'success');
            redirect('index.php');
        } else {
            $error = 'Failed to regenerate static files';
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';
?>

<h1>Regenerate Static Files</h1>

<?php if (!empty($error)): ?>
    <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="message message-success">Static files regenerated successfully.</div>
<?php endif; ?>

<p>
    This will regenerate all static HTML files for your blog, including:
</p>

<ul>
    <li>Homepage and pagination pages</li>
    <li>Individual post pages</li>
    <li>Category pages</li>
    <li>Archive pages</li>
    <li>RSS feed</li>
</ul>

<p>
    This process may take a few moments depending on the size of your blog.
</p>

<form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    
    <button type="submit" class="button">Regenerate All Static Files</button>
    <a href="index.php" class="button button-secondary">Cancel</a>
</form>

<?php
// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';
?>

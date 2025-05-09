<?php
/**
 * GreenBlog Admin Media
 * 
 * This file handles media management (upload, list, delete).
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

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Determine action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$pageTitle = 'Media';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid form submission', 'error');
        redirect('media.php');
    }
    
    // Handle media actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                handleMediaUpload();
                break;
                
            case 'delete':
                handleMediaDelete();
                break;
        }
    }
}

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';

// Display appropriate content based on action
switch ($action) {
    case 'upload':
        displayUploadForm();
        break;
        
    default:
        displayMediaList();
        break;
}

// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';

/**
 * Display list of media files
 */
function displayMediaList() {
    global $csrfToken;
    
    // Get all files in uploads directory
    $mediaFiles = [];
    
    if (file_exists(UPLOADS_DIR) && is_dir(UPLOADS_DIR)) {
        $files = scandir(UPLOADS_DIR);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = UPLOADS_DIR . '/' . $file;
                
                if (is_file($filePath)) {
                    $mediaFiles[] = [
                        'name' => $file,
                        'path' => '/uploads/' . $file,
                        'size' => filesize($filePath),
                        'date' => filemtime($filePath),
                        'type' => mime_content_type($filePath)
                    ];
                }
            }
        }
    }
    
    // Sort by date (newest first)
    usort($mediaFiles, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    ?>
    <div class="content-header">
        <h1>Media</h1>
        <a href="media.php?action=upload" class="button">Upload New</a>
    </div>
    
    <?php if (empty($mediaFiles)): ?>
        <p>No media files yet. <a href="media.php?action=upload">Upload your first file</a>.</p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($mediaFiles as $file): ?>
                <div class="media-item">
                    <?php if (strpos($file['type'], 'image/') === 0): ?>
                        <div class="media-preview">
                            <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="<?php echo htmlspecialchars($file['name']); ?>">
                        </div>
                    <?php else: ?>
                        <div class="media-preview media-file">
                            <span class="file-icon"><?php echo strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="media-info">
                        <div class="media-name" title="<?php echo htmlspecialchars($file['name']); ?>">
                            <?php echo htmlspecialchars($file['name']); ?>
                        </div>
                        
                        <div class="media-meta">
                            <?php echo formatFileSize($file['size']); ?> &bull;
                            <?php echo date('M j, Y', $file['date']); ?>
                        </div>
                        
                        <div class="media-actions">
                            <a href="<?php echo htmlspecialchars($file['path']); ?>" class="action-link" target="_blank">View</a>
                            <button class="action-link copy-link" data-url="<?php echo htmlspecialchars($file['path']); ?>">Copy URL</button>
                            <form method="post" action="media.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file['name']); ?>">
                                <button type="submit" class="action-link delete-link">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
            // Copy URL to clipboard
            document.querySelectorAll('.copy-link').forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = this.dataset.url;
                    const fullUrl = window.location.origin + url;
                    
                    // Create temporary input element
                    const input = document.createElement('input');
                    input.value = fullUrl;
                    document.body.appendChild(input);
                    
                    // Select and copy
                    input.select();
                    document.execCommand('copy');
                    
                    // Remove temporary element
                    document.body.removeChild(input);
                    
                    // Show feedback
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            });
        </script>
        
        <style>
            .media-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .media-item {
                background-color: white;
                border-radius: 5px;
                overflow: hidden;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            
            .media-preview {
                height: 150px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f5f5f5;
                overflow: hidden;
            }
            
            .media-preview img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }
            
            .media-file {
                background-color: #e0e0e0;
            }
            
            .file-icon {
                font-size: 24px;
                font-weight: bold;
                color: #666;
            }
            
            .media-info {
                padding: 10px;
            }
            
            .media-name {
                font-weight: bold;
                margin-bottom: 5px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .media-meta {
                font-size: 12px;
                color: #666;
                margin-bottom: 10px;
            }
            
            .media-actions {
                display: flex;
                justify-content: space-between;
            }
            
            .inline-form {
                display: inline;
            }
            
            .action-link {
                background: none;
                border: none;
                color: #2c8c3c;
                cursor: pointer;
                font-size: 14px;
                padding: 0;
                text-decoration: underline;
            }
            
            .action-link:hover {
                color: #1e6e2e;
            }
            
            .delete-link {
                color: #e74c3c;
            }
            
            .delete-link:hover {
                color: #c0392b;
            }
        </style>
    <?php endif; ?>
    <?php
}

/**
 * Display upload form
 */
function displayUploadForm() {
    global $csrfToken;
    
    ?>
    <div class="content-header">
        <h1>Upload Media</h1>
    </div>
    
    <form method="post" action="media.php" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="upload">
        
        <div class="form-group">
            <label for="file">Select File:</label>
            <input type="file" id="file" name="file" required>
            <p class="field-help">Allowed file types: JPG, PNG, GIF. Maximum file size: 2MB.</p>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button">Upload</button>
            <a href="media.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
    <?php
}

/**
 * Handle media upload
 */
function handleMediaUpload() {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'File upload failed';
        
        // Get more specific error message
        if (isset($_FILES['file'])) {
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File is too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded';
                    break;
            }
        }
        
        setFlashMessage($errorMessage, 'error');
        redirect('media.php?action=upload');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $_FILES['file']['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        setFlashMessage('Invalid file type. Only JPG, PNG, and GIF files are allowed.', 'error');
        redirect('media.php?action=upload');
    }
    
    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if ($_FILES['file']['size'] > $maxSize) {
        setFlashMessage('File is too large. Maximum size is 2MB.', 'error');
        redirect('media.php?action=upload');
    }
    
    // Create uploads directory if it doesn't exist
    if (!file_exists(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }
    
    // Generate safe filename
    $originalName = $_FILES['file']['name'];
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = preg_replace('/[^a-z0-9_-]/i', '_', $baseName);
    $timestamp = date('YmdHis');
    $filename = $baseName . '_' . $timestamp . '.' . $extension;
    
    // Move uploaded file
    $destination = UPLOADS_DIR . '/' . $filename;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        setFlashMessage('Failed to save uploaded file', 'error');
        redirect('media.php?action=upload');
    }
    
    setFlashMessage('File uploaded successfully', 'success');
    redirect('media.php');
}

/**
 * Handle media deletion
 */
function handleMediaDelete() {
    // Get filename
    $filename = trim($_POST['file_name'] ?? '');
    
    if (empty($filename)) {
        setFlashMessage('No file specified', 'error');
        redirect('media.php');
    }
    
    // Validate filename (prevent directory traversal)
    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        setFlashMessage('Invalid filename', 'error');
        redirect('media.php');
    }
    
    // Check if file exists
    $filePath = UPLOADS_DIR . '/' . $filename;
    if (!file_exists($filePath) || !is_file($filePath)) {
        setFlashMessage('File not found', 'error');
        redirect('media.php');
    }
    
    // Delete file
    if (!unlink($filePath)) {
        setFlashMessage('Failed to delete file', 'error');
        redirect('media.php');
    }
    
    setFlashMessage('File deleted successfully', 'success');
    redirect('media.php');
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 1) . ' ' . $units[$pow];
}

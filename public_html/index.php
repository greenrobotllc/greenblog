<?php
/**
 * GreenBlog Front Controller
 * 
 * This file serves as the front controller for the GreenBlog system.
 * It handles routing and serves static files when available.
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Define GREENBLOG constant to allow includes
define('GREENBLOG', true);

// Check if installation is complete
if (!file_exists(BASE_PATH . '/data/greenblog.db') || !file_exists(BASE_PATH . '/includes/config.php')) {
    // Redirect to setup
    header('Location: setup.php');
    exit;
}

// Include configuration
require_once BASE_PATH . '/includes/config.php';

// Include required files
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/static-generator.php';

// Start session
startSecureSession();

// Determine what to display
$route = determineRoute();

// Check if static file exists
$staticFile = getStaticFilePath($route);

if (file_exists($staticFile)) {
    // Serve static file
    include $staticFile;
    exit;
} else {
    // Generate static file if it doesn't exist
    generateStaticFile($route);
    
    // Check if static file was generated
    if (file_exists($staticFile)) {
        // Serve static file
        include $staticFile;
        exit;
    } else {
        // Display 404 error
        header('HTTP/1.0 404 Not Found');
        include TEMPLATES_DIR . '/404.template.php';
        exit;
    }
}

/**
 * Determine the current route
 * 
 * @return array Route information
 */
function determineRoute() {
    $route = [
        'type' => 'home',
        'page' => 1
    ];
    
    // Check for post
    if (isset($_GET['post'])) {
        $route['type'] = 'post';
        $route['slug'] = sanitizeInput($_GET['post']);
    }
    // Check for category
    elseif (isset($_GET['category'])) {
        $route['type'] = 'category';
        $route['slug'] = sanitizeInput($_GET['category']);
    }
    // Check for archive
    elseif (isset($_GET['year'])) {
        $route['type'] = 'archive';
        $route['year'] = (int)$_GET['year'];
        
        if (isset($_GET['month'])) {
            $route['month'] = (int)$_GET['month'];
        }
    }
    // Check for pagination
    elseif (isset($_GET['page'])) {
        $route['type'] = 'home';
        $route['page'] = max(1, (int)$_GET['page']);
    }
    
    return $route;
}

/**
 * Get the path to the static file for the current route
 * 
 * @param array $route Route information
 * @return string Path to static file
 */
function getStaticFilePath($route) {
    switch ($route['type']) {
        case 'post':
            return STATIC_DIR . '/' . $route['slug'] . '/index.html';
        
        case 'category':
            return STATIC_DIR . '/category/' . $route['slug'] . '/index.html';
        
        case 'archive':
            if (isset($route['month'])) {
                return STATIC_DIR . '/archive/' . $route['year'] . '/' . 
                       str_pad($route['month'], 2, '0', STR_PAD_LEFT) . '/index.html';
            } else {
                return STATIC_DIR . '/archive/' . $route['year'] . '/index.html';
            }
        
        case 'home':
            if ($route['page'] === 1) {
                return STATIC_DIR . '/index.html';
            } else {
                return STATIC_DIR . '/page/' . $route['page'] . '/index.html';
            }
        
        default:
            return STATIC_DIR . '/index.html';
    }
}

/**
 * Generate a static file for the current route
 * 
 * @param array $route Route information
 * @return bool True on success, false on failure
 */
function generateStaticFile($route) {
    switch ($route['type']) {
        case 'post':
            // Get post data
            $post = getRow(
                "SELECT p.*, u.username as author_name 
                 FROM posts p 
                 JOIN users u ON p.author_id = u.id 
                 WHERE p.slug = ? AND p.status = 'published'",
                [$route['slug']]
            );
            
            if (!$post) {
                return false;
            }
            
            // Get post categories
            $categories = getRows(
                "SELECT c.* 
                 FROM categories c 
                 JOIN post_categories pc ON c.id = pc.category_id 
                 WHERE pc.post_id = ?",
                [$post['id']]
            );
            
            // Load template
            ob_start();
            $pageTitle = $post['title'] . ' - ' . SITE_TITLE;
            $pageDescription = getExcerpt($post['content']);
            
            include TEMPLATES_DIR . '/post.template.php';
            $content = ob_get_clean();
            
            // Create directory for post
            $postDir = STATIC_DIR . '/' . $post['slug'];
            if (!file_exists($postDir)) {
                mkdir($postDir, 0755, true);
            }
            
            // Save file
            return file_put_contents($postDir . '/index.html', $content) !== false;
        
        case 'category':
            // Get category data
            $category = getRow(
                "SELECT * FROM categories WHERE slug = ?",
                [$route['slug']]
            );
            
            if (!$category) {
                return false;
            }
            
            // Get posts in this category
            $posts = getRows(
                "SELECT p.*, u.username as author_name 
                 FROM posts p 
                 JOIN users u ON p.author_id = u.id 
                 JOIN post_categories pc ON p.id = pc.post_id 
                 WHERE pc.category_id = ? AND p.status = 'published' 
                 ORDER BY p.published_date DESC",
                [$category['id']]
            );
            
            // Load template
            ob_start();
            $pageTitle = $category['name'] . ' - ' . SITE_TITLE;
            $pageDescription = 'Posts in category: ' . $category['name'];
            
            include TEMPLATES_DIR . '/category.template.php';
            $content = ob_get_clean();
            
            // Create directory for category
            $categoryDir = STATIC_DIR . '/category/' . $category['slug'];
            if (!file_exists($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }
            
            // Save file
            return file_put_contents($categoryDir . '/index.html', $content) !== false;
        
        case 'archive':
            if (isset($route['month'])) {
                // Monthly archive
                $year = $route['year'];
                $month = $route['month'];
                
                // Get posts for this year/month
                $posts = getRows(
                    "SELECT p.*, u.username as author_name 
                     FROM posts p 
                     JOIN users u ON p.author_id = u.id 
                     WHERE p.status = 'published' 
                     AND strftime('%Y', p.published_date) = ? 
                     AND strftime('%m', p.published_date) = ? 
                     ORDER BY p.published_date DESC",
                    [$year, str_pad($month, 2, '0', STR_PAD_LEFT)]
                );
                
                // Load template
                ob_start();
                $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                $pageTitle = "$monthName $year - " . SITE_TITLE;
                $pageDescription = "Archive for $monthName $year";
                
                include TEMPLATES_DIR . '/archive.template.php';
                $content = ob_get_clean();
                
                // Create directory for archive
                $archiveDir = STATIC_DIR . '/archive/' . $year . '/' . str_pad($month, 2, '0', STR_PAD_LEFT);
                if (!file_exists($archiveDir)) {
                    mkdir($archiveDir, 0755, true);
                }
                
                // Save file
                return file_put_contents($archiveDir . '/index.html', $content) !== false;
            } else {
                // Yearly archive
                $year = $route['year'];
                
                // Get posts for this year
                $posts = getRows(
                    "SELECT p.*, u.username as author_name 
                     FROM posts p 
                     JOIN users u ON p.author_id = u.id 
                     WHERE p.status = 'published' 
                     AND strftime('%Y', p.published_date) = ? 
                     ORDER BY p.published_date DESC",
                    [$year]
                );
                
                // Load template
                ob_start();
                $pageTitle = "$year - " . SITE_TITLE;
                $pageDescription = "Archive for $year";
                
                include TEMPLATES_DIR . '/yearly-archive.template.php';
                $content = ob_get_clean();
                
                // Create directory for archive
                $archiveDir = STATIC_DIR . '/archive/' . $year;
                if (!file_exists($archiveDir)) {
                    mkdir($archiveDir, 0755, true);
                }
                
                // Save file
                return file_put_contents($archiveDir . '/index.html', $content) !== false;
            }
        
        case 'home':
            // Get total number of published posts
            $totalPosts = getRow("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")['count'];
            
            // Calculate total number of pages
            $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
            
            // Get posts for this page
            $offset = ($route['page'] - 1) * POSTS_PER_PAGE;
            $posts = getRows(
                "SELECT p.*, u.username as author_name 
                 FROM posts p 
                 JOIN users u ON p.author_id = u.id 
                 WHERE p.status = 'published' 
                 ORDER BY p.published_date DESC 
                 LIMIT ? OFFSET ?",
                [POSTS_PER_PAGE, $offset]
            );
            
            // Load template
            ob_start();
            $pageTitle = SITE_TITLE;
            $pageDescription = SITE_DESCRIPTION;
            $currentPage = $route['page'];
            
            include TEMPLATES_DIR . '/index.template.php';
            $content = ob_get_clean();
            
            // Save file
            if ($route['page'] === 1) {
                // First page is index.html
                return file_put_contents(STATIC_DIR . '/index.html', $content) !== false;
            } else {
                // Create directory for page
                $pageDir = STATIC_DIR . '/page/' . $route['page'];
                if (!file_exists($pageDir)) {
                    mkdir($pageDir, 0755, true);
                }
                
                // Save file
                return file_put_contents($pageDir . '/index.html', $content) !== false;
            }
        
        default:
            return false;
    }
}

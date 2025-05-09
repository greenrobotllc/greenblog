<?php
/**
 * GreenBlog Static File Generator
 * 
 * This file handles the generation of static HTML files for the blog.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

/**
 * Generate all static files
 * 
 * @return bool True on success, false on failure
 */
function generateAllStaticFiles() {
    try {
        // Create static directory if it doesn't exist
        if (!file_exists(STATIC_DIR)) {
            mkdir(STATIC_DIR, 0755, true);
        }
        
        // Generate index pages (homepage with pagination)
        generateIndexPages();
        
        // Generate individual post pages
        generatePostPages();
        
        // Generate category pages
        generateCategoryPages();
        
        // Generate archive pages
        generateArchivePages();
        
        // Generate RSS feed
        generateRssFeed();
        
        return true;
    } catch (Exception $e) {
        error_log('Static file generation error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate index pages with pagination
 * 
 * @return void
 */
function generateIndexPages() {
    // Get total number of published posts
    $totalPosts = getRow("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")['count'];
    
    // Calculate total number of pages
    $totalPages = ceil($totalPosts / POSTS_PER_PAGE);
    
    // Generate each page
    for ($page = 1; $page <= $totalPages; $page++) {
        $offset = ($page - 1) * POSTS_PER_PAGE;
        
        // Get posts for this page
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
        $currentPage = $page;
        $totalPages = $totalPages;
        
        include TEMPLATES_DIR . '/index.template.php';
        $content = ob_get_clean();
        
        // Save file
        if ($page === 1) {
            // First page is both index.html and page/1/index.html
            file_put_contents(STATIC_DIR . '/index.html', $content);
            
            // Create page/1 directory
            if (!file_exists(STATIC_DIR . '/page/1')) {
                mkdir(STATIC_DIR . '/page/1', 0755, true);
            }
            
            file_put_contents(STATIC_DIR . '/page/1/index.html', $content);
        } else {
            // Create page/N directory
            if (!file_exists(STATIC_DIR . '/page/' . $page)) {
                mkdir(STATIC_DIR . '/page/' . $page, 0755, true);
            }
            
            file_put_contents(STATIC_DIR . '/page/' . $page . '/index.html', $content);
        }
    }
}

/**
 * Generate individual post pages
 * 
 * @return void
 */
function generatePostPages() {
    // Get all published posts
    $posts = getRows(
        "SELECT p.*, u.username as author_name 
         FROM posts p 
         JOIN users u ON p.author_id = u.id 
         WHERE p.status = 'published'"
    );
    
    foreach ($posts as $post) {
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
        file_put_contents($postDir . '/index.html', $content);
    }
}

/**
 * Generate category pages
 * 
 * @return void
 */
function generateCategoryPages() {
    // Get all categories
    $categories = getRows("SELECT * FROM categories");
    
    foreach ($categories as $category) {
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
        
        if (count($posts) > 0) {
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
            file_put_contents($categoryDir . '/index.html', $content);
        }
    }
}

/**
 * Generate archive pages by year and month
 * 
 * @return void
 */
function generateArchivePages() {
    // Get all years and months with published posts
    $archives = getRows(
        "SELECT DISTINCT 
         strftime('%Y', published_date) as year, 
         strftime('%m', published_date) as month 
         FROM posts 
         WHERE status = 'published' 
         ORDER BY year DESC, month DESC"
    );
    
    foreach ($archives as $archive) {
        $year = $archive['year'];
        $month = $archive['month'];
        
        // Get posts for this year/month
        $posts = getRows(
            "SELECT p.*, u.username as author_name 
             FROM posts p 
             JOIN users u ON p.author_id = u.id 
             WHERE p.status = 'published' 
             AND strftime('%Y', p.published_date) = ? 
             AND strftime('%m', p.published_date) = ? 
             ORDER BY p.published_date DESC",
            [$year, $month]
        );
        
        // Load template
        ob_start();
        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
        $pageTitle = "$monthName $year - " . SITE_TITLE;
        $pageDescription = "Archive for $monthName $year";
        
        include TEMPLATES_DIR . '/archive.template.php';
        $content = ob_get_clean();
        
        // Create directory for archive
        $archiveDir = STATIC_DIR . '/archive/' . $year . '/' . $month;
        if (!file_exists($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        
        // Save file
        file_put_contents($archiveDir . '/index.html', $content);
    }
    
    // Generate yearly archive pages
    $years = getRows(
        "SELECT DISTINCT strftime('%Y', published_date) as year 
         FROM posts 
         WHERE status = 'published' 
         ORDER BY year DESC"
    );
    
    foreach ($years as $yearData) {
        $year = $yearData['year'];
        
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
        file_put_contents($archiveDir . '/index.html', $content);
    }
}

/**
 * Generate RSS feed
 * 
 * @return void
 */
function generateRssFeed() {
    // Get recent posts
    $posts = getRows(
        "SELECT p.*, u.username as author_name 
         FROM posts p 
         JOIN users u ON p.author_id = u.id 
         WHERE p.status = 'published' 
         ORDER BY p.published_date DESC 
         LIMIT 20"
    );
    
    // Generate RSS XML
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0">' . "\n";
    $rss .= '<channel>' . "\n";
    $rss .= '<title>' . htmlspecialchars(SITE_TITLE) . '</title>' . "\n";
    $rss .= '<link>' . htmlspecialchars(SITE_URL) . '</link>' . "\n";
    $rss .= '<description>' . htmlspecialchars(SITE_DESCRIPTION) . '</description>' . "\n";
    $rss .= '<language>en-us</language>' . "\n";
    $rss .= '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . "\n";
    
    foreach ($posts as $post) {
        $rss .= '<item>' . "\n";
        $rss .= '<title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
        $rss .= '<link>' . htmlspecialchars(SITE_URL . '/' . $post['slug']) . '</link>' . "\n";
        $rss .= '<description>' . htmlspecialchars(getExcerpt($post['content'], 300)) . '</description>' . "\n";
        $rss .= '<pubDate>' . date(DATE_RSS, strtotime($post['published_date'])) . '</pubDate>' . "\n";
        $rss .= '<guid>' . htmlspecialchars(SITE_URL . '/' . $post['slug']) . '</guid>' . "\n";
        $rss .= '<author>' . htmlspecialchars($post['author_name']) . '</author>' . "\n";
        $rss .= '</item>' . "\n";
    }
    
    $rss .= '</channel>' . "\n";
    $rss .= '</rss>';
    
    // Save RSS feed
    file_put_contents(STATIC_DIR . '/feed.xml', $rss);
}

<?php
/**
 * GreenBlog Admin Dashboard
 * 
 * This file displays the admin dashboard.
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

// Get dashboard statistics
$stats = [
    'posts' => getRow("SELECT COUNT(*) as count FROM posts")['count'],
    'published' => getRow("SELECT COUNT(*) as count FROM posts WHERE status = 'published'")['count'],
    'drafts' => getRow("SELECT COUNT(*) as count FROM posts WHERE status = 'draft'")['count'],
    'categories' => getRow("SELECT COUNT(*) as count FROM categories")['count']
];

// Get recent posts
$recentPosts = getRows(
    "SELECT p.*, u.username as author_name 
     FROM posts p 
     JOIN users u ON p.author_id = u.id 
     ORDER BY p.created_date DESC 
     LIMIT 5"
);

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h2><?php echo $stats['posts']; ?></h2>
            <p>Total Posts</p>
        </div>
        
        <div class="stat-card">
            <h2><?php echo $stats['published']; ?></h2>
            <p>Published</p>
        </div>
        
        <div class="stat-card">
            <h2><?php echo $stats['drafts']; ?></h2>
            <p>Drafts</p>
        </div>
        
        <div class="stat-card">
            <h2><?php echo $stats['categories']; ?></h2>
            <p>Categories</p>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <a href="posts.php?action=new" class="button">New Post</a>
        <a href="categories.php?action=new" class="button">New Category</a>
        <a href="regenerate.php" class="button">Regenerate Static Files</a>
    </div>
    
    <h2>Recent Posts</h2>
    
    <?php if (empty($recentPosts)): ?>
        <p>No posts yet. <a href="posts.php?action=new">Create your first post</a>.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($post['created_date']); ?></td>
                        <td class="actions">
                            <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" class="action-link">Edit</a>
                            <a href="../<?php echo $post['slug']; ?>" class="action-link" target="_blank">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="view-all">
            <a href="posts.php">View all posts</a>
        </p>
    <?php endif; ?>
</div>

<?php
// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';
?>

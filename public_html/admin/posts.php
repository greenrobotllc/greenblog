<?php
/**
 * GreenBlog Admin Posts
 * 
 * This file handles post management (list, create, edit, delete).
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

// Determine action
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$pageTitle = 'Posts';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid form submission', 'error');
        redirect('posts.php');
    }
    
    // Handle post actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                handleCreatePost();
                break;
                
            case 'update':
                handleUpdatePost();
                break;
                
            case 'delete':
                handleDeletePost();
                break;
        }
    }
}

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';

// Display appropriate content based on action
switch ($action) {
    case 'new':
        displayNewPostForm();
        break;
        
    case 'edit':
        displayEditPostForm();
        break;
        
    default:
        displayPostsList();
        break;
}

// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';

/**
 * Display list of posts
 */
function displayPostsList() {
    global $csrfToken;
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Get total post count
    $totalPosts = getRow("SELECT COUNT(*) as count FROM posts")['count'];
    $totalPages = ceil($totalPosts / $perPage);
    
    // Get posts for current page
    $posts = getRows(
        "SELECT p.*, u.username as author_name 
         FROM posts p 
         JOIN users u ON p.author_id = u.id 
         ORDER BY p.created_date DESC 
         LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );
    
    ?>
    <div class="content-header">
        <h1>Posts</h1>
        <a href="posts.php?action=new" class="button">New Post</a>
    </div>
    
    <?php if (empty($posts)): ?>
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
                <?php foreach ($posts as $post): ?>
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
                            <form method="post" action="posts.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="action-link delete-link">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="posts.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php
}

/**
 * Display form for creating a new post
 */
function displayNewPostForm() {
    global $csrfToken;
    
    // Get all categories
    $categories = getRows("SELECT * FROM categories ORDER BY name");
    
    ?>
    <div class="content-header">
        <h1>New Post</h1>
    </div>
    
    <form method="post" action="posts.php" class="post-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="content">Content:</label>
            <textarea id="content" name="content" class="wysiwyg-editor" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="excerpt">Excerpt (optional):</label>
            <textarea id="excerpt" name="excerpt"></textarea>
            <p class="field-help">If left empty, an excerpt will be automatically generated from the content.</p>
        </div>
        
        <div class="form-group">
            <label>Categories:</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="featured_image">Featured Image URL (optional):</label>
            <input type="text" id="featured_image" name="featured_image">
            <p class="field-help">Enter the URL of an image or upload one in the <a href="media.php" target="_blank">Media</a> section.</p>
        </div>
        
        <div class="form-group">
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button">Save Post</button>
            <a href="posts.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
    
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '.wysiwyg-editor',
            height: 500,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    </script>
    <?php
}

/**
 * Display form for editing an existing post
 */
function displayEditPostForm() {
    global $csrfToken;
    
    // Get post ID
    $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Get post data
    $post = getRow("SELECT * FROM posts WHERE id = ?", [$postId]);
    
    if (!$post) {
        setFlashMessage('Post not found', 'error');
        redirect('posts.php');
    }
    
    // Get all categories
    $categories = getRows("SELECT * FROM categories ORDER BY name");
    
    // Get post categories
    $postCategories = getRows(
        "SELECT category_id FROM post_categories WHERE post_id = ?",
        [$postId]
    );
    
    // Convert to simple array
    $selectedCategories = array_map(function($item) {
        return $item['category_id'];
    }, $postCategories);
    
    ?>
    <div class="content-header">
        <h1>Edit Post</h1>
    </div>
    
    <form method="post" action="posts.php" class="post-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="content">Content:</label>
            <textarea id="content" name="content" class="wysiwyg-editor" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="excerpt">Excerpt (optional):</label>
            <textarea id="excerpt" name="excerpt"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
            <p class="field-help">If left empty, an excerpt will be automatically generated from the content.</p>
        </div>
        
        <div class="form-group">
            <label>Categories:</label>
            <div class="checkbox-group">
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                               <?php echo in_array($category['id'], $selectedCategories) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="featured_image">Featured Image URL (optional):</label>
            <input type="text" id="featured_image" name="featured_image" value="<?php echo htmlspecialchars($post['featured_image'] ?? ''); ?>">
            <p class="field-help">Enter the URL of an image or upload one in the <a href="media.php" target="_blank">Media</a> section.</p>
        </div>
        
        <div class="form-group">
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button">Update Post</button>
            <a href="posts.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
    
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '.wysiwyg-editor',
            height: 500,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    </script>
    <?php
}

/**
 * Handle creating a new post
 */
function handleCreatePost() {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $featuredImage = trim($_POST['featured_image'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categories = $_POST['categories'] ?? [];
    
    // Validate data
    if (empty($title) || empty($content)) {
        setFlashMessage('Title and content are required', 'error');
        redirect('posts.php?action=new');
    }
    
    // Generate slug
    $slug = generateSlug($title);
    
    // Check if slug already exists
    $existingPost = getRow("SELECT id FROM posts WHERE slug = ?", [$slug]);
    if ($existingPost) {
        // Append a random string to make the slug unique
        $slug .= '-' . substr(md5(uniqid()), 0, 6);
    }
    
    // Generate excerpt if empty
    if (empty($excerpt)) {
        $excerpt = getExcerpt($content);
    }
    
    // Set published date if status is published
    $publishedDate = null;
    if ($status === 'published') {
        $publishedDate = date('Y-m-d H:i:s');
    }
    
    // Insert post
    $postId = insertRecord('posts', [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'excerpt' => $excerpt,
        'featured_image' => $featuredImage,
        'status' => $status,
        'author_id' => getCurrentUserId(),
        'published_date' => $publishedDate
    ]);
    
    if (!$postId) {
        setFlashMessage('Failed to create post', 'error');
        redirect('posts.php?action=new');
    }
    
    // Add categories
    foreach ($categories as $categoryId) {
        insertRecord('post_categories', [
            'post_id' => $postId,
            'category_id' => $categoryId
        ]);
    }
    
    // Regenerate static files if published
    if ($status === 'published') {
        generateAllStaticFiles();
    }
    
    setFlashMessage('Post created successfully', 'success');
    redirect('posts.php');
}

/**
 * Handle updating an existing post
 */
function handleUpdatePost() {
    // Get form data
    $postId = (int)$_POST['post_id'];
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $featuredImage = trim($_POST['featured_image'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $categories = $_POST['categories'] ?? [];
    
    // Validate data
    if (empty($title) || empty($content)) {
        setFlashMessage('Title and content are required', 'error');
        redirect('posts.php?action=edit&id=' . $postId);
    }
    
    // Get existing post
    $post = getRow("SELECT * FROM posts WHERE id = ?", [$postId]);
    if (!$post) {
        setFlashMessage('Post not found', 'error');
        redirect('posts.php');
    }
    
    // Check if status changed from draft to published
    $wasPublished = false;
    if ($post['status'] === 'draft' && $status === 'published') {
        $wasPublished = true;
    }
    
    // Generate excerpt if empty
    if (empty($excerpt)) {
        $excerpt = getExcerpt($content);
    }
    
    // Set published date if status is published and wasn't before
    $publishedDate = $post['published_date'];
    if ($status === 'published' && empty($publishedDate)) {
        $publishedDate = date('Y-m-d H:i:s');
    }
    
    // Update post
    $success = updateRecord('posts', [
        'title' => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'featured_image' => $featuredImage,
        'status' => $status,
        'updated_date' => date('Y-m-d H:i:s'),
        'published_date' => $publishedDate
    ], 'id = ?', [$postId]);
    
    if (!$success) {
        setFlashMessage('Failed to update post', 'error');
        redirect('posts.php?action=edit&id=' . $postId);
    }
    
    // Update categories
    // First, delete all existing categories for this post
    deleteRecord('post_categories', 'post_id = ?', [$postId]);
    
    // Then add the selected categories
    foreach ($categories as $categoryId) {
        insertRecord('post_categories', [
            'post_id' => $postId,
            'category_id' => $categoryId
        ]);
    }
    
    // Regenerate static files if published
    if ($status === 'published' || $wasPublished) {
        generateAllStaticFiles();
    }
    
    setFlashMessage('Post updated successfully', 'success');
    redirect('posts.php');
}

/**
 * Handle deleting a post
 */
function handleDeletePost() {
    // Get post ID
    $postId = (int)$_POST['post_id'];
    
    // Get post data
    $post = getRow("SELECT * FROM posts WHERE id = ?", [$postId]);
    
    if (!$post) {
        setFlashMessage('Post not found', 'error');
        redirect('posts.php');
    }
    
    // Delete post categories
    deleteRecord('post_categories', 'post_id = ?', [$postId]);
    
    // Delete post
    $success = deleteRecord('posts', 'id = ?', [$postId]);
    
    if (!$success) {
        setFlashMessage('Failed to delete post', 'error');
        redirect('posts.php');
    }
    
    // Regenerate static files if the post was published
    if ($post['status'] === 'published') {
        generateAllStaticFiles();
    }
    
    setFlashMessage('Post deleted successfully', 'success');
    redirect('posts.php');
}

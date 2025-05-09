<?php
/**
 * GreenBlog Admin Categories
 * 
 * This file handles category management (list, create, edit, delete).
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
$pageTitle = 'Categories';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid form submission', 'error');
        redirect('categories.php');
    }
    
    // Handle category actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                handleCreateCategory();
                break;
                
            case 'update':
                handleUpdateCategory();
                break;
                
            case 'delete':
                handleDeleteCategory();
                break;
        }
    }
}

// Include header
include TEMPLATES_DIR . '/admin/header.template.php';

// Display appropriate content based on action
switch ($action) {
    case 'new':
        displayNewCategoryForm();
        break;
        
    case 'edit':
        displayEditCategoryForm();
        break;
        
    default:
        displayCategoriesList();
        break;
}

// Include footer
include TEMPLATES_DIR . '/admin/footer.template.php';

/**
 * Display list of categories
 */
function displayCategoriesList() {
    global $csrfToken;
    
    // Get all categories
    $categories = getRows("SELECT * FROM categories ORDER BY name");
    
    // Get post counts for each category
    $categoryCounts = [];
    foreach ($categories as $category) {
        $count = getRow(
            "SELECT COUNT(*) as count FROM post_categories WHERE category_id = ?",
            [$category['id']]
        )['count'];
        
        $categoryCounts[$category['id']] = $count;
    }
    
    ?>
    <div class="content-header">
        <h1>Categories</h1>
        <a href="categories.php?action=new" class="button">New Category</a>
    </div>
    
    <?php if (empty($categories)): ?>
        <p>No categories yet. <a href="categories.php?action=new">Create your first category</a>.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                        <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                        <td><?php echo $categoryCounts[$category['id']]; ?></td>
                        <td class="actions">
                            <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="action-link">Edit</a>
                            <a href="../category/<?php echo $category['slug']; ?>" class="action-link" target="_blank">View</a>
                            <?php if ($category['slug'] !== 'uncategorized'): ?>
                                <form method="post" action="categories.php" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" class="action-link delete-link">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}

/**
 * Display form for creating a new category
 */
function displayNewCategoryForm() {
    global $csrfToken;
    
    ?>
    <div class="content-header">
        <h1>New Category</h1>
    </div>
    
    <form method="post" action="categories.php" class="category-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="slug">Slug (optional):</label>
            <input type="text" id="slug" name="slug">
            <p class="field-help">If left empty, a slug will be automatically generated from the name.</p>
        </div>
        
        <div class="form-group">
            <label for="description">Description (optional):</label>
            <textarea id="description" name="description"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button">Save Category</button>
            <a href="categories.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
    
    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const nameField = this;
            const slugField = document.getElementById('slug');
            
            // Only update slug if it's empty or hasn't been manually edited
            if (slugField.value === '' || slugField.dataset.autoGenerated === 'true') {
                const slug = nameField.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                
                slugField.value = slug;
                slugField.dataset.autoGenerated = 'true';
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
    </script>
    <?php
}

/**
 * Display form for editing an existing category
 */
function displayEditCategoryForm() {
    global $csrfToken;
    
    // Get category ID
    $categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Get category data
    $category = getRow("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    
    if (!$category) {
        setFlashMessage('Category not found', 'error');
        redirect('categories.php');
    }
    
    ?>
    <div class="content-header">
        <h1>Edit Category</h1>
    </div>
    
    <form method="post" action="categories.php" class="category-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
        
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="slug">Slug:</label>
            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($category['slug']); ?>" <?php echo $category['slug'] === 'uncategorized' ? 'readonly' : ''; ?>>
            <?php if ($category['slug'] === 'uncategorized'): ?>
                <p class="field-help">The "uncategorized" slug cannot be changed.</p>
            <?php else: ?>
                <p class="field-help">The slug is used in URLs for this category.</p>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="description">Description (optional):</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button">Update Category</button>
            <a href="categories.php" class="button button-secondary">Cancel</a>
        </div>
    </form>
    
    <?php if ($category['slug'] !== 'uncategorized'): ?>
    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const nameField = this;
            const slugField = document.getElementById('slug');
            
            // Only update slug if it hasn't been manually edited
            if (slugField.dataset.autoGenerated === 'true') {
                const slug = nameField.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                
                slugField.value = slug;
            }
        });
        
        // Mark slug as manually edited
        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.autoGenerated = 'false';
        });
        
        // Initialize auto-generated flag
        document.getElementById('slug').dataset.autoGenerated = 'false';
    </script>
    <?php endif; ?>
    <?php
}

/**
 * Handle creating a new category
 */
function handleCreateCategory() {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate data
    if (empty($name)) {
        setFlashMessage('Category name is required', 'error');
        redirect('categories.php?action=new');
    }
    
    // Generate slug if empty
    if (empty($slug)) {
        $slug = generateSlug($name);
    } else {
        $slug = generateSlug($slug);
    }
    
    // Check if slug already exists
    $existingCategory = getRow("SELECT id FROM categories WHERE slug = ?", [$slug]);
    if ($existingCategory) {
        setFlashMessage('A category with this slug already exists', 'error');
        redirect('categories.php?action=new');
    }
    
    // Insert category
    $categoryId = insertRecord('categories', [
        'name' => $name,
        'slug' => $slug,
        'description' => $description
    ]);
    
    if (!$categoryId) {
        setFlashMessage('Failed to create category', 'error');
        redirect('categories.php?action=new');
    }
    
    // Regenerate static files
    generateAllStaticFiles();
    
    setFlashMessage('Category created successfully', 'success');
    redirect('categories.php');
}

/**
 * Handle updating an existing category
 */
function handleUpdateCategory() {
    // Get form data
    $categoryId = (int)$_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate data
    if (empty($name)) {
        setFlashMessage('Category name is required', 'error');
        redirect('categories.php?action=edit&id=' . $categoryId);
    }
    
    // Get existing category
    $category = getRow("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    if (!$category) {
        setFlashMessage('Category not found', 'error');
        redirect('categories.php');
    }
    
    // Don't allow changing the slug of the uncategorized category
    if ($category['slug'] === 'uncategorized') {
        $slug = 'uncategorized';
    } else {
        // Clean slug
        $slug = generateSlug($slug);
        
        // Check if slug already exists (and it's not this category)
        $existingCategory = getRow("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $categoryId]);
        if ($existingCategory) {
            setFlashMessage('A category with this slug already exists', 'error');
            redirect('categories.php?action=edit&id=' . $categoryId);
        }
    }
    
    // Update category
    $success = updateRecord('categories', [
        'name' => $name,
        'slug' => $slug,
        'description' => $description
    ], 'id = ?', [$categoryId]);
    
    if (!$success) {
        setFlashMessage('Failed to update category', 'error');
        redirect('categories.php?action=edit&id=' . $categoryId);
    }
    
    // Regenerate static files
    generateAllStaticFiles();
    
    setFlashMessage('Category updated successfully', 'success');
    redirect('categories.php');
}

/**
 * Handle deleting a category
 */
function handleDeleteCategory() {
    // Get category ID
    $categoryId = (int)$_POST['category_id'];
    
    // Get category data
    $category = getRow("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    
    if (!$category) {
        setFlashMessage('Category not found', 'error');
        redirect('categories.php');
    }
    
    // Don't allow deleting the uncategorized category
    if ($category['slug'] === 'uncategorized') {
        setFlashMessage('The "Uncategorized" category cannot be deleted', 'error');
        redirect('categories.php');
    }
    
    // Get uncategorized category ID
    $uncategorizedCategory = getRow("SELECT id FROM categories WHERE slug = 'uncategorized'");
    
    if (!$uncategorizedCategory) {
        setFlashMessage('Uncategorized category not found', 'error');
        redirect('categories.php');
    }
    
    // Move posts to uncategorized category
    $conn = getDbConnection();
    $conn->Execute(
        "UPDATE post_categories SET category_id = ? WHERE category_id = ?",
        [$uncategorizedCategory['id'], $categoryId]
    );
    
    // Delete category
    $success = deleteRecord('categories', 'id = ?', [$categoryId]);
    
    if (!$success) {
        setFlashMessage('Failed to delete category', 'error');
        redirect('categories.php');
    }
    
    // Regenerate static files
    generateAllStaticFiles();
    
    setFlashMessage('Category deleted successfully', 'success');
    redirect('categories.php');
}

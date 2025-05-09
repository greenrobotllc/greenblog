<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - <?php echo htmlspecialchars(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="site-header">
            <div class="site-title">
                <h1><a href="/"><?php echo htmlspecialchars(SITE_TITLE); ?></a></h1>
                <p class="site-description"><?php echo htmlspecialchars(SITE_DESCRIPTION); ?></p>
            </div>
            
            <nav class="site-nav">
                <ul>
                    <li><a href="/">Home</a></li>
                    <?php
                    // Get categories for navigation
                    $categories = getRows("SELECT * FROM categories ORDER BY name");
                    foreach ($categories as $category):
                    ?>
                        <li><a href="/category/<?php echo $category['slug']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="/feed.xml">RSS</a></li>
                </ul>
            </nav>
        </header>
        
        <main class="site-content">
            <div class="error-page">
                <h1>404 Not Found</h1>
                <p>The page you are looking for does not exist.</p>
                <p><a href="/">Return to Homepage</a></p>
            </div>
        </main>
        
        <footer class="site-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_TITLE); ?>. Powered by GreenBlog.</p>
        </footer>
    </div>
</body>
</html>

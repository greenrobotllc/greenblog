<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="alternate" type="application/rss+xml" title="<?php echo htmlspecialchars(SITE_TITLE); ?> RSS Feed" href="/feed.xml">
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
                    $navCategories = getRows("SELECT * FROM categories ORDER BY name");
                    foreach ($navCategories as $navCategory):
                    ?>
                        <li><a href="/category/<?php echo $navCategory['slug']; ?>" <?php echo $navCategory['id'] === $category['id'] ? 'class="active"' : ''; ?>><?php echo htmlspecialchars($navCategory['name']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="/feed.xml">RSS</a></li>
                </ul>
            </nav>
        </header>
        
        <main class="site-content">
            <header class="page-header">
                <h1 class="page-title">Category: <?php echo htmlspecialchars($category['name']); ?></h1>
                
                <?php if (!empty($category['description'])): ?>
                    <div class="category-description">
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <p>No posts found in this category.</p>
                </div>
            <?php else: ?>
                <div class="posts">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-summary">
                            <h2 class="post-title">
                                <a href="/<?php echo $post['slug']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            </h2>
                            
                            <div class="post-meta">
                                <span class="post-date"><?php echo formatDate($post['published_date']); ?></span>
                                <span class="post-author">by <?php echo htmlspecialchars($post['author_name']); ?></span>
                            </div>
                            
                            <?php if (!empty($post['featured_image'])): ?>
                                <div class="post-image">
                                    <a href="/<?php echo $post['slug']; ?>">
                                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="post-excerpt">
                                <?php echo getExcerpt($post['content'], 300); ?>
                            </div>
                            
                            <div class="post-read-more">
                                <a href="/<?php echo $post['slug']; ?>">Read More</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="site-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_TITLE); ?>. Powered by GreenBlog.</p>
        </footer>
    </div>
</body>
</html>

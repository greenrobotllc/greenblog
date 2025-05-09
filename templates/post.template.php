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
                        <li><a href="/category/<?php echo $navCategory['slug']; ?>"><?php echo htmlspecialchars($navCategory['name']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="/feed.xml">RSS</a></li>
                </ul>
            </nav>
        </header>
        
        <main class="site-content">
            <article class="post">
                <header class="post-header">
                    <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <div class="post-meta">
                        <span class="post-date"><?php echo formatDate($post['published_date']); ?></span>
                        <span class="post-author">by <?php echo htmlspecialchars($post['author_name']); ?></span>
                        
                        <?php if (!empty($categories)): ?>
                            <span class="post-categories">
                                in 
                                <?php 
                                $categoryLinks = [];
                                foreach ($categories as $category) {
                                    $categoryLinks[] = '<a href="/category/' . $category['slug'] . '">' . htmlspecialchars($category['name']) . '</a>';
                                }
                                echo implode(', ', $categoryLinks);
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>
                
                <?php if (!empty($post['featured_image'])): ?>
                    <div class="post-featured-image">
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <?php echo $post['content']; ?>
                </div>
                
                <footer class="post-footer">
                    <?php if (!empty($categories)): ?>
                        <div class="post-categories-footer">
                            <h3>Categories:</h3>
                            <ul>
                                <?php foreach ($categories as $category): ?>
                                    <li><a href="/category/<?php echo $category['slug']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-navigation">
                        <?php
                        // Get previous post
                        $prevPost = getRow(
                            "SELECT id, title, slug 
                             FROM posts 
                             WHERE status = 'published' 
                             AND published_date < ? 
                             ORDER BY published_date DESC 
                             LIMIT 1",
                            [$post['published_date']]
                        );
                        
                        // Get next post
                        $nextPost = getRow(
                            "SELECT id, title, slug 
                             FROM posts 
                             WHERE status = 'published' 
                             AND published_date > ? 
                             ORDER BY published_date ASC 
                             LIMIT 1",
                            [$post['published_date']]
                        );
                        ?>
                        
                        <?php if ($prevPost): ?>
                            <div class="post-prev">
                                <a href="/<?php echo $prevPost['slug']; ?>">&laquo; <?php echo htmlspecialchars($prevPost['title']); ?></a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($nextPost): ?>
                            <div class="post-next">
                                <a href="/<?php echo $nextPost['slug']; ?>"><?php echo htmlspecialchars($nextPost['title']); ?> &raquo;</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </footer>
            </article>
        </main>
        
        <footer class="site-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_TITLE); ?>. Powered by GreenBlog.</p>
        </footer>
    </div>
</body>
</html>

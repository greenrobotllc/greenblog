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
            <header class="page-header">
                <h1 class="page-title">Archive: <?php echo $year; ?></h1>
            </header>
            
            <?php
            // Group posts by month
            $postsByMonth = [];
            foreach ($posts as $post) {
                $month = date('m', strtotime($post['published_date']));
                if (!isset($postsByMonth[$month])) {
                    $postsByMonth[$month] = [];
                }
                $postsByMonth[$month][] = $post;
            }
            
            // Sort months in reverse order (newest first)
            krsort($postsByMonth);
            ?>
            
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <p>No posts found in this archive.</p>
                </div>
            <?php else: ?>
                <?php foreach ($postsByMonth as $month => $monthPosts): ?>
                    <div class="archive-month">
                        <h2><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h2>
                        
                        <ul class="archive-posts">
                            <?php foreach ($monthPosts as $post): ?>
                                <li>
                                    <span class="post-date"><?php echo date('j', strtotime($post['published_date'])); ?></span>
                                    <a href="/<?php echo $post['slug']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="month-link">
                            <a href="/archive/<?php echo $year; ?>/<?php echo $month; ?>">View all posts from <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="archive-navigation">
                <?php
                // Get previous and next years
                $prevYear = $year - 1;
                $nextYear = $year + 1;
                
                // Check if previous year has posts
                $prevYearHasPosts = getRow(
                    "SELECT COUNT(*) as count 
                     FROM posts 
                     WHERE status = 'published' 
                     AND strftime('%Y', published_date) = ?",
                    [$prevYear]
                )['count'] > 0;
                
                // Check if next year has posts
                $nextYearHasPosts = getRow(
                    "SELECT COUNT(*) as count 
                     FROM posts 
                     WHERE status = 'published' 
                     AND strftime('%Y', published_date) = ?",
                    [$nextYear]
                )['count'] > 0;
                ?>
                
                <?php if ($prevYearHasPosts): ?>
                    <a href="/archive/<?php echo $prevYear; ?>" class="prev-year">
                        &laquo; <?php echo $prevYear; ?>
                    </a>
                <?php endif; ?>
                
                <a href="/" class="home-link">
                    Back to Home
                </a>
                
                <?php if ($nextYearHasPosts): ?>
                    <a href="/archive/<?php echo $nextYear; ?>" class="next-year">
                        <?php echo $nextYear; ?> &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </main>
        
        <footer class="site-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_TITLE); ?>. Powered by GreenBlog.</p>
        </footer>
    </div>
</body>
</html>

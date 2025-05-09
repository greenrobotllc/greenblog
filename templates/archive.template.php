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
                <h1 class="page-title">Archive: <?php echo $monthName; ?> <?php echo $year; ?></h1>
            </header>
            
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <p>No posts found in this archive.</p>
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
            
            <div class="archive-navigation">
                <?php
                // Get previous and next months
                $prevMonth = $month - 1;
                $prevYear = $year;
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear--;
                }
                
                $nextMonth = $month + 1;
                $nextYear = $year;
                if ($nextMonth > 12) {
                    $nextMonth = 1;
                    $nextYear++;
                }
                
                // Check if previous month has posts
                $prevMonthHasPosts = getRow(
                    "SELECT COUNT(*) as count 
                     FROM posts 
                     WHERE status = 'published' 
                     AND strftime('%Y', published_date) = ? 
                     AND strftime('%m', published_date) = ?",
                    [$prevYear, str_pad($prevMonth, 2, '0', STR_PAD_LEFT)]
                )['count'] > 0;
                
                // Check if next month has posts
                $nextMonthHasPosts = getRow(
                    "SELECT COUNT(*) as count 
                     FROM posts 
                     WHERE status = 'published' 
                     AND strftime('%Y', published_date) = ? 
                     AND strftime('%m', published_date) = ?",
                    [$nextYear, str_pad($nextMonth, 2, '0', STR_PAD_LEFT)]
                )['count'] > 0;
                ?>
                
                <?php if ($prevMonthHasPosts): ?>
                    <a href="/archive/<?php echo $prevYear; ?>/<?php echo str_pad($prevMonth, 2, '0', STR_PAD_LEFT); ?>" class="prev-month">
                        &laquo; <?php echo date('F Y', mktime(0, 0, 0, $prevMonth, 1, $prevYear)); ?>
                    </a>
                <?php endif; ?>
                
                <a href="/archive/<?php echo $year; ?>" class="year-link">
                    View all posts from <?php echo $year; ?>
                </a>
                
                <?php if ($nextMonthHasPosts): ?>
                    <a href="/archive/<?php echo $nextYear; ?>/<?php echo str_pad($nextMonth, 2, '0', STR_PAD_LEFT); ?>" class="next-month">
                        <?php echo date('F Y', mktime(0, 0, 0, $nextMonth, 1, $nextYear)); ?> &raquo;
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars(SITE_TITLE); ?> Admin</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        a {
            color: #2c8c3c;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Layout */
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #2c8c3c;
            color: white;
            padding: 20px;
        }
        
        .content {
            flex: 1;
            padding: 20px;
            overflow-x: auto;
        }
        
        /* Sidebar */
        .site-title {
            font-size: 24px;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .site-title a {
            color: white;
            text-decoration: none;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-menu li {
            margin-bottom: 10px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 8px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .nav-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }
        
        .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }
        
        .user-info {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 14px;
        }
        
        /* Dashboard */
        .dashboard h1 {
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h2 {
            font-size: 36px;
            color: #2c8c3c;
            margin-bottom: 5px;
        }
        
        .dashboard-actions {
            margin-bottom: 30px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-published {
            background-color: #d4efdf;
            color: #27ae60;
        }
        
        .status-draft {
            background-color: #eaeded;
            color: #7f8c8d;
        }
        
        .actions {
            white-space: nowrap;
        }
        
        .action-link {
            margin-right: 10px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: inherit;
        }
        
        textarea {
            min-height: 200px;
        }
        
        .button {
            display: inline-block;
            background-color: #2c8c3c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .button:hover {
            background-color: #1e6e2e;
            text-decoration: none;
        }
        
        .button-secondary {
            background-color: #7f8c8d;
        }
        
        .button-secondary:hover {
            background-color: #6c7a7a;
        }
        
        .button-danger {
            background-color: #e74c3c;
        }
        
        .button-danger:hover {
            background-color: #c0392b;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .message-success {
            background-color: #d4efdf;
            color: #27ae60;
        }
        
        .message-error {
            background-color: #fadbd8;
            color: #e74c3c;
        }
        
        .message-info {
            background-color: #d6eaf8;
            color: #3498db;
        }
        
        /* Pagination */
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 3px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
            text-decoration: none;
        }
        
        .pagination .current {
            background-color: #2c8c3c;
            color: white;
            border-color: #2c8c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="site-title">
                <a href="index.php"><?php echo htmlspecialchars(SITE_TITLE); ?></a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                <li><a href="posts.php" <?php echo basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'class="active"' : ''; ?>>Posts</a></li>
                <li><a href="categories.php" <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'class="active"' : ''; ?>>Categories</a></li>
                <li><a href="media.php" <?php echo basename($_SERVER['PHP_SELF']) === 'media.php' ? 'class="active"' : ''; ?>>Media</a></li>
                <li><a href="settings.php" <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'class="active"' : ''; ?>>Settings</a></li>
                <li><a href="regenerate.php">Regenerate Static Files</a></li>
                <li><a href="../" target="_blank">View Blog</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            
            <div class="user-info">
                Logged in as: <strong><?php echo htmlspecialchars(getCurrentUsername()); ?></strong>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="message message-<?php echo $_SESSION['flash_message']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['flash_message']['message']); ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

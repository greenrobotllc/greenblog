<?php
/**
 * GreenBlog Helper Functions
 * 
 * This file contains various helper functions used throughout the application.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a slug from a string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function generateSlug($string) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get excerpt from content
 * 
 * @param string $content Content to extract excerpt from
 * @param int $length Maximum length of excerpt
 * @return string Excerpt
 */
function getExcerpt($content, $length = 150) {
    // Strip HTML tags
    $text = strip_tags($content);
    
    // Trim to length
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' ')) . '...';
    }
    
    return $text;
}

/**
 * Check if a file is an allowed image type
 * 
 * @param string $file File path
 * @return bool True if allowed, false otherwise
 */
function isAllowedImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file);
    finfo_close($fileInfo);
    
    return in_array($mimeType, $allowedTypes);
}

/**
 * Generate a random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return "$protocol://$host$uri";
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if a string starts with a specific substring
 * 
 * @param string $haystack String to check
 * @param string $needle Substring to look for
 * @return bool True if haystack starts with needle, false otherwise
 */
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if a string ends with a specific substring
 * 
 * @param string $haystack String to check
 * @param string $needle Substring to look for
 * @return bool True if haystack ends with needle, false otherwise
 */
function endsWith($haystack, $needle) {
    return substr($haystack, -strlen($needle)) === $needle;
}

/**
 * Display a flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message or null if none exists
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

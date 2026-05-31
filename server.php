<?php
/**
 * Router script for PHP built-in server.
 * Usage: php -S 127.0.0.1:8080 server.php
 */

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Serve static assets from public/ and assets/ directories
$publicFile = __DIR__ . '/public' . $path;
$assetsFile = __DIR__ . $path;

if ($path !== '/' && is_file($publicFile)) {
    return false; // let built-in server serve it
}

if (str_starts_with($path, '/assets/') && is_file($assetsFile)) {
    $ext = pathinfo($assetsFile, PATHINFO_EXTENSION);
    $types = ['css' => 'text/css', 'js' => 'text/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff2' => 'font/woff2'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    readfile($assetsFile);
    return true;
}

// Serve user uploads (mis. logo tenant) dari root /uploads/.
if (str_starts_with($path, '/uploads/') && is_file($assetsFile)) {
    $ext = pathinfo($assetsFile, PATHINFO_EXTENSION);
    $types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp'];
    header('Content-Type: ' . ($types[strtolower($ext)] ?? 'application/octet-stream'));
    readfile($assetsFile);
    return true;
}

// Rewrite to index.php
$_GET['url'] = $path;
require __DIR__ . '/public/index.php';

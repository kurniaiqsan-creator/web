<?php
/**
 * Single Entry Point — handles all routing without .htaccess
 * Works for both localhost/visi/ and visi.test
 */

// Determine if we're being accessed from a subdirectory (e.g., localhost/visi/)
// or directly (e.g., visi.test via VirtualHost)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract the URL path after the base directory
$baseDir = rtrim(dirname($scriptName), '/\\');
if ($baseDir === '/' || $baseDir === '\\') $baseDir = '';

// Remove the base directory prefix to get the actual route
$url = $requestUri;
if ($baseDir && str_starts_with($url, $baseDir)) {
    $url = substr($url, strlen($baseDir));
}
// Remove query string if any
$url = parse_url($url, PHP_URL_PATH);
// Clean up
$url = '/' . trim((string)$url, '/');

// Pass as the url parameter that the router expects
$_GET['url'] = $url;

// Load the application
require __DIR__ . '/public/index.php';

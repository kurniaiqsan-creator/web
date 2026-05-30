<?php
// Quick PHP test - just show headers
header('X-Visi-Test: ' . date('H:i:s'));
echo '<h1>PHP OK</h1>';
echo '<p>REQUEST_URI: ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'none') . '</p>';
echo '<p>SCRIPT_NAME: ' . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'none') . '</p>';
echo '<p>REDIRECT_URL: ' . htmlspecialchars($_SERVER['REDIRECT_URL'] ?? 'none') . '</p>';
echo '<p>REDIRECT_STATUS: ' . htmlspecialchars($_SERVER['REDIRECT_STATUS'] ?? 'none') . '</p>';

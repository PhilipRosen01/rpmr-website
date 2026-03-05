<?php

declare(strict_types=1);

/**
 * Minimal router for PHP's built-in dev server.
 *
 * Usage:
 *   php -S 127.0.0.1:8000 -t . router.php
 *
 * This lets "pretty" URLs like /projects/gridpics resolve to /projects/gridpics/index.html.
 */

$publicRoot = __DIR__;

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = is_string($uriPath) ? $uriPath : '/';
$uriPath = rawurldecode($uriPath);

// Serve existing files directly (CSS/JS/images/HTML/PHP, etc.)
$directPath = $publicRoot . $uriPath;
$directReal = realpath($directPath);
if (is_string($directReal) && str_starts_with($directReal, $publicRoot) && is_file($directReal)) {
    return false;
}

// Normalize trailing slashes.
$normalizedPath = $uriPath === '/' ? '/' : rtrim($uriPath, '/');

// 1) If it's a directory, serve its index.html
$dirCandidate = $publicRoot . ($normalizedPath === '/' ? '' : $normalizedPath);
$dirReal = realpath($dirCandidate);
if (is_string($dirReal) && str_starts_with($dirReal, $publicRoot) && is_dir($dirReal)) {
    $indexPath = $dirReal . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($indexPath)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($indexPath);
        return true;
    }
}

// 2) If /path.html exists, serve it (e.g. /projects -> /projects.html)
if ($normalizedPath !== '/' && !str_contains(basename($normalizedPath), '.')) {
    $htmlCandidate = $publicRoot . $normalizedPath . '.html';
    $htmlReal = realpath($htmlCandidate);
    if (is_string($htmlReal) && str_starts_with($htmlReal, $publicRoot) && is_file($htmlReal)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($htmlReal);
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "404 Not Found";

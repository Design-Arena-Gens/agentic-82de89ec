<?php
$root = dirname(__DIR__);

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

if (str_contains($path, '..')) {
    http_response_code(400);
    echo 'Invalid path';
    return;
}

if (str_starts_with($path, '/api/')) {
    http_response_code(404);
    echo 'Not Found';
    return;
}

if (str_starts_with($path, '/assets/')) {
    $assetPath = realpath($root . $path);
    if ($assetPath && str_starts_with($assetPath, $root . DIRECTORY_SEPARATOR) && is_file($assetPath)) {
        $ext = pathinfo($assetPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($assetPath);
        return;
    }
}

$target = $path;
if ($target === '/' || $target === '') {
    $target = '/index.php';
}

$targetPath = realpath($root . $target);
if (!$targetPath || !str_starts_with($targetPath, $root . DIRECTORY_SEPARATOR) || !is_file($targetPath)) {
    $targetPath = $root . '/index.php';
}

chdir($root);
ob_start();
require $targetPath;
$output = ob_get_clean();
echo $output;

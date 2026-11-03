<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri === '/' || $uri === '/aviate-app.html') {
    require __DIR__ . '/aviate-app.html';
    return true;
}

if (is_file($file) && str_ends_with($file, '.html')) {
    require $file;
    return true;
}

return false;
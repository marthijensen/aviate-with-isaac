<?php
/**
 * @var string $title
 * @var string $content Rendered page HTML
 * @var string $currentPath
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Aviate with Isaac</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/dashboard">Aviate with Isaac</a>
        <nav class="site-nav" aria-label="Main">
            <a href="/dashboard"<?= $currentPath === '/dashboard' ? ' aria-current="page"' : '' ?>>Dashboard</a>
        </nav>
    </header>

    <main class="page">
        <?= $content ?>
    </main>
</body>
</html>

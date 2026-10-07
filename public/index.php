<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$upcomingLessons = [];
$upcomingLessonsError = null;

try {
    $upcomingLessons = UpcomingLessons::fetch(supabase(), new DateTimeImmutable('now'), app_timezone());
} catch (RuntimeException $exception) {
    error_log('Upcoming lessons: ' . $exception->getMessage());
    $upcomingLessonsError = 'Upcoming lessons could not be loaded. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Aviate with Isaac</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <h1>Aviate with Isaac</h1>
        <p>Dashboard</p>
    </header>

    <main class="dashboard">
        <?php require dirname(__DIR__) . '/views/upcoming-lessons.php'; ?>
    </main>
</body>
</html>

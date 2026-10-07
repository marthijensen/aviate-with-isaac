<?php

declare(strict_types=1);

/**
 * Application routes.
 */
function app_router(): Router
{
    $router = new Router();

    $router->get('/', static function (): void {
        redirect('/dashboard');
    });

    $router->get('/dashboard', static function (): void {
        $upcomingLessons = [];
        $upcomingLessonsError = null;

        try {
            $upcomingLessons = UpcomingLessons::fetch(supabase(), new DateTimeImmutable('now'), app_timezone());
        } catch (RuntimeException $exception) {
            error_log('Upcoming lessons: ' . $exception->getMessage());
            $upcomingLessonsError = 'Upcoming lessons could not be loaded. Please try again later.';
        }

        render('dashboard', [
            'title' => 'Dashboard',
            'upcomingLessons' => $upcomingLessons,
            'upcomingLessonsError' => $upcomingLessonsError,
        ]);
    });

    return $router;
}

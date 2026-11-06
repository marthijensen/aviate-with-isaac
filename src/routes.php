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

    // The shared stylesheet lives at the repo root, next to aviate-app.html.
    $router->get('/app.css', static function (): void {
        header('Content-Type: text/css; charset=UTF-8');
        readfile(dirname(__DIR__) . '/app.css');
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

    
    $router->get('/students', static function (): void {
        $students = null;

        try {
            $students = supabase()->select('students', [
                'select' => 'id,first_name,last_name,created_at,status',
                'status' => 'eq.active',
                'order' => 'created_at.asc',
            ]);
        } catch (RuntimeException $exception) {
            error_log('Student list: ' . $exception->getMessage());
        }

        render('students', [
            'title' => 'Students',
            'students' => $students,
        ]);
    });


    $router->get('/students/profile', static function (): void {
        $student = null;
        $error = null;

        $id = $_GET['id'] ?? null;

        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            http_response_code(400);
            $error = 'A valid student ID is required.';
        } else {
            try {
                $results = supabase()->select('students', [
                    'select' => 'id,first_name,last_name,email,phone,training_goal,status,created_at',
                    'id' => 'eq.' . $id,
                    'limit' => '1',
                ]);

                if ($results === []) {
                    http_response_code(404);
                    $error = 'Student not found.';
                } else {
                    $student = $results[0];
                }
            } catch (RuntimeException $exception) {
                error_log('Student profile: ' . $exception->getMessage());
                http_response_code(503);
                $error = 'Student information is currently unavailable.';
            }
        }

        render('student-profile', [
            'title' => 'Student Profile',
            'student' => $student,
            'error' => $error,
        ]);
    });

    return $router;
}

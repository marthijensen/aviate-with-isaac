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
                $db = ReadOnlyDatabase::connect();

                $zone = app_timezone();
                $now = new DateTimeImmutable('now', $zone);
                $today = $now->format('Y-m-d');

                // Retrieve appointments from today onward.
                $statement = $db->prepare(
                    'SELECT
                        id,
                        student_id,
                        apointment_date,
                        start_time,
                        apointment_type,
                        status
                    FROM public.appointments
                    WHERE apointment_date >= :today
                    ORDER BY apointment_date ASC, start_time ASC'
                );

                $statement->execute([
                    'today' => $today,
                ]);

                $appointments = $statement->fetchAll();

                // Retrieve student names for the appointment cards.
                $students = $db->query(
                    'SELECT id, first_name, last_name
                    FROM public.students'
                )->fetchAll();

                // Reuse Sangchun's existing lesson formatting logic.
                $upcomingLessons = UpcomingLessons::prepare(
                    $appointments,
                    $students,
                    $now,
                    $zone
                );

            } catch (Throwable $exception) {
                error_log(
                    'Upcoming lessons: ' . $exception->getMessage()
                );

                $upcomingLessonsError =
                    'Upcoming lessons could not be loaded. Please try again later.';
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
            // Connect using Marthi's read-only database account.
            $db = ReadOnlyDatabase::connect();

            // Retrieve active students, oldest first.
            $statement = $db->query(
                "SELECT
                    id,
                    first_name,
                    last_name,
                    created_at,
                    status
                 FROM public.students
                 WHERE status = 'active'
                 ORDER BY created_at ASC"
            );

            $students = $statement->fetchAll();

        } catch (Throwable $exception) {
            error_log(
                'Student list database error: '
                . $exception->getMessage()
            );
        }

        render('students', [
            'title' => 'Students',
            'students' => $students,
        ]);
    });



    $router->get('/students/profile', static function (): void {
        $student = null;
        $error = null;

        $recentLessons = [];
        $upcomingAppointments = [];
        $appointmentsError = null;

        $id = $_GET['id'] ?? null;

        if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            http_response_code(400);
            $error = 'A valid student ID is required.';
        } else {
            try {
                    $db = ReadOnlyDatabase::connect();

                    $statement = $db->prepare(
                        'SELECT
                            id,
                            first_name,
                            last_name,
                            email,
                            phone,
                            training_goal,
                            status,
                            created_at
                        FROM public.students
                        WHERE id = :student_id
                        LIMIT 1'
                    );

                    $statement->execute([
                        'student_id' => $id,
                    ]);

                    $studentRow = $statement->fetch();

                    if ($studentRow === false) {
                        http_response_code(404);
                        $error = 'Student not found.';
                    } else {
                        $student = $studentRow;
                        try {
                            $today = (new DateTimeImmutable('now', app_timezone()))
                                ->format('Y-m-d');

                            // Get the student's five most recent past appointments.
                            $recentStatement = $db->prepare(
                                'SELECT
                                    id,
                                    apointment_date,
                                    start_time,
                                    apointment_type,
                                    status
                                FROM public.appointments
                                WHERE student_id = :student_id
                                AND apointment_date < :today
                                ORDER BY apointment_date DESC, start_time DESC
                                LIMIT 5'
                            );

                            $recentStatement->execute([
                                'student_id' => $id,
                                'today' => $today,
                            ]);

                            $recentLessons = $recentStatement->fetchAll();

                            // Get the student's next five scheduled appointments.
                            $upcomingStatement = $db->prepare(
                                "SELECT
                                    id,
                                    apointment_date,
                                    start_time,
                                    apointment_type,
                                    status
                                FROM public.appointments
                                WHERE student_id = :student_id
                                AND apointment_date >= :today
                                AND status = 'scheduled'
                                ORDER BY apointment_date ASC, start_time ASC
                                LIMIT 5"
                            );

                            $upcomingStatement->execute([
                                'student_id' => $id,
                                'today' => $today,
                            ]);

                            $upcomingAppointments = $upcomingStatement->fetchAll();

                        } catch (Throwable $exception) {
                            error_log(
                                'Student appointments: ' . $exception->getMessage()
                            );

                            $recentLessons = [];
                            $upcomingAppointments = [];
                            $appointmentsError = 'Appointments could not be loaded.';
                        }
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
            'recentLessons' => $recentLessons,
            'upcomingAppointments' => $upcomingAppointments,
            'appointmentsError' => $appointmentsError,
        ]);
    });

    // Display the Add Student form.
    $router->get('/students/add', static function (): void {
        render('add-student', [
            'title' => 'Add Student',
            'fields' => [],
            'errors' => [],
            'preview' => false,
        ]);
    });

    // Validate the form without saving to the database.
    $router->post('/students/add', static function (): void {
        $names = [
            'first_name', 'last_name', 'email', 'phone',
            'address', 'city', 'state', 'zip', 'training_goal',
        ];

        $fields = [];

        foreach ($names as $name) {
            $value = $_POST[$name] ?? '';
            $fields[$name] = is_string($value) ? trim($value) : '';
        }

        $errors = [];

        if ($fields['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        if ($fields['last_name'] === '') {
            $errors[] = 'Last name is required.';
        }

        if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if ($fields['training_goal'] === '') {
            $errors[] = 'Training goal is required.';
        }

        if ($errors !== []) {
            http_response_code(422);
        }

        render('add-student', [
            'title' => 'Add Student',
            'fields' => $fields,
            'errors' => $errors,
            'preview' => $errors === [],
        ]);
    });

    // Display the Schedule Lesson form.
    $router->get('/schedule', static function (): void {
        $studentOptions = [];
        $studentOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $studentOptions = $db->query(
                "SELECT id, first_name, last_name
                FROM public.students
                WHERE status = 'active'
                ORDER BY last_name ASC, first_name ASC"
            )->fetchAll();

        } catch (Throwable $exception) {
            error_log('Schedule students: ' . $exception->getMessage());
            $studentOptionsError = 'Student list could not be loaded.';
        }

        render('schedule', [
            'title' => 'Schedule Lesson',
            'fields' => [],
            'errors' => [],
            'preview' => false,
            'studentOptions' => $studentOptions,
            'studentOptionsError' => $studentOptionsError,
        ]);
    });


    // Validate a lesson without saving it to Supabase.
    $router->post('/schedule', static function (): void {
        $names = [
            'student_id',
            'lesson_type',
            'appointment_date',
            'start_time',
        ];

        $fields = [];

        foreach ($names as $name) {
            $value = $_POST[$name] ?? '';
            $fields[$name] = is_string($value) ? trim($value) : '';
        }

        $errors = [];

        // Validate the student ID.
        if (!ctype_digit($fields['student_id'])
            || (int) $fields['student_id'] < 1) {
            $errors[] = 'A valid student ID is required.';
        }

        // Validate the lesson type.
        if ($fields['lesson_type'] === '') {
            $errors[] = 'Please select a lesson type.';
        }

        // Validate the appointment date.
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fields['appointment_date'],
            app_timezone()
        );

        if ($date === false ||
            $date->format('Y-m-d') !== $fields['appointment_date']) {
            $errors[] = 'Please enter a valid appointment date.';
        } elseif ($date < new DateTimeImmutable('today', app_timezone())) {
            $errors[] = 'The appointment date cannot be in the past.';
        }

        // Validate the appointment time.
        if (!preg_match(
            '/^([01]\d|2[0-3]):[0-5]\d$/',
            $fields['start_time']
        )) {
            $errors[] = 'Please enter a valid start time.';
        }


        $studentOptions = [];
        $studentOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $studentOptions = $db->query(
                "SELECT id, first_name, last_name
                FROM public.students
                WHERE status = 'active'
                ORDER BY last_name ASC, first_name ASC"
            )->fetchAll();

            // Verify the selected student is actually active.
            if (ctype_digit($fields['student_id'])
                && (int) $fields['student_id'] > 0) {

                $validIds = array_map(
                    static fn (array $student): string =>
                        (string) $student['id'],
                    $studentOptions
                );

                if (!in_array($fields['student_id'], $validIds, true)) {
                    $errors[] = 'Please select an active student.';
                }
            }

        } catch (Throwable $exception) {
            error_log('Schedule students: ' . $exception->getMessage());

            $studentOptionsError = 'Student list could not be loaded.';
            $errors[] = 'Student selection could not be verified.';
        }

        if ($studentOptionsError !== null) {
            http_response_code(503);
        } elseif ($errors !== []) {
            http_response_code(422);
        }

        render('schedule', [
            'title' => 'Schedule Lesson',
            'fields' => $fields,
            'errors' => $errors,
            'preview' => $errors === [],
            'studentOptions' => $studentOptions,
            'studentOptionsError' => $studentOptionsError,
        ]);
    });


    return $router;
}

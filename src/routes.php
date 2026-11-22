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
                 ORDER BY last_name ASC, first_name ASC, id ASC"
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

                            // Get the student's next five scheduled or cancelled
                            // appointments, so cancellations stay visible.
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
                                AND status IN ('scheduled', 'cancelled', 'canceled')
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

        // Confirmation shown after a lesson change redirects back here.
        $notice = match ($_GET['notice'] ?? null) {
            'lesson-updated' => 'Lesson updated.',
            'lesson-cancelled' => 'Lesson cancelled.',
            default => null,
        };

        render('student-profile', [
            'title' => 'Student Profile',
            'notice' => $notice,
            'student' => $student,
            'error' => $error,
            'recentLessons' => $recentLessons,
            'upcomingAppointments' => $upcomingAppointments,
            'appointmentsError' => $appointmentsError,
        ]);
    });

    // Display the Add Student form.
    $router->get('/students/add', static function (): void {

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['add_student_csrf'])) {
            $_SESSION['add_student_csrf'] = bin2hex(random_bytes(32));
        }

        $courseOptions = [];
        $courseOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $courseOptions = $db->query(
                "SELECT course_id, course
                FROM public.course
                ORDER BY course_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $exception) {
            error_log('Add Student courses: ' . $exception->getMessage());
            $courseOptionsError = 'Courses could not be loaded.';
        }

        render('add-student', [
            'title' => 'Add Student',
            'fields' => [],
            'errors' => [],
            'preview' => false,
            'courseOptions' => $courseOptions,
            'courseOptionsError' => $courseOptionsError,
        ]);
    });

    // Validate the form
    $router->post('/students/add', static function (): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Reject requests outside explicitly enabled local development.
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        if (
            Env::get('AVIATE_LOCAL_WRITES', '0') !== '1'
            || !in_array($remoteAddress, ['127.0.0.1', '::1'], true)
        ) {
            http_response_code(403);
            echo 'Student creation is disabled in this environment.';
            return;
        }

        // Verify the request came from our form.
        $submittedToken = $_POST['csrf_token'] ?? '';
        $expectedToken = $_SESSION['add_student_csrf'] ?? '';

        if (
            !is_string($submittedToken)
            || !is_string($expectedToken)
            || $expectedToken === ''
            || !hash_equals($expectedToken, $submittedToken)
        ) {
            http_response_code(403);
            echo 'Invalid form token. Refresh the page and try again.';
            return;
        }

        $names = [
            'first_name', 'last_name', 'email', 'phone',
            'address', 'city', 'state', 'zip', 'course_id',
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

        if ($fields['course_id'] === '') {
            $errors[] = 'Please select a course.';
        }

        $courseOptions = [];
        $courseOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $courseOptions = $db->query(
                "SELECT course_id, course
                FROM public.course
                ORDER BY course_id ASC"
            )->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $exception) {
            error_log('Add Student courses: ' . $exception->getMessage());
            $courseOptionsError = 'Courses could not be loaded.';
        }

        if ($courseOptionsError === null && $fields['course_id'] !== '') {
            $validCourseIds = array_map(
                static fn(array $course): string =>
                    (string) $course['course_id'],
                $courseOptions
            );

            if (!in_array($fields['course_id'], $validCourseIds, true)) {
                $errors[] = 'Please select a valid course.';
            }
        }

        if ($courseOptionsError !== null) {
            http_response_code(503);
        } elseif ($errors !== []) {
            http_response_code(422);
        }

        if ($errors === [] && $courseOptionsError === null) {
            try {
                $db = WriteDatabase::connect();

                $statement = $db->prepare(
                    "INSERT INTO public.students (
                        first_name,
                        last_name,
                        email,
                        phone,
                        address,
                        city,
                        state,
                        zip,
                        course_id
                    ) VALUES (
                        :first_name,
                        :last_name,
                        :email,
                        :phone,
                        :address,
                        :city,
                        :state,
                        :zip,
                        :course_id
                    )"
                );

                $statement->execute([
                    'first_name' => $fields['first_name'],
                    'last_name' => $fields['last_name'],
                    'email' => $fields['email'],
                    'phone' => $fields['phone'],
                    'address' => $fields['address'],
                    'city' => $fields['city'],
                    'state' => $fields['state'],
                    'zip' => $fields['zip'],
                    'course_id' => (int) $fields['course_id'],
                ]);

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Student INSERT did not create exactly one row.'
                    );
                }

                // Redirect after saving to avoid duplicate submissions.
                header('Location: /students', true, 303);
                return;

            } catch (Throwable $exception) {
                error_log(
                    'Add Student INSERT failed: '
                    . $exception->getMessage()
                );

                $errors[] = 'Student could not be saved. Please try again.';
                http_response_code(500);
            }
        }

        render('add-student', [
            'title' => 'Add Student',
            'fields' => $fields,
            'errors' => $errors,
            'preview' => false,
            'courseOptions' => $courseOptions,
            'courseOptionsError' => $courseOptionsError,
        ]);
    });

    // Display the Schedule Lesson form.
    $router->get('/schedule', static function (): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['schedule_csrf'])) {
            $_SESSION['schedule_csrf'] = bin2hex(random_bytes(32));
        }

        $studentOptions = [];
        $studentOptionsError = null;

        try {
            $studentOptions = Appointments::studentOptions(ReadOnlyDatabase::connect());
        } catch (Throwable $exception) {
            error_log('Schedule students: ' . $exception->getMessage());
            $studentOptionsError = 'Student list could not be loaded.';
        }

        $lessonOptions = [];
        $lessonOptionsError = null;

        try {
            $lessonOptions = Appointments::lessonTypes(ReadOnlyDatabase::connect());
        } catch (Throwable $exception) {
            error_log('Schedule lesson types: ' . $exception->getMessage());
            $lessonOptionsError = 'Lesson types could not be loaded.';
        }

        render('schedule', [
            'title' => 'Schedule Lesson',
            'fields' => [],
            'errors' => [],
            'preview' => false,
            'studentOptions' => $studentOptions,
            'studentOptionsError' => $studentOptionsError,
            'lessonOptions' => $lessonOptions,
            'lessonOptionsError' => $lessonOptionsError,
        ]);
    });

    // Validate a lesson
    $router->post('/schedule', static function (): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Permit writes only during explicitly enabled local testing.
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        if (
            Env::get('AVIATE_LOCAL_WRITES', '0') !== '1'
            || !in_array($remoteAddress, ['127.0.0.1', '::1'], true)
        ) {
            http_response_code(403);
            echo 'Lesson scheduling is disabled in this environment.';
            return;
        }

        // Verify the form's CSRF token.
        $submittedToken = $_POST['csrf_token'] ?? '';
        $expectedToken = $_SESSION['schedule_csrf'] ?? '';

        if (
            !is_string($submittedToken)
            || !is_string($expectedToken)
            || $expectedToken === ''
            || !hash_equals($expectedToken, $submittedToken)
        ) {
            http_response_code(403);
            echo 'Invalid form token. Refresh the page and try again.';
            return;
        }

        $fields = Appointments::readForm($_POST);

        [
            'errors' => $errors,
            'studentOptions' => $studentOptions,
            'studentOptionsError' => $studentOptionsError,
            'lessonOptions' => $lessonOptions,
            'lessonOptionsError' => $lessonOptionsError,
        ] = check_lesson_form($fields);

        if ($studentOptionsError !== null || $lessonOptionsError !== null) {
            http_response_code(503);
        } elseif ($errors !== []) {
            http_response_code(422);
        }

        if (
            $errors === []
            && $studentOptionsError === null
            && $lessonOptionsError === null
        ) {
            try {
                $db = WriteDatabase::connect();

                $statement = $db->prepare(
                    "INSERT INTO public.appointments (
                        student_id,
                        apointment_date,
                        start_time,
                        apointment_type,
                        status
                    ) VALUES (
                        :student_id,
                        :apointment_date,
                        :start_time,
                        :apointment_type,
                        :status
                    )"
                );

                $statement->execute([
                    'student_id' => (int) $fields['student_id'],
                    'apointment_date' => $fields['appointment_date'],
                    'start_time' => $fields['start_time'],
                    'apointment_type' => $fields['lesson_type'],
                    'status' => 'scheduled',
                ]);

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Appointment INSERT did not create exactly one row.'
                    );
                }

                // Open the student's profile after saving.
                header(
                    'Location: /students/profile?id='
                        . (int) $fields['student_id'],
                    true,
                    303
                );
                return;

            } catch (Throwable $exception) {
                error_log(
                    'Schedule Lesson INSERT failed: '
                    . $exception->getMessage()
                );

                $errors[] = 'Lesson could not be saved. Please try again.';
                http_response_code(500);
            }
        }

        render('schedule', [
            'title' => 'Schedule Lesson',
            'fields' => $fields,
            'errors' => $errors,
            'preview' => false,
            'studentOptions' => $studentOptions,
            'studentOptionsError' => $studentOptionsError,
            'lessonOptions' => $lessonOptions,
            'lessonOptionsError' => $lessonOptionsError,
        ]);
    });


    // Display the Edit Scheduled Lesson form.
    $router->get('/appointments/edit', static function (): void {
        $id = positive_id($_GET, 'id');
        $appointment = load_changeable_lesson($id, 'Edit Lesson');

        if ($appointment === null) {
            return;
        }

        $fields = [
            'student_id' => (string) $appointment['student_id'],
            'lesson_type' => trim((string) $appointment['apointment_type']),
            'appointment_date' => (string) $appointment['apointment_date'],
            'start_time' => Appointments::timeInputValue($appointment['start_time']),
        ];

        $options = check_lesson_form($fields, (int) $appointment['student_id']);

        render_edit_lesson_form($id, $fields, [], $options);
    });

    // Save changes to a scheduled lesson.
    $router->post('/appointments/edit', static function (): void {
        if (!require_local_writes('Lesson editing is disabled in this environment.')
            || !require_csrf('edit_lesson_csrf')) {
            return;
        }

        $id = positive_id($_POST, 'appointment_id');
        $appointment = load_changeable_lesson($id, 'Edit Lesson');

        if ($appointment === null) {
            return;
        }

        $fields = Appointments::readForm($_POST);
        $check = check_lesson_form($fields, (int) $appointment['student_id']);
        $errors = $check['errors'];

        if ($check['studentOptionsError'] !== null || $check['lessonOptionsError'] !== null) {
            http_response_code(503);
        } elseif ($errors !== []) {
            http_response_code(422);
        } else {
            try {
                if (!Appointments::update(WriteDatabase::connect(), $id, $fields)) {
                    http_response_code(409);
                    render_lesson_notice('Edit Lesson', 'This lesson was cancelled or changed by someone else. Reload the student\'s profile and try again.', (int) $appointment['student_id']);
                    return;
                }

                // Open the student's profile after saving.
                redirect('/students/profile?id=' . (int) $fields['student_id'] . '&notice=lesson-updated', 303);
                return;

            } catch (Throwable $exception) {
                error_log('Edit lesson UPDATE failed: ' . $exception->getMessage());
                $errors[] = 'Lesson could not be saved. Please try again.';
                http_response_code(500);
            }
        }

        render_edit_lesson_form($id, $fields, $errors, $check);
    });

    // Ask for confirmation before cancelling a scheduled lesson.
    $router->get('/appointments/cancel', static function (): void {
        $appointment = load_changeable_lesson(positive_id($_GET, 'id'), 'Cancel Lesson');

        if ($appointment === null) {
            return;
        }

        render('cancel-lesson', [
            'title' => 'Cancel Lesson',
            'appointment' => $appointment,
            'csrfToken' => csrf_token('cancel_lesson_csrf'),
            'errors' => [],
        ]);
    });

    // Mark a scheduled lesson as cancelled.
    $router->post('/appointments/cancel', static function (): void {
        if (!require_local_writes('Lesson cancellation is disabled in this environment.')
            || !require_csrf('cancel_lesson_csrf')) {
            return;
        }

        $id = positive_id($_POST, 'appointment_id');
        $appointment = load_changeable_lesson($id, 'Cancel Lesson');

        if ($appointment === null) {
            return;
        }

        try {
            if (!Appointments::cancel(WriteDatabase::connect(), $id)) {
                http_response_code(409);
                render_lesson_notice('Cancel Lesson', 'This lesson was already cancelled or changed. Reload the student\'s profile to see its current status.', (int) $appointment['student_id']);
                return;
            }

            redirect('/students/profile?id=' . (int) $appointment['student_id'] . '&notice=lesson-cancelled', 303);
            return;

        } catch (Throwable $exception) {
            error_log('Cancel lesson UPDATE failed: ' . $exception->getMessage());
            http_response_code(500);
        }

        render('cancel-lesson', [
            'title' => 'Cancel Lesson',
            'appointment' => $appointment,
            'csrfToken' => csrf_token('cancel_lesson_csrf'),
            'errors' => ['Lesson could not be cancelled. Please try again.'],
        ]);
    });

    $router->get('/financials', static function (): void {
        render('financials', [
            'title' => 'Financial Dashboard',
        ]);
    });

    return $router;
}

/**
 * Validates the schedule / edit lesson form and loads the student and lesson
 * type options it needs. $includeStudentId keeps a lesson's current student
 * valid when editing, even if they are no longer active.
 *
 * @param array<string, string> $fields
 * @return array{errors: list<string>, studentOptions: array, studentOptionsError: ?string, lessonOptions: list<string>, lessonOptionsError: ?string}
 */
function check_lesson_form(array $fields, ?int $includeStudentId = null): array
{
    $errors = Appointments::validate($fields, app_timezone());

    $studentOptions = [];
    $studentOptionsError = null;

    try {
        $studentOptions = Appointments::studentOptions(ReadOnlyDatabase::connect(), $includeStudentId);

        // Verify the selected student is one of the options.
        if (ctype_digit($fields['student_id']) && (int) $fields['student_id'] > 0) {
            $validIds = array_map(static fn (array $student): string => (string) $student['id'], $studentOptions);

            if (!in_array($fields['student_id'], $validIds, true)) {
                $errors[] = 'Please select an active student.';
            }
        }
    } catch (Throwable $exception) {
        error_log('Lesson form students: ' . $exception->getMessage());
        $studentOptionsError = 'Student list could not be loaded.';
        $errors[] = 'Student selection could not be verified.';
    }

    $lessonOptions = [];
    $lessonOptionsError = null;

    try {
        $lessonOptions = Appointments::lessonTypes(ReadOnlyDatabase::connect());

        if ($fields['lesson_type'] !== '' && !in_array($fields['lesson_type'], $lessonOptions, true)) {
            $errors[] = 'Please select a valid lesson type.';
        }
    } catch (Throwable $exception) {
        error_log('Lesson form lesson types: ' . $exception->getMessage());
        $lessonOptionsError = 'Lesson types could not be loaded.';
        $errors[] = 'Lesson type could not be verified.';
    }

    return [
        'errors' => $errors,
        'studentOptions' => $studentOptions,
        'studentOptionsError' => $studentOptionsError,
        'lessonOptions' => $lessonOptions,
        'lessonOptionsError' => $lessonOptionsError,
    ];
}

/**
 * Renders the schedule form in edit mode for an existing lesson.
 *
 * @param array<string, string> $fields
 * @param list<string> $errors
 * @param array{studentOptions: array, studentOptionsError: ?string, lessonOptions: list<string>, lessonOptionsError: ?string} $options
 */
function render_edit_lesson_form(int $id, array $fields, array $errors, array $options): void
{
    render('schedule', [
        'title' => 'Edit Lesson',
        'heading' => 'Edit Lesson',
        'lead' => 'Change the student, lesson type, date, or time for this lesson.',
        'formAction' => '/appointments/edit',
        'csrfToken' => csrf_token('edit_lesson_csrf'),
        'submitLabel' => 'Save Changes',
        'backHref' => '/students/profile?id=' . (int) $fields['student_id'],
        'hiddenFields' => ['appointment_id' => $id],
        'fields' => $fields,
        'errors' => $errors,
        'preview' => false,
        'studentOptions' => $options['studentOptions'],
        'studentOptionsError' => $options['studentOptionsError'],
        'lessonOptions' => $options['lessonOptions'],
        'lessonOptionsError' => $options['lessonOptionsError'],
    ]);
}

/**
 * Loads a lesson that is about to be edited or cancelled. If it's missing,
 * can't be loaded, or can no longer be changed, renders a message page with
 * the right status code and returns null.
 *
 * @return array<string, mixed>|null
 */
function load_changeable_lesson(?int $id, string $title): ?array
{
    if ($id === null) {
        http_response_code(400);
        render_lesson_notice($title, 'A valid lesson ID is required.');
        return null;
    }

    try {
        $appointment = Appointments::find(ReadOnlyDatabase::connect(), $id);
    } catch (Throwable $exception) {
        error_log($title . ' load: ' . $exception->getMessage());
        http_response_code(503);
        render_lesson_notice($title, 'This lesson could not be loaded. Please try again later.');
        return null;
    }

    if ($appointment === null) {
        http_response_code(404);
        render_lesson_notice($title, 'Lesson not found.');
        return null;
    }

    $lockedReason = Appointments::lockedReason($appointment, app_timezone());

    if ($lockedReason !== null) {
        http_response_code(409);
        render_lesson_notice($title, $lockedReason, (int) $appointment['student_id']);
        return null;
    }

    return $appointment;
}

/** Renders a short message page for lesson actions that can't continue. */
function render_lesson_notice(string $title, string $message, ?int $studentId = null): void
{
    render('lesson-notice', [
        'title' => $title,
        'message' => $message,
        'backHref' => $studentId !== null ? '/students/profile?id=' . $studentId : '/dashboard',
        'backLabel' => $studentId !== null ? 'Back to student' : 'Back to dashboard',
    ]);
}

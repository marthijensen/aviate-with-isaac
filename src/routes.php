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
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        
        $students = [];
        try {
            $db = ReadOnlyDatabase::connect();
            $statement = $db->query(
                "SELECT id, first_name, last_name, status
                FROM public.students
                WHERE status = 'active'
                ORDER BY last_name ASC"
            );
            $students = $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            error_log('Students list error: ' . $exception->getMessage());
            $students = [];
        }
        
        $byId = [];
        foreach ($students as $student) {
            $byId[$student['id']] = $student;
        }
        $selectedStudent = $byId[$studentId] ?? null;

        render('students', [
            'title' => 'Students',
            'students' => $students,
            'selectedStudentId' => $studentId,
            'selectedStudent' => $selectedStudent,
            'byId' => $byId,
        ]);
    });
    
    $router->get('/student-profile', static function (): void {
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
            // TEMPORARY: Print the real error on screen
            echo '<pre style="background: #fee; padding: 15px; border: 1px solid red; color: red;">';
            echo 'DATABASE ERROR: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            echo '</pre>';
            exit;

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

        $lessonOptions = [];
        $lessonOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $lessonOptions = $db->query(
                "SELECT DISTINCT TRIM(lesson_type) AS lesson_type
                FROM public.lessons
                WHERE lesson_type IS NOT NULL
                AND TRIM(lesson_type) <> ''
                ORDER BY lesson_type ASC"
            )->fetchAll(PDO::FETCH_COLUMN);

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


        $lessonOptions = [];
        $lessonOptionsError = null;

        try {
            $db = ReadOnlyDatabase::connect();

            $lessonOptions = $db->query(
                "SELECT DISTINCT TRIM(lesson_type) AS lesson_type
                FROM public.lessons
                WHERE lesson_type IS NOT NULL
                AND TRIM(lesson_type) <> ''
                ORDER BY lesson_type ASC"
            )->fetchAll(PDO::FETCH_COLUMN);

            if ($fields['lesson_type'] !== ''
                && !in_array($fields['lesson_type'], $lessonOptions, true)) {
                $errors[] = 'Please select a valid lesson type.';
            }

        } catch (Throwable $exception) {
            error_log('Schedule lesson types: ' . $exception->getMessage());
            $lessonOptionsError = 'Lesson types could not be loaded.';
            $errors[] = 'Lesson type could not be verified.';
        }

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


    $router->get('/financials', static function (): void {
        render('financials', [
            'title' => 'Financial Dashboard',
        ]);
    });

    return $router;
}

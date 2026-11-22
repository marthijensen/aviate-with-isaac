<?php

declare(strict_types=1);

// Run with: php tests/AppointmentsTest.php
//
// Runs the Appointments SQL against an in-memory SQLite database attached as
// "public", so queries on public.appointments work without a Postgres server.
// Requires the pdo_sqlite extension.

require_once dirname(__DIR__) . '/src/bootstrap.php';

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
    if (!$condition) {
        $failures++;
    }
}

function test_db(): PDO
{
    $db = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec("ATTACH DATABASE ':memory:' AS public");
    $db->exec('CREATE TABLE public.students (id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT, status TEXT)');
    $db->exec('CREATE TABLE public.lessons (id INTEGER PRIMARY KEY, lesson_type TEXT)');
    $db->exec('CREATE TABLE public.appointments (id INTEGER PRIMARY KEY, student_id INTEGER, apointment_date TEXT, start_time TEXT, apointment_type TEXT, status TEXT)');

    $db->exec("INSERT INTO public.students VALUES
        (1, 'Ava', 'Lee', 'active'),
        (2, 'Ben', 'Ortiz', 'active'),
        (3, 'Cole', 'Diaz', 'inactive')");
    $db->exec("INSERT INTO public.lessons (lesson_type) VALUES (' Flight '), ('Ground'), ('Ground'), (''), (NULL)");

    return $db;
}

function add_appointment(PDO $db, int $id, int $studentId, string $date, string $status = 'scheduled'): void
{
    $db->prepare('INSERT INTO public.appointments VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$id, $studentId, $date, '09:00:00', 'Flight', $status]);
}

$zone = new DateTimeZone('America/Denver');
$today = new DateTimeImmutable('today', $zone);
$tomorrow = $today->modify('+1 day')->format('Y-m-d');
$yesterday = $today->modify('-1 day')->format('Y-m-d');

$validForm = [
    'student_id' => '2',
    'lesson_type' => 'Ground',
    'appointment_date' => $tomorrow,
    'start_time' => '14:30',
];

// --- Form validation (shared by Schedule Lesson and Edit Lesson) ---

check(Appointments::validate($validForm, $zone) === [], 'valid form has no errors');
check(Appointments::readForm(['student_id' => ' 2 ', 'lesson_type' => ['x']]) === [
    'student_id' => '2', 'lesson_type' => '', 'appointment_date' => '', 'start_time' => '',
], 'readForm trims values and ignores non-strings');

$errors = Appointments::validate(['student_id' => 'abc', 'lesson_type' => '', 'appointment_date' => '2026-02-30', 'start_time' => '25:00'], $zone);
check($errors === [
    'A valid student ID is required.',
    'Please select a lesson type.',
    'Please enter a valid appointment date.',
    'Please enter a valid start time.',
], 'invalid fields return the existing Schedule Lesson messages');

$errors = Appointments::validate(['appointment_date' => $yesterday] + $validForm, $zone);
check($errors === ['The appointment date cannot be in the past.'], 'past date is rejected');

// --- Options ---

$db = test_db();
check(array_column(Appointments::studentOptions($db), 'id') === [1, 2], 'student options are active students only');
check(array_column(Appointments::studentOptions($db, 3), 'id') === [3, 1, 2], 'editing keeps the lesson\'s inactive student selectable');
check(Appointments::lessonTypes($db) === ['Flight', 'Ground'], 'lesson types are trimmed, distinct, and skip blanks');

// --- Find ---

add_appointment($db, 10, 1, $tomorrow);
$found = Appointments::find($db, 10);
check($found !== null && $found['first_name'] === 'Ava' && $found['apointment_type'] === 'Flight', 'find loads the lesson with its student');
check(Appointments::find($db, 999) === null, 'find returns null for a missing lesson');

// --- Which lessons can be changed ---

check(Appointments::lockedReason(['status' => 'scheduled', 'apointment_date' => $tomorrow], $zone) === null, 'future scheduled lesson can be changed');
check(Appointments::lockedReason(['status' => 'scheduled', 'apointment_date' => $today->format('Y-m-d')], $zone) === null, 'lesson later today can be changed');
check(Appointments::lockedReason(['status' => 'scheduled', 'apointment_date' => $yesterday], $zone) === 'Past lessons can\'t be changed.', 'past lesson is locked');
check(Appointments::lockedReason(['status' => 'Cancelled', 'apointment_date' => $tomorrow], $zone) === 'This lesson has been cancelled.', 'cancelled lesson is locked');
check(Appointments::lockedReason(['status' => 'completed', 'apointment_date' => $tomorrow], $zone) === 'Only scheduled lessons can be changed.', 'completed lesson is locked');

// --- Update ---

check(Appointments::update($db, 10, $validForm), 'update saves a scheduled lesson');
$saved = Appointments::find($db, 10);
check(
    (int) $saved['student_id'] === 2
    && $saved['apointment_type'] === 'Ground'
    && $saved['apointment_date'] === $tomorrow
    && $saved['start_time'] === '14:30'
    && $saved['status'] === 'scheduled',
    'update changes student, type, date and time but not status'
);

add_appointment($db, 11, 1, $tomorrow, 'cancelled');
check(!Appointments::update($db, 11, $validForm), 'update refuses a cancelled lesson');
check(Appointments::find($db, 11)['apointment_type'] === 'Flight', 'cancelled lesson is left unchanged');
check(!Appointments::update($db, 999, $validForm), 'update reports a missing lesson');

add_appointment($db, 12, 1, $tomorrow);
Appointments::update($db, 12, $validForm);
check(Appointments::find($db, 10)['start_time'] === '14:30' && (int) Appointments::find($db, 12)['student_id'] === 2, 'update only changes the selected lesson');

// --- Cancel ---

add_appointment($db, 20, 1, $tomorrow);
check(Appointments::cancel($db, 20), 'cancel marks a scheduled lesson');
$cancelled = Appointments::find($db, 20);
check($cancelled !== null && $cancelled['status'] === 'cancelled', 'cancelled lesson is kept with status "cancelled"');
check(!Appointments::cancel($db, 20), 'cancelling twice is refused');
check(Appointments::lockedReason($cancelled, $zone) === 'This lesson has been cancelled.', 'cancelled lesson can no longer be edited');

add_appointment($db, 21, 1, $tomorrow, 'completed');
check(!Appointments::cancel($db, 21) && Appointments::find($db, 21)['status'] === 'completed', 'completed lesson is not cancelled');
check(!Appointments::cancel($db, 999), 'cancel reports a missing lesson');

// A cancelled lesson must never count as upcoming or completed.
$upcoming = UpcomingLessons::prepare([$cancelled], [], new DateTimeImmutable('now', $zone), $zone);
check($upcoming === [], 'cancelled lesson is not on the dashboard');
check(Appointments::statusLabel('cancelled') === 'Cancelled' && Appointments::statusLabel('canceled') === 'Cancelled', 'both spellings show as "Cancelled"');
check(Appointments::statusLabel('completed') === 'Completed' && Appointments::statusLabel(null) === 'Scheduled', 'other statuses get readable labels');

// --- Views ---

function render_view(string $view, array $vars): string
{
    extract($vars);
    ob_start();
    require dirname(__DIR__) . "/views/{$view}.php";
    return (string) ob_get_clean();
}

$profileVars = [
    'notice' => 'Lesson cancelled.',
    'error' => null,
    'student' => ['id' => 1, 'first_name' => 'Ava', 'last_name' => 'Lee'],
    'appointmentsError' => null,
    'recentLessons' => [['apointment_date' => $yesterday, 'apointment_type' => 'Flight', 'status' => 'canceled']],
    'upcomingAppointments' => [
        ['id' => 30, 'apointment_date' => $tomorrow, 'start_time' => '09:00:00', 'apointment_type' => 'Flight', 'status' => 'scheduled'],
        ['id' => 31, 'apointment_date' => $tomorrow, 'start_time' => '11:00:00', 'apointment_type' => 'Ground', 'status' => 'cancelled'],
    ],
];
$html = render_view('student-profile', $profileVars);
check(str_contains($html, 'Lesson cancelled.'), 'profile shows the cancellation confirmation');
check(str_contains($html, '/appointments/edit?id=30') && str_contains($html, '/appointments/cancel?id=30'), 'scheduled lesson has Edit and Cancel links');
check(!str_contains($html, '/appointments/edit?id=31') && !str_contains($html, '/appointments/cancel?id=31'), 'cancelled lesson has no Edit or Cancel links');
check(substr_count($html, '<em class="warn">Cancelled</em>') === 2, 'cancelled lessons are labelled "Cancelled" in both lists');
check(!str_contains($html, 'Completed'), 'cancelled lesson is not shown as completed');

$html = render_view('cancel-lesson', [
    'appointment' => ['id' => 30, 'student_id' => 1, 'first_name' => 'Ava', 'last_name' => 'Lee', 'apointment_date' => '2026-12-01', 'start_time' => '14:30:00', 'apointment_type' => 'Flight'],
    'csrfToken' => 'token-123',
    'errors' => [],
]);
check(str_contains($html, 'Ava Lee') && str_contains($html, '2:30 PM') && str_contains($html, 'Tue 12/1/2026'), 'confirmation page shows the lesson details');
check(str_contains($html, 'name="appointment_id" value="30"') && str_contains($html, 'value="token-123"'), 'confirmation form posts the lesson ID and token');
check(str_contains($html, 'href="/students/profile?id=1">Keep Lesson'), 'Keep Lesson returns to the student');

// --- Display helpers ---

check(Appointments::timeInputValue('14:30:00') === '14:30', 'Postgres time converts to a time input value');
check(Appointments::timeInputValue(null) === '', 'missing time converts to an empty input');

echo PHP_EOL . ($failures === 0 ? 'All tests passed.' : "{$failures} test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);

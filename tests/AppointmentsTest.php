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

// --- Display helpers ---

check(Appointments::timeInputValue('14:30:00') === '14:30', 'Postgres time converts to a time input value');
check(Appointments::timeInputValue(null) === '', 'missing time converts to an empty input');

echo PHP_EOL . ($failures === 0 ? 'All tests passed.' : "{$failures} test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);

<?php

declare(strict_types=1);

// Run with: php tests/UpcomingLessonsTest.php

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

/** Builds a row shaped like the `appointments` table. */
function appointment(int $id, string $date, ?string $time = '09:00:00', ?string $status = 'scheduled', ?int $studentId = 1, ?string $type = 'Flight'): array
{
    return ['id' => $id, 'student_id' => $studentId, 'apointment_date' => $date, 'start_time' => $time, 'apointment_type' => $type, 'status' => $status];
}

function ids(array $lessons): array
{
    return array_map('intval', array_column($lessons, 'id'));
}

function renderCard(array $upcomingLessons, ?string $upcomingLessonsError = null): string
{
    ob_start();
    require dirname(__DIR__) . '/views/upcoming-lessons.php';
    return (string) ob_get_clean();
}

$zone = new DateTimeZone('America/Denver');
// Tue Nov 3, 2026, 10:00 AM in Denver.
$now = new DateTimeImmutable('2026-11-03 10:00:00', $zone);
$students = [
    ['id' => 1, 'first_name' => 'Ava', 'last_name' => 'Lee'],
    ['id' => 2, 'first_name' => 'Ben', 'last_name' => 'Ortiz'],
    ['id' => 3, 'first_name' => 'Cole', 'last_name' => 'Diaz'],
];

// Multiple upcoming lessons are sorted by date, then time.
$rows = [
    appointment(3, '2026-11-06', '08:00:00', studentId: 3),
    appointment(1, '2026-11-04', '14:00:00', studentId: 1),
    appointment(2, '2026-11-04', '09:30:00', studentId: 2, type: 'Ground'),
];
$result = UpcomingLessons::prepare($rows, $students, $now, $zone);
check(ids($result) === [2, 1, 3], 'multiple upcoming lessons are sorted by date and time');
check($result[0]['student_name'] === 'Ben Ortiz', 'student name comes from the students table');
check($result[0]['date'] === 'Wed 11/4', 'date uses the dashboard format');
check($result[0]['time'] === '9:30 AM', 'time uses the dashboard format');
check($result[0]['lesson_type'] === 'Ground', 'lesson comes from apointment_type');

// Past, completed and cancelled lessons do not appear.
$rows = [
    appointment(10, '2026-11-02', '15:00:00'),                       // yesterday
    appointment(11, '2026-11-03', '09:59:00'),                       // earlier today
    appointment(12, '2026-11-03', '10:00:00'),                       // starting now
    appointment(13, '2026-11-05', '09:00:00', 'completed'),
    appointment(14, '2026-11-05', '09:00:00', 'cancelled'),
    appointment(15, '2026-11-05', '09:00:00', 'Canceled'),
    appointment(16, '2026-11-05', '09:00:00', null),                 // no status yet
];
$result = UpcomingLessons::prepare($rows, $students, $now, $zone);
check(ids($result) === [12, 16], 'past, completed and cancelled lessons are excluded');

// Appointments without a start time stay listed for their whole day, after timed ones.
$rows = [
    appointment(20, '2026-11-03', null),
    appointment(21, '2026-11-03', '16:00:00'),
    appointment(22, '2026-11-02', null),
];
$result = UpcomingLessons::prepare($rows, $students, $now, $zone);
check(ids($result) === [21, 20], 'lessons without a time are kept for their day and sorted last');
check($result[1]['time'] === 'Time TBD', 'missing time shows Time TBD');

// Missing or unknown data falls back like aviate-app.html.
$result = UpcomingLessons::prepare([appointment(30, '2026-11-04', studentId: 99, type: '')], $students, $now, $zone);
check($result[0]['student_name'] === 'Student', 'unknown student falls back to "Student"');
check($result[0]['lesson_type'] === 'Lesson', 'blank appointment type falls back to "Lesson"');
check(UpcomingLessons::prepare([appointment(31, 'not-a-date')], $students, $now, $zone) === [], 'invalid dates are skipped');

// The number shown is limited.
$rows = array_map(static fn (int $i): array => appointment($i, '2026-11-10', sprintf('%02d:00:00', $i)), range(1, 15));
check(count(UpcomingLessons::prepare($rows, $students, $now, $zone, 10)) === 10, 'list is limited to 10 lessons');

// Empty state.
check(UpcomingLessons::prepare([], $students, $now, $zone) === [], 'no rows returns no lessons');
$html = renderCard([]);
check(str_contains($html, 'No upcoming lessons scheduled.'), 'empty state is shown when no lessons exist');

// Rendered card lists lessons and escapes output.
$html = renderCard([['id' => '1', 'student_name' => '<b>Eve</b>', 'date' => 'Wed 11/4', 'time' => '9:30 AM', 'lesson_type' => 'Flight']]);
check(str_contains($html, '&lt;b&gt;Eve&lt;/b&gt;') && !str_contains($html, '<b>Eve</b>'), 'student name is HTML-escaped');
check(str_contains($html, 'Wed 11/4') && str_contains($html, '9:30 AM'), 'card shows date and time');
check(!str_contains($html, 'No upcoming lessons scheduled.'), 'empty state is hidden when lessons exist');

// On a load error the card says so instead of claiming nothing is scheduled.
$html = renderCard([], 'Upcoming lessons could not be loaded.');
check(str_contains($html, 'Upcoming lessons are unavailable.') && !str_contains($html, 'No upcoming lessons'), 'load error is shown');

echo PHP_EOL . ($failures === 0 ? 'All tests passed.' : "{$failures} test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);

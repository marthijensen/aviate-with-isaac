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

function lesson(string $id, string $start, string $status = 'scheduled', ?array $student = ['first_name' => 'Ava', 'last_name' => 'Lee'], string $type = 'flight'): array
{
    return ['id' => $id, 'scheduled_start' => $start, 'status' => $status, 'lesson_type' => $type, 'student' => $student];
}

function renderCard(array $upcomingLessons, ?string $upcomingLessonsError = null): string
{
    ob_start();
    require dirname(__DIR__) . '/views/upcoming-lessons.php';
    return (string) ob_get_clean();
}

$now = new DateTimeImmutable('2026-10-06T15:00:00Z');
$utc = new DateTimeZone('UTC');

// Multiple upcoming lessons are sorted by date/time.
$rows = [
    lesson('c', '2026-10-09T13:30:00Z', student: ['first_name' => 'Cole', 'last_name' => 'Diaz']),
    lesson('a', '2026-10-06T16:00:00Z', student: ['first_name' => 'Ava', 'last_name' => 'Lee']),
    lesson('b', '2026-10-07T09:15:00Z', student: ['first_name' => 'Ben', 'last_name' => 'Ortiz'], type: 'ground'),
];
$result = UpcomingLessons::prepare($rows, $now, $utc);
check(array_column($result, 'id') === ['a', 'b', 'c'], 'multiple upcoming lessons are sorted soonest first');
check($result[0]['student_name'] === 'Ava Lee', 'student name is displayed');
check($result[0]['date'] === 'Tue, Oct 6, 2026', 'lesson date is formatted');
check($result[0]['time'] === '4:00 PM UTC', 'lesson time is formatted');
check($result[1]['lesson_type'] === 'Ground', 'lesson type is formatted');

// Past, completed and cancelled lessons do not appear.
$rows = [
    lesson('past', '2026-10-05T15:00:00Z'),
    lesson('earlier-today', '2026-10-06T14:59:00Z'),
    lesson('completed', '2026-10-08T15:00:00Z', 'completed'),
    lesson('cancelled', '2026-10-08T15:00:00Z', 'cancelled'),
    lesson('future', '2026-10-08T15:00:00Z'),
];
$result = UpcomingLessons::prepare($rows, $now, $utc);
check(array_column($result, 'id') === ['future'], 'completed, cancelled and past lessons are excluded');

// Times are shown in the instructor's time zone.
$denver = new DateTimeZone('America/Denver');
$result = UpcomingLessons::prepare([lesson('x', '2026-10-07T15:00:00Z')], $now, $denver);
check($result[0]['time'] === '9:00 AM MDT', 'time is converted to the display time zone');
check($result[0]['date'] === 'Wed, Oct 7, 2026', 'date is converted to the display time zone');

// Missing student data is handled.
$result = UpcomingLessons::prepare([lesson('x', '2026-10-07T15:00:00Z', student: null)], $now, $utc);
check($result[0]['student_name'] === 'Unknown student', 'missing student falls back to a placeholder');

// Empty state.
check(UpcomingLessons::prepare([], $now, $utc) === [], 'no rows returns no lessons');
$html = renderCard([]);
check(str_contains($html, 'No upcoming lessons scheduled.'), 'empty state is shown when no lessons exist');

// Rendered card lists lessons and escapes output.
$html = renderCard([['id' => '1', 'student_name' => '<b>Eve</b>', 'date' => 'Tue, Oct 6, 2026', 'time' => '4:00 PM UTC', 'lesson_type' => 'Flight']]);
check(str_contains($html, '&lt;b&gt;Eve&lt;/b&gt;') && !str_contains($html, '<b>Eve</b>'), 'student name is HTML-escaped');
check(str_contains($html, 'Tue, Oct 6, 2026') && str_contains($html, '4:00 PM UTC'), 'card shows date and time');
check(!str_contains($html, 'No upcoming lessons scheduled.'), 'empty state is hidden when lessons exist');

// Errors are shown instead of the table.
$html = renderCard([], 'Upcoming lessons could not be loaded.');
check(str_contains($html, 'role="alert"') && !str_contains($html, 'No upcoming lessons'), 'load error is shown');

echo PHP_EOL . ($failures === 0 ? 'All tests passed.' : "{$failures} test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);

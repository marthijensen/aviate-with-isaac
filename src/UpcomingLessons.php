<?php

declare(strict_types=1);

/**
 * Story 19 - View upcoming lessons.
 *
 * Reads scheduled lessons from the `appointments` table (the same table
 * aviate-app.html writes to when a lesson is scheduled) and prepares the
 * ones that haven't happened yet for the dashboard.
 *
 * `apointment_date` and `start_time` are a local date and time with no
 * time zone, so they are interpreted in the app's time zone (APP_TIMEZONE).
 */
final class UpcomingLessons
{
    public const DEFAULT_LIMIT = 10;

    /** Appointment statuses that never count as upcoming. */
    private const EXCLUDED_STATUSES = ['cancelled', 'canceled', 'completed'];

    /**
     * Fetches upcoming appointments and their students from Supabase, soonest first.
     *
     * @return list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}>
     */
    public static function fetch(SupabaseClient $supabase, DateTimeImmutable $now, DateTimeZone $zone, int $limit = self::DEFAULT_LIMIT): array
    {
        $appointments = $supabase->select('appointments', [
            'select' => 'id,student_id,apointment_date,start_time,apointment_type,status',
            'apointment_date' => 'gte.' . $now->setTimezone($zone)->format('Y-m-d'),
            'order' => 'apointment_date.asc,start_time.asc',
        ]);

        $studentIds = array_values(array_unique(array_filter(
            array_map(static fn (array $row): int => (int) ($row['student_id'] ?? 0), $appointments)
        )));

        $students = $studentIds === [] ? [] : $supabase->select('students', [
            'select' => 'id,first_name,last_name',
            'id' => 'in.(' . implode(',', $studentIds) . ')',
        ]);

        return self::prepare($appointments, $students, $now, $zone, $limit);
    }

    /**
     * Keeps appointments that haven't started and aren't cancelled or
     * completed, sorts them by date/time and formats them for display.
     *
     * @param list<array<string, mixed>> $appointments Rows from `appointments`
     * @param list<array<string, mixed>> $students Rows from `students`
     * @return list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}>
     */
    public static function prepare(array $appointments, array $students, DateTimeImmutable $now, DateTimeZone $zone, int $limit = self::DEFAULT_LIMIT): array
    {
        $namesById = [];
        foreach ($students as $student) {
            $namesById[(string) ($student['id'] ?? '')] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        }

        $today = $now->setTimezone($zone)->format('Y-m-d');
        $lessons = [];

        foreach ($appointments as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (in_array($status, self::EXCLUDED_STATUSES, true)) {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($row['apointment_date'] ?? ''), $zone);
            if ($date === false) {
                continue;
            }

            $time = trim((string) ($row['start_time'] ?? ''));
            $start = $time !== '' ? self::combine($date, $time) : null;

            if ($start !== null ? $start < $now : $date->format('Y-m-d') < $today) {
                continue;
            }

            $lessons[] = [
                // Appointments without a start time sort to the end of their day.
                'sort' => $start ?? $date->setTime(23, 59, 59),
                'start' => $start,
                'date' => $date,
                'row' => $row,
            ];
        }

        usort($lessons, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(static function (array $lesson) use ($namesById): array {
            $name = $namesById[(string) ($lesson['row']['student_id'] ?? '')] ?? '';
            $type = trim((string) ($lesson['row']['apointment_type'] ?? ''));

            return [
                'id' => (string) ($lesson['row']['id'] ?? ''),
                // Fallbacks and formats match the cards in aviate-app.html.
                'student_name' => $name !== '' ? $name : 'Student',
                'date' => $lesson['date']->format('D n/j'),
                'time' => $lesson['start'] ? $lesson['start']->format('g:i A') : 'Time TBD',
                'lesson_type' => $type !== '' ? $type : 'Lesson',
            ];
        }, array_slice($lessons, 0, $limit));
    }

    /** Combines a local date with a Postgres `time` value such as "14:30:00". */
    private static function combine(DateTimeImmutable $date, string $time): ?DateTimeImmutable
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $time, $parts)) {
            return null;
        }

        return $date->setTime((int) $parts[1], (int) $parts[2], (int) ($parts[3] ?? 0));
    }
}

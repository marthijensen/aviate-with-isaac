<?php

declare(strict_types=1);

/**
 * Story 19 - View upcoming lessons.
 *
 * Retrieves future scheduled lessons and prepares them for the dashboard.
 */
final class UpcomingLessons
{
    public const DEFAULT_LIMIT = 10;

    /**
     * Fetches future, scheduled lessons from Supabase, soonest first.
     *
     * @return list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}>
     */
    public static function fetch(SupabaseClient $supabase, DateTimeImmutable $now, DateTimeZone $displayZone, int $limit = self::DEFAULT_LIMIT): array
    {
        $rows = $supabase->select('lessons', [
            'select' => 'id,scheduled_start,status,lesson_type,student:students(first_name,last_name)',
            'status' => 'eq.scheduled',
            'scheduled_start' => 'gte.' . $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'order' => 'scheduled_start.asc',
            'limit' => (string) $limit,
        ]);

        return self::prepare($rows, $now, $displayZone);
    }

    /**
     * Keeps only scheduled lessons that have not started yet, sorts them by
     * date/time and formats them for display.
     *
     * The database query already filters and sorts; this repeats it so the
     * dashboard stays correct regardless of the data source.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}>
     */
    public static function prepare(array $rows, DateTimeImmutable $now, DateTimeZone $displayZone): array
    {
        $lessons = [];

        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'scheduled' || empty($row['scheduled_start'])) {
                continue;
            }

            $start = new DateTimeImmutable((string) $row['scheduled_start']);
            if ($start < $now) {
                continue;
            }

            $lessons[] = ['start' => $start, 'row' => $row];
        }

        usort($lessons, static fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        return array_map(static function (array $lesson) use ($displayZone): array {
            $local = $lesson['start']->setTimezone($displayZone);

            return [
                'id' => (string) ($lesson['row']['id'] ?? ''),
                'student_name' => self::studentName($lesson['row']['student'] ?? null),
                'date' => $local->format('D, M j, Y'),
                'time' => $local->format('g:i A T'),
                'lesson_type' => ucfirst((string) ($lesson['row']['lesson_type'] ?? '')),
            ];
        }, $lessons);
    }

    private static function studentName(mixed $student): string
    {
        if (!is_array($student)) {
            return 'Unknown student';
        }

        $name = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        return $name !== '' ? $name : 'Unknown student';
    }
}

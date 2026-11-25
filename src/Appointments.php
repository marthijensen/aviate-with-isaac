<?php

declare(strict_types=1);

/**
 * Reads, validates and changes rows in public.appointments.
 *
 * Shared by Schedule Lesson (insert), Edit Scheduled Lesson (update) and
 * Cancel Lesson so all three apply the same rules.
 */
final class Appointments
{
    /** Form fields posted by the schedule / edit lesson form. */
    public const FORM_FIELDS = ['student_id', 'lesson_type', 'appointment_date', 'start_time'];

    /**
     * Reads the lesson form fields from $_POST, trimmed.
     *
     * @return array<string, string>
     */
    public static function readForm(array $post): array
    {
        $fields = [];

        foreach (self::FORM_FIELDS as $name) {
            $value = $post[$name] ?? '';
            $fields[$name] = is_string($value) ? trim($value) : '';
        }

        return $fields;
    }

    /**
     * Checks the format of the lesson form fields. Whether the student and
     * lesson type exist is checked separately against the option lists.
     *
     * @param array<string, string> $fields
     * @return list<string> Error messages
     */
    public static function validate(array $fields, DateTimeZone $zone): array
    {
        $errors = [];

        if (!ctype_digit($fields['student_id']) || (int) $fields['student_id'] < 1) {
            $errors[] = 'A valid student ID is required.';
        }

        if ($fields['lesson_type'] === '') {
            $errors[] = 'Please select a lesson type.';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fields['appointment_date'], $zone);

        if ($date === false || $date->format('Y-m-d') !== $fields['appointment_date']) {
            $errors[] = 'Please enter a valid appointment date.';
        } elseif ($date < new DateTimeImmutable('today', $zone)) {
            $errors[] = 'The appointment date cannot be in the past.';
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $fields['start_time'])) {
            $errors[] = 'Please enter a valid start time.';
        }

        return $errors;
    }

    /**
     * Active students for the student selector. $includeStudentId keeps a
     * lesson's current student selectable even if they are no longer active.
     *
     * @return list<array{id: int|string, first_name: string, last_name: string}>
     */
    public static function studentOptions(PDO $db, ?int $includeStudentId = null): array
    {
        $statement = $db->prepare(
            "SELECT id, first_name, last_name
            FROM public.students
            WHERE status = 'active' OR id = :include_id
            ORDER BY last_name ASC, first_name ASC"
        );

        $statement->execute(['include_id' => $includeStudentId ?? 0]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<string> Lesson types for the lesson type selector. */
    public static function lessonTypes(PDO $db): array
    {
        return $db->query(
            "SELECT DISTINCT TRIM(lesson_type) AS lesson_type
            FROM public.lessons
            WHERE lesson_type IS NOT NULL
            AND TRIM(lesson_type) <> ''
            ORDER BY lesson_type ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Loads one appointment with its student's name, or null if it doesn't exist.
     *
     * @return array<string, mixed>|null
     */
    public static function find(PDO $db, int $id): ?array
    {
        $statement = $db->prepare(
            'SELECT
                a.id,
                a.student_id,
                a.apointment_date,
                a.start_time,
                a.apointment_type,
                a.status,
                s.first_name,
                s.last_name
            FROM public.appointments a
            LEFT JOIN public.students s ON s.id = a.student_id
            WHERE a.id = :id'
        );

        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Why a lesson can't be edited or cancelled, or null if it can.
     * Only scheduled lessons that haven't passed can be changed.
     *
     * @param array<string, mixed> $appointment
     */
    public static function lockedReason(array $appointment, DateTimeZone $zone): ?string
    {
        $status = strtolower(trim((string) ($appointment['status'] ?? '')));

        if (in_array($status, ['cancelled', 'canceled'], true)) {
            return 'This lesson has been cancelled.';
        }

        if ($status !== 'scheduled') {
            return 'Only scheduled lessons can be changed.';
        }

        $today = (new DateTimeImmutable('today', $zone))->format('Y-m-d');

        if ((string) ($appointment['apointment_date'] ?? '') < $today) {
            return 'Past lessons can\'t be changed.';
        }

        return null;
    }

    /**
     * Saves new details for a scheduled lesson.
     *
     * @param array<string, string> $fields Validated form fields
     * @return bool False if the lesson no longer exists or is no longer scheduled
     */
    public static function update(PDO $db, int $id, array $fields): bool
    {
        $statement = $db->prepare(
            "UPDATE public.appointments
            SET student_id = :student_id,
                apointment_date = :apointment_date,
                start_time = :start_time,
                apointment_type = :apointment_type
            WHERE id = :id
            AND status = 'scheduled'"
        );

        $statement->execute([
            'id' => $id,
            'student_id' => (int) $fields['student_id'],
            'apointment_date' => $fields['appointment_date'],
            'start_time' => $fields['start_time'],
            'apointment_type' => $fields['lesson_type'],
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * Marks a scheduled lesson as cancelled. The row is kept for the record.
     *
     * @return bool False if the lesson no longer exists or is no longer scheduled
     */
    public static function cancel(PDO $db, int $id): bool
    {
        $statement = $db->prepare(
            "UPDATE public.appointments
            SET status = 'cancelled'
            WHERE id = :id
            AND status = 'scheduled'"
        );

        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    /**
     * Converts a Postgres time ("14:30:00") to an <input type="time"> value ("14:30").
     */
    public static function timeInputValue(?string $time): string
    {
        return preg_match('/^(\d{2}:\d{2})/', (string) $time, $match) ? $match[1] : '';
    }

    /** Display label for an appointment status, e.g. "Cancelled". */
    public static function statusLabel(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            '' => 'Scheduled',
            'canceled' => 'Cancelled',
            default => ucfirst($status),
        };
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

try {
    $db = WriteDatabase::connect();

    $result = $db->query(
        "SELECT
            current_user AS username,
            has_column_privilege(
                current_user,
                'public.students',
                'first_name',
                'INSERT'
            ) AS can_add_student,
            has_column_privilege(
                current_user,
                'public.appointments',
                'student_id',
                'INSERT'
            ) AS can_schedule"
    )->fetch(PDO::FETCH_ASSOC);

    echo "Connected as: " . $result['username'] . PHP_EOL;
    echo "Student INSERT permission: "
        . var_export($result['can_add_student'], true)
        . PHP_EOL;
    echo "Appointment INSERT permission: "
        . var_export($result['can_schedule'], true)
        . PHP_EOL;

} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Writer connection failed: "
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}

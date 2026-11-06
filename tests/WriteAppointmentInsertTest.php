<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$db = null;

try {
    $db = WriteDatabase::connect();

    // Start a transaction so the test is not permanent.
    $db->beginTransaction();

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
        'student_id' => 13,
        'apointment_date' => '2026-11-12',
        'start_time' => '10:00',
        'apointment_type' => 'Private Pilot Flight',
        'status' => 'scheduled',
    ]);

    if ($statement->rowCount() !== 1) {
        throw new RuntimeException(
            'Expected exactly one appointment.'
        );
    }

    echo "Appointment INSERT successful!" . PHP_EOL;

    // Undo the test appointment.
    $db->rollBack();

    echo "Transaction rolled back successfully." . PHP_EOL;
    echo "No test appointment was saved." . PHP_EOL;

} catch (Throwable $exception) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(
        STDERR,
        "Appointment INSERT failed: "
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}

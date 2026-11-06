<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$db = null;

try {
    $db = WriteDatabase::connect();

    $db->beginTransaction();

    $statement = $db->prepare(
        "INSERT INTO public.students (
            first_name,
            last_name,
            email,
            course_id
        ) VALUES (
            :first_name,
            :last_name,
            :email,
            :course_id
        )"
    );

    $statement->execute([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'email' => 'test-' . bin2hex(random_bytes(6))
            . '@example.invalid',
        'course_id' => 1,
    ]);

    if ($statement->rowCount() !== 1) {
        throw new RuntimeException(
            'Expected exactly one inserted row.'
        );
    }

    echo "Student INSERT successful!" . PHP_EOL;

    // Undo the test; leave no permanent student record.
    $db->rollBack();

    echo "Transaction rolled back successfully." . PHP_EOL;
    echo "No test student was saved." . PHP_EOL;

} catch (Throwable $exception) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(
        STDERR,
        "INSERT test failed: "
        . $exception->getMessage()
        . PHP_EOL
    );

    exit(1);
}


<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/ReadOnlyDatabase.php';

try {
    $db = ReadOnlyDatabase::connect();

    // Verify that we are using the intended database role.
    $role = $db->query('SELECT current_user')->fetchColumn();

    if ($role !== 'readonly_user') {
        throw new RuntimeException(
            'Unexpected database role. Connection test stopped.'
        );
    }

    echo "PASS: Connected using readonly_user.\n";

    // Count records without retrieving personal information.
    $students = $db->query(
        'SELECT COUNT(*) FROM public.students'
    )->fetchColumn();

    $appointments = $db->query(
        'SELECT COUNT(*) FROM public.appointments'
    )->fetchColumn();

    echo "PASS: Students table accessible.\n";
    echo "Student records: $students\n";

    echo "PASS: Appointments table accessible.\n";
    echo "Appointment records: $appointments\n";

    echo "\nRead-only connection test completed.\n";

} catch (Throwable $error) {
    echo "FAIL: " . $error->getMessage() . "\n";
    exit(1);
}

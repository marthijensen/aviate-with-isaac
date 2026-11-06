<?php

require_once __DIR__ . '/../src/bootstrap.php';

// Fictional student information
$student = [
    'id' => 1,
    'first_name' => 'Sample',
    'last_name' => 'Student',
    'email' => 'sample@example.test',
    'phone' => '555-0100',
    'training_goal' => 'Private Pilot',
    'status' => 'active',
];

$error = null;
$appointmentsError = null;

$recentLessons = [
    [
        'apointment_date' => '2026-11-01',
        'apointment_type' => 'Ground Lesson',
        'status' => 'completed',
    ],
];

$upcomingAppointments = [
    [
        'apointment_date' => '2026-11-10',
        'apointment_type' => 'Flight Lesson',
        'start_time' => '09:30:00',
        'status' => 'scheduled',
    ],
];

// Render the view into memory instead of displaying it.
ob_start();
require __DIR__ . '/../views/student-profile.php';
$html = ob_get_clean();

// Check whether important content appears.
$checks = [
    'Student name' => 'Sample Student',
    'Recent Lessons heading' => 'Recent Lessons',
    'Upcoming Appointments heading' => 'Upcoming Appointments',
    'Recent lesson date' => '2026-11-01',
    'Recent lesson type' => 'Ground Lesson',
    'Upcoming lesson date' => '2026-11-10',
    'Upcoming lesson type' => 'Flight Lesson',
];

$failed = false;

foreach ($checks as $label => $expected) {
    if (str_contains($html, $expected)) {
        echo "PASS: $label\n";
    } else {
        echo "FAIL: $label\n";
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}

echo "\nAll student profile view checks passed.\n";

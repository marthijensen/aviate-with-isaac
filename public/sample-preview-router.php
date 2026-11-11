<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = dirname(__DIR__) . $path;

if (is_file($file)) {
    return false;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$student = [
    'id' => 42,
    'first_name' => 'Jamie',
    'last_name' => 'Chen',
    'email' => 'jamie.chen@example.test',
    'phone' => '555-0142',
    'training_goal' => 'Private Pilot',
    'status' => 'active',
];

if ($path === '/students/profile') {
    render('student-profile', [
        'title' => 'Student Profile',
        'student' => $student,
        'error' => null,
        'recentLessons' => [
            [
                'apointment_date' => '2026-10-28',
                'apointment_type' => 'Ground Lesson',
                'status' => 'completed',
            ],
        ],
        'upcomingAppointments' => [
            [
                'apointment_date' => '2026-11-18',
                'apointment_type' => 'Flight Lesson',
                'start_time' => '09:30:00',
                'status' => 'scheduled',
            ],
        ],
        'appointmentsError' => null,
    ]);
    return true;
}

if ($path === '/students/trainingprogress') {
    render('trainingprogress', [
        'title' => 'Sample Student Progress',
        'selectedStudentId' => 42,
        'selectedStudent' => $student,
        'topics' => [
            ['topic_id' => 101, 'topic' => 'Preflight Preparation', 'subtopic' => 'Aircraft documents', 'display_order' => 1],
            ['topic_id' => 102, 'topic' => 'Preflight Preparation', 'subtopic' => 'Weather briefing', 'display_order' => 2],
            ['topic_id' => 103, 'topic' => 'Preflight Preparation', 'subtopic' => 'Aircraft inspection', 'display_order' => 3],
            ['topic_id' => 104, 'topic' => 'Flight Maneuvers', 'subtopic' => 'Straight-and-level flight', 'display_order' => 4],
            ['topic_id' => 105, 'topic' => 'Flight Maneuvers', 'subtopic' => 'Turns around a point', 'display_order' => 5],
        ],
        'completed' => [101 => true, 103 => true],
        'error' => null,
        'progressLoadFailed' => false,
        'csrf' => '',
        'writesEnabled' => false,
    ]);
    return true;
}

http_response_code(404);
echo 'Sample preview route not found.';

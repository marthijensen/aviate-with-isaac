<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

function progress_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

putenv('AVIATE_LOCAL_WRITES=0');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$router = app_router();

progress_check(progress_id('123') === 123, 'Valid ID rejected');
foreach ([null, [], '0', '-1', '1abc', '1.5', '99999999999999999999999999'] as $invalid) {
    progress_check(progress_id($invalid) === null, 'Invalid ID accepted');
}

$_GET = ['id' => 'bad'];
ob_start();
$router->dispatch('GET', '/students/progress?id=bad');
$html = ob_get_clean();
progress_check(http_response_code() === 400 && str_contains($html, 'A valid student ID'), 'Invalid student route');

ob_start();
$router->dispatch('POST', '/students/student-progress');
$json = json_decode(ob_get_clean(), true);
progress_check(http_response_code() === 403 && $json['success'] === false, 'Write protection failed');

putenv('AVIATE_LOCAL_WRITES=1');
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
progress_check(!progress_writes_enabled(), 'Non-local writes allowed');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
$_SESSION['student_progress_csrf'] = 'test-token';
$_POST = ['csrf_token' => 'wrong'];
ob_start();
$router->dispatch('POST', '/students/student-progress');
$json = json_decode(ob_get_clean(), true);
progress_check(http_response_code() === 403 && $json['success'] === false, 'CSRF protection failed');

$_POST = ['csrf_token' => 'test-token', 'student_id' => '1', 'topic_id' => '2', 'is_completed' => 'invalid'];
ob_start();
$router->dispatch('POST', '/students/student-progress');
$json = json_decode(ob_get_clean(), true);
progress_check(http_response_code() === 422 && $json['success'] === false, 'Invalid status accepted');

$selectedStudentId = 1;
$selectedStudent = ['first_name' => '<Sample>', 'last_name' => 'Student', 'training_goal' => 'Private Pilot', 'course_id' => 1];
$topics = [
    ['topic_id' => 2, 'course_id' => 1, 'topic' => 'Basics', 'subtopic' => 'Preflight'],
    ['topic_id' => 3, 'course_id' => 2, 'topic' => 'Basics', 'subtopic' => 'Weather'],
];
$progress = [
    ['student_id' => 1, 'course_topic_id' => 2, 'is_completed' => 't'],
    ['student_id' => 1, 'course_topic_id' => 3, 'is_completed' => 'f'],
    ['student_id' => 2, 'course_topic_id' => 3, 'is_completed' => true],
];
$error = null;
$canWrite = false;
$csrfToken = '';
ob_start();
require dirname(__DIR__) . '/views/student-progress.php';
$html = ob_get_clean();
progress_check(str_contains($html, '&lt;Sample&gt; Student'), 'Student name not escaped');
progress_check(substr_count($html, '>Completed</span>') === 1, 'Completion status incorrect');
progress_check(substr_count($html, '>Pending</span>') === 1, 'Pending status incorrect');
progress_check(str_contains($html, "fetch('/students/student-progress'"), 'Incorrect POST URL');
progress_check(str_contains($html, 'Preflight') && str_contains($html, 'Weather'), 'Topics from multiple courses should render');
progress_check(str_contains($html, 'Training Topics'), 'Shared checklist heading missing');
progress_check(str_contains($html, 'Progress is read-only'), 'Read-only notice absent');
progress_check(substr_count($html, 'disabled') >= 2, 'Checkboxes not disabled');

echo "All student progress checks passed. Live database persistence is not covered.\n";

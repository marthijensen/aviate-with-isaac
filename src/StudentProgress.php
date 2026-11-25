<?php

declare(strict_types=1);

/** Student checklist routes, isolated from scheduling and other app features. */
function progress_id($value): ?int
{
    if (!is_string($value) || !ctype_digit($value)) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function progress_writes_enabled(): bool
{
    return Env::get('AVIATE_LOCAL_WRITES', '0') === '1'
        && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

function progress_json(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($body);
}

function register_student_progress_routes(Router $router): void
{
    $router->get('/students/progress', static function (): void {
        $studentId = progress_id($_GET['id'] ?? $_GET['student_id'] ?? null);
        $student = null;
        $topics = [];
        $progress = [];
        $error = null;
        $canWrite = progress_writes_enabled();
        $token = '';

        if ($studentId === null) {
            http_response_code(400);
            $error = 'A valid student ID is required.';
        } else {
            try {
                $db = ReadOnlyDatabase::connect();
                $statement = $db->prepare(
                    'SELECT id, first_name, last_name, course_id, training_goal
                     FROM public.students WHERE id = :student_id'
                );
                $statement->execute(['student_id' => $studentId]);
                $student = $statement->fetch() ?: null;

                if ($student === null) {
                    http_response_code(404);
                    $error = 'Student not found.';
                } elseif ($student['course_id'] !== null) {
                    $statement = $db->prepare(
                        'SELECT topic_id, topic, subtopic, display_order, course_id
                         FROM public.course_topics WHERE course_id = :course_id
                         ORDER BY display_order ASC, topic_id ASC'
                    );
                    $statement->execute(['course_id' => $student['course_id']]);
                    $topics = $statement->fetchAll();

                    $statement = $db->prepare(
                        'SELECT p.student_id, p.course_topic_id,
                                CASE WHEN p.is_completed THEN 1 ELSE 0 END AS is_completed
                         FROM public.student_progress p
                         JOIN public.course_topics t ON t.topic_id = p.course_topic_id
                         WHERE p.student_id = :student_id AND t.course_id = :course_id'
                    );
                    $statement->execute([
                        'student_id' => $studentId,
                        'course_id' => $student['course_id'],
                    ]);
                    $progress = $statement->fetchAll();
                }
            } catch (Throwable $exception) {
                error_log('Student progress read failed: ' . $exception->getMessage());
                http_response_code(503);
                $error = 'Student progress is currently unavailable. Please try again later.';
                $topics = [];
                $progress = [];
            }
        }

        if ($error === null && $canWrite) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            if (empty($_SESSION['student_progress_csrf'])) {
                $_SESSION['student_progress_csrf'] = bin2hex(random_bytes(32));
            }
            $token = $_SESSION['student_progress_csrf'];
        }

        render('student-progress', [
            'title' => 'Student Progress Checklist',
            'selectedStudentId' => $studentId ?? 0,
            'selectedStudent' => $student,
            'progress' => $progress,
            'topics' => $topics,
            'error' => $error,
            'canWrite' => $canWrite && $error === null,
            'csrfToken' => $token,
        ]);
    });

    $router->post('/students/student-progress', static function (): void {
        if (!progress_writes_enabled()) {
            progress_json(403, ['success' => false, 'error' => 'Progress updates are disabled in this environment.']);
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $submitted = $_POST['csrf_token'] ?? null;
        $expected = $_SESSION['student_progress_csrf'] ?? null;
        if (!is_string($submitted) || !is_string($expected) || $expected === ''
            || !hash_equals($expected, $submitted)) {
            progress_json(403, ['success' => false, 'error' => 'Invalid form token. Refresh the page and try again.']);
            return;
        }

        $studentId = progress_id($_POST['student_id'] ?? null);
        $topicId = progress_id($_POST['topic_id'] ?? null);
        $completed = $_POST['is_completed'] ?? null;
        if ($studentId === null || $topicId === null || !in_array($completed, ['0', '1'], true)) {
            progress_json(422, ['success' => false, 'error' => 'Invalid student, topic or completion status.']);
            return;
        }

        try {
            $db = WriteDatabase::connect();
            // Validate the student/course/topic relationship within the write itself.
            // A mismatch inserts zero rows and cannot update another course's progress.
            $statement = $db->prepare(
                'INSERT INTO public.student_progress (student_id, course_topic_id, is_completed, completed_at)
                 SELECT s.id, t.topic_id, CAST(:completed AS boolean),
                        CASE WHEN CAST(:timestamp_flag AS boolean) THEN CURRENT_TIMESTAMP ELSE NULL END
                 FROM public.students s
                 JOIN public.course_topics t ON t.course_id = s.course_id
                 WHERE s.id = :student_id AND t.topic_id = :topic_id
                 ON CONFLICT (student_id, course_topic_id)
                 DO UPDATE SET is_completed = EXCLUDED.is_completed,
                               completed_at = EXCLUDED.completed_at'
            );
            $flag = $completed === '1' ? 'true' : 'false';
            $statement->execute([
                'student_id' => $studentId,
                'topic_id' => $topicId,
                'completed' => $flag,
                'timestamp_flag' => $flag,
            ]);
            if ($statement->rowCount() !== 1) {
                progress_json(422, ['success' => false, 'error' => 'Student or topic not found for this course.']);
                return;
            }
            progress_json(200, ['success' => true]);
        } catch (Throwable $exception) {
            error_log('Toggle progress failed: ' . $exception->getMessage());
            progress_json(500, ['success' => false, 'error' => 'Progress could not be saved. Please try again.']);
        }
    });
}

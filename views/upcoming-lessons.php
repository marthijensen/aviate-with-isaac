<?php
/**
 * Dashboard card: upcoming lessons.
 *
 * @var list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}> $upcomingLessons
 * @var string|null $upcomingLessonsError
 */
?>
<section class="card" aria-labelledby="upcoming-lessons-heading">
    <h2 id="upcoming-lessons-heading">Upcoming Lessons</h2>

    <?php if (!empty($upcomingLessonsError)): ?>
        <p class="notice notice-error" role="alert"><?= e($upcomingLessonsError) ?></p>
    <?php elseif ($upcomingLessons === []): ?>
        <p class="empty-state">No upcoming lessons scheduled.</p>
    <?php else: ?>
        <table class="lessons-table">
            <thead>
                <tr>
                    <th scope="col">Student</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcomingLessons as $lesson): ?>
                    <tr>
                        <td data-label="Student"><?= e($lesson['student_name']) ?></td>
                        <td data-label="Date"><?= e($lesson['date']) ?></td>
                        <td data-label="Time"><?= e($lesson['time']) ?></td>
                        <td data-label="Type"><?= e($lesson['lesson_type']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

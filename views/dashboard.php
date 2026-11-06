<?php
/**
 * @var list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}> $upcomingLessons
 * @var string|null $upcomingLessonsError
 */
?>
<h1>Today at Aviate.</h1>
<p class="lead">Your next scheduled lessons.</p>

<div class="actions" aria-label="Dashboard quick actions">
    <a href="/students">View Students</a>
    <a href="/students/add">+ Add Student</a>
</div>

<?php if (!empty($upcomingLessonsError)): ?>
  <p class="action-note" role="alert"><?= e($upcomingLessonsError) ?></p>
<?php endif; ?>


<section class="fields-grid" aria-label="Dashboard">

    <?php require __DIR__ . '/upcoming-lessons.php'; ?>

    <article class="field-card">
        <h2>Students</h2>

        <p>
            View your active flight students,
            training goals, and profiles.
        </p>

        <ul class="rows">
            <li>
                <span>
                    <strong>Student Management</strong>
                    <small>View active students and profiles</small>
                </span>
                <a href="/students">View Students</a>
            </li>
            <li>
                <span>
                    <strong>New Student</strong>
                    <small>Add a student to your roster</small>
                </span>
                <a href="/students/add">Add Student</a>
            </li>
        </ul>
    </article>

</section>
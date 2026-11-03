<?php
/**
 * @var list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}> $upcomingLessons
 * @var string|null $upcomingLessonsError
 */
?>
<h1>Today at Aviate.</h1>
<p class="lead">Your next scheduled lessons.</p>

<?php if (!empty($upcomingLessonsError)): ?>
  <p class="action-note" role="alert"><?= e($upcomingLessonsError) ?></p>
<?php endif; ?>

<section class="fields-grid" aria-label="Dashboard">
  <?php require __DIR__ . '/upcoming-lessons.php'; ?>
</section>

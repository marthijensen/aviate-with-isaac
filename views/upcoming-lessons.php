<?php
/**
 * Dashboard card: upcoming lessons. Uses the .field-card / .rows styles from app.css.
 *
 * @var list<array{id: string, student_name: string, date: string, time: string, lesson_type: string}> $upcomingLessons
 * @var string|null $upcomingLessonsError
 */
?>
<article class="field-card" aria-labelledby="upcoming-lessons-heading">
  <h2 id="upcoming-lessons-heading">Upcoming Lessons</h2>
  <ul class="rows">
    <?php if (!empty($upcomingLessonsError)): ?>
      <li><span>Upcoming lessons are unavailable.</span></li>
    <?php elseif ($upcomingLessons === []): ?>
      <li><span>No upcoming lessons scheduled.</span></li>
    <?php else: foreach ($upcomingLessons as $lesson): ?>
      <li><span><strong><?= e($lesson['student_name']) ?></strong><small><?= e($lesson['lesson_type']) ?></small></span><span class="when"><strong><?= e($lesson['time']) ?></strong><small><?= e($lesson['date']) ?></small></span></li>
    <?php endforeach; endif; ?>
  </ul>
</article>

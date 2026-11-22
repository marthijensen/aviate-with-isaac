<?php
/**
 * Confirmation page for cancelling a scheduled lesson.
 *
 * @var array<string, mixed> $appointment Row from Appointments::find()
 * @var string $csrfToken
 * @var list<string> $errors
 */
$studentName = trim(($appointment['first_name'] ?? '') . ' ' . ($appointment['last_name'] ?? '')) ?: 'Student';
$date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $appointment['apointment_date']);
$time = Appointments::timeInputValue($appointment['start_time'] ?? null);
$time = $time !== '' ? (DateTimeImmutable::createFromFormat('H:i', $time) ?: null)?->format('g:i A') : null;
$profileHref = '/students/profile?id=' . (int) $appointment['student_id'];
?>
<h1>Cancel Lesson</h1>

<p class="lead">The lesson stays on the student's record, marked as cancelled.</p>

<section class="fields-grid">
    <article class="field-card">
        <h2>Cancel this lesson?</h2>

        <?php if ($errors !== []): ?>
            <div class="action-note" role="alert">
                <?php foreach ($errors as $message): ?>
                    <p><?= e($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <ul class="rows">
            <li>
                <span>
                    <strong><?= e($studentName) ?></strong>
                    <small><?= e(trim((string) ($appointment['apointment_type'] ?? '')) ?: 'Lesson') ?></small>
                </span>
                <span class="when">
                    <strong><?= e($time ?? 'Time TBD') ?></strong>
                    <small><?= e($date ? $date->format('D n/j/Y') : (string) $appointment['apointment_date']) ?></small>
                </span>
            </li>
        </ul>

        <form method="post" action="/appointments/cancel">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="appointment_id" value="<?= e((string) $appointment['id']) ?>">

            <div class="actions">
                <button type="submit">Cancel Lesson</button>
                <a href="<?= e($profileHref) ?>">Keep Lesson</a>
            </div>
        </form>
    </article>
</section>

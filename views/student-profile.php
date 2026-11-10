
<h1>Student Profile</h1>

<p class="lead">
    View student information and training details.
</p>

<p>
    <a href="/students">← Back to Students</a>
</p>

<?php if ($error !== null): ?>

    <section class="fields-grid">
        <article class="field-card">
            <p><?= e($error) ?></p>
        </article>
    </section>

<?php elseif ($student !== null): ?>

    <section class="fields-grid">
        <article class="field-card">

            <h2>
                <?= e(trim(
                    (string) ($student['first_name'] ?? '')
                    . ' '
                    . (string) ($student['last_name'] ?? '')
                )) ?>
            </h2>

            <ul class="rows">
                <li>
                    <span><strong>Email</strong></span>
                    <span><?= e((string) ($student['email'] ?? 'Not provided')) ?></span>
                </li>
                <li>
                    <span><strong>Phone</strong></span>
                    <span><?= e((string) ($student['phone'] ?? 'Not provided')) ?></span>
                </li>
                <li>
                    <span><strong>Training Goal</strong></span>
                    <span><?= e((string) ($student['training_goal'] ?? 'Not provided')) ?></span>
                </li>
                <li>
                    <span><strong>Status</strong></span>
                    <span><?= e((string) ($student['status'] ?? 'Unknown')) ?></span>
                </li>
            </ul>

        </article>
    </section>

<?php endif; ?>


<?php if ($student !== null && $error === null): ?>

<section class="fields-grid">

    <!-- RECENT LESSONS -->
    <article class="field-card">
        <h2>Recent Lessons</h2>

        <?php if ($appointmentsError !== null): ?>

            <p><?= e($appointmentsError) ?></p>

        <?php elseif ($recentLessons === []): ?>

            <p>No recent lessons found.</p>

        <?php else: ?>

            <ul class="rows">
                <?php foreach ($recentLessons as $lesson): ?>
                    <li>
                        <span>
                            <strong>
                                <?= e((string) ($lesson['apointment_date'] ?? 'No date')) ?>
                            </strong>
                            <small>
                                <?= e((string) ($lesson['apointment_type'] ?? 'Lesson')) ?>
                            </small>
                        </span>

                        <span class="when">
                            <?= e((string) ($lesson['status'] ?? 'Unknown')) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </article>

    <!-- UPCOMING APPOINTMENTS -->
    <article class="field-card">
        <h2>Upcoming Appointments</h2>

        <?php if ($appointmentsError !== null): ?>

            <p><?= e($appointmentsError) ?></p>

        <?php elseif ($upcomingAppointments === []): ?>

            <p>No upcoming appointments scheduled.</p>

        <?php else: ?>

            <ul class="rows">
                <?php foreach ($upcomingAppointments as $appointment): ?>
                    <li>
                        <span>
                            <strong>
                                <?= e((string) ($appointment['apointment_type'] ?? 'Lesson')) ?>
                            </strong>
                            <small>
                                <?= e((string) ($appointment['apointment_date'] ?? 'No date')) ?>
                            </small>
                        </span>

                        <span class="when">
                            <?= e((string) ($appointment['start_time'] ?? 'Time TBD')) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php endif; ?>
    </article>

</section>

<section class="fields-grid" style="margin-top: 20px;">
    <article class="progress-card" style="width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Progress Tracker</h2>
            <a href="student-progress.php?id=<?= e((string) ($student['id'] ?? '')) ?>" style="font-weight: 600; text-decoration: none;">View Full Checklist →</a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #1a202c;">%</div>
                <div style="font-size: 0.85rem; color: #718096; margin-top: 4px;">Overall progress</div>
            </div>
            <div style="border-left: 1px solid #e2e8f0; padding-left: 20px;">
                <div style="font-size: 2rem; font-weight: bold; color: #1a202c;">— / —</div>
                <div style="font-size: 0.85rem; color: #718096; margin-top: 4px;">Subtopics complete</div>
            </div>
            <div style="border-left: 1px solid #e2e8f0; padding-left: 20px;">
                <div style="font-size: 2rem; font-weight: bold; color: #1a202c;">— / —</div>
                <div style="font-size: 0.85rem; color: #718096; margin-top: 4px;">Topics complete</div>
            </div>
        </div>
    </article>
</section>

<?php endif; ?>

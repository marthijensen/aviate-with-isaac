
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

<?php endif; ?>

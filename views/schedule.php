<h1>Schedule Lesson</h1>

<p class="lead">
    Schedule a flight or ground lesson for a student.
</p>

<section class="fields-grid">

    <article class="field-card">

        <h2>Lesson Details</h2>
        <?php if ($studentOptionsError !== null): ?>
            <p class="action-note" role="alert">
                <?= e($studentOptionsError) ?>
            </p>
        <?php elseif ($studentOptions === []): ?>
            <p class="action-note">
                No active students are available for scheduling.
            </p>
        <?php endif; ?>

        <?php if ($lessonOptionsError !== null): ?>
            <p class="action-note" role="alert">
                <?= e($lessonOptionsError) ?>
            </p>
        <?php elseif ($lessonOptions === []): ?>
            <p class="action-note">
                No lesson types are available for scheduling.
            </p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="action-note" role="alert">
                <?php foreach ($errors as $message): ?>
                    <p><?= e($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/schedule">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e((string) ($_SESSION['schedule_csrf'] ?? '')) ?>"
            >

            <div class="field-grid">

                <label class="span-2" for="student_id">
                    Student *

                    <select
                        class="field-input"
                        id="student_id"
                        name="student_id"
                        required
                        <?= ($studentOptionsError !== null || $studentOptions === [])
                            ? 'disabled' : '' ?>
                    >
                        <option value="">Select a student</option>

                        <?php foreach ($studentOptions as $option): ?>

                            <?php
                                $optionId = (string) $option['id'];

                                $studentName = trim(
                                    (string) $option['first_name']
                                    . ' '
                                    . (string) $option['last_name']
                                );
                            ?>

                            <option
                                value="<?= e($optionId) ?>"
                                <?= ($fields['student_id'] ?? '') === $optionId
                                    ? 'selected' : '' ?>
                            >
                                <?= e($studentName) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label class="span-2" for="lesson_type">
                    Lesson Type *

                    <select
                        class="field-input"
                        id="lesson_type"
                        name="lesson_type"
                        required
                        <?= ($lessonOptionsError !== null || $lessonOptions === [])
                            ? 'disabled' : '' ?>
                    >
                        <option value="">Select lesson type</option>

                        <?php foreach ($lessonOptions as $type): ?>

                            <option
                                value="<?= e((string) $type) ?>"
                                <?= ($fields['lesson_type'] ?? '') === $type
                                    ? 'selected' : '' ?>
                            >
                                <?= e((string) $type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label for="appointment_date">
                    Appointment Date *
                    <input
                        class="field-input"
                        type="date"
                        id="appointment_date"
                        name="appointment_date"
                        required
                        value="<?= e($fields['appointment_date'] ?? '') ?>"
                    >
                </label>

                <label for="start_time">
                    Start Time *
                    <input
                        class="field-input"
                        type="time"
                        id="start_time"
                        name="start_time"
                        required
                        value="<?= e($fields['start_time'] ?? '') ?>"
                    >
                </label>

                <div class="span-2 actions">
                    <button type="submit">
                        Schedule Lesson
                    </button>
                </div>

            </div>

        </form>

    </article>

</section>
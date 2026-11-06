<h1>Schedule Lesson</h1>

<p class="lead">
    Schedule a flight or ground lesson for a student.
</p>

<section class="fields-grid">

    <article class="field-card">

        <h2>Lesson Details</h2>

        <?php if ($preview): ?>
            <p class="action-note" role="status">
                Lesson information validated successfully.
                This is a preview only — nothing has been saved.
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

            <div class="field-grid">

                <label class="span-2" for="student_id">
                    Student ID *
                    <input
                        class="field-input"
                        type="number"
                        id="student_id"
                        name="student_id"
                        min="1"
                        required
                        placeholder="Temporary test ID"
                        value="<?= e($fields['student_id'] ?? '') ?>"
                    >
                </label>

                <label class="span-2" for="lesson_type">
                    Lesson Type *
                    <select
                        class="field-input"
                        id="lesson_type"
                        name="lesson_type"
                        required
                    >
                        <option value="">Select lesson type</option>

                        <?php foreach ([
                            'Flight Lesson',
                            'Ground Lesson',
                        ] as $type): ?>

                            <option value="<?= e($type) ?>"
                                <?= ($fields['lesson_type'] ?? '') === $type
                                    ? 'selected' : '' ?>>
                                <?= e($type) ?>
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
                        Validate Lesson
                    </button>
                </div>

            </div>

        </form>

        <p>
            Preview mode: appointments are not saved to Supabase.
        </p>

    </article>

</section>

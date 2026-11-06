<h1>Add Student</h1>

<p class="lead">
    Enter information for a new flight student.
</p>

<p>
    <a href="/students">← Back to Students</a>
</p>

<section class="fields-grid">
    <article class="field-card">

        <?php if ($errors !== []): ?>
            <div role="alert">
                <?php foreach ($errors as $message): ?>
                    <p><?= e($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <?php if ($courseOptionsError !== null): ?>
            <p class="action-note" role="alert">
                <?= e($courseOptionsError) ?>
            </p>
        <?php elseif ($courseOptions === []): ?>
            <p class="action-note">
                No courses are available.
            </p>
        <?php endif; ?>

        <form method="post" action="/students/add">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e((string) ($_SESSION['add_student_csrf'] ?? '')) ?>"
            >

            <h2>Student Information</h2>

            <p>
                <label for="first_name">First Name *</label>
                <input id="first_name" name="first_name"
                       required
                       value="<?= e($fields['first_name'] ?? '') ?>">
            </p>

            <p>
                <label for="last_name">Last Name *</label>
                <input id="last_name" name="last_name"
                       required
                       value="<?= e($fields['last_name'] ?? '') ?>">
            </p>

            <p>
                <label for="email">Email *</label>
                <input id="email" name="email" type="email"
                       required
                       value="<?= e($fields['email'] ?? '') ?>">
            </p>

            <p>
                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="tel"
                       value="<?= e($fields['phone'] ?? '') ?>">
            </p>

            <p>
                <label for="course_id">Course *</label>

                <select
                    class="field-input"
                    id="course_id"
                    name="course_id"
                    required
                    <?= ($courseOptionsError !== null || $courseOptions === [])
                        ? 'disabled' : '' ?>
                >
                    <option value="">Select a course</option>

                    <?php foreach ($courseOptions as $course): ?>
                        <option
                            value="<?= e((string) $course['course_id']) ?>"
                            <?= (string) ($fields['course_id'] ?? '') ===
                                (string) $course['course_id']
                                ? 'selected' : '' ?>
                        >
                            <?= e((string) $course['course']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </p>

            <h2>Address (Optional)</h2>

            <?php foreach ([
                'address' => 'Street Address',
                'city' => 'City',
                'state' => 'State',
                'zip' => 'ZIP Code',
            ] as $name => $label): ?>

                <p>
                    <label for="<?= e($name) ?>">
                        <?= e($label) ?>
                    </label>
                    <input id="<?= e($name) ?>"
                           name="<?= e($name) ?>"
                           value="<?= e($fields[$name] ?? '') ?>">
                </p>

            <?php endforeach; ?>

            <button type="submit">
                Save Student
            </button>

        </form>
    </article>
</section>

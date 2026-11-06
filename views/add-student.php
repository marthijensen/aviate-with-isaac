<h1>Add Student</h1>

<p class="lead">
    Enter information for a new flight student.
</p>

<p>
    <a href="/students">← Back to Students</a>
</p>

<section class="fields-grid">
    <article class="field-card">

        <?php if ($preview): ?>
            <p role="status">
                Student information validated successfully.
                This is a preview only — nothing has been saved.
            </p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div role="alert">
                <?php foreach ($errors as $message): ?>
                    <p><?= e($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/students/add">

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
                <label for="training_goal">Training Goal *</label>
                <input id="training_goal" name="training_goal"
                       required
                       list="training-goals"
                       value="<?= e($fields['training_goal'] ?? '') ?>">

                <datalist id="training-goals">
                    <option value="Private Pilot">
                    <option value="Instrument Rating">
                    <option value="Commercial Pilot">
                    <option value="Certified Flight Instructor">
                </datalist>
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
                Validate Student Information
            </button>

        </form>
    </article>
</section>

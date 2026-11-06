
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


<?php
// Student data will be supplied by the route.
// null means the database isn't connected yet.
$students = $students ?? null;
?>

<h1>Students</h1>

<p class="lead">
    View and manage your flight students.
</p>

<p>
    <a href="/students/add">+ Add Student</a>
</p>

<section class="fields-grid">

    <article class="field-card">

        <h2>Active Students</h2>

        <?php if ($students === null): ?>

            <p>Student records are not connected yet.</p>

        <?php elseif ($students === []): ?>

            <p>No active students found.</p>

        <?php else: ?>

            <ul class="rows">

                <?php foreach ($students as $student): ?>

                    <li>
                        <span>
                            <strong>
                                <?= e(trim(
                                    (string) ($student['first_name'] ?? '')
                                    . ' '
                                    . (string) ($student['last_name'] ?? '')
                                )) ?>
                            </strong>
                            <a href="/students/profile?id=<?= rawurlencode((string) $student['id']) ?>">
                                View Profile
                            </a>
                        </span>
                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

    </article>

</section>

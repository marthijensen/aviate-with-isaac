<?php
// Student data supplied by the route/controller.
$students = $students ?? null;
?>
<!-- ========================================== -->
<!-- MAIN VIEW CONTENT                          -->
<!-- ========================================== -->

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
                            <?php 
                                // Fallback check for different potential database key names
                                $firstName = $student['first_name'] ?? $student['FIRST_NAME'] ?? '';
                                $lastName = $student['last_name'] ?? $student['LAST_NAME'] ?? '';
                                $fullName = trim($firstName . ' ' . $lastName);
                                $studentId = (string) ($student['id'] ?? $student['ID'] ?? '');
                            ?>
                            <a href="/students/profile?id=<?= rawurlencode($studentId) ?>" style="text-decoration: none; color: inherit;">
                                <strong><?= htmlspecialchars($fullName !== '' ? $fullName : 'Unnamed Student', ENT_QUOTES, 'UTF-8') ?></strong>
                            </a>
                        </span>
                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

    </article>

</section>
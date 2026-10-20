<?php

require_once __DIR__ . '/active-students.php';

$activeStudentCount = getActiveStudentCount();

?>

<div class="student-summary-card">

    <p class="eyebrow">
        STUDENT OVERVIEW
    </p>

    <h3>
        Active Students
    </h3>

    <div class="student-count">
        <?= htmlspecialchars(
            (string) $activeStudentCount
        ) ?>
    </div>

    <p>
        Students currently in training
    </p>

    <a href="students.php">
        View Students →
    </a>

</div>
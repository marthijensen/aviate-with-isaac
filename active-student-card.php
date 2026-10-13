<?php

// Placeholder:
// This file assumes the dashboard already has a database
// connection available in a variable called $pdo.

require_once 'active-students.php';

$activeStudentCount = getActiveStudentCount($pdo);

?>

<div class="student-summary-card">
    <h3>Active Students</h3>

    <div class="student-count">
        <?= htmlspecialchars((string) $activeStudentCount) ?>
    </div>

    <p>Students currently in training</p>

    <!-- Placeholder:
         Replace students.php with the actual student list page -->
    <a href="students.php">View Students</a>
</div>
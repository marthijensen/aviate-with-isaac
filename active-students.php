<?php

/**
 * Returns the number of active students.
 *
 * Placeholder:
 * $pdo should be replaced with the team's actual
 * database connection variable.
 */
function getActiveStudentCount($pdo) {
    $sql = "SELECT COUNT(*)
            FROM students
            WHERE status = 'active'";

    $stmt = $pdo->query($sql);

    return (int) $stmt->fetchColumn();
}
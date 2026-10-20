<?php

require_once __DIR__ . '/supabase.php';

/*
 * Get every active student from Supabase.
 */
$students = supabaseRequest(
    'students'
    . '?status=eq.active'
    . '&select=id,first_name,last_name,created_at,status'
    . '&order=created_at.asc'
);

$activeStudentCount = count($students);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Active Students</title>

    <link
        rel="stylesheet"
        href="active-student-card.css"
    >

</head>

<body>

<header class="top-nav">

    <div class="logo">
        AVIATE WITH ISAAC
    </div>

</header>


<main class="dashboard-container">

    <section class="student-summary">

        <p class="eyebrow">
            STUDENT OVERVIEW
        </p>

        <h1>
            Active Students:
            <?= $activeStudentCount ?>
        </h1>

    </section>


    <section class="student-list">

        <div class="student-list-header">

            <span>
                Student
            </span>

            <span>
                Active For
            </span>

            <span></span>

        </div>


        <?php foreach ($students as $student): ?>

            <?php

            $created = new DateTime(
                $student['created_at']
            );

            $today = new DateTime();

            $difference = $created->diff($today);

            if ($difference->y > 0) {

                $activeFor =
                    $difference->y
                    . ' year'
                    . ($difference->y !== 1 ? 's' : '');

            } elseif ($difference->m > 0) {

                $activeFor =
                    $difference->m
                    . ' month'
                    . ($difference->m !== 1 ? 's' : '');

            } else {

                $activeFor =
                    $difference->d
                    . ' day'
                    . ($difference->d !== 1 ? 's' : '');
            }

            ?>


            <div class="student-row">

                <div class="student-name">

                    <?= htmlspecialchars(
                        $student['first_name']
                        . ' '
                        . $student['last_name']
                    ) ?>

                </div>


                <div class="student-time">

                    <?= htmlspecialchars(
                        $activeFor
                    ) ?>

                </div>


                <div class="student-action">

                    <a
                        href="student.php?id=<?= urlencode(
                            $student['id']
                        ) ?>"
                    >
                        View Student →
                    </a>

                </div>

            </div>

        <?php endforeach; ?>


        <?php if (empty($students)): ?>

            <div class="empty-state">

                No active students found.

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>
<?php

// PLACEHOLDER DATA
// Later this will come from Supabase using the student ID.

$student = [
    'id' => 1,
    'first_name' => 'John',
    'last_name' => 'Smith',
    'email' => 'john.smith@email.com',
    'phone' => '(801) 555-1234',
    'address' => '123 Main Street',
    'city' => 'Provo',
    'state' => 'UT',
    'zip' => '84601',
    'training_goal' => 'Private Pilot Certificate',
    'status' => 'active',
    'created_at' => '2026-05-10'
];

$recentLessons = [
    [
        'date' => '2026-10-18',
        'type' => 'Flight Lesson',
        'notes' => 'Practiced landings and pattern work.'
    ],
    [
        'date' => '2026-10-12',
        'type' => 'Ground Lesson',
        'notes' => 'Reviewed weather and airport operations.'
    ]
];

$upcomingAppointments = [
    [
        'date' => '2026-10-24',
        'time' => '10:00 AM',
        'type' => 'Flight Lesson'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
    </title>

    <link rel="stylesheet" href="student-profile.css">
</head>

<body>

<header class="top-nav">
    <div class="logo">AVIATE WITH ISAAC</div>
</header>

<main class="student-profile-container">

    <a class="back-link" href="preview.html">
        ← Back to Students
    </a>

    <section class="student-hero">

        <div>
            <p class="eyebrow">STUDENT PROFILE</p>

            <h1>
                <?= htmlspecialchars($student['first_name']) ?>
                <?= htmlspecialchars($student['last_name']) ?>
            </h1>

            <p class="student-goal">
                <?= htmlspecialchars($student['training_goal']) ?>
            </p>
        </div>

        <div class="student-status">
            <?= htmlspecialchars(strtoupper($student['status'])) ?>
        </div>

    </section>


    <section class="profile-grid">

        <div class="profile-card">

            <p class="section-label">CONTACT INFORMATION</p>

            <div class="info-row">
                <span>Email</span>
                <strong><?= htmlspecialchars($student['email']) ?></strong>
            </div>

            <div class="info-row">
                <span>Phone</span>
                <strong><?= htmlspecialchars($student['phone']) ?></strong>
            </div>

            <div class="info-row">
                <span>Address</span>

                <strong>
                    <?= htmlspecialchars($student['address']) ?><br>

                    <?= htmlspecialchars(
                        $student['city'] . ', ' .
                        $student['state'] . ' ' .
                        $student['zip']
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="profile-card">

            <p class="section-label">TRAINING OVERVIEW</p>

            <div class="info-row">
                <span>Training Goal</span>
                <strong><?= htmlspecialchars($student['training_goal']) ?></strong>
            </div>

            <div class="info-row">
                <span>Status</span>
                <strong><?= htmlspecialchars(ucfirst($student['status'])) ?></strong>
            </div>

            <div class="info-row">
                <span>Student Since</span>
                <strong><?= htmlspecialchars($student['created_at']) ?></strong>
            </div>

        </div>

    </section>


    <section class="profile-section">

        <div class="section-heading">
            <div>
                <p class="eyebrow">TRAINING</p>
                <h2>Training Progress</h2>
            </div>

            <a href="#">View Full Training Record →</a>
        </div>

        <div class="progress-grid">

            <div class="progress-card">
                <span class="progress-number">32</span>
                <span>Flight Hours</span>
            </div>

            <div class="progress-card">
                <span class="progress-number">8</span>
                <span>Completed Requirements</span>
            </div>

            <div class="progress-card">
                <span class="progress-number">3</span>
                <span>Skills Needing Practice</span>
            </div>

        </div>

    </section>


    <section class="profile-section">

        <div class="section-heading">
            <div>
                <p class="eyebrow">TRAINING HISTORY</p>
                <h2>Recent Lessons</h2>
            </div>
        </div>

        <div class="table">

            <div class="table-header">
                <span>Date</span>
                <span>Type</span>
                <span>Notes</span>
            </div>

            <?php foreach ($recentLessons as $lesson): ?>

                <div class="table-row">

                    <span>
                        <?= htmlspecialchars($lesson['date']) ?>
                    </span>

                    <span>
                        <?= htmlspecialchars($lesson['type']) ?>
                    </span>

                    <span>
                        <?= htmlspecialchars($lesson['notes']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <section class="profile-section">

        <div class="section-heading">
            <div>
                <p class="eyebrow">SCHEDULE</p>
                <h2>Upcoming Appointments</h2>
            </div>
        </div>

        <div class="table">

            <div class="table-header">
                <span>Date</span>
                <span>Time</span>
                <span>Type</span>
            </div>

            <?php foreach ($upcomingAppointments as $appointment): ?>

                <div class="table-row">

                    <span>
                        <?= htmlspecialchars($appointment['date']) ?>
                    </span>

                    <span>
                        <?= htmlspecialchars($appointment['time']) ?>
                    </span>

                    <span>
                        <?= htmlspecialchars($appointment['type']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

</main>

</body>
</html>
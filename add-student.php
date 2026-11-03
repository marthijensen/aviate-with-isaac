<?php

require_once __DIR__ . '/supabase.php';

/*
 * Add a new student.
 * The form below sends its data back to this same page.
 * When the form is submitted, we check it, save it to
 * Supabase, and then open the new student's profile.
 */

// Training goals the instructor can pick from (they can also type their own).
$trainingGoals = [
    'Private Pilot',
    'Instrument Rating',
    'Commercial Pilot',
    'Certified Flight Instructor',
];

// Field values (kept so the form refills if there is an error).
$fields = [
    'first_name'    => '',
    'last_name'     => '',
    'email'         => '',
    'phone'         => '',
    'address'       => '',
    'city'          => '',
    'state'         => '',
    'zip'           => '',
    'training_goal' => '',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read and tidy up what was typed.
    foreach ($fields as $name => $value) {
        $fields[$name] = trim($_POST[$name] ?? '');
    }

    // Check the required fields.
    if ($fields['first_name'] === '') {
        $errors[] = 'First name is required.';
    }

    if ($fields['last_name'] === '') {
        $errors[] = 'Last name is required.';
    }

    if ($fields['email'] === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Save to Supabase if everything looks good.
    if (empty($errors)) {

        // Only send fields that were filled in.
        $newStudent = array_filter(
            $fields,
            fn ($value) => $value !== ''
        );

        // New students start out active.
        $newStudent['status'] = 'active';

        try {
            $saved = supabaseRequest('students', 'POST', $newStudent);

            if (!empty($saved[0]['id'])) {
                header('Location: student.php?id=' . urlencode($saved[0]['id']));
            } else {
                header('Location: students.php');
            }
            exit;

        } catch (Throwable $error) {
            $errors[] = 'The student could not be saved. '
                . $error->getMessage();
        }
    }
}

// Small helper to safely print a value back into the form.
function old($fields, $name)
{
    return htmlspecialchars($fields[$name], ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Student</title>

    <link rel="stylesheet" href="active-student-card.css">
</head>

<body>

<header class="top-nav">
    <div class="logo">
        AVIATE WITH ISAAC
    </div>
</header>


<main class="dashboard-container">

    <a class="back-link" href="students.php">
        ← Back to Students
    </a>

    <section class="student-summary">
        <p class="eyebrow">NEW STUDENT</p>
        <h1>Add a Student</h1>
    </section>


    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <?php foreach ($errors as $message): ?>
                <p><?= htmlspecialchars($message) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <form class="student-form" method="post" action="add-student.php">

        <p class="section-label">CONTACT INFORMATION</p>

        <div class="form-grid">

            <label>
                First name *
                <input type="text" name="first_name" required
                       value="<?= old($fields, 'first_name') ?>">
            </label>

            <label>
                Last name *
                <input type="text" name="last_name" required
                       value="<?= old($fields, 'last_name') ?>">
            </label>

            <label>
                Email *
                <input type="email" name="email" required
                       value="<?= old($fields, 'email') ?>">
            </label>

            <label>
                Phone
                <input type="tel" name="phone"
                       value="<?= old($fields, 'phone') ?>">
            </label>

            <label class="full-width">
                Street address
                <input type="text" name="address"
                       value="<?= old($fields, 'address') ?>">
            </label>

            <label>
                City
                <input type="text" name="city"
                       value="<?= old($fields, 'city') ?>">
            </label>

            <div class="form-row-pair">
                <label>
                    State
                    <input type="text" name="state" maxlength="2"
                           value="<?= old($fields, 'state') ?>">
                </label>

                <label>
                    ZIP
                    <input type="text" name="zip" maxlength="10"
                           value="<?= old($fields, 'zip') ?>">
                </label>
            </div>

        </div>


        <p class="section-label">TRAINING</p>

        <div class="form-grid">

            <label class="full-width">
                Training goal
                <input type="text" name="training_goal" list="training-goals"
                       placeholder="Choose or type a goal"
                       value="<?= old($fields, 'training_goal') ?>">

                <datalist id="training-goals">
                    <?php foreach ($trainingGoals as $goal): ?>
                        <option value="<?= htmlspecialchars($goal) ?>">
                    <?php endforeach; ?>
                </datalist>
            </label>

        </div>


        <div class="form-actions">
            <a class="button-secondary" href="students.php">Cancel</a>
            <button class="button-primary" type="submit">Save Student</button>
        </div>

    </form>

</main>

</body>

</html>

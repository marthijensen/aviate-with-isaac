<?php
/** @var string $title */
/** @var int $selectedStudentId */
/** @var ?array $selectedStudent */
/** @var array $progress */
/** @var array $topics */

// Process progress map safely filtered for this student
$completedSubtopics = [];
foreach ($progress as $p) {
    if (((int)($p['student_id'] ?? 0)) === $selectedStudentId) {
        if (in_array($p['is_completed'] ?? null, [true, 1, '1', 't', 'true'], true)) {
            $completedSubtopics[(int)$p['course_topic_id']] = true;
        }
    }
}

// Group topics by parent topic name
$groupedTopics = [];
foreach ($topics as $topic) {
    $topicName = trim((string)($topic['topic'] ?? 'General Topic'));
    $groupedTopics[$topicName][] = $topic;
}

$trainingGoal = trim($selectedStudent['training_goal'] ?? '');
$courseTitle = $trainingGoal !== '' ? $trainingGoal . ' Topics' : 'Course Topics';
?>

<p><a href="<?= $selectedStudent ? '/students/profile?id=' . (int) $selectedStudentId : '/students' ?>">← Back</a></p>
<?php if ($error !== null): ?>
  <h1>Student Progress Checklist</h1>
  <p><?= e($error) ?></p>
<?php elseif ($selectedStudentId > 0 && $selectedStudent): ?>
  <h1 id="page-title">Progress Checklist for <?= e(trim((string) ($selectedStudent['first_name'] ?? '') . ' ' . (string) ($selectedStudent['last_name'] ?? ''))) ?></h1>
  <p class="lead" id="page-lead">Viewing training checklist and topic completion status.</p>
  <?php if (!$canWrite): ?><p>Progress is read-only in this environment.</p><?php endif; ?>

  <section class="fields-grid single" aria-label="Student Progress">
    <article class="field-card">
      <h2><?= e($courseTitle) ?></h2>
      <ul class="rows" style="list-style: none; padding: 0;">
      <?php if (!empty($groupedTopics)): ?>
        <?php foreach ($groupedTopics as $topicName => $subtopics): 
            $count = count($subtopics);
            $label = $count === 1 ? 'subtopic' : 'subtopics';
        ?>
          <li style="padding: 0.75rem 0; border-bottom: 1px solid #eaeaea;">
            <details style="cursor: pointer;">
              <summary style="font-weight: 600; display: flex; justify-content: space-between; align-items: center; outline: none;">
                <span style="font-size: 1.05rem; color: #222;"><?= e($topicName) ?></span>
                <span style="font-size: 0.85rem; color: #666; font-weight: 500; background: #f4f4f5; padding: 0.2rem 0.6rem; border-radius: 4px;">
                  <?= $count ?> <?= $label ?> ▾
                </span>
              </summary>
              <ul style="list-style: none; padding-left: 1rem; margin-top: 0.75rem; display: grid; gap: 0.5rem; border-left: 2px solid #e4e4e7;">
                <?php foreach ($subtopics as $sub): 
                    $topicId = (int)($sub['topic_id'] ?? 0);
                    $isCompleted = isset($completedSubtopics[$topicId]);
                ?>
                  <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; background: #fafafa; border-radius: 4px;">
                    <span style="color: #444; font-weight: 500;"><?= e($sub['subtopic'] ?? 'General') ?></span>
                    <span class="when" style="display: flex; align-items: center; gap: 0.4rem;">
                      <label style="cursor: pointer; display: flex; align-items: center; gap: 0.3rem; font-size: 0.9rem; color: <?= $isCompleted ? '#16a34a' : '#52525b' ?>;">
                        <input type="checkbox" 
                               class="progress-checkbox" 
                               data-student-id="<?= $selectedStudentId ?>" 
                               data-topic-id="<?= $topicId ?>" 
                               <?= $isCompleted ? 'checked' : '' ?>
                               <?= $canWrite ? '' : 'disabled' ?>
                               style="cursor: pointer; width: 1rem; height: 1rem;">
                        <span class="status-text"><?= $isCompleted ? 'Completed' : 'Pending' ?></span>
                      </label>
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </details>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li><span>No topics found for this student's course.</span></li>
      <?php endif; ?>
      </ul>
    </article>
  </section>
<?php endif; ?>

<script>
  document.addEventListener('change', async (event) => {
    if (!event.target.classList.contains('progress-checkbox')) return;

    const checkbox = event.target;
    const studentId = checkbox.dataset.studentId;
    const topicId = checkbox.dataset.topicId;
    const isCompleted = checkbox.checked;
    checkbox.disabled = true;
    const statusLabel = checkbox.closest('label').querySelector('.status-text');

    try {
      const formData = new FormData();
      formData.append('csrf_token', <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
      formData.append('student_id', studentId);
      formData.append('topic_id', topicId);
      formData.append('is_completed', isCompleted ? '1' : '0');

      const response = await fetch('/students/student-progress', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (response.ok && result.success) {
        statusLabel.textContent = isCompleted ? 'Completed' : 'Pending';
        checkbox.closest('label').style.color = isCompleted ? '#16a34a' : '#52525b';
      } else {
        checkbox.checked = !isCompleted;
        alert('Failed to update progress: ' + (result.error || 'Unknown server error'));
      }
    } catch (err) {
      checkbox.checked = !isCompleted;
      alert('Network error while updating progress.');
      console.error(err);
    } finally {
      checkbox.disabled = false;
    }
  });
</script>
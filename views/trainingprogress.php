<?php
/** @var string $title */
/** @var int $selectedStudentId */
/** @var ?array $selectedStudent */
/** @var array $topics */
/** @var array<int, true> $completed */
/** @var ?string $error */
/** @var bool $progressLoadFailed */
/** @var string $csrf */
/** @var bool $writesEnabled */

$groupedTopics = [];

foreach ($topics as $topic) {
    $groupedTopics[trim((string) ($topic['topic'] ?? 'General Topic'))][] = $topic;
}

$totalItems = count($topics);
$doneItems = 0;
$topicsDone = 0;

foreach ($groupedTopics as $items) {
    $finished = 0;

    foreach ($items as $item) {
        if (isset($completed[(int) ($item['topic_id'] ?? 0)])) {
            $finished++;
        }
    }

    $doneItems += $finished;

    if ($finished === count($items)) {
        $topicsDone++;
    }
}

$percent = $totalItems > 0 ? (int) round($doneItems / $totalItems * 100) : 0;
?>
<style>
    .tp-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 16px 0 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    .tp-stat strong { display: block; font-size: 2rem; color: #1a202c; }
    .tp-stat span { color: #718096; font-size: 0.85rem; }
    .tp-topic { padding: 0.75rem 0; border-bottom: 1px solid #eaeaea; }
    .tp-topic summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; cursor: pointer; font-weight: 600; }
    .tp-count { font-size: 0.85rem; color: #666; font-weight: 500; background: #f4f4f5; padding: 0.2rem 0.6rem; border-radius: 4px; white-space: nowrap; }
    .tp-items { list-style: none; padding-left: 1rem; margin: 0.75rem 0 0; display: grid; gap: 0.5rem; border-left: 2px solid #e4e4e7; }
    .tp-items li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 0.35rem 0.5rem; background: #fafafa; border-radius: 4px; }
    .tp-items label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer; white-space: nowrap; color: #52525b; }
    .tp-items label.tp-done { color: #16a34a; }
    .tp-items input[type=checkbox] { width: 1rem; height: 1rem; cursor: pointer; }
    .tp-items input[type=checkbox]:disabled { cursor: not-allowed; }
    #tp-toast { position: fixed; bottom: 20px; right: 20px; padding: 10px 16px; background: #fff; border: 1px solid #1a202c; font-size: 0.85rem; font-weight: 600; }
    #tp-toast.tp-error { background: #1a202c; color: #fff; }
    #tp-toast:empty { display: none; }
    @media (max-width: 750px) { .tp-stats { grid-template-columns: 1fr; } }
</style>

<?php if ($error !== null): ?>

  <h1>Progress Checklist</h1>
  <p><a href="/students">← Back to Students</a></p>

  <section class="fields-grid single">
    <article class="field-card">
      <p><?= e($error) ?></p>
    </article>
  </section>

<?php elseif ($selectedStudent !== null): ?>

  <h1 id="page-title">Progress Checklist for <?= e(trim((string) ($selectedStudent['first_name'] ?? '') . ' ' . (string) ($selectedStudent['last_name'] ?? ''))) ?></h1>
  <p class="lead" id="page-lead">Check items off as the student completes them.</p>
  <p><a href="/students/profile?id=<?= $selectedStudentId ?>">← Back to Student Profile</a></p>

  <section class="fields-grid single" aria-label="Student Progress">
    <article class="field-card">

      <?php $trainingGoal = trim((string) ($selectedStudent['training_goal'] ?? '')); ?>
      <h2><?= e($trainingGoal !== '' ? $trainingGoal . ' Topics' : 'Course Topics') ?></h2>

      <?php if (!$writesEnabled): ?>
        <p class="action-note">Saving is turned off, so the checkboxes are read-only. Set AVIATE_LOCAL_WRITES=1 (this computer) or AVIATE_DEMO_WRITES=1 in .env to enable it.</p>
      <?php endif; ?>

      <?php if ($progressLoadFailed): ?>
        <p class="action-note">Saved progress could not be loaded, so nothing is shown as checked.</p>
      <?php endif; ?>

      <div class="tp-stats">
        <div class="tp-stat"><strong id="tp-percent"><?= $percent ?>%</strong><span>Overall progress</span></div>
        <div class="tp-stat"><strong id="tp-items"><?= $doneItems ?> / <?= $totalItems ?></strong><span>Subtopics complete</span></div>
        <div class="tp-stat"><strong id="tp-topics"><?= $topicsDone ?> / <?= count($groupedTopics) ?></strong><span>Topics complete</span></div>
      </div>

      <?php if ($groupedTopics === []): ?>

        <p>No topics found for this student's course.</p>

      <?php else: ?>

        <div id="tp-list">
          <?php foreach ($groupedTopics as $topicName => $items):
              $finished = 0;

              foreach ($items as $item) {
                  if (isset($completed[(int) ($item['topic_id'] ?? 0)])) {
                      $finished++;
                  }
              }
          ?>
            <details class="tp-topic">
              <summary>
                <span><?= e((string) $topicName) ?></span>
                <span class="tp-count"><?= $finished ?>/<?= count($items) ?></span>
              </summary>

              <ul class="tp-items">
                <?php foreach ($items as $item):
                    $itemId = (int) ($item['topic_id'] ?? 0);
                    $isCompleted = isset($completed[$itemId]);
                ?>
                  <li>
                    <span><?= e((string) ($item['subtopic'] ?? 'General')) ?></span>
                    <label class="<?= $isCompleted ? 'tp-done' : '' ?>">
                      <input type="checkbox" class="progress-checkbox"
                             data-topic-id="<?= $itemId ?>"
                             <?= $isCompleted ? 'checked' : '' ?>
                             <?= $writesEnabled ? '' : 'disabled' ?>>
                      <span class="status-text"><?= $isCompleted ? 'Completed' : 'Pending' ?></span>
                    </label>
                  </li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </article>
  </section>

  <div id="tp-toast" role="status" aria-live="polite"></div>

  <script>
    (function () {
      const studentId = <?= $selectedStudentId ?>;
      const csrf = <?= json_encode($csrf) ?>;
      const list = document.getElementById('tp-list');
      const toast = document.getElementById('tp-toast');

      if (!list) {
        return;
      }

      function say(message, isError, ms) {
        toast.textContent = message || '';
        toast.className = isError ? 'tp-error' : '';
        if (message && ms) {
          setTimeout(function () { if (toast.textContent === message) toast.textContent = ''; }, ms);
        }
      }

      // Recalculate the labels, counts and summary cards from the checkboxes.
      function refresh() {
        let done = 0;
        let total = 0;
        let topicsDone = 0;
        const topics = list.querySelectorAll('details.tp-topic');

        topics.forEach(function (details) {
          const boxes = Array.from(details.querySelectorAll('input.progress-checkbox'));
          const n = boxes.filter(function (b) { return b.checked; }).length;

          done += n;
          total += boxes.length;
          if (boxes.length > 0 && n === boxes.length) topicsDone++;

          details.querySelector('.tp-count').textContent = n + '/' + boxes.length;

          boxes.forEach(function (b) {
            const label = b.closest('label');
            label.classList.toggle('tp-done', b.checked);
            label.querySelector('.status-text').textContent = b.checked ? 'Completed' : 'Pending';
          });
        });

        document.getElementById('tp-percent').textContent = (total ? Math.round(done / total * 100) : 0) + '%';
        document.getElementById('tp-items').textContent = done + ' / ' + total;
        document.getElementById('tp-topics').textContent = topicsDone + ' / ' + topics.length;
      }

      list.addEventListener('change', async function (event) {
        const box = event.target;

        if (!box.classList.contains('progress-checkbox')) {
          return;
        }

        const isCompleted = box.checked;
        box.disabled = true;
        refresh();
        say('Saving...');

        try {
          const formData = new FormData();
          formData.append('csrf_token', csrf);
          formData.append('student_id', String(studentId));
          formData.append('topic_id', box.dataset.topicId);
          formData.append('is_completed', isCompleted ? '1' : '0');

          const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
          const result = await response.json().catch(function () { return {}; });

          if (!response.ok || !result.success) {
            throw new Error(result.error || 'Unknown server error');
          }

          say('Saved', false, 1500);
        } catch (error) {
          box.checked = !isCompleted;
          refresh();
          say('Could not save: ' + error.message, true);
        } finally {
          box.disabled = false;
        }
      });

      refresh();
    })();
  </script>

<?php endif; ?>

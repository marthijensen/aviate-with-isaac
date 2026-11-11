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
    .pt-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0 8px; padding-top: 20px; border-top: 1px solid var(--line); }
    .pt-stat strong { display: block; font-size: 2rem; color: var(--navy); }
    .pt-stat span { color: var(--muted); font-size: 0.85rem; }
    details.pt-topic { border-top: 1px solid var(--line); }
    details.pt-topic summary { display: flex; align-items: center; gap: 12px; padding: 14px 0; cursor: pointer; font-weight: 600; color: var(--navy); }
    details.pt-topic .pt-count { margin-left: auto; color: var(--muted); font-weight: 400; white-space: nowrap; }
    ul.pt-subs { list-style: none; margin: 0; padding: 0 0 14px 30px; }
    ul.pt-subs li { padding: 5px 0; }
    ul.pt-subs label { display: flex; gap: 10px; align-items: flex-start; cursor: pointer; }
    ul.pt-subs li.pt-done span { color: var(--muted); text-decoration: line-through; }
    .pt-topics input[type=checkbox] { width: 18px; height: 18px; margin: 2px 0 0; accent-color: var(--navy); flex: none; }
    .pt-topics input[type=checkbox]:disabled { cursor: not-allowed; }
    #pt-toast { position: fixed; bottom: 20px; right: 20px; padding: 10px 16px; background: var(--paper); border: 1px solid var(--navy); font-size: 0.85rem; font-weight: 600; }
    #pt-toast.pt-error { background: var(--navy); color: #fff; }
    #pt-toast:empty { display: none; }
    @media (max-width: 750px) { .pt-stats { grid-template-columns: 1fr; } ul.pt-subs { padding-left: 0; } }
</style>

<h1>Training Progress</h1>

<p class="lead">
    Check off topics and subtopics as the student completes them.
</p>

<?php if ($error !== null): ?>

    <p><a href="/students">← Back to Students</a></p>

    <section class="fields-grid single">
        <article class="field-card">
            <p><?= e($error) ?></p>
        </article>
    </section>

<?php elseif ($selectedStudent !== null): ?>

    <p>
        <a href="/students/profile?id=<?= $selectedStudentId ?>">← Back to Student Profile</a>
    </p>

    <section class="fields-grid single">
        <article class="field-card">

            <h2><?= e(trim((string) ($selectedStudent['first_name'] ?? '') . ' ' . (string) ($selectedStudent['last_name'] ?? ''))) ?></h2>

            <?php if (trim((string) ($selectedStudent['training_goal'] ?? '')) !== ''): ?>
                <p><?= e((string) $selectedStudent['training_goal']) ?></p>
            <?php endif; ?>

            <?php if (!$writesEnabled): ?>
                <p class="action-note">Saving is turned off, so the checkboxes are read-only. Set AVIATE_LOCAL_WRITES=1 (this computer) or AVIATE_DEMO_WRITES=1 in .env to enable it.</p>
            <?php endif; ?>

            <?php if ($progressLoadFailed): ?>
                <p class="action-note">Saved progress could not be loaded, so nothing is shown as checked.</p>
            <?php endif; ?>

            <div class="pt-stats">
                <div class="pt-stat"><strong id="pt-percent"><?= $percent ?>%</strong><span>Overall progress</span></div>
                <div class="pt-stat"><strong id="pt-subs"><?= $doneItems ?> / <?= $totalItems ?></strong><span>Subtopics complete</span></div>
                <div class="pt-stat"><strong id="pt-topics"><?= $topicsDone ?> / <?= count($groupedTopics) ?></strong><span>Topics complete</span></div>
            </div>

            <?php if ($groupedTopics === []): ?>

                <p>No topics found for this student's course.</p>

            <?php else: ?>

                <div class="pt-topics" id="pt-topics-list">

                    <?php foreach ($groupedTopics as $topicName => $items):
                        $finished = 0;

                        foreach ($items as $item) {
                            if (isset($completed[(int) ($item['topic_id'] ?? 0)])) {
                                $finished++;
                            }
                        }
                    ?>

                        <details class="pt-topic">

                            <summary>
                                <input type="checkbox" class="pt-topic-check"
                                       aria-label="Mark all of <?= e((string) $topicName) ?>"
                                       <?= ($items !== [] && $finished === count($items)) ? 'checked' : '' ?>
                                       <?= $writesEnabled ? '' : 'disabled' ?>>
                                <span><?= e((string) $topicName) ?></span>
                                <span class="pt-count"><?= $finished ?>/<?= count($items) ?></span>
                            </summary>

                            <ul class="pt-subs">
                                <?php foreach ($items as $item):
                                    $itemId = (int) ($item['topic_id'] ?? 0);
                                ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" class="pt-sub"
                                                   data-id="<?= $itemId ?>"
                                                   <?= isset($completed[$itemId]) ? 'checked' : '' ?>
                                                   <?= $writesEnabled ? '' : 'disabled' ?>>
                                            <span><?= e((string) ($item['subtopic'] ?? 'General')) ?></span>
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

    <div id="pt-toast" role="status" aria-live="polite"></div>

    <script>
    (function () {
        const studentId = <?= $selectedStudentId ?>;
        const csrf = <?= json_encode($csrf) ?>;
        const root = document.getElementById('pt-topics-list');
        const toast = document.getElementById('pt-toast');

        if (!root) {
            return;
        }

        function say(message, isError, ms) {
            toast.textContent = message || '';
            toast.className = isError ? 'pt-error' : '';
            if (message && ms) {
                setTimeout(function () { if (toast.textContent === message) toast.textContent = ''; }, ms);
            }
        }

        // Recalculate counts, strikethrough and partial state from the checkboxes.
        function refresh() {
            let done = 0;
            let total = 0;
            let topicsDone = 0;
            const details = root.querySelectorAll('details.pt-topic');

            details.forEach(function (d) {
                const subs = Array.from(d.querySelectorAll('input.pt-sub'));
                const n = subs.filter(function (s) { return s.checked; }).length;
                const all = subs.length > 0 && n === subs.length;

                done += n;
                total += subs.length;
                if (all) topicsDone++;

                const topicBox = d.querySelector('input.pt-topic-check');
                topicBox.checked = all;
                topicBox.indeterminate = n > 0 && !all;

                d.querySelector('.pt-count').textContent = n + '/' + subs.length;
                subs.forEach(function (s) { s.closest('li').classList.toggle('pt-done', s.checked); });
            });

            document.getElementById('pt-percent').textContent = (total ? Math.round(done / total * 100) : 0) + '%';
            document.getElementById('pt-subs').textContent = done + ' / ' + total;
            document.getElementById('pt-topics').textContent = topicsDone + ' / ' + details.length;
        }

        async function save(boxes, checked, previous) {
            say('Saving...');

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrf);
                formData.append('student_id', String(studentId));
                formData.append('topic_ids', boxes.map(function (b) { return b.dataset.id; }).join(','));
                formData.append('is_completed', checked ? '1' : '0');

                const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
                const result = await response.json().catch(function () { return {}; });

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'Unknown server error');
                }

                say('Saved', false, 1500);
            } catch (error) {
                boxes.forEach(function (b, i) { b.checked = previous[i]; });
                refresh();
                say('Could not save: ' + error.message, true);
            }
        }

        root.addEventListener('change', function (event) {
            const box = event.target;

            if (box.classList.contains('pt-sub')) {
                refresh();
                save([box], box.checked, [!box.checked]);
            } else if (box.classList.contains('pt-topic-check')) {
                const subs = Array.from(box.closest('details').querySelectorAll('input.pt-sub'));
                const previous = subs.map(function (s) { return s.checked; });

                subs.forEach(function (s) { s.checked = box.checked; });
                refresh();
                save(subs, box.checked, previous);
            }
        });

        refresh();
    })();
    </script>

<?php endif; ?>

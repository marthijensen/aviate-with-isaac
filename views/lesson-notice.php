<?php
/**
 * Message page for lesson actions that can't continue (not found, cancelled, past).
 *
 * @var string $title
 * @var string $message
 * @var string $backHref
 * @var string $backLabel
 */
?>
<h1><?= e($title) ?></h1>

<section class="fields-grid single">
    <article class="field-card">
        <p class="action-note" role="alert"><?= e($message) ?></p>
        <div class="actions">
            <a href="<?= e($backHref) ?>"><?= e($backLabel) ?></a>
        </div>
    </article>
</section>

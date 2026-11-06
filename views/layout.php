<?php
/**
 * Shared page shell. Markup and classes match aviate-app.html so pages use app.css.
 *
 * @var string $title
 * @var string $content Rendered page HTML
 * @var string $currentPath
 */
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Aviate with Isaac | <?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/app.css" />
  </head>
  <body>
    <header class="app-header">
      <a class="brand" href="/dashboard" aria-label="Aviate with Isaac home">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 1.4 14.3 9.2 22.2 12 14.3 14.8 12 22.6 9.7 14.8 1.8 12 9.7 9.2 12 1.4Z" />
          </svg>
        </span>
        <span class="brand-text">
          <strong>AVIATE</strong>
          <small>WITH ISAAC</small>
        </span>
      </a>
      <div class="header-meta">
        <span class="status"><span class="status-dot"></span><span>Briefing ready</span></span>
      </div>
    </header>

    <div class="app-shell">
      <aside class="sidebar">
          <nav class="side-nav" aria-label="App navigation">

              <a href="/dashboard"<?= $currentPath === '/dashboard' ? ' class="active" aria-current="page"' : '' ?>>
                  Dashboard
              </a>

              <a href="/students"<?= str_starts_with($currentPath, '/students')? ' class="active" aria-current="page"' : '' ?>>
                  Students
              </a>

              <a href="/schedule"<?= $currentPath === '/schedule' ? ' class="active" aria-current="page"' : '' ?>>
                  Schedule
              </a>
              
          </nav>
      </aside>

      <main class="main-display">
        <?= $content ?>
      </main>
    </div>
  </body>
</html>

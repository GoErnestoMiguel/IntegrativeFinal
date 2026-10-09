<?php
require 'config.php';
require 'public-content.php';
$page_title = 'Announcements';
$program = in_array($_GET['program'] ?? '', ['TUPAD', '4Ps'], true) ? $_GET['program'] : 'All';
$announcements = array_filter($announcements, function ($item) use ($program) { return $program === 'All' || $item['program'] === 'All' || $item['program'] === $program; });
require 'header.php';
if ($content_error) echo '<p class="container error" role="alert">' . e($content_error) . '</p>';
?>
<section class="container section narrow">
    <p class="eyebrow">FROM THE COMMUNITY DESK</p><h1>Announcements</h1>
    <p>Public updates from the community desk.</p>
    <form action="announcements.php" method="get" class="toolbar">
        <label class="field">Program<select name="program"><?php foreach (['All', 'TUPAD', '4Ps'] as $option): ?><option <?= $program === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
        <button class="secondary">Show announcements</button>
    </form>
    <?php if (!$announcements && !$content_error): ?><p>No announcements published for this program yet.</p><?php endif; ?>
    <?php foreach ($announcements as $announcement): ?>
        <?php if ($program !== 'All' && $announcement['program'] !== 'All' && $announcement['program'] !== $program) continue; ?>
        <article class="card">
            <span class="badge"><?= e($announcement['program']) ?></span>
            <h2><?= e($announcement['title']) ?></h2>
            <p><?= e($announcement['body']) ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php require 'footer.php'; ?>

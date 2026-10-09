<?php
require 'config.php';
require 'public-content.php';
$page_title = 'Calendar';
$selected_month = (string) ($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $selected_month)) $selected_month = date('Y-m');
$first_day = new DateTime($selected_month . '-01');
$previous_month = (clone $first_day)->modify('-1 month')->format('Y-m');
$next_month = (clone $first_day)->modify('+1 month')->format('Y-m');
$program = in_array($_GET['program'] ?? '', ['TUPAD', '4Ps'], true) ? $_GET['program'] : 'All';
$visible_events = array_filter($events, function ($event) use ($program, $selected_month) {
    return substr($event['date'], 0, 7) === $selected_month && ($program === 'All' || $event['program'] === 'All' || $event['program'] === $program);
});
$days_in_month = (int) $first_day->format('t');
$offset = (int) $first_day->format('w');
require 'header.php';
if ($content_error) echo '<p class="container error" role="alert">' . e($content_error) . '</p>';
?>
<section class="container section">
    <p class="eyebrow">PLAN YOUR NEXT VISIT</p><h1>Community calendar</h1>
    <div class="notice">Times are Philippine time. Check the event details for instructions.</div>
    <div class="toolbar">
        <div class="month-nav">
            <a class="button secondary" aria-label="Previous month" href="calendar.php?<?= e(http_build_query(['month' => $previous_month, 'program' => $program])) ?>">←</a>
            <h2><?= e($first_day->format('F Y')) ?></h2>
            <a class="button secondary" aria-label="Next month" href="calendar.php?<?= e(http_build_query(['month' => $next_month, 'program' => $program])) ?>">→</a>
        </div>
        <form method="get" action="calendar.php">
            <input type="hidden" name="month" value="<?= e($selected_month) ?>">
            <label class="field">Program<select name="program"><?php foreach (['All', 'TUPAD', '4Ps'] as $option): ?><option <?= $program === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
            <button class="secondary">Show events</button>
        </form>
    </div>
    <table class="calendar" aria-label="<?= e($first_day->format('F Y')) ?> calendar">
        <thead><tr><?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?><th scope="col"><?= $day ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php for ($cell = 0; $cell < ceil(($offset + $days_in_month) / 7) * 7; $cell++): ?>
            <?php if ($cell % 7 === 0): ?><tr><?php endif; ?>
            <?php $number = $cell - $offset + 1; $date = sprintf('%s-%02d', $selected_month, $number); ?>
            <td class="<?= $date === date('Y-m-d') ? 'today' : '' ?>">
                <?php if ($number >= 1 && $number <= $days_in_month): ?>
                    <?= $number ?>
                    <?php foreach ($visible_events as $key => $event): ?>
                        <?php if ($event['date'] === $date): ?><a href="#event-<?= $key ?>"><?= e($event['program']) ?> event</a><?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <?php if ($cell % 7 === 6): ?></tr><?php endif; ?>
        <?php endfor; ?>
        </tbody>
    </table>
    <h2>Events this month</h2>
    <?php if (!$visible_events): ?><p class="notice">No events published for this month and program.</p><?php endif; ?>
    <?php foreach ($visible_events as $key => $event): ?>
        <article class="card event" id="event-<?= $key ?>">
            <div class="date-box"><span><?= e(date('M', strtotime($event['date']))) ?></span><b><?= e(date('d', strtotime($event['date']))) ?></b></div>
            <div><span class="badge"><?= e($event['program']) ?></span><h3><?= e($event['title']) ?></h3><p><?= e($event['time']) ?> · <?= e($event['venue']) ?></p><p class="small"><?= e($event['details']) ?></p></div>
        </article>
    <?php endforeach; ?>
</section>
<?php require 'footer.php'; ?>

<?php
require 'config.php';
$page_title = 'Initial qualification check';
require_once 'qualification-rules.php';
$questions = qualification_questions();
$answers = $_SESSION['qualification_answers'] ?? [];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = [];
    foreach ($questions as $key => $question) {
        $answer = $_POST[$key] ?? '';
        if (!is_string($answer) || !array_key_exists($answer, $question[1])) {
            $error = 'Please answer every question before viewing your results. Choose Not sure when you need help.';
        } else {
            $answers[$key] = $answer;
        }
    }
    if (!valid_form()) $error = 'Please refresh this page and try again.';
    if (!$error) {
        $_SESSION['qualification_answers'] = $answers;
        header('Location: checker.php?results=1');
        exit;
    }
}
$complete = count($answers) === count($questions);
foreach ($questions as $key => $question) {
    if (!isset($answers[$key]) || !is_string($answers[$key]) || !array_key_exists($answers[$key], $question[1])) $complete = false;
}
$show_results = !$error && isset($_GET['results']) && $complete;
require 'header.php';
?>
<section class="container section narrow">
    <p class="eyebrow">OPTIONAL SELF-CHECK</p>
    <h1>Initial qualification check</h1>
    <p>Answer <?= count($questions) ?> short questions about work, household circumstances and application readiness. Results explain possible matches and what staff still need to verify.</p>
    <div class="notice">This is preliminary guidance based on your answers, not an eligibility decision. DOLE or DSWD must validate qualifications, available places, and current rules.</div>
    <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if (!$show_results): ?>
        <p class="notice">This optional self-check is separate from registration. Your answers here are not attached to a new application. <a href="register.php">Go directly to application forms</a>.</p><form action="checker.php" method="post" id="qualification-form" class="card">
            <input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>">
            <div id="check-progress" hidden><p id="check-count" aria-live="polite"></p><progress id="check-bar" max="<?= count($questions) ?>" value="1" aria-label="Question progress"></progress></div>
            <?php $number = 0; foreach ($questions as $key => $question): $number++; ?>
                <fieldset class="check-question">
                    <legend><?= $number ?>. <?= e($question[0]) ?></legend><p class="small"><?= e($question[2]) ?></p>
                    <?php foreach ($question[1] as $value => $label): ?>
                        <label class="check-choice"><input type="radio" name="<?= e($key) ?>" value="<?= e($value) ?>" required <?= ($answers[$key] ?? '') === $value ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <div class="actions"><button type="button" id="check-back" class="secondary" hidden>← Back</button><button type="button" id="check-next" class="primary" hidden>Next question →</button><button type="submit" id="check-submit" class="primary">View my results</button></div>
        </form>
    <?php else:
        $results = qualification_results($answers);
        foreach ($results as $program => $result): ?>
            <article class="card program-card <?= $program === '4Ps' ? 'red-top' : '' ?>">
                <span class="badge"><?= e($program) ?> · PRELIMINARY RESULT</span><h2><?= e($result[0]) ?></h2>
                <ul><?php foreach ($result[1] as $note): ?><li><?= e($note) ?></li><?php endforeach; ?></ul>
                <h3>Documents to prepare</h3>
                <?php foreach ($result[2] as $document): ?><label class="checkbox"><input type="checkbox"><span><?= e($document) ?></span></label><?php endforeach; ?>
                <p class="small">This is a preparation list, not a fixed final requirement. Ask the office about accepted alternatives.</p>
                <div class="actions"><a class="button primary" href="register.php?program=<?= urlencode($program) ?>"><?= $program === 'TUPAD' ? 'Open separate TUPAD form' : 'Open separate 4Ps form' ?></a><a href="<?= e($result[3]) ?>" class="button secondary">Read program details</a></div>
            </article>
        <?php endforeach; ?>
        <details><summary>Review my answers</summary><dl class="review"><?php foreach ($questions as $key => $question): ?><div><dt><?= e($question[0]) ?></dt><dd><?= e($question[1][$answers[$key]]) ?></dd></div><?php endforeach; ?></dl></details>
        <div class="actions"><a href="checker.php" class="button secondary">Change my answers</a><button type="button" class="secondary" data-print>Print results and checklist</button><a href="help.php" class="button secondary">Ask for help</a></div>
        <p class="small">Guidance reviewed September 17, 2026. Sources: <a href="https://dole.gov.ph/news/dole-expands-livelihood-and-emergency-employment-access-under-updated-kabuhayan-tupad-guidelines/">DOLE program guidance</a> and <a href="https://pantawid.dswd.gov.ph/2026/03/4ps-to-register-new-family-beneficiaries-using-cbms-data/">DSWD household targeting</a>.</p>
    <?php endif; ?>
</section>
<?php require 'footer.php'; ?>

<?php
$page_title = 'TUPAD';
require 'header.php';

?>
<div lang="en">
<section class="page-heading"><div class="container"><p class="eyebrow"><?= e('DOLE · EMERGENCY EMPLOYMENT') ?></p><h1>TUPAD</h1><p>Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers</p></div></section>
<section class="container section detail-layout">
    <div>
        <h2><?= e('What is TUPAD?') ?></h2>
        <p><?= e('TUPAD provides short-term emergency employment for disadvantaged or displaced workers. Approved activities may include community cleaning, maintenance, and other local projects.') ?></p>
        <h2><?= e('Who is it for?') ?></h2>
        <p><?= e('Displaced, underemployed, seasonal, and other disadvantaged workers. The implementing office reviews each applicant against current DOLE rules and the approved local project.') ?></p>
        <h2><?= e('How it works') ?></h2>
        <ol class="steps">
            <li><strong><?= e('Register your interest') ?></strong><span><?= e('Complete the short form and keep your application ID.') ?></span></li>
            <li><strong><?= e('Complete screening') ?></strong><span><?= e('Prepare documents and attend an interview if requested.') ?></span></li>
            <li><strong><?= e('Attend orientation if selected') ?></strong><span><?= e('Follow the confirmed instructions before starting approved community work.') ?></span></li>
        </ol>
        <div class="notice"><?= e('Registration does not guarantee approval. The implementing office confirms the available places, work duration, wage, and final requirements.') ?></div>
        <a href="https://dole.gov.ph/tupad-contents/"><?= e('Read the DOLE program overview') ?></a>
    </div>
    <aside class="card">
        <h2><?= e('Documents to prepare') ?></h2>
        <ul class="checklist">
            <li><?= e('Accepted valid identification') ?></li>
            <li><?= e('Proof of residence, if requested') ?></li>
            <li><?= e('Beneficiary information or profiling form') ?></li>
            <li><?= e('Proof or declaration of employment circumstances, as requested') ?></li>
        </ul>
        <p class="small"><?= e('Confirm the final list with the local implementing office.') ?></p>
        <div class="actions"><a href="register.php?program=TUPAD&amp;mode=full" class="button primary"><?= e('Start registration') ?></a></div>
    </aside>
</section>
</div>
<?php require 'footer.php'; ?>

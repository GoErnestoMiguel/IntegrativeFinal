<?php
$page_title = '4Ps';
require 'header.php';

?>
<div lang="en">
<section class="page-heading"><div class="container"><p class="eyebrow"><?= e('DSWD · FAMILY SUPPORT') ?></p><h1>4Ps</h1><p>Pantawid Pamilyang Pilipino Program</p></div></section>
<section class="container section detail-layout">
    <div>
        <h2><?= e('What is 4Ps?') ?></h2>
        <p><?= e('4Ps provides conditional cash support to eligible households for health, nutrition, and children’s education.') ?></p>
        <h2><?= e('Who is it for?') ?></h2>
        <p><?= e('Households identified through official targeting, with children aged 0–18 or a pregnant household member at registration, and willing to comply with program conditions. DSWD carries out the official assessment and selection.') ?></p>
        <h2><?= e('How it works') ?></h2>
        <ol class="steps">
            <li><strong><?= e('Request local guidance') ?></strong><span><?= e('Provide your details to ask about assessment or referral.') ?></span></li>
            <li><strong><?= e('Prepare your documents') ?></strong><span><?= e('Follow the requirements given by authorized personnel.') ?></span></li>
            <li><strong><?= e('Coordinate with DSWD') ?></strong><span><?= e('Follow official validation and enrollment instructions.') ?></span></li>
        </ol>
        <div class="notice"><?= e('This form is a local assessment or assistance request, not direct 4Ps enrollment. DSWD makes the official decision. 4Ps does not require TUPAD cleaning work.') ?></div>
        <a href="https://car.dswd.gov.ph/programs-services/core-programs/pantawid-pamilyang-pilipino-program-4ps/"><?= e('Read the DSWD program overview') ?></a>
    </div>
    <aside class="card red-top">
        <h2><?= e('Documents to prepare') ?></h2>
        <ul class="checklist">
            <li><?= e('PhilSys ID or proof of registration; confirm current acceptance with DSWD') ?></li>
            <li><?= e('Birth certificates of household members') ?></li>
            <li><?= e('School records, when applicable') ?></li>
            <li><?= e('Health or pregnancy records, when applicable') ?></li>
            <li><?= e('Marriage, death, or disability records, when applicable') ?></li>
        </ul>
        <p class="small"><?= e('Ask the DSWD City/Municipal Link for the final checklist.') ?></p>
        <div class="actions"><a href="register.php?program=4Ps&amp;mode=full" class="button primary"><?= e('Start registration') ?></a></div>
    </aside>
</section>
</div>
<?php require 'footer.php'; ?>

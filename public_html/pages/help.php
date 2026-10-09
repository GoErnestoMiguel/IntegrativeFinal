<?php
$page_title = 'Help';
require 'header.php';

?>
<div lang="en">
<section class="container section narrow">
    <p class="eyebrow"><?= e('YOU DO NOT HAVE TO DO THIS ALONE') ?></p><h1><?= e('Help with your application') ?></h1>
    <div class="card">
        <h2><?= e('Ask someone to guide you') ?></h2>
        <ol class="steps">
            <li><strong><?= e('Visit the barangay help desk') ?></strong><span><?= e('Ask for help with TUPAD or 4Ps guidance. Confirm the actual office hours locally.') ?></span></li>
            <li><strong><?= e('Prepare your available documents') ?></strong><span><?= e('Staff can explain what you need and help you complete the form.') ?></span></li>
            <li><strong><?= e('Review the details together') ?></strong><span><?= e('Check your name, address, and preferred contact before submitting.') ?></span></li>
            <li><strong><?= e('Keep a printed receipt') ?></strong><span><?= e('Use the application ID when asking for updates.') ?></span></li>
        </ol>
    </div>
    <h2><?= e('Frequently asked questions') ?></h2>
    <details><summary><?= e('Do I need a phone or email?') ?></summary><p><?= e('Online registration requires an email address and verification code (OTP). If you need assistance, visit the barangay help desk. Choose help desk updates for in-person follow-up after registering.') ?></p></details>
    <details><summary><?= e('Does my application ID mean I am approved?') ?></summary><p><?= e('No. It identifies your local request. DOLE or DSWD validation and program requirements still apply.') ?></p></details>
    <details><summary><?= e('What if I have missing documents?') ?></summary><p><?= e('Upload the applicable JPG or PNG photos, up to 5 MB each. You can select multiple photos for each requirement. PDF files are not accepted. You may add missing copies later in My Profile. Read staff review comments before submitting replacements.') ?></p></details>
    <details><summary><?= e('Will I receive text messages?') ?></summary><p><?= e('Choose Email for application update emails when email delivery is configured. SMS is not available. My Profile always shows saved status changes and document review comments.') ?></p></details>
    <details><summary><?= e('Are my applications saved?') ?></summary><p><?= e('Yes. Your application remains saved after closing your browser. Sign in to My Profile with your issued username and the password you chose during registration. Use your applicant ID when asking staff for help. Authorized staff can review your application.') ?></p></details>
    <div class="actions"><a href="register.php" class="button primary"><?= e('Try registration') ?></a></div>
</section>
</div>
<?php require 'footer.php'; ?>

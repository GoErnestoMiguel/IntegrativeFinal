<?php
require 'config.php'; $page_title='Privacy notice'; require 'header.php';$privacy=hosting_settings();
?>
<section class="container section narrow"><h1>Privacy notice</h1>
<?php if(!$privacy['privacy_approved']): ?><p class="notice">Draft notice for review by the responsible barangay office. Public registration remains closed until the production privacy settings are completed.</p><?php endif; ?>
<article class="card"><h2>Information used for your application</h2><p>The portal collects identity and contact details, application answers, and the documents you submit for barangay application review. Staff may compare your details with resident records supplied by the barangay. TUPAD work assignments and attendance are also recorded when applicable.</p>
<h2>Who can view it</h2><p>You can view your own application after signing in. Authorized staff can review applications and supporting records. Email service providers process verification and notification emails. Do not include document copies or unnecessary sensitive details in email instructions.</p>
<h2>What verification means</h2><p>Matching suggestions help staff locate resident records. They do not automatically establish identity or approve government assistance. Staff remain responsible for reviewing evidence and explaining decisions.</p>
<h2>Retention</h2><p><?= e($privacy['retention_statement'] ?: 'The responsible office must confirm its retention and deletion policy before this service opens for real applications.') ?></p>
<h2>Corrections and privacy questions</h2><p>Contact <?= e($privacy['privacy_contact'] ?: 'the barangay help desk; a designated privacy contact will be published before launch') ?> to ask about access to your information, corrections, or other privacy concerns. Staff may verify your identity before responding.</p>
<h2>Account protection</h2><p>Keep your password and email verification codes private. Use only documents that apply to the request. The website uses a session cookie to maintain your login and protect form submissions.</p></article></section>
<?php require 'footer.php'; ?>

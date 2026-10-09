<?php
require 'config.php';
$page_title = 'Application receipt';
$application = null;
$load_error = '';
try {
    if (!empty($_SESSION['last_applicant_id'])) $application = find_applicant($_SESSION['last_applicant_id']);
} catch (PDOException $error) { $load_error = database_error(); }
$credentials = $_SESSION['registration_credentials'] ?? null;
unset($_SESSION['registration_credentials']);
require 'header.php';
?>
<section class="container section narrow">
    <?php if ($load_error): ?><p class="error"><?= e($load_error) ?></p><?php endif; ?>
    <?php if (!$application): ?>
        <h1>No receipt yet</h1><p>Complete registration to receive your receipt.</p><a href="register.php" class="button primary">Go to registration</a>
    <?php else: ?>
        <div class="card success">
            <span class="badge">APPLICATION RECEIPT</span><h1>Your request is registered.</h1>
            <p>Your applicant ID is shown below. Keep it for follow-up. Use your username and password to sign in to My profile.</p>
            <?php if ($credentials && $credentials['id']===$application['id']): ?><div class="notice"><h2>Your login details</h2><p>Username: <strong><?= e($credentials['username']) ?></strong></p><p>Temporary password: <strong><?= e($credentials['password']) ?></strong></p><p>Sign in within 24 hours and change this password before viewing your profile. Keep these details private.</p><p><?= $credentials['mail_status']==='sent' ? 'The confirmation email was accepted by the mail server. Check your inbox and spam folder.' : 'Your application is saved, but the email was not sent. Keep these login details and contact staff if needed.' ?></p></div><?php endif; ?>
            <p class="receipt-id"><?= e($application['id']) ?></p>
            <dl class="review">
                <div><dt>Applicant</dt><dd><?= e($application['name']) ?></dd></div>
                <div><dt>Program</dt><dd><?= e($application['program']) ?></dd></div>
                <div><dt>Update preference</dt><dd><?= $application['method'] === 'none' ? 'Help desk updates' : e($application['contact']) ?></dd></div>
                <div><dt>Status</dt><dd><?= e($application['status']) ?></dd></div>
            </dl>
            <div class="notice">Keep this application reference number for follow-up. It is not an official beneficiary ID or proof of approval.</div>
            <div class="actions"><button type="button" class="secondary" data-print>Print receipt</button><a href="profile.php" class="button primary">View my profile →</a></div>
        </div>
    <?php endif; ?>
</section>
<?php require 'footer.php'; ?>

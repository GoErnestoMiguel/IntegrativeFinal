<section class="card section" id="admin-login" aria-labelledby="admin-login-title">
<p class="eyebrow">STAFF ACCESS</p><h2 id="admin-login-title">Admin login</h2>
<?php if ($admin_error): ?><p class="error" role="alert"><?= e($admin_error) ?></p><?php endif; ?>
<?php if (isset($_GET['admin']) && !admin_signed_in()): ?><p class="notice">Please sign in to access the admin panel.</p><?php endif; ?>
<?php if (admin_signed_in()): ?>
<p>Signed in as <?= e($_SESSION['admin_user']) ?>.</p><div style="display:flex;flex-direction:column;align-items:flex-start;gap:12px"><a class="button primary" href="admin-panel.php">Open admin panel</a>
<form method="post" action="profile.php" style="margin:0"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button class="secondary" name="admin_action" value="logout">Log out</button></form></div>
<?php elseif(!empty($_SESSION['staff_challenge'])): ?>
<p>Enter the verification code sent to your staff email. It expires in 10 minutes.</p>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="admin_action" value="verify"><label class="field">Staff verification code<input name="staff_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label><button class="primary">Verify and sign in</button></form>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button name="admin_action" value="logout" class="secondary">Start again</button></form>
<?php else: ?>
<p>Staff can sign in here to manage applicants, profiles, work schedules and attendance.</p>
<form method="post" action="profile.php#admin-login">
<input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="admin_action" value="login">
<label class="field">Username<input name="username" required autocomplete="username" maxlength="80"></label>
<label class="field">Password<input type="password" name="password" required autocomplete="current-password" maxlength="200"></label>
<button class="primary">Log in as admin</button></form>
<?php endif; ?></section>

<?php if (!$account): ?>
<p>Sign in using your issued username and your current password, or the temporary password on your registration receipt for your first login. If staff reset your account, use the temporary password they provided. Existing applicants without credentials should contact the help desk.</p>
<form method="post" class="card"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="account_action" value="login"><label class="field">Username<input name="username" required autocomplete="username" maxlength="80"></label><label class="field">Password<input type="password" name="password" required autocomplete="current-password" maxlength="72"></label><button class="primary">Sign in</button></form>
<?php else: ?>
<section class="card"><h2>Account settings</h2>
<?php if ($account['must_change_password']): ?><p class="notice">Change your temporary password before viewing your profile.</p><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><p class="notice">Your login details have been updated.</p><?php endif; ?>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="account_action" value="update">
<label class="field">Username<input name="new_username" value="<?= e($account['username']) ?>" required minlength="3" maxlength="80" autocomplete="username"></label>
<label class="field">Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
<label class="field">New password<input type="password" name="new_password" minlength="15" maxlength="72" autocomplete="new-password" <?= $account['must_change_password']?'required':'' ?>></label><p class="small">Use 15–72 characters. After the first change, leave blank to keep your current password.</p>
<label class="field">Confirm new password<input type="password" name="confirm_password" autocomplete="new-password"></label><button class="primary">Save login details</button></form>
<form method="post"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><button class="secondary" name="close_profile" value="1">Log out</button></form></section>
<?php endif; ?>

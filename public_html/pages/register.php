<?php
require 'config.php';
require_once 'application-fields.php';
require_once 'question-subtitles.php';

require_once 'email-otp.php';
require_once 'documents.php';
require_once 'default-credentials.php';
require_once 'workflow.php';
if(!hosting_settings()['registration_open']) { $page_title='Registration'; require 'header.php'; echo '<section class="container section"><h1>Registration is not open</h1><p>Please check the announcements or contact the help desk.</p></section>'; require 'footer.php'; exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    http_response_code(413);
    exit('The submission exceeds the server upload limit. Go back, choose smaller files, and try again.');
}
$selected_program = $_POST['program'] ?? $_GET['program'] ?? '';
if (!is_string($selected_program) || !in_array($selected_program, ['TUPAD', '4Ps'], true)) {
    $page_title = 'Apply';
    require 'header.php';
    ?>
    <section class="container section narrow">
        <p class="eyebrow">APPLICATION</p>
        <h1>Which program are you applying for?</h1>
        <p>Choose a program to open its application form.</p>
        <form action="register.php" method="get" class="card">
            <label class="field">Program
                <select name="program" required>
                    <option value="">Select a program</option>
                    <option value="TUPAD">TUPAD — Emergency employment</option>
                    <option value="4Ps">4Ps — Family assistance assessment</option>
                </select>
            </label>
            <button class="primary" type="submit">Continue to application →</button>
        </form>
    </section>
    <?php
    require 'footer.php';
    exit;
}
$program = $selected_program;
$mode = ($_POST['mode'] ?? $_GET['mode'] ?? '') === 'simple' ? 'simple' : 'full';
$intake_fields = intake_fields($program, $mode);
$details = [];
$page_title = 'Registration';
$errors = [];
$documents = [];
$possible_duplicate=false;
// Independent tokens let separate application tabs submit without invalidating each other.
$_SESSION['application_tokens']=array_filter($_SESSION['application_tokens'] ?? [],fn($expiry)=>$expiry>time());
$submission_token=$_SERVER['REQUEST_METHOD']==='POST'?(string)($_POST['submission_token'] ?? ''):bin2hex(random_bytes(16));
if($_SERVER['REQUEST_METHOD']!=='POST') $_SESSION['application_tokens'][$submission_token]=time()+7200;

$form = [
    'program' => $program,
    'email' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'name' => '', 'birth_date' => '', 'address' => '', 'method' => 'none', 'contact' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = $key==='password' ? (string)($_POST[$key] ?? '') : trim((string) ($_POST[$key] ?? ''));
    }
    $form['name'] = implode(' ', array_filter([$form['first_name'], $form['middle_name'], $form['last_name']], fn($v) => $v !== ''));
    foreach (['first_name','last_name'] as $part) {
        if ($form[$part] === '' || strlen($form[$part]) > 60) $errors[] = 'Enter first name(s) and last name, up to 60 characters each.';
    }
    if (strlen($form['middle_name']) > 60) $errors[] = 'Middle name must be at most 60 characters.';
    try { default_credentials($form['first_name'],$form['last_name'],$form['birth_date']); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    // Email updates always go to the account email verified by OTP.
    if ($form['method'] === 'email') $form['contact'] = $form['email'];
    foreach ($intake_fields as $key => $definition) {
        $details[$key] = is_string($_POST[$key] ?? '') ? trim($_POST[$key] ?? '') : '';
        if (strpos($definition[0], '(optional)') === false && $details[$key] === '') $errors[] = $definition[0] . ': please answer.';
        if (strlen($details[$key]) > 500) $errors[] = $definition[0] . ': use at most 500 characters.';
        if (is_array($definition[1]) && !in_array($details[$key], $definition[1], true)) $errors[] = $definition[0] . ': choose a listed answer.';
        if ($definition[1] === 'number' && $details[$key] !== '' && (!ctype_digit($details[$key]) || (int)$details[$key] > 100000000)) $errors[] = $definition[0] . ': enter a valid whole number.';
    }
    if ($program === '4Ps' && ((int)($details['household_size'] ?? 0) < 1 || (int)($details['children_count'] ?? 0) > (int)($details['household_size'] ?? 0))) $errors[] = 'Check household size and number of children.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 254) $errors[] = 'Enter a valid email address for your login details.';
    if (!valid_form()) $errors[] = 'Please refresh the page and try again.';
    if (valid_form()) {
        $otp_error = check_email_otp($form['email'], $_POST['email_otp'] ?? '');
        if ($otp_error) $errors[] = $otp_error;
    }
    if (!in_array($form['program'], ['TUPAD', '4Ps'], true)) $errors[] = 'Choose a program.';
    if (strlen($form['name']) < 2 || strlen($form['name']) > 120) $errors[] = 'Enter a full name between 2 and 120 characters.';
    if (strlen($form['address']) < 5 || strlen($form['address']) > 250) $errors[] = 'Enter an address between 5 and 250 characters.';
    $birth = DateTime::createFromFormat('!Y-m-d', $form['birth_date']);
    if (!$birth || $birth->format('Y-m-d') !== $form['birth_date'] || $birth > new DateTime('today') || $birth < new DateTime('-120 years')) {
        $errors[] = 'Enter a valid date of birth.';
    }
    if (!in_array($form['method'], ['email', 'sms', 'none'], true)) $errors[] = 'Choose an update method.';
    if ($form['method'] === 'email' && !filter_var($form['contact'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($form['method'] === 'sms' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $form['contact'])) $errors[] = 'Use a mobile number such as 09171234567.';
    if (strlen($form['contact']) > 254) $errors[] = 'The contact is too long.';
    if (empty($_POST['consent'])) $errors[] = 'Please confirm your application details.';

    try { $documents = document_uploads($program); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    if (!$errors) {
        try {
            workflow_require();
            $q=database()->prepare('SELECT name FROM applicants WHERE program=? AND birth_date=?');
            $q->execute([$program,$form['birth_date']]);
            foreach($q->fetchAll(PDO::FETCH_COLUMN) as $name) if(match_text($name)===match_text($form['name'])) $possible_duplicate=true;
            if($possible_duplicate) {
                $note=trim((string)($_POST['duplicate_note'] ?? ''));
                if(empty($_POST['duplicate_confirm']) || strlen($note)<10 || strlen($note)>500) $errors[]='A similar application exists. Check My Profile first. If this is a separate request, explain why below so staff can review it.';
                else $details['duplicate_explanation']=$note;
            }
        } catch(Throwable $ex) { $errors[]=$ex instanceof PDOException?database_error():$ex->getMessage(); }
    }
    if (!$errors) {
        $submission = (string) ($_POST['submission_token'] ?? '');
        if (empty($_SESSION['application_tokens'][$submission]) || !preg_match('/^[a-f0-9]{32}$/', $submission)) {
            $errors[] = 'Please reload the form and try again.';
        } else {
            try {
                $_SESSION['last_applicant_id'] = save_applicant($form, [], $submission, ['mode' => $mode, 'answers' => $details], $documents);
                unset($_SESSION['email_otp']);
                unset($_SESSION['profile_applicant_id'], $_SESSION['applicant_auth']);
                header('Location: receipt.php');
                exit;
            } catch (Throwable $error) {
                error_log('Request failed: '.basename(__FILE__));
                $errors[] = $error instanceof PDOException ? database_error() : 'The application could not be saved. Please contact staff or try again.';
            }
        }
    }
}
require 'header.php';
?>
<section class="container section narrow">
    <p class="eyebrow">BARANGAY INTAKE FORM</p><h1><?= e($program) ?> <?= $program === '4Ps' ? 'assessment request' : 'application' ?></h1>
<p>This locally designed form is not an official DOLE or DSWD form. Staff will review your request.</p>
<p class="small"><a href="register.php">Change program</a> · Fields marked * are required.</p>
<div class="notice mode-notice">
    <?php if ($mode === 'full'): ?>
        <span>Having trouble? Go to simple mode.</span>
        <a class="button primary" href="register.php?program=<?= urlencode($program) ?>&amp;mode=simple">Simple mode</a>
    <?php else: ?>
        <span>Return to the full form to see all questions on one page.</span>
        <a class="button secondary" href="register.php?program=<?= urlencode($program) ?>&amp;mode=full">Full Form</a>
    <?php endif; ?>
</div>
<p class="small">Choose your mode before filling in the form. Switching modes starts a fresh form.</p>
    <?php if ($errors): ?>
        <div class="error" role="alert" tabindex="-1" id="form-errors"><strong>Please check the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <script src="../javascript/email-otp.js?v=1" defer></script>
    <form action="register.php" method="post" enctype="multipart/form-data" class="card" id="application-form" data-mode="<?= e($mode) ?>">
<input type="hidden" name="mode" value="<?= e($mode) ?>"><input type="hidden" name="program" value="<?= e($program) ?>">
<div id="application-progress" hidden><p id="application-count" role="status"></p><progress id="application-bar" max="4" value="1" aria-label="Application progress"></progress></div>
        <input type="hidden" name="submission_token" value="<?= e($submission_token) ?>">
        <input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>">
        <fieldset class="application-step">
            <legend>1. Tell us about yourself</legend>            <label class="field">First name(s) *<input name="first_name" value="<?= e($form['first_name']) ?>" required maxlength="60" placeholder="Example: Juan Carlo" autocomplete="given-name"></label>
            <label class="field">Middle name (optional)<input name="middle_name" value="<?= e($form['middle_name']) ?>" maxlength="60" autocomplete="additional-name"></label>
            <label class="field">Last name *<input name="last_name" value="<?= e($form['last_name']) ?>" required maxlength="60" placeholder="Example: Dela Cruz" autocomplete="family-name"></label>
            <label class="field">Date of birth *<?php question_hint('birth_date'); ?><input type="date" name="birth_date" <?php question_hint_attribute('birth_date'); ?> value="<?= e($form['birth_date']) ?>" max="<?= date('Y-m-d') ?>" required></label>
            <label class="field">Complete address *<?php question_hint('address'); ?><textarea name="address" <?php question_hint_attribute('address'); ?> required minlength="5" maxlength="250" rows="3" placeholder="House / street, purok, barangay, city"><?= e($form['address']) ?></textarea></label>
        </fieldset>
        <fieldset class="application-step"><legend>2. <?= $program === 'TUPAD' ? 'Your work situation' : 'Your household' ?></legend>
<p><?= $program === 'TUPAD' ? 'Help staff understand your work situation.' : 'Help staff prepare your assessment or referral to DSWD. This is not enrollment.' ?></p>
<?php foreach ($intake_fields as $key=>$definition): $required = strpos($definition[0], '(optional)') === false; ?>
<label class="field"><?= e($definition[0]) ?><?= $required ? ' *' : '' ?><?php question_hint($key); ?>
<?php if (is_array($definition[1])): ?><select name="<?= e($key) ?>" <?php question_hint_attribute($key); ?> <?= $required ? 'required' : '' ?>><option value="">Choose an answer</option><?php foreach ($definition[1] as $option): ?><option <?= ($details[$key] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select>
<?php else: ?><input name="<?= e($key) ?>" <?php question_hint_attribute($key); ?> type="<?= $definition[1] === 'number' ? 'number' : 'text' ?>" <?= $definition[1] === 'number' ? 'min="0" max="100000000" step="1"' : 'maxlength="500"' ?> value="<?= e($details[$key] ?? '') ?>" <?= $required ? 'required' : '' ?>><?php endif; ?></label>
<?php endforeach; ?></fieldset>
        <fieldset class="application-step">
            <legend>3. Documents and updates</legend>
            <label class="field">Email for your account *<input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($form['email']) ?>"></label><p class="small">We will email your application ID and username to this address.</p>
            <div class="otp-row"><label class="field">Email verification code (OTP) *<input name="email_otp" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" aria-describedby="otp-status"></label><button type="button" class="secondary" id="get-otp">Get OTP</button></div>
            <p class="small" id="otp-status" role="status">Select Get OTP to receive a code by email. Enter it here before submitting your application. The code expires in 10 minutes.</p>
<p class="small">Your username and temporary password will appear on your receipt and in your confirmation email. Sign in within 24 hours and change the temporary password before opening your profile.</p>
<?php document_fields($program); ?>
            <label class="field">Preferred method<?php question_hint('method'); ?>
                <select name="method" <?php question_hint_attribute('method'); ?> id="contact-method">
                    <option value="none" <?= $form['method'] === 'none' ? 'selected' : '' ?>>Help desk — in-person updates</option>
                    <option value="sms" <?= $form['method'] === 'sms' ? 'selected' : '' ?>>Text message</option>
                    <option value="email" <?= $form['method'] === 'email' ? 'selected' : '' ?>>Email</option>
                </select>
            </label>
            <label class="field" id="contact-field"><span id="contact-label">Email or mobile number (if selected)</span><?php question_hint('contact'); ?><input id="contact" name="contact" <?php question_hint_attribute('contact'); ?> value="<?= e($form['contact']) ?>" maxlength="254"></label>
            <p class="small">Your registration confirmation is sent to your account email. SMS notifications are not available.</p>
        </fieldset>
        <fieldset class="application-step"><legend>4. Review and send</legend><p><a href="privacy.php" target="_blank" rel="noopener">Read the privacy notice</a></p><p>Check your answers. After saving, keep your application reference number for follow-up.</p><dl id="application-review" class="review"></dl>
        <?php if($possible_duplicate): ?><div class="notice"><label class="checkbox"><input type="checkbox" name="duplicate_confirm" value="1" required <?= !empty($_POST['duplicate_confirm'])?'checked':'' ?>><span>I checked my existing application and need to submit a separate request for staff review.</span></label><label class="field">Why is this a separate application? Include the application period if different.<textarea name="duplicate_note" required minlength="10" maxlength="500"><?= e($_POST['duplicate_note'] ?? '') ?></textarea></label></div><?php endif; ?>
        <label class="checkbox"><input type="checkbox" name="consent" <?php question_hint_attribute('consent'); ?> <?= !empty($_POST['consent']) ? 'checked' : '' ?> required><span>I have read the privacy notice and confirm that the information I have provided is accurate to the best of my knowledge. *<?php question_hint('consent'); ?></span></label>
        </fieldset><div class="actions"><button type="button" class="secondary" id="application-back" hidden>Back</button><button type="button" class="primary" id="application-next" hidden>Continue →</button><button type="submit" class="primary" id="application-submit">Save and get reference number →</button></div>
    </form>
    <p class="small">Already applied to this program? Sign in to My Profile to check your existing application before applying again. <a href="help.php">Need help?</a></p>
</section>
<?php require 'footer.php'; ?>





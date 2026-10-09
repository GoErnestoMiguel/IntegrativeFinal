<?php
require 'config.php';
require_once 'admin-auth.php';
require_admin();
require 'profile-functions.php'; require_once 'applicant-picker.php';
$page_title = 'Manage registrant profile';
$id = (string) ($_POST['id'] ?? $_GET['id'] ?? '');
$error = ''; $record = null; $form = [];
try {
    $record = profile_record($id);
    if (!$record && $id !== '') $error = 'Applicant not found. Choose an applicant from the admin panel.';
    elseif ($record) {
        $form = array_merge(['worker_id'=>'','work_start'=>'','work_end'=>'','work_time'=>'','work_venue'=>'','work_notes'=>''], $record['profile']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['worker_id'] as $key) $form[$key] = trim((string) ($_POST[$key] ?? ''));
            $form['worker_id'] = strtoupper($form['worker_id']);
            if (!valid_form()) $error = 'Refresh the page and try again.';
            elseif ($record['program'] !== 'TUPAD') $error = 'TUPAD IDs and work schedules apply only to TUPAD applicants.';
            elseif ($form['worker_id'] !== '' && !preg_match('/^TUPAD-[A-Z0-9-]{1,58}$/', $form['worker_id'])) $error = 'Use a unique TUPAD ID starting with TUPAD-, followed by letters, numbers or hyphens (maximum 64 characters).';
            elseif (strlen($form['work_time']) > 100 || strlen($form['work_venue']) > 250 || strlen($form['work_notes']) > 1000) $error = 'Time, venue or instructions exceed their allowed length.';
            elseif (($form['work_start'] !== '' || $form['work_end'] !== '') && (!profile_date_valid($form['work_start']) || !profile_date_valid($form['work_end']) || $form['work_end'] < $form['work_start'] || $form['work_time'] === '' || $form['work_venue'] === '')) $error = 'Enter valid start/end dates, time and venue. End date must be on or after start date.';
            if (!$error) {
                // Prevent a worker ID from shadowing another applicant's permanent ID.
                $query = database()->prepare('SELECT id FROM applicants WHERE id = ? AND id <> ?');
                $query->execute([$form['worker_id'], $record['id']]);
                if ($query->fetchColumn()) $error = 'This ID is already used by another applicant.';
            }
            if (!$error) {
                // Check the alternate unique ID explicitly; use a transaction to avoid updating the wrong profile.
                $db = database(); $db->beginTransaction();
                try {
                    $lock = $db->prepare('SELECT id FROM applicants WHERE id = ? FOR UPDATE'); $lock->execute([$record['id']]);
                    $exists = $db->prepare('SELECT applicant_id FROM registrant_profiles WHERE applicant_id = ?'); $exists->execute([$record['id']]);
                    $values = [$form['worker_id'] ?: null, $form['work_start'] ?: null, $form['work_end'] ?: null, $form['work_time'], $form['work_venue'], $form['work_notes']];
                    if ($exists->fetchColumn()) {
                        $query = $db->prepare('UPDATE registrant_profiles SET worker_id=?, work_start=?, work_end=?, work_time=?, work_venue=?, work_notes=? WHERE applicant_id=?');
                        $query->execute(array_merge($values, [$record['id']]));
                    } else {
                        $query = $db->prepare('INSERT INTO registrant_profiles (worker_id,work_start,work_end,work_time,work_venue,work_notes,applicant_id) VALUES (?,?,?,?,?,?,?)');
                        $query->execute(array_merge($values, [$record['id']]));
                    }
                    $db->commit(); header('Location: admin-profile.php?id=' . urlencode($record['id']) . '&saved=1'); exit;
                } catch (Throwable $exception) { $db->rollBack(); throw $exception; }
            }
        }
    }
} catch (PDOException $exception) { error_log('Request failed: '.basename(__FILE__)); $error = ($exception->errorInfo[1] ?? 0) === 1062 ? 'That TUPAD ID belongs to another applicant. Choose a different ID.' : database_error(); }
require 'header.php';
?>
<section class="container section narrow"><h1>Manage profile and TUPAD ID</h1><?php require 'admin-nav.php'; ?>
<form method="get" class="card"><?php applicant_picker('id', $id); ?><button class="primary">Open profile</button></form>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && !$error): ?><p class="notice">Profile updated. The applicant will see the changes when they open or refresh their profile.</p><?php endif; ?>
<?php if ($record): ?><h2><?= e($record['name']) ?></h2><p>Permanent applicant ID: <?= e($record['id']) ?></p><a href="admin.php?id=<?= urlencode($record['id']) ?>#applicant">Manage application status</a>
<?php if ($record['program'] === 'TUPAD'): ?>
<form method="post" class="card"><input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="id" value="<?= e($record['id']) ?>">
<label class="field">Assigned TUPAD ID<input name="worker_id" value="<?= e($form['worker_id']) ?>" maxlength="64" pattern="TUPAD-[A-Za-z0-9-]+" placeholder="TUPAD-2026-0001"></label><p class="small">Leave blank until assigned. The original applicant ID always remains usable.</p>
<p>Manage worker lists by date on the <a href="admin-schedule.php">Work schedule page</a>.</p><button class="primary">Save TUPAD ID</button></form>
<?php else: ?><p>4Ps applicants can view their profile and status. TUPAD work assignments do not apply.</p><?php endif; endif; ?></section>
<?php require 'footer.php'; ?>


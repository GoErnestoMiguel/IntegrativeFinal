<?php
require 'config.php';
require_once 'admin-auth.php';
require_admin();
require 'profile-functions.php';
require_once 'workflow.php';
$page_title = 'TUPAD attendance';
$date = (string) ($_POST['date'] ?? $_GET['date'] ?? date('Y-m-d'));
$parsed = DateTime::createFromFormat('!Y-m-d', $date);
$error = ''; $rows = [];
$valid_date = $parsed && $parsed->format('Y-m-d') === $date && $date <= date('Y-m-d');
if (!$valid_date) { $error = 'Choose a valid work date, today or earlier.'; $date = date('Y-m-d'); }
$message = $_SESSION['attendance_message'] ?? '';
unset($_SESSION['attendance_message']);
try {
    workflow_require();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        $id = strtoupper(trim((string) ($_POST['worker_id'] ?? '')));
        if (!valid_form()) $error = 'Refresh this page and try again.';
        elseif (!preg_match('/^[A-Z0-9-]{1,64}$/', $id)) $error = 'Enter a valid worker application ID.';
        else {
            $worker = profile_record($id);
            if ($worker) $id = $worker['id'];
            if (!$worker) $error = 'Worker ID not found. Check the ID in Applicants.';
            elseif ($worker['program'] !== 'TUPAD') $error = 'Only TUPAD workers can be recorded here.';
            elseif (!in_array($worker['status'], ['Orientation scheduled','Community work scheduled','Confirmed member'], true)) $error = 'This worker must have Community work scheduled status in Applicants first.';
            else {
                $db=database(); $db->beginTransaction();
                try {
                    $q=$db->prepare('SELECT applicant_id FROM work_roster WHERE work_date=? AND applicant_id=? FOR UPDATE'); $q->execute([$date,$id]);
                    $scheduled=(bool)$q->fetchColumn(); $reason=trim((string)($_POST['override_reason'] ?? ''));
                    if(!$scheduled && (empty($_POST['override']) || strlen($reason)<10 || strlen($reason)>500)) throw new RuntimeException('This worker is not scheduled for this date. Select the exception option and enter a reason (10–500 characters) if authorized.');
                    $query = $db->prepare('INSERT IGNORE INTO tupad_attendance (applicant_id, work_date) VALUES (?, ?)');
                    $query->execute([$id, $date]);
                    if($query->rowCount()) {
                        workflow_audit($db,$id,'Attendance recorded',$date.($scheduled?' — on roster':' — exception: '.$reason));
                        $_SESSION['attendance_message'] = $worker['name'] . ' was marked present for ' . $date . '.';
                    } else $_SESSION['attendance_message']='This worker is already recorded for this date.';
                    $db->commit();
                } catch(Throwable $ex) { if($db->inTransaction()) $db->rollBack(); throw $ex; }
                header('Location: attendance.php?date=' . urlencode($date)); exit;
            }
        }
    }
    $query = database()->prepare('SELECT a.applicant_id, a.work_date, a.recorded_at, p.name FROM tupad_attendance a JOIN applicants p ON p.id = a.applicant_id WHERE a.work_date = ? ORDER BY a.recorded_at, a.id');
    $query->execute([$date]); $rows = $query->fetchAll();
} catch (Throwable $exception) { error_log('Request failed: '.basename(__FILE__)); $error = $exception instanceof PDOException?database_error():$exception->getMessage(); }
require 'header.php';
?>
<section class="container section">
<p class="eyebrow">ADMIN PANEL</p><h1>TUPAD daily attendance</h1>
<?php require 'admin-nav.php'; ?>
<p>Choose the cleaning date, then enter each worker’s application ID to mark them present. Workers must be at a scheduling stage and assigned to the selected date.</p>
<p class="small">The system checks the selected day’s roster. An unscheduled worker requires a staff exception and reason. Recorded time is the entry time, not a verified arrival time.</p>
<?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if ($message): ?><p class="notice" role="status"><?= e($message) ?></p><?php endif; ?>
<form method="get" class="toolbar"><label class="field">Work date<input type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>" required></label><button class="secondary">View attendance</button></form>
<form method="post" class="card">
<input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>"><input type="hidden" name="date" value="<?= e($date) ?>">
<h2>Mark present · <?= e($date) ?></h2>
<label class="field">TUPAD worker application ID<input name="worker_id" maxlength="64" required autocomplete="off" placeholder="TUP-…" autofocus></label>
<label class="checkbox"><input type="checkbox" name="override" value="1"><span>Authorize an exception for an unscheduled worker</span></label><label class="field">Exception reason (only for unscheduled workers)<textarea name="override_reason" maxlength="500"></textarea></label><button class="primary">Mark present</button>
</form>
<?php try { $q=database()->prepare('SELECT a.id,a.name,t.id AS attendance_id FROM work_roster r JOIN applicants a ON a.id=r.applicant_id LEFT JOIN tupad_attendance t ON t.applicant_id=r.applicant_id AND t.work_date=r.work_date WHERE r.work_date=? ORDER BY a.name'); $q->execute([$date]); $scheduledRows=$q->fetchAll(); } catch(PDOException $e) { $scheduledRows=[]; } ?>
<h2>Scheduled workers</h2><ul><?php foreach($scheduledRows as $s): ?><li><?= e($s['name']) ?> · <?= e($s['id']) ?> · <?= $s['attendance_id']?'Present':'Not yet recorded' ?></li><?php endforeach; ?></ul>
<h2>Attendance sheet · <?= e($date) ?></h2><p><?= count($rows) ?> worker(s) present</p>
<button class="secondary" type="button" data-print>Print attendance sheet</button>
<div class="table-scroll"><table class="applicant-table"><thead><tr><th>No.</th><th>Worker ID</th><th>Worker name</th><th>Status</th><th>Recorded at</th></tr></thead><tbody>
<?php foreach ($rows as $index => $row): ?><tr><td><?= $index + 1 ?></td><td><?= e($row['applicant_id']) ?></td><td><?= e($row['name']) ?></td><td>Present</td><td><?= e($row['recorded_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5">No attendance recorded for this date.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require 'footer.php'; ?>

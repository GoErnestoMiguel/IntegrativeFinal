<?php
require 'config.php';
require_once 'applicant-picker.php';
require_once 'documents.php';
require_once 'admin-auth.php';
require_admin();
require_once 'application-fields.php';
require_once 'workflow.php';
$page_title = 'View applicants';
$feedback = $error = '';
$search = substr(trim((string) ($_GET['search'] ?? '')), 0, 120);
$selected_id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
$number = max(1, (int) ($_GET['p'] ?? 1));
$rows = []; $application = null; $total = 0;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        workflow_require();
        $application = find_applicant($selected_id);
        $status = (string) ($_POST['status'] ?? '');
        $message = trim((string) ($_POST['message'] ?? ''));
        if (!valid_form()) {
            $feedback = 'Refresh the page and try again.';
        } elseif (!$application) {
            $feedback = 'Applicant not found.';
        } elseif (!in_array($status, allowed_statuses($application['program'],$application['status']), true) || strlen($message) < 10 || strlen($message) > 1000) {
            $feedback = 'Choose a valid status and enter instructions between 10 and 1,000 characters.';
        } else {
            $db = database();
            $db->beginTransaction();
            try {
                $query=$db->prepare('SELECT status FROM applicants WHERE id=? FOR UPDATE'); $query->execute([$selected_id]);
                $current=$query->fetchColumn();
                if($current!==($_POST['current_status'] ?? '')) throw new RuntimeException('This application was updated in another window. Reload before saving.');
                if(!in_array($status,allowed_statuses($application['program'],$current),true)) throw new RuntimeException('The status changed in another window. Reload and review the current status.');
                if(in_array($status,['Orientation scheduled','Referred to DSWD','Community work scheduled','Confirmed member'],true)) {
                    $q=$db->prepare("SELECT decision FROM residency_reviews WHERE applicant_id=?"); $q->execute([$selected_id]);
                    if($q->fetchColumn()!=='Verified') throw new RuntimeException('Complete the residency verification before this stage.');
                    foreach(duplicate_candidates($application) as $duplicate) if(!$duplicate['decision'] || $duplicate['decision']==='Duplicate application') throw new RuntimeException('Resolve duplicate applications before advancing.');
                }
                $query = $db->prepare('UPDATE applicants SET status = ? WHERE id = ?');
                $query->execute([$status, $selected_id]);
                $query = $db->prepare('INSERT INTO application_history (applicant_id, status, message) VALUES (?, ?, ?)');
                $query->execute([$selected_id, $status, $message]);
                $member_table = $application['program']==='TUPAD' ? 'tupad_members' : 'fourps_members';
                if ($status==='Confirmed member') { $query=$db->prepare("INSERT IGNORE INTO $member_table (applicant_id) VALUES (?)"); $query->execute([$selected_id]); }
                workflow_audit($db,$selected_id,'Status updated',$current.' → '.$status.': '.$message);
                $notification=queue_notification($db,$selected_id,'Application update: '.$status,$message);
                $db->commit();
                try { deliver_notification($notification); } catch(Throwable $e) { error_log('Status notification remains pending.'); }
                header('Location: admin.php?id=' . urlencode($selected_id) . '&saved=1');
                exit;
            } catch (Throwable $exception) {
                if($db->inTransaction()) $db->rollBack();
                throw $exception;
            }
        }
    }
    if (isset($_GET['saved'])) $feedback = 'Status saved. The applicant profile is updated. Check Notifications for email delivery results.';
    $where = ' WHERE name LIKE ? OR id LIKE ? OR program LIKE ? OR status LIKE ?';
    $terms = array_fill(0, 4, '%' . $search . '%');
    $query = database()->prepare('SELECT COUNT(*) FROM applicants' . $where);
    $query->execute($terms);
    $total = (int) $query->fetchColumn();
    $number = min($number, max(1, (int) ceil($total / 20)));
    $query = database()->prepare('SELECT id, name, program, status, created_at FROM applicants' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 20 OFFSET ' . (($number - 1) * 20));
    $query->execute($terms);
    $rows = $query->fetchAll();
    if ($selected_id) $application = find_applicant($selected_id);
} catch (Throwable $exception) {
    error_log('Request failed: '.basename(__FILE__));
    $error = $exception instanceof PDOException ? database_error() : $exception->getMessage();
}
require 'header.php';
?>
<section class="container section">
    <p class="eyebrow">APPLICANT MANAGEMENT</p><h1>View applicants</h1><?php require 'admin-nav.php'; ?>
    
    <form method="get" class="card"><?php applicant_picker('id', $selected_id); ?><button class="primary">Open applicant details</button></form><a href="register.php" class="button primary">Add an applicant →</a>
    <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($feedback): ?><p class="notice" role="status"><?= e($feedback) ?></p><?php endif; ?>
    <form action="admin.php" method="get" class="toolbar">
        <label class="field">Search applicants<input name="search" value="<?= e($search) ?>" placeholder="Name, ID, program, or status" maxlength="120"></label>
        <button class="secondary">Search</button><a href="admin.php">Clear search</a>
    </form>
    <p><?= $total ?> matching applicant(s) · Page <?= $number ?></p>
    <div class="table-scroll"><table class="applicant-table">
        <thead><tr><th>Application ID</th><th>Name</th><th>Program</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($rows as $row): ?>
            <tr><td><?= e($row['id']) ?></td><td><?= e($row['name']) ?></td><td><?= e($row['program']) ?></td><td><?= e($row['status']) ?></td><td><?= e($row['created_at']) ?></td><td><a href="admin.php?id=<?= urlencode($row['id']) ?>#applicant">View details</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6">No matching applicants. Submit a registration to add one.</td></tr><?php endif; ?></tbody>
    </table></div>
    <div class="actions">
        <?php if ($number > 1): ?><a class="button secondary" href="admin.php?<?= e(http_build_query(['search' => $search, 'p' => $number - 1])) ?>">Previous</a><?php endif; ?>
        <?php if ($number * 20 < $total): ?><a class="button secondary" href="admin.php?<?= e(http_build_query(['search' => $search, 'p' => $number + 1])) ?>">Next</a><?php endif; ?>
    </div>
    <?php if ($application): ?>
        <article id="applicant" class="card section">
            <span class="badge"><?= e($application['program']) ?></span><h2><?= e($application['name']) ?></h2>
            <dl class="review">
                <?php foreach (['id'=>'Application ID','birth_date'=>'Birth date','address'=>'Address','method'=>'Update method','contact'=>'Contact','status'=>'Current status'] as $key=>$label): ?><div><dt><?= e($label) ?></dt><dd><?= e($application[$key]) ?></dd></div><?php endforeach; ?>
            </dl>
            <details><summary>Earlier initial-check answers (if recorded)</summary><p class="small">New applications do not include the separate self-check.</p><dl class="review">
                <?php $labels = ['age'=>'Age group','residency'=>'Local resident','work'=>'Work situation','prev_aid'=>'Recent aid','household'=>'Child / pregnant member','income'=>'Financial difficulty','already_4ps'=>'Current cash transfer']; ?>
                <?php $values = ['yes'=>'Yes','no'=>'No','under18'=>'Under 18','18up'=>'18 or above','none'=>'Unemployed','informal'=>'Irregular / seasonal work','regular_gov'=>'Regular government work','regular_private'=>'Regular private work','unsure'=>'Not sure','other'=>'Another cash transfer']; ?>
                <?php foreach (json_decode($application['qualification_answers'], true) ?: [] as $key => $value): ?><div><dt><?= e($labels[$key] ?? $key) ?></dt><dd><?= e($values[$value] ?? $value) ?></dd></div><?php endforeach; ?>
            </dl></details>
            <?php if (!empty($application['details']['answers'])): ?><h3>Application answers</h3><p>Version: <?= e($application['details']['mode']) ?></p><dl class="review"><?php $definitions = intake_fields($application['program'], $application['details']['mode']); foreach ($application['details']['answers'] as $key=>$value): ?><div><dt><?= e($definitions[$key][0] ?? $key) ?></dt><dd><?= e($value === '' ? 'Not provided' : $value) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
            <p><a class="button primary" href="admin-review.php?id=<?= urlencode($application['id']) ?>">Review residency, duplicates and documents</a></p><p><a class="button primary" href="admin-profile.php?id=<?= urlencode($application['id']) ?>">Manage profile, TUPAD ID and work schedule</a></p><h3>Submitted documents</h3><p class="small">Review these copies against barangay records. Uploading a file does not verify residency.</p><?php document_list($application['id']); ?><h3>Status history</h3><div class="timeline">
                <?php foreach ($application['history'] as $item): ?><article><strong><?= e($item['status']) ?></strong><p class="small"><?= e($item['created_at']) ?></p><p><?= e($item['message']) ?></p></article><?php endforeach; ?>
            </div>
            <form action="admin.php" method="post">
                <input type="hidden" name="form_token" value="<?= e($_SESSION['form_token']) ?>">
                <input type="hidden" name="id" value="<?= e($application['id']) ?>">
                <input type="hidden" name="current_status" value="<?= e($application['status']) ?>"><p class="small">Only the next valid stages are offered. To correct an earlier decision, return to Under review. Include the date, time and venue for an interview or orientation.</p><label class="field">New status<select name="status"><?php foreach (allowed_statuses($application['program'],$application['status']) as $status): ?><option <?= $status === $application['status'] ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
                <label class="field">Instructions<textarea name="message" required minlength="10" maxlength="1000" rows="3" placeholder="Include interview date, time, venue, and documents."></textarea></label>
                <button class="primary">Save status</button>
            </form>
        </article>
    <?php endif; ?>
</section>
<?php require 'footer.php'; ?>



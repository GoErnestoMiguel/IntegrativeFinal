<?php
// Call only from authenticated staff pages.
function applicant_picker($field, $selected = '', $workers_only = false) {
    $rows=[]; $failed=false;
    try {
        $sql='SELECT a.id,a.name,a.program,a.status,p.worker_id FROM applicants a LEFT JOIN registrant_profiles p ON p.applicant_id=a.id';
        if ($workers_only) $sql.=" WHERE a.program='TUPAD' AND a.status IN ('Orientation scheduled','Community work scheduled','Confirmed member')";
        $rows=database()->query($sql.' ORDER BY a.name,a.id')->fetchAll();
    } catch (PDOException $error) { $failed=true; error_log('Request failed: '.basename(__FILE__)); }
    ?>
    <div class="applicant-picker" data-applicant-picker>
      <label class="field picker-search" hidden>Search by name or ID<input type="search" data-picker-search placeholder="Type a name, applicant ID or TUPAD ID" autocomplete="off"></label>
      <label class="field"><?= $workers_only ? 'Choose a TUPAD worker' : 'Choose an applicant' ?><select name="<?= e($field) ?>" required data-picker-select><option value="">Select a person</option>
      <?php foreach ($rows as $row): ?><option value="<?= e($row['id']) ?>" <?= $selected===$row['id']?'selected':'' ?>><?= e($row['name'].' — '.$row['id'].($row['worker_id']?' / '.$row['worker_id']:'').' · '.$row['program']) ?></option><?php endforeach; ?>
      </select></label>
      <p class="small" data-picker-count role="status"><?= $failed?'Unable to load applicants. Check the database connection.':count($rows).' people available.' ?></p>
      <?php if ($workers_only): ?><p class="small">Lists TUPAD workers with orientation scheduled, community work scheduled, or confirmed membership. Change the applicant’s status first if they are not listed.</p><?php endif; ?>
    </div>
    <?php
}

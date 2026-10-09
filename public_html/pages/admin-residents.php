<?php
require 'config.php';require 'admin-auth.php';require_admin();require 'resident-import.php';
$page_title='Resident registry';$error='';$message=$_SESSION['resident_message']??'';unset($_SESSION['resident_message']);
if($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  if(!valid_form())throw new RuntimeException('Refresh the page and try again.');
  $action=$_POST['action']??'';
  if($action==='setup') {throw new RuntimeException('Ask the server administrator to run the private database migration tool.');}
  elseif($action==='preview') {
   unset($_SESSION['resident_import']);$file=$_FILES['csv']??null;
   if(!$file||!is_scalar($file['error'])||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']))throw new RuntimeException('Choose a CSV file to upload.');
   if(filesize($file['tmp_name'])>2*1024*1024||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='csv')throw new RuntimeException('Use a CSV file up to 2 MB.');
   $data=parse_resident_csv($file['tmp_name']);
   $existing=database()->query('SELECT resident_id FROM resident_registry')->fetchAll(PDO::FETCH_COLUMN);$existing=array_fill_keys(array_map('strtoupper',$existing),true);
   $skip=0;foreach($data as $r)if(isset($existing[$r['resident_id']]))$skip++;
   $_SESSION['resident_import']=['rows'=>$data,'skip'=>$skip,'expires'=>time()+1800,'token'=>bin2hex(random_bytes(16))];
  } elseif($action==='cancel')unset($_SESSION['resident_import']);
  elseif($action==='confirm') {
   $pending=$_SESSION['resident_import']??null;
   if(!$pending||$pending['expires']<time()||!hash_equals($pending['token'],(string)($_POST['import_token']??'')))throw new RuntimeException('The preview expired. Upload the CSV again.');
   $db=database();$db->beginTransaction();$added=0;$skipped=0;
   try {
    $q=$db->prepare('INSERT INTO resident_registry (resident_id,household_id,first_name,middle_name,last_name,birth_date,barangay,city,province,details,is_sample,imported_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach($pending['rows'] as $r) {
     try{$q->execute([$r['resident_id'],$r['household_id'],$r['first_name'],$r['middle_name']??'',$r['last_name'],$r['birth_date'],'San Sebastian','Lipa City','Batangas',json_encode($r,JSON_THROW_ON_ERROR),(int)$r['is_sample'],$_SESSION['admin_user']]);$added++;}
     catch(PDOException $ex){if(($ex->errorInfo[1]??0)!==1062)throw $ex;$skipped++;}
    }
    $db->commit();
   }catch(Throwable $ex){$db->rollBack();throw $ex;}
   unset($_SESSION['resident_import']);$_SESSION['resident_message']="$added residents imported; $skipped existing IDs skipped. No applications or login accounts were created.";
   header('Location: admin-residents.php');exit;
  }
 }catch(Throwable $ex){error_log('Request failed: '.basename(__FILE__));$error=$ex instanceof PDOException?'Could not access the registry. Start MySQL and initialize the registry below.':$ex->getMessage();}
}
$rows=[];$total=0;$ready=false;$selected=null;$search=trim((string)($_GET['search']??''));$page=max(1,(int)($_GET['p']??1));
try {
 $db=database();$where=' WHERE resident_id LIKE ? OR household_id LIKE ? OR CONCAT_WS(" ",first_name,middle_name,last_name) LIKE ?';$terms=array_fill(0,3,'%'.$search.'%');
 $q=$db->prepare('SELECT COUNT(*) FROM resident_registry'.$where);$q->execute($terms);$total=(int)$q->fetchColumn();$ready=true;
 $page=min($page,max(1,(int)ceil($total/25)));$offset=($page-1)*25;
 $q=$db->prepare('SELECT * FROM resident_registry'.$where." ORDER BY last_name,first_name,resident_id LIMIT 25 OFFSET $offset");$q->execute($terms);$rows=$q->fetchAll();
 if(isset($_GET['id'])){$q=$db->prepare('SELECT * FROM resident_registry WHERE resident_id=?');$q->execute([(string)$_GET['id']]);$selected=$q->fetch();}
}catch(PDOException $ex){$error=$error?:'Registry unavailable. Start MySQL, then initialize the registry below or import resident-migration.sql.';}
$pending=$_SESSION['resident_import']??null;if($pending&&$pending['expires']<time()){unset($_SESSION['resident_import']);$pending=null;}
require 'header.php';?>
<section class="container section"><h1>Resident registry</h1><?php require 'admin-nav.php';?>
<p>Barangay San Sebastian, Lipa City, Batangas. Imported records support staff checks; importing does not verify identity, approve program eligibility, or create an application.</p>
<?php if($error):?><p class="error" role="alert"><?=e($error)?></p><?php endif;?>
<?php if($message):?><p class="notice" role="status"><?=e($message)?></p><?php endif;?>
<?php if(!$ready):?><form method="post"><input type="hidden" name="form_token" value="<?=e($_SESSION['form_token'])?>"><button class="primary" name="action" value="setup">Initialize resident registry</button></form><?php else:?>
<details class="card" <?= $pending?'open':'' ?>><summary>Import resident records</summary>
<p>Upload CSV (UTF-8), up to 2 MB and 1,000 rows. In Excel, use Save As → CSV UTF-8. Preview the records before confirming. Existing resident IDs are skipped, never overwritten.</p>
<p>Required column names: <code>resident_id, household_id, first_name, last_name, birth_date, barangay, city, province</code>. Dates: YYYY-MM-DD. Optional columns from the supplied sample CSV are preserved. Sample rows remain clearly labeled.</p>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="form_token" value="<?=e($_SESSION['form_token'])?>"><label class="field">Resident CSV<input type="file" name="csv" accept=".csv,text/csv" required></label><button class="primary" name="action" value="preview">Preview import</button></form>
<?php if($pending): $preview_columns=array_keys($pending['rows'][0]); ?>
<h2 id="import-preview-heading">Review import</h2>
<p><?=count($pending['rows'])?> rows: <?=count($pending['rows'])-$pending['skip']?> new IDs and <?=$pending['skip']?> existing IDs to skip. All rows are shown below and have passed format validation.</p>
<p id="import-preview-help" class="small">Scroll down to review every resident and sideways to see all fields. Column headings stay visible while scrolling. Format validation does not verify the accuracy of these records.</p>
<div class="actions"><button type="button" class="secondary" data-preview-scroll="first">First row</button><button type="button" class="secondary" data-preview-scroll="last">Last row</button></div>
<script src="../javascript/import-preview.js?v=20261009" defer></script>
<div class="table-scroll import-preview-scroll" role="region" aria-labelledby="import-preview-heading" aria-describedby="import-preview-help" tabindex="0">
<table class="applicant-table import-preview-table"><thead><tr><th scope="col">Row</th><?php foreach($preview_columns as $column): ?><th scope="col"><?=e(ucwords(str_replace('_',' ',$column)))?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach($pending['rows'] as $index=>$r): ?><tr><td><?=$index+1?></td><?php foreach($preview_columns as $column): ?><td><?=e($column==='is_sample'?($r[$column]==='1'?'Sample':'Imported'):($r[$column]??''))?></td><?php endforeach; ?></tr><?php endforeach; ?>
</tbody></table></div><form method="post" class="actions"><input type="hidden" name="form_token" value="<?=e($_SESSION['form_token'])?>"><input type="hidden" name="import_token" value="<?=e($pending['token'])?>"><button class="primary" name="action" value="confirm">Confirm import</button><button class="secondary" name="action" value="cancel">Cancel</button></form><?php endif;?></details>
<form method="get" class="toolbar resident-search"><label class="field">Search residents<input name="search" value="<?=e($search)?>" placeholder="Name, resident ID or household ID"></label><button class="secondary">Search</button><a href="admin-residents.php">Clear</a></form><p><?=$total?> residents · Page <?=$page?></p>
<div class="table-scroll"><table class="applicant-table"><thead><tr><th>Resident ID</th><th>Name</th><th>Birth date</th><th>Household</th><th>Data type</th><th>Details</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['resident_id'])?></td><td><?=e($r['first_name'].' '.$r['middle_name'].' '.$r['last_name'])?></td><td><?=e($r['birth_date'])?></td><td><?=e($r['household_id'])?></td><td><?=$r['is_sample']?'Sample':'Imported'?></td><td><a href="?id=<?=urlencode($r['resident_id'])?>#resident">View</a></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6">No residents found. Import a CSV to get started.</td></tr><?php endif;?></tbody></table></div>
<div class="actions"><?php if($page>1):?><a href="?<?=e(http_build_query(['search'=>$search,'p'=>$page-1]))?>">Previous</a><?php endif;?><?php if($page*25<$total):?><a href="?<?=e(http_build_query(['search'=>$search,'p'=>$page+1]))?>">Next</a><?php endif;?></div>
<?php if($selected):?><article class="card" id="resident"><h2><?=e($selected['first_name'].' '.$selected['last_name'])?></h2><p><?=$selected['is_sample']?'Fictional sample resident.':'Imported resident record.'?> Identity and residency have not been verified by this import.</p><dl class="review"><?php foreach(json_decode($selected['details'],true) as $key=>$value):?><div><dt><?=e(ucwords(str_replace('_',' ',$key)))?></dt><dd><?=e($value)?></dd></div><?php endforeach;?></dl><p class="small">Imported by <?=e($selected['imported_by'])?> on <?=e($selected['imported_at'])?></p></article><?php endif;endif;?></section><?php require 'footer.php';?>



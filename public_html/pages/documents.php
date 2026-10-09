<?php
require_once __DIR__.'/image-documents.php';
function document_categories($program = null) {
 $tupad = [
  'tupad_identification'=>'Accepted valid identification',
  'tupad_residence'=>'Proof of residence, if requested',
  'tupad_profiling'=>'Beneficiary information or profiling form',
  'tupad_employment'=>'Proof or declaration of employment circumstances, as requested'
 ];
 $fourps = [
  'fourps_philsys'=>'PhilSys ID or proof of registration; confirm current acceptance with DSWD',
  'fourps_birth'=>'Birth certificates of household members',
  'fourps_school'=>'School records, when applicable',
  'fourps_health'=>'Health or pregnancy records, when applicable',
  'fourps_civil'=>'Marriage, death, or disability records, when applicable'
 ];
 if ($program === 'TUPAD') return $tupad;
 if ($program === '4Ps') return $fourps;
 if ($program !== null) throw new RuntimeException('Choose a valid program for document submission.');
 // Preserve labels for documents submitted before program-specific categories existed.
 return $tupad + $fourps + ['identity'=>'Identity document','residency'=>'Proof of residence','supporting'=>'Other supporting document'];
}
function document_uploads($program) {
 $files=[];$bytes=0;$categories=document_categories($program);
 foreach($_FILES as $key=>$upload) {
  if(!str_starts_with($key,'document_')) continue;
  $category=substr($key,9);
  if(!isset($categories[$category])) throw new RuntimeException('A document category does not match your program.');
  if(!is_array($upload))throw new RuntimeException('Invalid upload data.');
  foreach(['error','name','tmp_name','size','type'] as $part) if(!isset($upload[$part]) || !is_array($upload[$part])) throw new RuntimeException('Invalid photo upload. Select your photos again.');
  $keys=array_keys($upload['error']);
  foreach(['name','tmp_name','size','type'] as $part) if(array_keys($upload[$part])!==$keys)throw new RuntimeException('Invalid upload structure.');
  foreach($keys as $index) {
   $error=$upload['error'][$index];$tmp=$upload['tmp_name'][$index];$name=$upload['name'][$index];
   if(!is_int($error) || !is_string($tmp) || !is_string($name))throw new RuntimeException('Invalid photo upload.');
   if($error===UPLOAD_ERR_NO_FILE)continue;
   if($error!==UPLOAD_ERR_OK)throw new RuntimeException('A photo could not be uploaded. Use files up to 5 MB and reselect them.');
   if(!is_uploaded_file($tmp))throw new RuntimeException('Invalid uploaded file.');
   if(count($files)>=DOCUMENT_BATCH_COUNT)throw new RuntimeException('Select at most 8 photos per submission. Add more later in My Profile.');
   $info=inspect_document_image($tmp,$name);$bytes+=$info['size'];
   if($bytes>DOCUMENT_BATCH_BYTES)throw new RuntimeException('Keep all selected photos together within 20 MB per submission.');
   $files[]=array_merge($info,['category'=>$category,'tmp'=>$tmp]);
  }
 }
 if(isset($_POST['document_count']) && (!is_string($_POST['document_count']) || !ctype_digit($_POST['document_count']) || (int)$_POST['document_count']!==count($files)))throw new RuntimeException('Some selected photos did not arrive. Select fewer photos and try again.');
 return $files;
}
function document_directory() {
 // Outside the project and htdocs by default. Never put private files in public uploads.
 $path=hosting_settings()['document_dir'];
 if (!is_dir($path) && !mkdir($path,0700,true)) throw new RuntimeException('Document storage is unavailable. Contact the help desk.');
 $real=realpath($path); $root=realpath($_SERVER['DOCUMENT_ROOT']??dirname(__DIR__));
 if (!$real || ($root && (strtolower($real)===strtolower($root) || str_starts_with(strtolower(str_replace('\\','/',$real)),rtrim(strtolower(str_replace('\\','/',$root)),'/').'/')))) throw new RuntimeException('Document storage must be outside the public website folder.');
 return $real;
}
function store_documents($db,$id,$files,&$created) {
 if (!$files) return;
 $q=$db->prepare('SELECT COUNT(*) FROM applicant_documents WHERE applicant_id=?');$q->execute([$id]);
 if ((int)$q->fetchColumn()+count($files)>DOCUMENT_TOTAL_COUNT) throw new RuntimeException('The limit is 40 photos per application. Contact staff for assistance.');
 $dir=document_directory();
 foreach ($files as $file) {
  $name=bin2hex(random_bytes(32));$path=$dir.DIRECTORY_SEPARATOR.$name;
  $size=recreate_document_image($file['tmp'],$path,$file);
  $created[]=$path;
  $q=$db->prepare('INSERT INTO applicant_documents (applicant_id,category,original_name,storage_name,mime_type,file_size) VALUES (?,?,?,?,?,?)');
  $q->execute([$id,$file['category'],$file['original_name'],$name,'image/jpeg',$size]);
 }
}
function document_fields($program) { ?>
 <div class="notice">Upload clear JPG or PNG photos for staff review. Up to 5 MB per photo, 8 photos and 20 MB per submission. PDFs, HEIC, GIF and other file types are not accepted. Uploading does not confirm residency or eligibility.</div>
 <h3><?= e($program) ?> document photos</h3>
 <p class="small">Include all four corners and make sure every word is readable. Photograph both sides if needed. For multiple pages or household members, select several photos in the appropriate category. Leave requirements that do not apply blank.</p>
 <?php foreach(document_categories($program) as $key=>$label): ?><label class="field"><?= e($label) ?><input type="file" name="document_<?= e($key) ?>[]" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple data-document-upload></label><?php endforeach; ?>
 <p class="small" data-document-summary role="status">You can add more photos later through My Profile, up to 40 per application. Each photo may be up to 8 megapixels and 6,000 pixels on either side; the server may ask for a smaller image if memory is limited.</p>
 <p class="small">Only you and authorized staff can access the stored copies. Photos are recreated as JPG files before saving; transparency becomes white. Check text clarity and orientation in the saved copy. If the form shows an error, select your photos again. Ask the help desk if you only have a PDF.</p>
 <?php }
function document_list($id) {
 try { $q=database()->prepare('SELECT d.id,d.category,d.original_name,d.file_size,d.uploaded_at,r.decision,r.instructions FROM applicant_documents d LEFT JOIN document_reviews r ON r.document_id=d.id WHERE d.applicant_id=? ORDER BY d.id DESC');$q->execute([$id]);$rows=$q->fetchAll(); }
 catch(PDOException $ex) { echo '<p class="error">Document access is temporarily unavailable. Please contact staff.</p>';return; }
 if (!$rows) {echo '<p>No documents submitted yet.</p>';return;}
 echo '<ul class="document-list">';
 foreach($rows as $row) echo '<li><a href="document-download.php?id='.(int)$row['id'].'">'.e(document_categories()[$row['category']]??$row['category']).' — '.e($row['original_name']).'</a><p class="small">'.e($row['uploaded_at']).' · '.round($row['file_size']/1024).' KB · '.e($row['decision'] ?? 'Under review').'</p><p>'.nl2br(e($row['instructions'] ?? '')).'</p></li>';
 echo '</ul>';
}

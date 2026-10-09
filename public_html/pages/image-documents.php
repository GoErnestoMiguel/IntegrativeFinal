<?php
// Only decoded pixels are written to permanent storage, never the original upload bytes.
const DOCUMENT_MAX_BYTES=5*1024*1024;
const DOCUMENT_BATCH_BYTES=20*1024*1024;
const DOCUMENT_BATCH_COUNT=8;
const DOCUMENT_TOTAL_COUNT=40;
function image_support_available() {
 return extension_loaded('gd') && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng') && function_exists('imagejpeg');
}
function inspect_document_image($path,$originalName) {
 if(!image_support_available()) throw new RuntimeException('Image processing is unavailable. Please contact staff.');
 $size=filesize($path);
 if(!$size || $size>DOCUMENT_MAX_BYTES) throw new RuntimeException('Each photo must be no larger than 5 MB.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
 $extension=strtolower(pathinfo($originalName,PATHINFO_EXTENSION));
 if(!in_array($mime,['image/jpeg','image/png'],true) || !in_array($extension,$mime==='image/jpeg'?['jpg','jpeg']:['png'],true)) throw new RuntimeException('Upload JPG or PNG photos only. PDFs, HEIC and other file types are not accepted.');
 $info=@getimagesize($path);
 if(!$info || ($info['mime'] ?? '')!==$mime) throw new RuntimeException('This photo cannot be read. Choose a different copy.');
 [$width,$height]=$info;
 if($width<1 || $height<1 || $width>6000 || $height>6000 || $width*$height>8000000) throw new RuntimeException('Use a smaller photo: at most 8 megapixels and 6,000 pixels on either side.');
 $limit=trim(ini_get('memory_limit')); $bytes=(int)$limit;
 if($bytes>0) {
  $suffix=strtolower(substr($limit,-1));$bytes*=match($suffix){'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1};
  if(memory_get_usage(true)+$width*$height*20+16*1024*1024>$bytes) throw new RuntimeException('This photo is too large to process. Reduce its dimensions and try again.');
 }
 $base=pathinfo(basename(str_replace('\\','/',$originalName)),PATHINFO_FILENAME);
 $base=trim(preg_replace('/[^a-zA-Z0-9 _()-]/','_',$base));
 return ['mime'=>$mime,'size'=>$size,'width'=>$width,'height'=>$height,'original_name'=>substr($base?:'document',0,180).'.jpg'];
}
function recreate_document_image($source,$destination,$info) {
 $decoded=$canvas=null;$stream=null;$created=false;
 try {
  $decoded=$info['mime']==='image/jpeg'?@imagecreatefromjpeg($source):@imagecreatefrompng($source);
  if(!$decoded) throw new RuntimeException('This image is damaged or unsupported. Choose another photo.');
  // Respect phone-camera orientation where EXIF support is available; no EXIF is saved.
  if($info['mime']==='image/jpeg' && function_exists('exif_read_data')) {
   $exif=@exif_read_data($source,'IFD0',true,false);$orientation=(int)($exif['IFD0']['Orientation'] ?? 1);
   if(in_array($orientation,[2,4,5,7],true)) imageflip($decoded,in_array($orientation,[4,5],true)?IMG_FLIP_VERTICAL:IMG_FLIP_HORIZONTAL);
   $angle=match($orientation){3=>180,5,6=>-90,7,8=>90,default=>0};
   if($angle) { $rotated=imagerotate($decoded,$angle,0);if(!$rotated)throw new RuntimeException('Could not orient this photo.');imagedestroy($decoded);$decoded=$rotated; }
  }
  $canvas=imagecreatetruecolor(imagesx($decoded),imagesy($decoded));
  if(!$canvas) throw new RuntimeException('Could not process this photo.');
  imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));
  imagecopy($canvas,$decoded,0,0,0,0,imagesx($decoded),imagesy($decoded));
  $stream=@fopen($destination,'xb');if(!$stream)throw new RuntimeException('Could not store this photo.');
  $created=true;
  @chmod($destination,0600);
  if(!imagejpeg($canvas,$stream,90))throw new RuntimeException('Could not save the processed photo.');
  fclose($stream);$stream=null;clearstatcache(true,$destination);$size=filesize($destination);
  if(!$size || $size>DOCUMENT_MAX_BYTES) throw new RuntimeException('The processed photo exceeds 5 MB. Reduce its dimensions and try again.');
  return $size;
 } catch(Throwable $e) { if(is_resource($stream)){fclose($stream);$stream=null;} if($created && is_file($destination))@unlink($destination); throw $e; }
 finally { if($decoded)imagedestroy($decoded);if($canvas)imagedestroy($canvas); }
}

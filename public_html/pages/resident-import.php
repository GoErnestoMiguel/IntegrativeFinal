<?php
function parse_resident_csv($path) {
 $handle=fopen($path,'rb');if(!$handle)throw new RuntimeException('Cannot read the CSV.');
 try {
  if(fread($handle,3)!=="\xEF\xBB\xBF")rewind($handle);
  $header=fgetcsv($handle,0,',','"','');if(!$header)throw new RuntimeException('The CSV is empty.');
  $header[0]=preg_replace('/^\xEF\xBB\xBF/','',$header[0]);$header=array_map('trim',$header);
  $required=['resident_id','household_id','first_name','last_name','birth_date','barangay','city','province'];
  if(count($header)!==count(array_unique($header)))throw new RuntimeException('Duplicate column names.');
  foreach($required as $key)if(!in_array($key,$header,true))throw new RuntimeException('Missing column: '.$key);
  $rows=[];$seen=[];$line=1;
  while(($values=fgetcsv($handle,0,',','"',''))!==false) {
   $line++;if($values===[null])continue;
   if(count($rows)>=1000)throw new RuntimeException('Import up to 1,000 residents at a time.');
   if(count($values)!==count($header))throw new RuntimeException("Row $line: column count does not match the header.");
   $row=array_combine($header,array_map('trim',$values));
   foreach($row as $key=>$value)if(strlen($value)>500 || !preg_match('//u',$value))throw new RuntimeException("Row $line: use UTF-8 text with at most 500 bytes per field.");
   foreach($required as $key)if($row[$key]==='')throw new RuntimeException("Row $line: $key is required.");
   foreach(['resident_id','household_id'] as $key)if(!preg_match('/^[A-Za-z0-9_-]{1,64}$/',$row[$key]))throw new RuntimeException("Row $line: $key must contain up to 64 letters, numbers, hyphens or underscores.");
   $row['resident_id']=strtoupper($row['resident_id']);$row['household_id']=strtoupper($row['household_id']);
   if(isset($seen[$row['resident_id']]))throw new RuntimeException("Row $line: duplicate resident ID in this file.");$seen[$row['resident_id']]=true;
   foreach(['first_name','middle_name','last_name','barangay','city','province'] as $key)if(strlen($row[$key]??'')>100)throw new RuntimeException("Row $line: $key is too long.");
   $date=DateTime::createFromFormat('!Y-m-d',$row['birth_date']);
   if(!$date||$date->format('Y-m-d')!==$row['birth_date']||$date>new DateTime('today')||$date<new DateTime('-120 years'))throw new RuntimeException("Row $line: enter a valid birth_date as YYYY-MM-DD.");
   if(strcasecmp($row['barangay'],'San Sebastian')!==0||strcasecmp($row['city'],'Lipa City')!==0||strcasecmp($row['province'],'Batangas')!==0)throw new RuntimeException("Row $line: this registry accepts San Sebastian, Lipa City, Batangas records only.");
   foreach(['monthly_personal_income_php','monthly_household_income_php','household_size'] as $key)if(isset($row[$key])&&$row[$key]!==''&&(!is_numeric($row[$key])||(float)$row[$key]<0))throw new RuntimeException("Row $line: $key must be a non-negative number.");
   if(isset($row['is_sample'])&&!in_array($row['is_sample'],['0','1',''],true))throw new RuntimeException("Row $line: is_sample must be 0 or 1.");
   $row['is_sample']=($row['is_sample']??'0')==='1'||str_starts_with($row['resident_id'],'SS-TEST-')?'1':'0';
   // Imported text must never establish identity verification or eligibility.
   $row['verification_status']='Not verified';unset($row['age_as_of_2026_10_08']);
   $rows[]=$row;
  }
  if(!$rows)throw new RuntimeException('No resident rows found.');return $rows;
 } finally {fclose($handle);}
}

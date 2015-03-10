<?php
require_once('hhb_.inc.php');
require_once('duplicate_image_finder.class.php');


$pdi=new phpDupeImage();
hhb_init();

$ch=hhb_curl_init();
curl_setopt_array($ch,




array (
CURLOPT_URL=>"https://www.slavehack.com/workimage.php?rand=16",
CURLOPT_ENCODING=>"",
CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.115 Safari/537.36",
CURLOPT_REFERER=>"https://www.slavehack.com/index2.php",
CURLOPT_COOKIE=>"__cfduid=dc9688e2fe87240dfbe6db4daa53bf1d01425561973; PHPSESSID=nddes5u8nejt4eqsv2ga7ch0o5; _gat=1; _ga=GA1.2.77699514.1425561964",
CURLOPT_ENCODING=>"",
CURLOPT_HTTPHEADER=>array(
    "accept-language: nb-NO,nb;q=0.8,no;q=0.6,nn;q=0.4,en-US;q=0.2,en;q=0.2",
    "accept: image/webp,*/*;q=0.8"
    )
)

);
$dir=str_replace("\\","/",__DIR__)."/saved_images/";
$dir.=6024;
if(!is_dir($dir)){
	assert(mkdir($dir,770,true));
}
$dir.="/";
$duplicates=0;
for($i=0;$i<1000;++$i){
	$fn=$dir."tempfile.png";
	assert($fn!==false);
	$data= hhb_curl_exec($ch,"https://www.slavehack.com/workimage.php?rand=".rand(100,PHP_INT_MAX));
	$len=strlen($data);//34466
	assert($len>30000,"Error: picture received is less than 30000 bytes (probably corrupt :/)");
	assert($len<90000,"Error: picture received is more than 90000 bytes (probably corrupt :/ )");
	var_dump("LENGTH:",strlen($data));
	assert(strlen($data)===file_put_contents($fn,$data));
	$fp=$pdi->fingerprint($fn);
//	var_dump($fp);
//var_dump($dir.$fp.".png");
	if(file_exists($dir.$fp.".png")){
	++$duplicates;
	var_dump('FOUND DUPLICATE WITH FINGERPRINT, $fp: ',$fp,'TOTAL DUPLICATS: '.$duplicates);
		} else {
	copy($fn,$dir.$fp.".png");
		}
	echo "...".$i."...";
}
echo "done! :D";




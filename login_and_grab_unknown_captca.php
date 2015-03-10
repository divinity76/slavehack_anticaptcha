<?php
require_once('hhb_.inc.php');
require_once('duplicate_image_finder.class.php');
require_once('db.inc.php');
require_once('generate_dictionary.php');
hhb_init();
$dictionary=generate_dictionary();
require_once('duplicate_image_finder.class.php');
$dir=hhb_combine_filepaths(__DIR__);
$passwd=$dir.'/passwd.txt';
assert(is_readable($passwd),"Error: passwd.txt does not exist. Create it. first line is username. second line is password. any following lines is ignored.");
$passwd=file($passwd,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
assert(count($passwd)>=2,"Error: passwd.txt is in wrong format. must have at least 2 lines. first line is username, second line is password.");
$username=$passwd[0];
assert(strlen($username)>0);
$password=$passwd[1];
assert(strlen($password)>0);
$ch=hhb_curl_init();
$folder=get_folder();
$html=hhb_curl_exec($ch,"https://www.slavehack.com/");//get session cookies, referer, n stuff


$postfields="login=".rawurlencode($username).'&lpass='.rawurlencode($password).'&layout='.rawurlencode('default');
//var_dump('postfields:',$postfields);
curl_setopt_array($ch,
array(
CURLOPT_URL=>'https://www.slavehack.com/login.php',
CURLOPT_POST=>true,
CURLOPT_POSTFIELDS=>array(
    "login"=>$username,
	"lpass"=>$password,
	"layout"=>"default"
    )
)
);
$html=hhb_curl_exec($ch,'https://www.slavehack.com/login.php');
//$html=hhb_curl_exec($ch,'http://4chanx.org:1337/');
//var_dump(__LINE__,$html);
assert(false!==stripos($html,'Click to show IP'),"Error: could not log in. (wrong username/password?)");
//great, we've logged in! :D
assert(false!==stripos($html,'validate you\'re a human being'),"Error: could not find botcheck!");
//Great, we've got botcheck!
//var_dump($html);
for($i=0;$i<1000;++$i){
	$fp=grab_and_save_image($folder,$ch);
	if(array_key_exists($fp,$dictionary))
	{
	echo "According to the dictionary, we have captcha number: ".$dictionary[$fp];
	die("dying... you should verify that number with ".$folder);
	}
	echo "...".$i."...";
}
finished($folder);
die("FINISHED");



function grab_and_save_image($path,$ch){
	assert((is_resource($ch) && get_resource_type($ch)=="curl"),"This should be a curl handle!".hhb_return_var_dump($ch));
	static $pdi=false;
	if($pdi===false){
		$pdi=new phpDupeImage();
		$pdi->sensitivity=100;
	}
	static $duplicates=0;
	$tempfile=hhb_combine_filepaths($path,'tempfile.png');
	
	assert($tempfile!=false);
	$data= hhb_curl_exec($ch,"https://www.slavehack.com/workimage.php?rand=".rand(100,9999));
	$len=strlen($data);//34466
	assert($len>30000,"Error: picture received is less than 30000 bytes (probably corrupt :/)");
	assert($len<90000,"Error: picture received is more than 90000 bytes (probably corrupt :/ )");
	//var_dump("LENGTH:",strlen($data));
	assert(strlen($data)===file_put_contents($tempfile,$data),'Error: could not write the whole image to disk! (are you out of harddrive space?)');
	$fp=$pdi->fingerprint($tempfile);
//	var_dump($fp);
	$newfilename=hhb_combine_filepaths($path,$fp.'.png');
	//var_dump($newfilename);
	if(file_exists($newfilename)){
	++$duplicates;
	var_dump('FOUND DUPLICATE WITH FINGERPRINT, $fp: ',$fp,'TOTAL DUPLICATS: '.$duplicates);
		} else {
	assert(copy($tempfile,$newfilename));
		}
	return $fp;
		}




function get_folder(){
	$i=0;
	$folder=hhb_combine_filepaths(__DIR__,'/unknown_images');
	assert(is_dir($folder));
	$folder=hhb_combine_filepaths($folder,'/unknown_');
	while(is_dir($folder.$i)){
	++$i;
	assert($i<PHP_INT_MAX);//lawl;
	}
	$folder.=$i;
	assert(mkdir($folder));
	return $folder;
}
function finished($folder){
	$date=getdate();
	$data="FINISHED_".$date['mday'].'_'.$date['month'].'_'.$date['year'];
	file_put_contents(hhb_combine_filepaths($folder,$data),$data);
	return $data;
	}




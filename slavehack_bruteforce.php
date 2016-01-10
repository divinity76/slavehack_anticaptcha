<?php 
$username='sh_username';
$password='sh_password';
require_once('hhb_.inc.php');
hhb_init();
if(!isCli()){
    die("this script must only run from cli.");
}
$db=getdb();
register_shutdown_function(function()use(&$db){
	@$db->commit();
});
//ZGVjbGFyZSh0aWNrcyA9IDEpOw0KDQpmdW5jdGlvbiBzaWdfaGFuZGxlcigkc2lnbm8pDQp7DQplY2hvICJTSUdIQU5ETEVSISI7DQogICAgIHN3aXRjaCAoJHNpZ25vKSB7DQogICAgICAgICBjYXNlIFNJR1RFUk06DQoJCSBlY2hvICJTSUdURVJNIjsNCiAgICAgICAgICAgICAvLyBoYW5kbGUgc2h1dGRvd24gdGFza3MNCiAgICAgICAgICAgICBleGl0Ow0KICAgICAgICAgICAgIGJyZWFrOw0KICAgICAgICAgY2FzZSBTSUdIVVA6DQogICAgICAgICAgICAgLy8gaGFuZGxlIHJlc3RhcnQgdGFza3MNCgkJIGVjaG8gIlNJR0hVUCI7DQogICAgICAgICAgICAgYnJlYWs7DQoJICAgICBjYXNlIFNJR0lOVDoNCgkJIGVjaG8gIlNJR0lOVCI7DQoJCSANCgkJIGJyZWFrOw0KICAgICAgICAgY2FzZSBTSUdVU1IxOg0KICAgICAgICAgICAgIGVjaG8gIkNhdWdodCBTSUdVU1IxLi4uXG4iOw0KICAgICAgICAgICAgIGJyZWFrOw0KICAgICAgICAgZGVmYXVsdDoNCgkJIGVjaG8gIkRFRkFVTFQgUkVBQ0hFRCI7DQogICAgICAgICAgICAgLy8gaGFuZGxlIGFsbCBvdGhlciBzaWduYWxzDQogICAgIH0NCgkgZGllKCJESUVEUyIpOw0KDQp9DQoNCmVjaG8gIkluc3RhbGxpbmcgc2lnbmFsIGhhbmRsZXIuLi5cbiI7DQoNCi8vIHNldHVwIHNpZ25hbCBoYW5kbGVycw0KcGNudGxfc2lnbmFsKFNJR1RFUk0sICJzaWdfaGFuZGxlciIpOw0KcGNudGxfc2lnbmFsKFNJR0hVUCwgICJzaWdfaGFuZGxlciIpOw0KcGNudGxfc2lnbmFsKFNJR1VTUjEsICJzaWdfaGFuZGxlciIpOw0KcGNudGxfc2lnbmFsKFNJR0lOVCwgInNpZ19oYW5kbGVyIik7DQo=


//$db->query('INSERT INTO `ips` (`ip`,`scantime`,`pingable`) VALUES(0,0,0);');hhb_var_dump(isScanned(0),isScanned(1),isScanned(2));die();
$ch=hhb_curl_init();
login($ch);
brutecaptcha($ch);
scanIPS($ch);

function isScanned($ip){
	static $isScannedStm=false;
	static $boundip=0;
	if($isScannedStm===false){
	global $db;
	$isScannedStm=$db->prepare('SELECT 1 FROM `ips` WHERE `ip` = :ip LIMIT 1');
	$isScannedStm->bindParam(':ip',$boundip,PDO::PARAM_INT);
	return isScanned($ip);
	}
	$boundip=$ip;
	$isScannedStm->execute();
	//var_dump($isScannedStm->fetch(PDO::FETCH_NUM));
	return !!($isScannedStm->fetch(PDO::FETCH_NUM));
}

function scanIPS($ch,$start=1){
    global $db;
	if($start===1){
	    echo "searching for start...";
	   // while(isScanned($start)){
	   //     ++$start;
	   // }
$start=$db->query('
SELECT ips.ip+1 AS Missing
FROM ips
LEFT JOIN ips AS next ON ips.ip+1 = next.ip
WHERE next.ip IS NULL
ORDER BY ips.ip LIMIT 1;
')->fetch(PDO::FETCH_NUM)[0];
//var_dump($res);die();
	    echo "found start: ".$start.PHP_EOL;
	}
    static $insertstm=false;
	static $bindip=false;
	static $bindscantime=false;
	static $bindpingable=false;
	if(false===$insertstm){
	$insertstm=$db->prepare('INSERT INTO `ips` (`ip`,`scantime`,`pingable`) VALUES(:ip,:scantime,:pingable);');
	$insertstm->bindParam(':ip',$bindip,PDO::PARAM_INT);
	$insertstm->bindParam(':scantime',$bindscantime,PDO::PARAM_INT);
	$insertstm->bindParam(':pingable',$bindpingable,PDO::PARAM_INT);
	}
	login($ch);
	brutecaptcha($ch);
	$url="";
	$headers=array();
	$cookies=array();
	$debuginfo="";
	$db->beginTransaction();
	for($i=$start;$i<0xFFFFFFFF;++$i){
	//if(isScanned($i){continue;}
	$ip=long2ip($i);
	$url='http://www.slavehack.com/index2.php?'.http_build_query(array(
		'page'=>'internet',
		'var2'=>$ip,
		'var3'=>'',
		'var4'=>'',
	));
	echo "scanning ".$ip.PHP_EOL;
	$html=hhb_curl_exec2($ch,$url,$headers,$cookies,$debuginfo);
	if(!isLoggedIn($html)){
	echo "not logged in...".PHP_EOL;
	//die("DIEDSING1");
	login($ch);
	--$i;
	continue;
	}
	if(isBotCheck($html)){
	echo "botcheck...".PHP_EOL;
	//die("DIEDSING2");
	--$i;
	brutecaptcha($ch);
	continue;
	}
	if(($i%100000)===0){
		$db->commit();
		$db->beginTransaction();
	}
	if(false!==stripos($html,'You were able to ping the server')){
	//SERVER FOUND.
	echo "SERVER FOUND AT IP ".$ip.PHP_EOL;
	$bindip=$i;
	$bindscantime=time();
	$bindpingable=1;
	$insertstm->execute();
	$db->commit();
	$db->beginTransaction();

	continue;
	}
	if(false!==stripos($html,'There is no webservice running at')){
	//NO SERVER AT THE IP.
	$bindip=$i;
	$bindscantime=time();
	$bindpingable=0;
	$insertstm->execute();
	continue;
	}
	hhb_var_dump($url,$headers,$cookies,$debuginfo,$html);
	throw new Exception('UNREACHABLE: tried to scan ip '.$ip.'.. is not botcheck, is logged in, could not ping server, could not confirm that server is not pinged. SHOULD NEVER HAPPEN.');
	}
	throw new Exception('HOLY SUPER SUCCESSS. REACHED 0xFFFFFFFF !!!!');
}

function isCli()
{
    return (php_sapi_name() === 'cli');
}
function getdb(){
    $dbpath=hhb_combine_filepaths(__DIR__,'/sh_db.sqlite3');
    $db=new PDO('sqlite:'.$dbpath,'','',
            array(PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    if($db->query('SELECT count(*) AS `res` FROM sqlite_master WHERE type=\'table\' AND name='.$db->quote('bruteforce_logs').';')->fetch(PDO::FETCH_ASSOC)['res']!=='1'){
        echo "recreating bruteforce_logs.".PHP_EOL;
        $db->query('
CREATE TABLE `bruteforce_logs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `starttime` INTEGER NOT NULL DEFAULT NULL,
  `attempts` INTEGER NULL DEFAULT NULL,
  `endtime` INTEGER NULL DEFAULT NULL
  --`time_used` INTEGER NULL DEFAULT NULL
                );
                ');
    }

	//$db->query('DROP TABLE IF EXISTS `ips`;VACUUM;');
    if($db->query('SELECT count(*) AS `res` FROM sqlite_master WHERE type=\'table\' AND name='.$db->quote('ips').';')->fetch(PDO::FETCH_ASSOC)['res']!=='1'){
        echo "recreating ips.".PHP_EOL;
        $db->query('
CREATE TABLE `ips` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `ip` INTEGER NOT NULL DEFAULT NULL,
  `scantime` TIMESTAMP NULL DEFAULT NULL,
  `pingable` INTEGER NULL DEFAULT NULL
);
                ');
			}
    if(false && $db->query('SELECT count(*) AS `res` FROM `ips`;')->fetch(PDO::FETCH_ASSOC)['res']==='0'){
        echo "creating ips...";
		$db->query('PRAGMA synchronous = OFF');
		$db->query('PRAGMA journal_mode = MEMORY');
        $db->beginTransaction();
        $stm=$db->prepare('INSERT INTO `ips` (`ip`,`scantime`,`pingable`) VALUES(:ip,0,0);');
        $i=0x00;
		$str="";
        $stm->bindParam(':ip',$i,PDO::PARAM_INT);
		$time1=microtime(true);
        for(;$i<0xFFFFFFFF;++$i)
        {
			if(0===($i%1000000)){				
				$db->commit();
				$time2=microtime(true);
				$tu=$time2-$time1;
				$inserts_per_sec=1000000/$tu;
				echo "inserts per sec:".$inserts_per_sec.". lid:".$db->lastInsertId().PHP_EOL;
				$db->beginTransaction();
				$time1=microtime(true);
			}
            $stm->execute();
        }
        $db->commit();
	echo "DONE. exiting.";
	die();
    }
    return $db;
}
function brutecaptcha($ch){
    $starttime=time();
    $attempts=0;
    while(true){
    $html="";
    $headers=array();
    $cookies=array();
    $debuginfo="";
    $pass=mt_rand(1000,9999);
    curl_setopt_array($ch,array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query(array(
                    'nummer'=>$pass,
            )),
    ));
    $html=hhb_curl_exec2($ch,'http://www.slavehack.com/index2.php',$headers,$cookies,$debuginfo);
    ++$attempts;
    if(false!==stripos($html,'window.parent.logout();</script>')){
       echo "force logged out... attempts:".$attempts.PHP_EOL;
        login($ch);
        continue;
    }
	if(!isLoggedIn($html)){
	echo "not logged in at bruteforce...";
	login($ch);
	continue;
	}
    if(!isBotCheck($html)){
        echo "NO BOTCHECK! attempts:".$attempts.PHP_EOL;
        if(!isLoggedIn($html)){
            hhb_var_dump($html,$headers,$cookies,$debuginfo);
            throw new Exception('Not logged in. No botcheck. Can not find "Click to show IP"... something is very wrong here.');
        }
        $endtime=time();
        
        global $db;
        $stm=$db->prepare('INSERT INTO `bruteforce_logs` (`starttime`,`attempts`,`endtime`) VALUES(
                :starttime,:attempts,:endtime);');
        $stm->execute(array(
                ':starttime'=>$starttime,
                ':attempts'=>$attempts,
                ':endtime'=>$endtime,
        ));
        return $attempts;//logged in! :D
    }
    echo "code is not ".$pass.PHP_EOL;
    }
}
function isBotCheck($html){
    if(false===stripos($html,'got to ask you to repeat the numbers displayed in the image below to validate')){
        return false;
    }
    //can check for more stuff...
    return true;
}
function isLoggedIn($html){
    if(false===stripos($html,'Click to show IP')){
        return false;
    }
    //check for more?
    return true;
}
function login($ch){
	global $username,$password;
    $headers=array();
    $cookies=array();
    $debuginfo="";
    $html="";
    curl_setopt($ch,CURLOPT_HTTPGET,true);
    $html=hhb_curl_exec2($ch,'http://www.slavehack.com/index.php?page=logout',$headers,$cookies,$debuginfo);
    //$html=hhb_curl_exec2($ch,'http://www.slavehack.com/',$headers,$cookies,$debuginfo);
    assert(false!==stripos ($html ,'Forgot your password ?'));
    //hhb_var_dump($html,$headers,$cookies,$debuginfo);
    curl_setopt_array($ch,array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query(array(
                    'login'=>$username,
                    'lpass'=>$password,
                    'layout'=>'default',
                    'Name'=>'',
                    'Path'=>'',
                    
            )),
    ));
    $html=hhb_curl_exec2($ch,'http://www.slavehack.com/login.php',$headers,$cookies,$debuginfo);
    if(!isLoggedIn($html)){
     hhb_var_dump($html,$headers,$cookies,$debuginfo);
    throw new Exception("failed to log in!");
    }
    //hhb_var_dump($html,$headers,$cookies,$debuginfo);
    return true;
}

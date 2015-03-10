<?php
throw new Exception("NEVER MIND THIS FILE.");
init();
$db_file=__DIR__.'/captcha_db.sqlite3';
$db_file=str_replace("\\",'/',$db_file);//dont ask
create_sqlite3db($db_file);
$dbc=new PDO('sqlite:'.$db_file,'','',array(
PDO::ATTR_EMULATE_PREPARES => false, 
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
));
try{
$requests=parse_requests($_POST);//why? trying to be atomic. don't do a half-assed job if some requests are supported, and some requests are not.
//if some requests are unsupported, don't do anything.
}catch(Exception $ex){
	$r=new Response(array($ex->__toString()),Response::STATUS_ERROR);
	die($r->__toString());
}
foreach($requests as $request){
	switch($request){
		
		default:
		{
			throw new LogicException("the request parser is broken!");
		}
	}
}










class Response{
	const STATUS_OK=200;
	const STATUS_ERROR=500;
	public $status=Response::OK;
	public $response_data="";
	function __construct($response_data,$status){
		$this->response_data=$response_data;
		$this->status=$status;//validate status? geez
	}
	public function __toString(){
				$arr=array();
				$arr["status"]=$this->status;
				$arr["response"]=$this->response_data;
		switch($this->status){
			case Response::STATUS_OK:{
				$arr["status_string"]="STATUS_OK";
				break;
			}
			case Response::STATUS_ERROR:{
				$arr["status_string"]="STATUS_ERROR";
				$this["error"]=$this->response_data;
				break;
			}
			default:
			{
				throw new LogicException("invalid status, should be unreachable..");
			}
		}
		return json_encode($arr);
	}
}


function parse_requests($raw_requests){
	$ret=array();
	$request_counter=0;
foreach($raw_requests as $request=>$data){
	++$request_counter;
	switch($request){
		case "upload_image":
		{
			$ret[]="upload_image";
			break;
		}
		
		default:
		{
			throw new InvalidArgumentException('UNSUPPORTED REQUEST: '.$request);
		}
	}
}

}



function hhb_init(){
error_reporting(E_ALL);
set_error_handler("hhb_exception_error_handler");
//	ini_set("log_errors",true);
//	ini_set("display_errors",true);
//	ini_set("log_errors_max_len",0);
//	ini_set("error_prepend_string",'<error>');
//	ini_set("error_append_string",'</error>'.PHP_EOL);
//	ini_set("error_log",__DIR__.'/error_log.php');
}
function hhb_exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
function create_sqlite3db($filename){
if(file_exists($filename)){
	return;
}
	$dbh=false;
	assert(false!==($dbh=sqlite_open($filename)));
	sqlite_close($dbh);//void function
}
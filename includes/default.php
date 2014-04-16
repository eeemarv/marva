<?php

if(!isset($rootpath)){
	$rootpath = "";
}


$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('Marva', __DIR__.'/');

require_once __DIR__.'/../parameters.php';

use Symfony\Component\Yaml\Yaml;
 
$parameters = array_merge_recursive(Yaml::parse(__DIR__.'/../site/parameters.yml'), $parameters); 

if ($parameters['debug']){
	error_reporting(E_ALL ^ E_NOTICE);
} else {
	error_reporting(0);
}

if ($parameters['redirect']){
	header('HTTP/1.1 301 Moved Permanently'); 
	header('Location: '.$parameters['redirect']); 
	exit();
}	 

/*
$con = $parameters['db'];
$port = ($con['port']) ? ':'.$con['port'] : '';

$db = NewADOConnection($con['driver']);
$db->Connect($con['host'].$port, $con['user'], $con['password'], $con['dbname']); 
$db->setFetchMode(ADODB_FETCH_ASSOC);
*/

$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'dbname' => $parameters['db']['dbname'],
    'user' => $parameters['db']['user'],
    'password' => $parameters['db']['password'],
    'host' => $parameters['db']['host'],
    'driver' => $parameters['db']['driver'],
);
$db = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);



unset($con, $parameters['db']);



$systemMailAddresses = array_intersect_key($parameters['mail'], array(
	'info' => 'info', 'admin' => 'admin', 'support' => 'support', 'news-admin' => 'news-admin',
	'noreply' => 'noreply', 'list' => 'list'));
	
foreach ($systemMailAddresses as $key => $val){
	$val = (empty($val)) ? $key : $val;
	$parameters['mail'][$key] = (filter_var($val, FILTER_VALIDATE_EMAIL)) ? $val : $val.'@'.$_SERVER['HTTP_HOST'];
}


/*

define(STATUS_ACTIVE, 1);
define(STATUS_SYSTEM, 2);
define(STATUS_INTERLETS, 4);
define(STATUS_LEAVING, 64);
define(STATUS_POSTACTIVE, 128);
define(STATUS_INFO, 256);
define(STATUS_INFOMOMENT, 512);

*/

	




$dbconfig = $db->fetchAll('SELECT * FROM config');  //



// Fetch configuration keys from the database
function readconfigfromdb($searchkey){
    global $db;
    global $dbconfig;

    foreach ($dbconfig as $key => $list) {
		if($list['setting'] == $searchkey) {
			return $list['value'];
		}
    }
}

/*
// Handle legacy config settings from the original config file
$configuration["system"]["currency"] = readconfigfromdb("currency");
$configuration["system"]["systemtag"] = readconfigfromdb("systemtag");
$configuration["system"]["systemname"] = readconfigfromdb("systemname");
$configuration["system"]["sessionname"] = readconfigfromdb("sessionname");
$configuration["system"]["emptypasswordlogin"] = readconfigfromdb("emptypasswordlogin");
$configuration["system"]["timezone"] = readconfigfromdb("timezone");
$configuration["system"]["pwscore"] = readconfigfromdb("pwscore");
$configuration["system"]["maintenance"] = readconfigfromdb("maintenance");
$configuration["system"]["newuserdays"] = readconfigfromdb("newuserdays");
$configuration["users"]["minlimit"] = readconfigfromdb("minlimit");
$configuration["mail"]["enabled"] = readconfigfromdb("mailenabled");
$configuration["mail"]["admin"] = readconfigfromdb("admin");
$configuration["mail"]["support"] = readconfigfromdb("support");
$configuration["mail"]["from_address"] = readconfigfromdb("from_address");
$configuration["mail"]["from_address_transactions"] = readconfigfromdb("from_address_transactions");
*/


// removed release.xml version was eLAS 2.5.16
// What schema version to expect  
//$schemaversion =  2207;
//$soapversion = 1200;
//$restversion = 1;


//date_default_timezone_set($parameters['timezone']);


// flash-messages
function setstatus($status, $type = "info"){
	global $_SESSION;
	$type = (in_array($type, array('info', 'warning', 'success', 'danger'))) ? $type : 'info';
	array_push($_SESSION["status"], array('message' => $status, 'type' => $type));
}


function getCurrencyText($amount, $includeAmount = true){
	global $parameters;
	$out = ($includeAmount) ? $amount.' ' : '';
	$out .= (((int) $amount == 1 || (int) $amount == -1)) ? $parameters['currency_singular'] : $parameters['currency_plural'];
	return $out;	
}

function generateUniqueId(){
    return rtrim(strtr(base64_encode(hash('sha256', uniqid(mt_rand(), true), true)), '+/', '-_'), '=');	
	
}


// Make timestamps for SQL statements
function make_timestamp($timestring){
        $month = substr($timestring,3,2);
        $day = substr($timestring, 0,2);
        $year = substr($timestring,6,4 );
        $timestamp = mktime(0,0,0,$month, $day, $year);
        return $timestamp;
}

function getUserClass($user){
	global $parameters;
	switch ($user['status']){
		case '0':
		case '3':
		case '5':
		case '6':
		case '8':
		case '9': return 'inactive';
		case '2': return 'danger';
		case '4': return 'info';
		case '7': return 'warning'; 
		default:
			return ($user['unix'] > (time() - ($parameters['new_user_days'] * 86400))) ? 'success' : ''; 	
	}	
}

function dateFormatTransform($in, $reverse = false){
	if ($reverse){
		$datetime = DateTime::createFromFormat('d-m-Y', $in);
		return $datetime->format('Y-m-d H:i:s');
	}	
	return date('d-m-Y', strtotime($in));	
}

function getLocalLetscode($letscode){	
	list($letscode) = explode(' ', trim($letscode));
	list($letscode) = explode('/', trim($letscode));	
	return trim($letscode);
}



function log_event($id,$type,$event){
    global $db, $elasdebug, $dirbase, $rootpath;
	
	$ip = $_SERVER['REMOTE_ADDR'];
 
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
   	$ts = new \DateTime();
	$mytype = strtolower($type);	

	$id = ($id) ? $id : 0;
	
	if($mytype != "debug" && $elasdebug != 0){
		$db->insert('eventlog', 
			array('userid' => $id, 'type' => $mytype, 'timestamp' => $ts, 'event' => $event, 'ip' => $ip)); //
	}
}



?>

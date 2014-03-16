<?php
//error_reporting(0);

if(!isset($rootpath)){
	$rootpath = "";
}


$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('Marva', __DIR__.'/');

require_once __DIR__.'/../parameters.php';

use Symfony\Component\Yaml\Yaml;

$parameters = array_merge(Yaml::parse(__DIR__.'/../site/parameters.yml'), $parameters); 

if ($parameters['debug']){
	error_reporting(E_ERROR);
} else {
	error_reporting(0);
}

if ($parameters['redirect']){
	header('HTTP/1.1 301 Moved Permanently'); 
	header('Location: '.$parameters['redirect']); 
	exit();
}	 

/*
# Config file handling
# the config file is named config/elas.conf.php
# we will load it into the array $configuration

// Get the baseurl, used for several things including multisite
$baseurl = $_SERVER['HTTP_HOST'];

# Get rid of missing rootpath errors
if(!isset($rootpath)){
	$rootpath = "";
}

if(is_dir($rootpath."sites/$baseurl")){
	$dirbase = $baseurl;
} else {
	$dirbase = "default";
}

$xml_config = $rootpath ."sites/$dirbase/config/elas.xml";

if(file_exists($xml_config)){
	$xmlconfig = simplexml_load_file("$xml_config");
	$configuration["db"]["dsn"] = $xmlconfig->dbdsn;
	$configuration["system"]["timezone"] = $xmlconfig->timezone;
	$configuration["hosting"]["enabled"] = false;
} 


if (!isset($configuration["db"]["dsn"])  && ($nocheckconfig != TRUE )){
	
// Check for presence of the $configuration variable: if not present, the configuration file should be created and we redirect the flow to the setup page
	
	echo 'configuration file error';
	exit;
}
*/





$con = $parameters['db'];
$port = ($con['port']) ? ':'.$con['port'] : '';

$db = NewADOConnection($con['driver']);
$db->Connect($con['host'].$port, $con['user'], $con['password'], $con['dbname']); 
$db->setFetchMode(ADODB_FETCH_ASSOC);

unset($con, $parameters['db']);


// Read the full config table to an array
$query = "SELECT * FROM config";
$dbconfig = $db->GetArray($query);
//var_dump ($dbconfig);


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



//require_once $dir.'/inc_config.php';


// removed release.xml version was eLAS 2.5.16
// What schema version to expect  
$schemaversion =  2207;
$soapversion = 1200;
$restversion = 1;


//date_default_timezone_set($parameters['timezone']);


// flash-messages
function setstatus($status, $type = "info"){
	global $_SESSION;
	$type = (in_array($type, array('info', 'warning', 'success', 'danger'))) ? $type : 'info';
	array_push($_SESSION["status"], array('message' => $status, 'type' => $type));
}


// Make timestamps for SQL statements
function make_timestamp($timestring){
        $month = substr($timestring,3,2);
        $day = substr($timestring, 0,2);
        $year = substr($timestring,6,4 );
        $timestamp = mktime(0,0,0,$month, $day, $year);
        return $timestamp;
}

?>

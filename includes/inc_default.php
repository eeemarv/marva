<?php
error_reporting(0);


require_once __DIR__.'/../vendor/autoload.php';
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
 





require_once $dir.'/inc_config.php';


// removed release.xml version was eLAS 2.5.16
// What schema version to expect  
$schemaversion =  2207;
$soapversion = 1200;
$restversion = 1;


date_default_timezone_set($site['timezone']);


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

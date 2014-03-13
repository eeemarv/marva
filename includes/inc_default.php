<?php
//
error_reporting(0); 


# Get rid of missing rootpath errors
if(!isset($rootpath)){
	$rootpath = "";
}

//override the include path, so we pick up the contrib directory first
ini_set('include_path',$rootpath.'contrib/includes:'.ini_get('include_path')); 

 

require_once('inc_config.php');


// removed release.xml version was eLAS 2.5.16
// What schema version to expect  
$schemaversion =  2207;
$soapversion = 1200;
$restversion = 1;


// Set the timezeone to value in configuration
date_default_timezone_set($configuration["system"]["timezone"]);

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

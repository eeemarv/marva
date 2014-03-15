<?php

//require_once('../vendor/adodb/adodb-php/adodb-errorpear.inc.php');
//require_once('../vendor/adodb/adodb-php/adodb.inc.php');


//require_once('adodb/adodb-errorpear.inc.php');
//require_once('adodb/adodb.inc.php');

/*
if (isset($configuration["db"]["dsn"])) {
    $db_dsn=$configuration["db"]["dsn"];
    $parseddsn=parse_url($db_dsn);
    $db = NewADOConnection($db_dsn);
    $db->setFetchMode(ADODB_FETCH_ASSOC);

}
*/

$con = $parameters['db'];
$port = ($con['port']) ? ':'.$con['port'] : '';

$db = NewADOConnection($con['driver']);
$db->Connect($con['host'].$port, $con['user'], $con['password'], $con['dbname']); 
$db->setFetchMode(ADODB_FETCH_ASSOC);

unset($con, $parameters['db']);


function getadoerror(){
	$e = ADODB_Pear_Error();
        if(is_object($e)){
            return $e->message;
        }
	return false;
}
/*
require_once($rootpath."includes/inc_dbconfig.php");
require_once($rootpath."includes/inc_legacyconfig.php");
*/


?>

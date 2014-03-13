<?php




//require_once('../vendor/adodb/adodb-php/adodb-errorpear.inc.php');
//require_once('../vendor/adodb/adodb-php/adodb.inc.php');


//require_once('adodb/adodb-errorpear.inc.php');
//require_once('adodb/adodb.inc.php');


if (isset($configuration["db"]["dsn"])) {
    $db_dsn=$configuration["db"]["dsn"];
    $parseddsn=parse_url($db_dsn);
    $db = NewADOConnection($db_dsn);
    $db->setFetchMode(ADODB_FETCH_ASSOC);

}


function getadoerror(){
	$e = ADODB_Pear_Error();
        if(is_object($e)){
            return $e->message;
        }
	return FALSE;
}

require_once($rootpath."includes/inc_dbconfig.php");
require_once($rootpath."includes/inc_legacyconfig.php");



?>

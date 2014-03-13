<?php

// Read the full config table to an array
$query = "SELECT * FROM config";
$dbconfig = $db->GetArray($query);
//var_dump ($dbconfig);

// Read parameters into array
$query = "SELECT * FROM parameters";
$dbparameters = $db->GetArray($query);

// Fetch configuration keys from the database
function readconfigfromdb($searchkey){
    global $db;
    global $dbconfig;

    foreach ($dbconfig as $key => $list) {
	#echo "<br />" .$list['setting'] ." - " .$list['value'];
	if($list['setting'] == $searchkey) {
		return $list['value'];
	}
    }
}

function readparameter($searchkey){
	global $dbparameters;
	foreach ($dbparameters as $key => $list) {
	#echo "<br />" .$list['setting'] ." - " .$list['value'];
	if($list['parameter'] == $searchkey) {
		return $list['value'];
	}
    }
}	

?>

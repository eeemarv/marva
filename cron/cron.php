<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/default.php");


require_once($rootpath."cron/inc_cron.php");


require_once($rootpath."cron/inc_upgrade.php");

require_once($rootpath."includes/inc_userinfo.php");


session_start();

header('Content-type: text/plain');



// Upgrade the DB first if required
/*		
$dbversion = $db->fetchColummn('select value from `parameters` WHERE `parameter`= \'schemaversion\'') ;
$currentversion = $dbversion;
$doneversion = $currentversion;
while($currentversion < $schemaversion){
	$currentversion = $currentversion +1;
	if(doupgrade($currentversion) == TRUE){
		$doneversion = $currentversion;
	}
}
echo "Upgraded database from schema version $dbversion to $doneversion\n\n";
*/


echo ' *** Cron system running [' .$parameters['letsgroup_code'].'] ***\n\n';



// Auto mail saldo on request
$frequency = readconfigfromdb("saldofreqdays") * 1440;
if(check_timestamp("saldo", $frequency) == 1) {
	automail_saldo();
}




// Clean up expired news items
$frequency = 1440;  
if(check_timestamp("cleanup_news", $frequency) == 1){
        cleanup_news();
}

// Clean up expired tokens
$frequency = 1440;
if(check_timestamp("cleanup_tokens", $frequency) == 1){
        cleanup_tokens();
}

// RUN the ILPQ
$frequency = 5;
if(check_timestamp("processqueue", $frequency) == 1){
	require_once("$rootpath/interlets/processqueue.php");
	write_timestamp("processqueue");
}


echo "\nCron run finished\n";



// functions 


function cleanup_tokens(){
	echo "Running cleanup_tokens\n";
	do_cleanup_tokens();
	write_timestamp("cleanup_tokens");
}

function cleanup_news() {
	echo "Running cleanup_news\n";
	do_cleanup_news();
	write_timestamp("cleanup_news");
}



function automail_saldo(){
	// Get all users that want their saldo auto-mailed.
	echo "Running automail_saldo\n";
	global $db;
        $query = "SELECT users.id, users.name, users.saldo AS saldo, contact.value AS cvalue FROM users,contact,type_contact ";
	$query .= "WHERE users.id = contact.id_user AND contact.id_type_contact = type_contact.id AND type_contact.abbrev = 'mail' AND users.status <> 0 AND users.cron_saldo = 1";
	$users = $db->GetArray($query);
	
	foreach($users as $key => $value) {
		$mybalance = $value["saldo"];
		mail_balance($value["cvalue"], $mybalance);
	}

	//Timestamp this run
	write_timestamp("saldo");
}


?>

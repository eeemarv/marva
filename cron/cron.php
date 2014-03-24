<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/default.php");


require_once($rootpath."cron/inc_cron.php");


require_once($rootpath."cron/inc_upgrade.php");

require_once($rootpath."includes/inc_userinfo.php");
require_once($rootpath."includes/inc_saldofunctions.php");

require_once($rootpath."includes/inc_eventlog.php");
session_start();

header('Content-type: text/plain');



# Upgrade the DB first if required
/*			
$query = "SELECT * FROM `parameters` WHERE `parameter`= 'schemaversion'";
$qresult = $db->GetRow($query) ;
$dbversion = $qresult["value"];
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


// Update counts for each message category
$frequency = 1440;
if(check_timestamp("cat_update_count", $frequency) == 1) {
        cat_update_count();
}

// Update the cached saldo
$frequency = 1440;
if(check_timestamp("saldo_update", $frequency) == 1) {
	saldo_update();
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











function cat_update_count() {
	echo "Running cat_update_count\n";
	
	
        $catlist = get_cat();
        
        
        
        foreach ($catlist AS $key => $value){
                $cat_id = $value["id"];
                update_stat_msgs($cat_id);
        }

	write_timestamp("cat_update_count");
}

function saldo_update(){
	global $db;
	echo "Running saldo_update ...";

	$query = "SELECT * FROM users"; 
	$userrows = $db->GetArray($query);

	foreach ($userrows AS $key => $value){
		//echo $value["id"] ." ";
		update_saldo($value["id"]);	
	}
	echo "\n";
	write_timestamp("saldo_update");
}


 


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

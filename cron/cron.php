<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/default.php");

require_once($rootpath."includes/inc_amq.php");
require_once($rootpath."cron/inc_cron.php");
require_once($rootpath."cron/inc_upgrade.php");
require_once($rootpath."cron/inc_stats.php");

require_once($rootpath."includes/inc_mailfunctions.php");
require_once($rootpath."includes/inc_userinfo.php");
require_once($rootpath."includes/inc_saldofunctions.php");

require_once($rootpath."includes/inc_news.php");
require_once($rootpath."includes/inc_eventlog.php");
session_start();

header('Content-type: text/plain');
//global $elas;
global $xmlconfig;
global $db;

# Upgrade the DB first if required
			
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


// PUT MAIN BODY HERE
// echo "<p><small>Build from branch: " . $elas->branch .", revision: " .$elas->revision .", build: " .$elas->build;
echo " *** eLAS v" .$elas->version . "(" .$elas->branch .")" ." build #" . $elas->build ." Cron system running [" .readconfigfromdb("systemtag") ."] ***\n\n";



// Check and create required paths
$frequency = 10;
if(check_timestamp("create_paths", $frequency) == 1) {
	create_paths();
}

// Check for incoming messages on the AMQ
$frequency = 5;
if(check_timestamp("process_ampmessages", $frequency) == 1) {
	process_amqmessages();
}

// Auto mail saldo on request
$frequency = readconfigfromdb("saldofreqdays") * 1440;
if(check_timestamp("saldo", $frequency) == 1) {
	automail_saldo();
}

// Auto mail messages that have expired to the admin
$frequency = readconfigfromdb("adminmsgexpfreqdays") * 1440;
if(check_timestamp("admin_exp_msg", $frequency) == 1 && readconfigfromdb("adminmsgexp") == 1){
	automail_admin_exp_msg();
}

// Check for and mail expired messages to the user
$frequency = 1440;  
if(check_timestamp("user_exp_msgs", $frequency) == 1 && readconfigfromdb("msgexpwarnenabled") == 1){
	check_user_exp_msgs();
}

// Clean up expired messages after the grace period
$frequency = 1440;  
if(check_timestamp("cleanup_messages", $frequency) == 1 && readconfigfromdb("msgcleanupenabled") == 1){
        cleanup_messages();
}

// Update counts for each message category
$frequency = 60;
if(check_timestamp("cat_update_count", $frequency) == 1) {
        cat_update_count();
}

// Update the cached saldo
$frequency = 60;
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

// Check if the hosting contract expired
$frequency = 10080;
if(check_timestamp("check_hosting", $frequency) == 1){
        check_hosting();
}

// Publish news items that were approved
$frequency = 30;
if(check_timestamp("publish_news", $frequency) == 1){
	publish_news();
}

// Update the stats table
$frequency = 720;
if(check_timestamp("update_stats", $frequency) == 1){
	update_stats();
}

// Process the mail queue
// DISABLED in 2.5
// $frequency = 0;
// if(check_timestamp("mailq_run", $frequency) == 1 && readconfigfromdb("mailinglists_enabled") == 1){
//	mailq_run();
//}
	
// END
echo "\nCron run finished\n";

////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////

function process_amqmessages(){
	global $configuration;
	
	echo "Running Process AMQ messages...\n";
	//echo "Hosting is " .$configuration["hosting"]["enabled"] . "\n";
	if ($configuration["hosting"]["enabled"]	== 1){
		echo "Getting hosting AMQ updates...\n";
		amq_processhosting();
	}
	amq_processincoming();
	write_timestamp("process_ampmessages");
}

function create_paths() {
	global $rootpath;
	global $baseurl;
	
	echo "Running create_paths...\n";
	
	// Auto create the json directory
	$dirname = "$rootpath/sites/$baseurl/json";
	if (!file_exists($dirname)){
		echo "    Creating directory $dirname\n";
		mkdir("$dirname", 0770);
		echo "    Creating .htaccess file for $dirname\n";
		file_put_contents("$dirname/.htaccess", "Deny from all\n");
	}
	
	write_timestamp("create_paths");
}

function mailq_run(){
	# Process mails in the queue and hand them of to a droid
	global $provider;
	global $configuration;
	global $db;
	global $baseurl;
	
	$systemname = readconfigfromdb("systemname");
    $systemtag = readconfigfromdb("systemtag");
    
	echo "Running mailq_run...\n";
	
	$query = "SELECT * FROM mailq WHERE `sent` = 0"; 
	$mails = $db->GetArray($query);
			
	foreach ($mails AS $key => $value){
		echo "Processing message " .$value["msgid"] . " to list " .$value["listname"] . "\n";
		
		# Get all subscribers for that list
		$query = "SELECT * FROM lists, listsubscriptions WHERE listsubscriptions.listname = lists.listname AND listsubscriptions.listname = '" .$value["listname"] . "'";
		$subscribers = $db->GetArray($query);

		$footer = "--\nJe krijgt deze mail via de lijst '" .$value["listname"] ."' op de eLAS installatie van " .$systemname .".\nJe kan je mailinstellingen en abonnementen wijzigen in je eLAS profiel op http://" .$baseurl .".";
		// Set maildroid format version
		$message["mformat"] = "1";
		$message["id"] = $value["msgid"];
		$message["contenttype"] = "text/html";
		$message["charset"] = "utf-8";
		$message["from"] = $value["from"];
		$message["to"] = array();
		$message["subject"] = "[eLAS-$systemtag " . $value["listname"] ."] " .$value["subject"];
		$message["body"] = "<html>\n" .$value["message"];
		$message["body"] .= "\n\n<small>$footer</small></html>";
		$message["body"] = nl2br($message["body"]);
		
		
		foreach ($subscribers AS $subkey => $subvalue){
			//echo "\nFound subsciberID: " . $subvalue["user_id"] . "\n";
			$usermails =  get_user_mailarray($subvalue["user_id"]);
			//var_dump($usermails);
					
			foreach($usermails as $mailkey => $mailvalue){			
				array_push($message["to"], $mailvalue["value"]);
			}
			
			//var_dump($message);
		}
		$json = json_encode($message);
				
		$mystatus = esm_mqueue($json);
		if($mystatus == 1){
			$query = "UPDATE mailq SET  sent = 1 WHERE msgid = '" . $message["id"] ."'";
			$db->Execute($query);
			$mid = $message["id"];
			log_event("","Mail","Queued $mid to ESM");
		} else {
			echo "Failed to AMQ queue message " . $message["id"] ."\n";
			log_event("","Mail","Failed to queue $mid to ESM");
		}
	}
	echo "\n";

	write_timestamp("mailq_run");
}

function check_hosting(){
	global $configuration;
	global $db;
	//TODO Add check if this instance has certain features like mailing lists and enable/disable them
	if($configuration["hosting"]["enabled"] == 1){
		// From 2.4.32 notifications will no longer be sent by eLAS but by ESM
		echo "Running check_contract\n";
		//$provider = get_provider();
		$contract = get_contract();
		//print_r($contract);
		$enddate = strtotime($contract["end"]);
		$graceperiod = $contract["graceperiod"];
		$now = time();

		switch($enddate){
			case (($enddate + ($graceperiod * 24 * 60 * 60)) < $now):
				//Het contract is vervallen en uit grace
				//LOCK eLAS
				$query = "UPDATE parameters SET value = 1 WHERE parameter = 'lockout'";
				$db->Execute($query);
				break;
			case (($enddate + ($graceperiod * 24 * 60 * 60)) > $now):
				//Het contract is niet vervallen of uit grace
				//extra unLOCK eLAS
				$query = "UPDATE parameters SET value = 0 WHERE parameter = 'lockout'";
				$db->Execute($query);
				break;
		}
		//sendemail($from,$mailto,$subject,$content)
	}
	write_timestamp("check_hosting");
}

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

function publish_news(){
	global $db;
	global $baseurl;
	
    echo "Running publish_news...\n";

    $query = "SELECT * FROM news WHERE approved = 1 AND published IS NULL OR published = 0;";
	$newsitems = $db->GetArray($query);

    foreach ($newsitems AS $key => $value){
		mail_news($value["id"]);
		
		# GVS 20130505 Killing ostatus support
		#// Push to ostatus too
		$fullurl = "http://" . $baseurl ."/news/view.php?id=" . $value["id"];
		$message = "Nieuws: " .$value["headline"];
		#ostatus_queue($message, $fullurl);
			
		$q2 = "UPDATE news SET published=1 WHERE id=" .$value["id"];
		$db->Execute($q2);
	}
	write_timestamp("publish_news");
}
 
function cleanup_messages(){
	// Fetch a list of all expired messages that are beyond the grace period and delete them
	echo "Running cleanup_messages\n";
	do_auto_cleanup_messages();
	do_auto_cleanup_inactive_messages();
	write_timestamp("cleanup_messages");
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

function check_user_exp_msgs(){
	//Fetch a list of all non-expired messages that havent sent a notification out yet and mail the user
	echo "Running check_user_exp_msgs\n";
	$msgexpwarningdays = readconfigfromdb("msgexpwarningdays");
	$msgcleanupdays = readconfigfromdb("msgexpcleanupdays");
	$warn_messages = get_warn_messages($msgexpwarningdays);
	foreach ($warn_messages AS $key => $value){
		//For each of these, we need to fetch the user's mailaddress and send him a mail.
		$user = get_user_maildetails($value["id_user"]);
		$username = $user["name"];

		$content = "Beste $username\n\nJe vraag of aanbod '" .$value["content"] ."'";
		$content .= " in eLAS gaat over " .$msgexpwarningdays;
		$content .= " dagen vervallen.  Om dit te voorkomen kan je inloggen op eLAS en onder de optie 'Mijn Vraag & Aanbod' voor verlengen kiezen.";
		$content .= "\n\nAls je niets doet verdwijnt dit V/A $msgcleanupdays na de vervaldag uit je lijst.";
		$mailaddr = $user["emailaddress"];
		$subject = "Je V/A in eLAS gaat vervallen";
		mail_user_expwarn($mailaddr,$subject,$content);
		mark_expwarn($value["id"],1);
	}

	//Fetch a list of expired messages and warn the user again.
	$warn_messages = get_expired_messages();
	foreach ($warn_messages AS $key => $value){
                //For each of these, we need to fetch the user's mailaddress and send him a mail.               
		$user = get_user_maildetails($value["id_user"]);
                $username = $user["name"];

                $content = "Beste $username\n\nJe vraag of aanbod '" .$value["content"] ."'";
                $content .= " in eLAS is vervallen. Als je het niet verlengt wordt het $msgcleanupdays na de vervaldag automatisch verwijderd.";
                $mailaddr = $user["emailaddress"];
                $subject = "Je V/A in eLAS is vervallen";
                mail_user_expwarn($mailaddr,$subject,$content);
                mark_expwarn($value["id"],2);
        }

	// Finally, clear all the old flags with a single SQL statement 
	// UPDATE messages SET exp_user_warn = 0 WHERE validity > now + 10
	do_clear_msgflags();

	// Write the timestamp
	write_timestamp("user_exp_msgs");
}


function automail_admin_exp_msg(){
	// Fetch a list of all expired messages and mail them to the admin
	echo "Running automail_admin_exp_msg\n";
	global $db;
	$today = date("Y-m-d");
	$query = "SELECT users.name AS username, messages.content AS message, messages.id AS mid, messages.validity AS validity FROM messages,users WHERE users.status <> 0 AND messages.id_user = users.id AND validity <= '" .$today ."'";
	$messages = $db->GetArray($query);

	//foreach($messages as $key => $value) {
	//	echo $value["mid"];
	//	echo $value["username"];
	//	echo $value["message"];
	//	echo $value["validity"];
	//	echo "\n";
	//}
	mail_admin_expmsg($messages);

	write_timestamp("admin_exp_msg");
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

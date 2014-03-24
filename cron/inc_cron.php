<?php
// Functions required by the cron script

function update_stat_msgs($cat_id){
	global $db;

	$query = "SELECT COUNT(*) AS stat_msg_wanted";
        $query .= " FROM messages, users ";
        $query .= " WHERE ";
	$query .= " id_category = ".$cat_id ;
        $query .= " AND messages.id_user = users.id ";
        $query .= " AND (users.status = 1 OR users.status = 2 OR users.status = 3) ";
	$query .= " AND msg_type = 0 ";

    	$row = $db->GetRow($query);
        $stat_wanted = $row["stat_msg_wanted"];


	$query = "SELECT COUNT(*) AS stat_msg_offer";
        $query .= " FROM messages, users ";
        $query .= " WHERE ";
	$query .= " id_category = ".$cat_id ;
        $query .= " AND messages.id_user = users.id ";
        $query .= " AND (users.status = 1 OR users.status = 2 OR users.status = 3) ";
        $query .= " AND msg_type = 1 ";
        $row = $db->GetRow($query);
        $stat_offer = $row["stat_msg_offer"];

        $posted_list["stat_msgs_wanted"] = $stat_wanted;
        $posted_list["stat_msgs_offers"] = $stat_offer;
        $result = $db->AutoExecute("categories", $posted_list, 'UPDATE', "id=$cat_id");
}

function get_cat(){
        global $db;
        $query = "SELECT * FROM categories WHERE leafnote=1 order by fullname";
        $cat_list = $db->GetArray($query);
        return $cat_list;
}




function do_auto_cleanup_inactive_messages(){
	global $db;
	$query = "SELECT * FROM users WHERE status = 0";
	$users = $db->GetArray($query);

	foreach ($users AS $key => $value){
		$q2 = "DELETE FROM messages WHERE id_user = " .$value["id"];
		$db->Execute($q2);
	}
}

function do_cleanup_news() {
    global $db;
    $now = date('Y-m-d', time());
	$query = "DELETE FROM news WHERE itemdate < '" .$now ."' AND sticky <> 1";
	$db->Execute($query);
}

function do_cleanup_tokens(){
	global $db;
        $now = date('Y-m-d H:i:s', time());
	$query = "DELETE FROM tokens WHERE validity < '" .$now ."'";
        $db->Execute($query);
}


function mail_balance($to, $balance){
	global $parameters;
	
	$from = $parameters['mail']['noreply'];

	$subject .= '['.$parameters['letsgroup_code'].'] - Saldo mail'; 

	$content .= "-- Dit is een automatische mail, niet beantwoorden aub --\r\n";
	$content .= "\nJe ontvangt deze mail omdat je de optie 'Mail saldo' op de website hebt geactiveerd,\nzet deze uit om deze mails niet meer te ontvangen.\n";

	$currency = $parameters['currency_plural'];
	$mailcontent .= "\nJe huidig LETS saldo is " .$balance ." " .$currency ."\n";

    sendemail($from, $to, $subject, $content);
}



function check_timestamp($cronjob,$agelimit){
	// agelimit is the time after which to rerun the job in MINUTES
	global $db;
	$query = "SELECT lastrun FROM cron WHERE cronjob = '" .$cronjob ."'";
	$job = $db->GetRow($query);
	$now = time();
	$limit = $now - ($agelimit * 60);
	$timestamp = strtotime($job["lastrun"]);

	if($limit > $timestamp) {
			return 1;
	} else {
			return 0;
	}

	//log the cronjob execution
	
}

function write_timestamp($cronjob){
        global $db;
        $query = "SELECT cronjob FROM cron WHERE cronjob = '" .$cronjob ."'";
        $job = $db->GetRow($query);
        $ts = date("Y-m-d H:i:s");

        if($job["cronjob"] != $cronjob){
                $qins = "INSERT INTO cron(cronjob) VALUES ('" .$cronjob ."')";
                $db->execute($qins);
        } else {
                $qupd = "UPDATE cron SET lastrun = '" .$ts ."' WHERE cronjob = '" .$cronjob ."'";
                $db->Execute($qupd);
        }

	//Write completion to eventlog
	log_event(" ","Cron","Cronjob $cronjob finished");
}

?>


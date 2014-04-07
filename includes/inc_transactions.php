<?php
/**
 * copyleft 2014 martti <info@martti.be>
 * 
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
 * 
 * see LICENSE
*/

// Enable logging
// require_once 'includes/inc_userinfo.php';
// require_once 'includes/mail.php';

function check_duplicate_transaction($transid){
	global $db;
	return $db->fetchColumn('select id from transactions where transid = ?', array($transid)) ? true : false; 
}


function sign_transaction($posted_list, $sharedsecret) {
	$signamount = (float) $posted_list["amount"];
	$signamount = $signamount * 100;
	$signamount = round($signamount);
	$tosign = $sharedsecret .$posted_list["transid"] .strtolower($posted_list["letscode_to"]) .$signamount; 
	$signature = sha1($tosign);
	log_event("","debug","Signing $tosign: $signature");
	return $signature;
}



function update_balance($userid){
	global $db;
	
	$balance = $db->fetchColumn('select saldo from users where id = ?', array($user_id));
	$min = $db->fetchColumn('select sum(amount) from transactions where id_from = ?', array($user_id));
	$plus = $db->fetchColumn('select sum(amount) from transactions where id_to = ?', array($user_id));
	
	$new_balance = $plus - $min;

	if ($new_balance != $balance){
		$db->update('users', array('saldo' => $new_balance), array('id' => $userid));	
	}

}


?>

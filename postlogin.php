<?php

// PROCESS THE LOGIN CREDENTIALS AND BUILD THE SESSION

ob_start();

require_once('./includes/inc_default.php');
require_once('./includes/inc_adoconnection.php');
require_once('./includes/inc_userinfo.php');
require_once('./includes/inc_eventlog.php');
require_once('./includes/inc_auth.php');

$myuser = get_user_maildetails_by_login($_POST['login']);

$redirect = NULL;
if($_POST['location'] != '') {
    $redirect = $_POST['location'];
}

$url = 'login.php';

if ($myuser['password'] == hash('sha512', $_POST['password']) || $myuser['password'] == md5($_POST['password']) || $myuser["password"] == sha1($_POST['password'])){
	if($myuser['status'] == 0){
		setstatus('Gebruiker is gedeactiveerd');
		$url .= ($redirect) ? '&location='.urlencode($redirect) : '';
		header('location: '.$url);
		exit;		
		
	} else {
		if(readconfigfromdb("maintenance") == 1 && $myuser["accountrole"] != "admin"){
			setstatus('eLAS is in onderhoud, probeer later opnieuw', 'info');
			$url .= ($redirect) ? '&location='.urlencode($redirect) : '';
			header('location: '.$url);
			exit;
	
        } else {
			startsession($myuser);
			$url = ($redirect) ? $redirect : 'messages.php';
			header('Location: '.$url);
			exit;			
		}
	}
} else {
	$uname = $_POST['login'];
	log_event($s_id,'LogFail', 'Login for user '.$uname.' with password failed');
	$url .= ($redirect) ? '?location='.urlencode($redirect) : '';
	header('location: '.$url);	
	exit;
}



?>

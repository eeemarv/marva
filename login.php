<?php
ob_start();

$rootpath = './';
require_once($rootpath.'includes/inc_default.php');
require_once($rootpath.'includes/inc_adoconnection.php');
require_once($rootpath.'includes/inc_eventlog.php');
require_once($rootpath.'includes/inc_userinfo.php');
require_once($rootpath.'includes/inc_auth.php');
require_once($rootpath.'includes/inc_tokens.php');

require_once($rootpath.'includes/request.php');

$req = new request('anonymous', true);

$req->add('letscode', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 50, 'label' => 'Letscode'), array('not_empty' => true, 'active_letscode' => true))
	->add('password', '', 'post', array('type' => 'password', 'size' => 50, 'maxlength' => 50, 'label' => 'Paswoord'), array('not_empty' => true))
	->add('submit_login', '', 'post', array('type' => 'submit', 'label' => 'Login'))
	->add('token', '', 'get')
	->add('location', 'messages.php', 'get|post', array('type' => 'hidden'));




$location = ltrim($req->get('location'), '/');
$location = ($location) ? $location : 'messages.php';
$location = ($location == 'login.php') ? 'messages.php' : $location;
$location = urlencode($location); 

$login_redirect = 'login.php?location='.$location;

/*
if ($req->getSid()){
	header('location : '.$location);
			
}
*/ 

if($req->get('token')){
	if(verify_token($req->get('token'),'guestlogin') == 0){
		$_SESSION['id'] = 0;
		$_SESSION['name'] = 'letsguest';
		$_SESSION['letscode'] = 'X000';
		$_SESSION['accountrole'] = 'guest';
		$_SESSION['type'] = 'interlets';
		$_SESSION['status'] = array();
		log_event($_SESSION['id'],'Login','Guest login using token succeeded');
		setstatus($_SESSION['name'] .' ingelogd', 'success');
		setstatus('Je bent ingelogd als LETS-gast, je kan informatie raadplegen maar niets wijzigen of transacties invoeren.  
			Als guest kan je ook niet rechtstreeks reageren op V/A of andere mails versturen uit Marva', 'info');
		header('location : '.$location);
		exit;
	} else {
		setstatus('Interlets login is mislukt', 'error');
		log_event("","LogFail", "Token login failed ($token)");
	}
}


if ($req->isPost()){
	if (!$req->errors){
		$user = get_user_by_letscode($req->get('letscode'));
		
		if ($user['password'] == hash('sha512', $_POST['password']) 
			|| $user['password'] == md5($_POST['password']) 
			|| $user["password"] == sha1($_POST['password'])){
				
			if($user['status'] == 0){
				setstatus('Gebruiker is gedeactiveerd', 'error');
				header('location: '.$login_redirect);
				exit;		
				
			} else {
				if(readconfigfromdb("maintenance") == 1 && $myuser["accountrole"] != "admin"){
					setstatus('eLAS is in onderhoud, probeer later opnieuw', 'info');
					header('location: '.$login_redirect);
					exit;
			
				} else {

					$_SESSION['id'] = $user['id'];
					$_SESSION['name'] = $user['name'];
					$_SESSION['fullname'] = $user['fullname'];
					$_SESSION['login'] = $user['login'];
					$_SESSION['letscode'] = $user["letscode"];
					$_SESSION['accountrole'] = $user['accountrole'];
					$_SESSION['userstatus'] = $user['status'];
					$_SESSION['email'] = $user['emailaddress'];
					$_SESSION['lang'] = $user['lang'];
					$_SESSION['user_postcode'] = $user['postcode'];
					$_SESSION['type'] = 'local';
					$_SESSION['status'] = array();

					$browser = $_SERVER['HTTP_USER_AGENT'];
					log_event($user["id"],"Login","User logged in");
					log_event($user["id"],"Agent","$browser");				
					$db->AutoExecute('users', array('lastlogin' => date('Y-m-d H:i:s')), 'UPDATE', 'id='.$s_id);		
					setstatus($req->get('letscode').' '.$user['name'].' ingelogd.', 'success');
	
					if ($user['accountrole'] == 'admin'){				
						$result = $db->Execute('select * from letsgroups where apimethod = \'internal\'');
						if ($result->RecordCount()){
							setstatus('Er bestaat geen LETS groep met type intern voor je eigen groep.  
								Voeg die toe onder beheer LETS Groepen.', 'warning');
						}					
					}
					
					header('Location: '.$location);
					exit;			
				}
			}
		} else {
			$uname = $_POST['login'];
			log_event($s_id, 'LogFail', 'Login for user '.$req->get('letscode').' '.$user['name'].' with password failed');
			setstatus('login niet gelukt.', 'danger');
			header('location: '.$login_redirect);	
			exit;
		}	 
	}
}

if(readconfigfromdb("maintenance") == 1){
	setstatus('Marva is niet beschikbaar wegens onderhoud.  Enkel admin gebruikers kunnen inloggen', 'warning');
}

require_once($rootpath.'includes/inc_header.php');

echo '<h1><a href="login.php">Login</a></h1>';

if(!$req->get('token')){
	echo '<form method="post" class="trans" action="login.php">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	$req->set_output('tr')->render(array('letscode', 'password'));
	echo '<tr><td colspan="2">';
	$req->set_output('nolabel')->render(array('submit_login', 'location'));
	echo '</td></tr></table></form>';			
}


include($rootpath.'includes/inc_footer.php');



?>


<?php
ob_start();

$rootpath = './';

require_once 'includes/default.php';


require_once 'includes/userinfo.php';

require_once 'includes/request.php';

$req = new request('anonymous', true);

$req->setEntity('login')
	->setUrl('login.php')
	->add('letscode', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 50, 'label' => 'Letscode'), array('required' => true, 'active_letscode' => true))
	->add('password', '', 'post', array('type' => 'password', 'size' => 50, 'maxlength' => 50, 'label' => 'Paswoord'), array('required' => true))
	->add('submit_login', '', 'post', array('type' => 'submit', 'label' => 'Login', 'class' => 'btn btn-primary'))
	->add('cancel', '', 'post', array('type' => 'submit', 'label' => 'Annuleren', 'class' => 'btn btn-default'))
	->add('token', '', 'get')
	->add('location', 'messages.php', 'get|post', array('type' => 'hidden'));


if ($req->get('cancel')){
	header('location: .');
	exit;
}

$location = ltrim(urldecode($req->get('location')), '/');
$location = ($location) ? $location : 'messages.php';
$location = ($location == 'login.php') ? 'messages.php' : $location;

$login_redirect = 'login.php?location='.urlencode($location);



if($req->get('token')){
	if ($db->fetchColumn('select token from tokens where token = ? and validity > ?', array($req->get('token'), time()))){
		$_SESSION['id'] = 0;
		$_SESSION['name'] = 'letsguest';
		$_SESSION['letscode'] = 'X000';
		$_SESSION['accountrole'] = 'guest';
		$_SESSION['type'] = 'interlets';
		$_SESSION['status'] = array();
		log_event($_SESSION['id'],'Login','Guest login using token succeeded');
		setstatus($_SESSION['name'] .' ingelogd', 'success');
		setstatus('Je bent ingelogd als LETS-gast, je kan informatie raadplegen maar niets wijzigen of transacties invoeren.  
			Als guest kan je ook niet rechtstreeks reageren op V/A of andere mails versturen.', 'info');
		header('location : '.$location);
		exit;
	} else {
		setstatus('Interlets login is mislukt.', 'danger');
		log_event('','LogFail', 'Token login failed ('.$req->get('token').')');
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
				if($parameters['maintenance'] && !$req->isAdmin()){
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
									
					$db->update('users', array('lastlogin' => date('Y-m-d H:i:s')), array('id', $user['id']));		//
					setstatus($req->get('letscode').' '.$user['name'].' ingelogd.', 'success');
				
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

if($parameters['maintenance']){
	setstatus('Marva is niet beschikbaar wegens onderhoud.  Enkel admin gebruikers kunnen inloggen', 'warning');
}

require_once 'includes/header.php';

echo '<h1><a href="login.php">Inloggen</a></h1>';

if (file_exists('site/login_content.html')){
	include 'site/login_content.html';
}

if(!$req->get('token')){
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render(array('letscode', 'password'));
	echo '<div>';
	$req->set_output('nolabel')->render(array('submit_login', 'cancel', 'location'));
	echo '</div></form>';
	echo '<ul><li><a href="passwordlost.php">Paswoord vergeten</a></li></ul>';			
}


include 'includes/footer.php';



?>


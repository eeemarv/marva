<?php
ob_start();
$rootpath = "./";
require_once 'includes/default.php';

require_once 'includes/userinfo.php';
require_once 'includes/passwords.php';




require_once 'includes/request.php';



$req = new request('anonymous', true);


$req->add('email', '', 'post', array('type' => 'text', 'label' => 'Email adres', 'size' => 50, 'maxlength' => 50), array('not_empty' => true, 'email' => true, 'match' => 'email_active_user'))
	->add('send', '', 'post', array('type' => 'submit', 'label' => 'Reset Paswoord', 'class' => 'btn btn-primary'))
	->add('cancel', '', 'post', array('type' => 'submit', 'label' => 'Annuleren', 'class' => 'btn btn-default'));

if ($req->get('cancel')){
	header('location: .');
	exit;
}

if ($req->get('send') && !$req->errors()){
	$email = $req->get('email');
	log_event($s_id, "System", "Activation request for ".$email);
	
	$query = "SELECT * FROM contact WHERE value = '" .$email ."'";
	$contact = $db->GetRow($query); 

	if(!empty($contact["value"])){
		$user = get_user_maildetails($contact["id_user"]);
		$posted_list["pw1"] = generatePassword();
		if(update_password($contact["id_user"], $posted_list) == TRUE){
			sendactivationmail($posted_list["pw1"], $user,0);
			log_event($s_id,"System","Account " .$user["login"] ." reactivated");
			setstatus('Een email voor paswoord-reset werd naar je inbox verzonden.', 'success');
			header('Location: '.$rootpath);
			exit;
		} else {
			setstatus("Heractivatie mislukt, contacteer de beheerder", 'danger');
			log_event($s_id,"System","Account " .$user["login"] ." activation failed");
		}
	} else {
		log_event($s_id,"System","Activation request for unkown mail " .$email);
		setstatus("E-mail adress " .$email ." niet gevonden", 'danger');
	}	
}

	
require_once('includes/header.php');

echo '<h1><a href="passwordlost.php">Nieuw paswoord aanvragen</a></h1>';

if (file_exists('site/passwordlost_content.html')){
	include 'site/passwordlost_content.html';
}

echo '<form method="post" class="trans form-horizontal" role="form">';
$req->set_output('formgroup')->render(array('email', 'recaptcha'));
echo '<div>';
$req->set_output('nolabel')->render(array('send', 'cancel'));
echo '</div></form>';

        
require_once('includes/footer.php');

?>


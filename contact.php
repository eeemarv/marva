<?php
ob_start();

$rootpath = "./";

require_once($rootpath.'includes/default.php');

require_once($rootpath.'includes/inc_content.php');

require_once($rootpath.'includes/request.php');

$req = new request('anonymous', true);

$req->setEntity('contact')
	->add('email', '', 'post', array('type' => 'text', 'label' => 'Email adres', 'size' => 50, 'maxlength' => 50), array('not_empty' => true, 'email' => true))
	->add('subject', '', 'post', array('type' => 'text', 'label' => 'Onderwerp', 'size' => 50, 'maxlength' => 60), array('not_empty' => true))
	->add('content', '', 'post', array('type' => 'textarea', 'label' => 'Bericht', 'cols' => 50, 'rows' => 7), array('not_empty' => true))
	->add('recaptcha', '', 'post', array('type' => 'recaptcha', 'label' => 'Recaptcha'), array( 'match' => 'recaptcha', 'not_empty' => true))
	->addSubmitButtons();

if ($req->get('cancel')){
	$location = ($req->getSid()) ? 'messages.php' : '.';
	header('location: '.$location);
	exit;
}

if ($req->get('send') && !$req->errors()){
	
	
	
	
}	



require_once 'includes/header.php';

echo '<h1><a href="contact.php">Contact Beheer</a></h1>';

echo '<form method="post" class="trans form-horizontal" role="form">';
$email = ($req->getSid()) ? 'non_existing_dummy_1' : 'email';
$recaptcha = ($req->getSid()) ? 'non_existing_dummy_2' : 'recaptcha';
$req->set_output('formgroup')->render(array($email, 'subject', 'content', $recaptcha));
echo '<div>';
$req->set_output('nolabel')->render(array('send', 'cancel'));
echo '</div></form>';

        
require_once 'includes/footer.php';

/*
        if(!empty($error_list)){
                show_form($user["login"],$user["emailaddress"],$error_list,$posted_list);
        }else{
		HelpMail($posted_list,$rootpath);
        }
*/






function checkmailaddress($email){
	global $db;
	$query = "SELECT contact.value FROM contact, type_contact WHERE id_type_contact = type_contact.id and type_contact.abbrev = 'mail' AND contact.value = '" .$email ."'";
	$checkedaddress = $db->GetRow($query);
	return $checkedaddress;
}

function get_user_maildetails($userid){
        global $db;
        $query = "SELECT * FROM users WHERE id = $userid";
        $user = $db->GetRow($query);
        $query = "SELECT * FROM contact, type_contact WHERE id_user = $userid AND id_type_contact = type_contact.id and type_contact.abbrev = 'mail'";
        $contacts = $db->GetRow($query);
        $user["emailaddress"] = $contacts["value"];

        return $user;
}


function helpmail($posted_list,$rootpath){
   	global $configuration;
	global $elas;
	global $elasversion;
	global $parameters;

	$mailfrom .= "From: " .trim($posted_list['email']);
        if (!empty($configuration["mail"]["support"])){
		$mailto .= trim($configuration["mail"]["support"])."\r\n";
        }else { 
		 Echo "No support adress set in config, not sending";
		 return 0;
	}

	$mailsubject = $parameters['code'] ." - " .$posted_list['subject'];

        $mailcontent  = "-- via de Marva website werd het volgende bericht ingegeven --\r\n";
	$mailcontent .= "E-mail: {$posted_list['email']}\r\n";
	$mailcontent .= "Login:  {$posted_list['login']}\r\n";
	$mailcontent .= "Omschrijving:\r\n";
	$mailcontent .= "{$posted_list['omschrijving']}\r\n";
	$mailcontent .= "\r\n";
	$mailcontent .= "User Agent:\r\n";
        $mailcontent .= "{$posted_list['browser']}\r\n";
	$mailcontent .= "\r\n";
    mail($mailto,$mailsubject,$mailcontent,$mailfrom);
	setstatus("bericht verstuurd", 'success');

}

?>


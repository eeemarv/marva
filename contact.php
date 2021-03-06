<?php
ob_start();

$rootpath = "./";

require_once 'includes/default.php';

require_once 'includes/mail.php';

require_once 'includes/request.php';

$req = new request('anonymous', true);

$req->setEntity('contact')
	->add('email', '', 'post', array('type' => 'text', 'label' => 'Email adres', 'size' => 50, 'maxlength' => 50), array('required' => true, 'email' => true))
	->add('subject', '', 'post', array('type' => 'text', 'label' => 'Onderwerp', 'size' => 50, 'maxlength' => 60), array('required' => true))
	->add('content', '', 'post', array('type' => 'textarea', 'label' => 'Bericht', 'rows' => 7), array('required' => true))
	->add('mailcc', 'checked', 'post', array('type' => 'checkbox', 'label' => 'Stuur een kopie naar mezelf'))
	->add('recaptcha', '', 'post', array('type' => 'recaptcha', 'label' => 'Recaptcha'), array('recaptcha' => true))
	->addSubmitButtons();

if ($req->get('cancel')){
	$location = ($req->getSid()) ? 'messages.php' : '.';
	header('location: '.$location);
	exit;
}

if ($req->get('send') && !$req->errors()){
	
	
	
	
}	



require_once 'includes/header.php';

$support = ($req->getSid()) ? ' support' : '';

echo '<h1><a href="contact.php">Contact '.$support.'</a></h1>';

$include =  ($req->getSid()) ? 'support' : 'contact';

if (file_exists('site/'.$include.'_content.html')){
	include 'site/'.$include.'_content.html';
}

echo '<form method="post" class="trans form-horizontal" role="form">';
$email = ($req->getSid()) ? 'non_existing_dummy_1' : 'email';
$recaptcha = ($req->getSid()) ? 'non_existing_dummy_2' : 'recaptcha';
$req->set_output('formgroup')->render(array($email, 'subject', 'content', $recaptcha));
$req->set_output('formgroupcheckbox')->render('mailcc');
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

        $mailcontent  = "-- via de website werd het volgende bericht ingegeven --\r\n";
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


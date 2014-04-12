<?php
// copyleft 2014 martti <info@martti.be>


/**
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
* see LICENSE
*/


/** Provided functions:

 * generatePassword ($length = 10)i			Generate a random password
 * update_password($id, $posted_list)			Write password to database
 * sendactivationmail($password, $user,$s_id)		Send the password to the user
 * sendpasswordresetemail($password, $user,$s_id)	Send the password reset message to the user
*/

function sendpasswordresetemail($password, $user, $s_id){
	global $baseurl, $parameters;
	
	$from = $parameters['mail']['noreply'];

	if (!empty($user["emailaddress"])){
			$to = $user["emailaddress"];
	} else {
			echo "<p><b>Geen E-mail adres bekend voor deze gebruiker, stuur het wachtwoord op een andere manier door!</b></p>";
			return 0;
	}

	$subject = '['.$parameters['letsgroup_code'].'] Marva account';

	$content  = "*** Dit is een automatische mail van het Marva systeem van ";
	$content .= $parameters['letsgroup_code'].' '.$parameters['letsgroup_name'].' ***\r\n\n';
	$content .= "Beste ";
	$content .= $user["name"];
	$content .= "\n\n";
	$content .= "Marva heeft voor jouw een nieuw wachtwoord ingesteld, zodat je (weer) kan inloggen op http://$baseurl.\n";
	$content .= "\neLAS is een elektronisch systeem voor het beheer van vraag & aanbod en transacties.
		Er werd voor jou een account aangemaakt waarmee je kan inloggen en je gegevens beheren.\n\n";
	$content .= "\n-- Account gegevens --\n";
	$content .= "Login: ";
	$content .= $user["login"];
	$content .= "\nPasswoord: ";
	$content .= $password;
	$content .= "\n-- --\n\n";
	


	$content .= "Als je nog vragen of problemen hebt, kan je terecht bij ";
	$content .= $parameters['mail']['support'];
	$content .= "\n\n";
	$content .= "Met vriendelijke groeten.\n\nDe Marva Account robot\n";

	sendemail($from, $to, $subject, $content);
	log_event($s_id, "Mail", "Password reset email sent to $to");
	$status = "OK - Een nieuw wachtwoord is verstuurd via email";
	return $status;
}


function sendactivationmail($password, $user, $s_id){
	global $baseurl, $parameters;
	$mailfrom = $parameters['mail']['noreply'];

	if (!empty($user["emailaddress"])){
			$mailto = $user["emailaddress"];
	} else {
			echo "<p><b>Geen E-mail adres bekend voor deze gebruiker, stuur het wachtwoord op een andere manier door!</b></p>";

			return 0;
	}


	$subject = '['.$paramters['letsgroup_code'].'] '.$parameters['letsgroup_name'].' account activatie';

	$content  = '*** Dit is een automatische mail ***\r\n\n';
	$content .= 'Beste '.$user['name'].'\n\n';
	$content .= 'Welkom bij Letsgroep '.$parameters['letsgroup_name'];
	$content .= ". Surf naar $systemtag via http://$baseurl" ;
	$content .= " en meld je aan met onderstaande gegevens.\n";
	$content .= "\n-- Account gegevens --\n";
	$content .= "Login: ".$user["login"];
	$content .= "\nPasswoord: ".$password."\n-- --\n\n";
        

	$content .= "Je kan je gebruikersgevens, vraag&aanbod en lets-transacties";
	$content .= " zelf bijwerken op de website.";
	$content .= "\n\n";


	$content .= "Als je nog vragen of problemen hebt, kan je terecht bij ";
	$content .= $parameters['mail']['support'];
	$content .= "\n\n";
	$content .= "Veel plezier bij het letsen! \n";


	sendemail($from, $to, $subject, $content);

	log_event($s_id, "Mail", "Activation mail sent to $mailto");

	echo "OK - Activatiemail verstuurd";
}



function update_password($id, $posted_list){
	global $db;
	$posted_list["password"]=hash('sha512',$posted_list["pw1"]);
	$posted_list["mdate"] = date("Y-m-d H:i:s");
	$result = $db->AutoExecute("users", $posted_list, 'UPDATE', "id=$id");
	if($result == true){
		setstatus('Passwoord gewijzigd','success');
	} else {
		setstatus('Passwoord niet gewijzigd', 'danger');
	}
        return $result;
}

function generatePassword ($length = 10)
{
    srand((double)microtime()*1000000);  
    $number = rand(0,9);
      
    $vowels = array("a", "e", "i", "o", "u");  
    $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr", 
    "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl");  
      
    $num_vowels = count($vowels);  
    $num_cons = count($cons);  
      
    for($i = 0; $i < $length; $i++){  
        $password .= $cons[rand(0, $num_cons - 1)] . $vowels[rand(0, $num_vowels - 1)];  
    }  
      
    $word = substr($password, 0, ($length - 1));
    return $word . $number;
}


?> 

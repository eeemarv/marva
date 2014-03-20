<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_userinfo.php");
require_once($rootpath."includes/inc_passwords.php");
require_once $rootpath.'includes/mail.php';

require_once($rootpath.'includes/request.php');

$req = new request('admin');

// Array ( [letsgroup] => LETS Test [letscode_to] => 1 [letscode_from] => 1 [amount] => 2 [minlimit] => -500 [balance] => -540 [description] => 3 ) 



$mode = $_POST["mode"];
//$posted_list["id"] = $_POST["id"];
$id = $_POST["id"];
$posted_list["name"] = $_POST["name"];
$posted_list["fullname"] = $_POST["fullname"];
$posted_list["letscode"] = $_POST["letscode"];
$posted_list["postcode"] = $_POST["postcode"];
$posted_list["birthday"] = $_POST["birthday"];
$posted_list["hobbies"] = $_POST["hobbies"];
$posted_list["comments"] = $_POST["comments"];
$posted_list["login"] = $_POST["login"];
$posted_list["accountrole"] = $_POST["accountrole"];
$posted_list["status"] = $_POST["status"];
$posted_list["admincomment"] = $_POST["admincomment"];
$posted_list["minlimit"] = $_POST["minlimit"];
if($_POST["maxlimit"] == 0) {
	$posted_list["maxlimit"] = NULL;
} else {
	$posted_list["maxlimit"] = $_POST["maxlimit"];
}
$posted_list["presharedkey"] = $_POST["presharedkey"];

$email = $_POST["email"];
$address = $_POST["address"];
$telephone = $_POST["telephone"];
$gsm = $_POST["gsm"];

$activate = $_POST["activate"];

if($mode == "new"){
	$error_list = validate_input($posted_list);
}
if($mode == "edit"){
	$error_list = validate_input_onedit($id, $posted_list);
}
if (empty($error_list)){
	switch ($mode){
	        case "new":
			$result = insert_user($posted_list);
                        if($result == TRUE) {
                                setstatus("<font color='green'><strong>OK</font> - Gebruiker is opgeslagen", 'succes');
				// After save, create the contact records
				// $abbrev, $value, $id
				$myuser = get_user_by_letscode($posted_list["letscode"]);
				//$myuser = get_user_maildetails($tmpuser["id"]);
				if(!empty($email)){	
					$result1 = create_contact("mail", $email, $myuser["id"]);
				} else {	
					$result1 = TRUE;
				}
				if(!empty($address)){
					$result2 = create_contact("adr", $address, $myuser["id"]);
				} else {
                                        $result2 = TRUE;
                                }
				if(!empty($telephone)){
                                	$result3 = create_contact("tel", $telephone, $myuser["id"]);
				} else {
                                        $result3 = TRUE;
                                }
                                if(!empty($gsm)){
                                	$result4 = create_contact("gsm", $gsm, $myuser["id"]);
				} else {
                                        $result4 = TRUE;
                                }
				if($result1 == TRUE && $result2 == TRUE && $result3 == TRUE && $result4 == TRUE) {
					setstatus("OK - Contactgegevens opgeslagen", 'success');
				} else {
					setstatus("Fout bij het opslaan van de contactgegevens", 'danger');
				}	
				// Activate the user if activate is set
				if($activate == "true"){
					$mailuser = get_user_maildetails($myuser["id"]);
					$pw = generatePassword();
					$posted_list["password"]= hash('sha512',$pw);
					$activateresult = set_pw($mailuser["id"], $posted_list);
					if($activateresult == TRUE) {
		                 setstatus("OK- Gebruiker is geactiveerd met password $pw", 'success');
						// Now send a mail
						if(!empty($email)){
							sendactivationmail($pw, $mailuser, $s_id);
							sendadminmail($posted_list, $mailuser);
						}
					} else {
						setstatus('Fout bij het activeren van gebruiker', 'error');
					}
				}
                        } else {
                                setstatus('Fout bij het opslaan van de gebruiker', 'error');
                        }

                        
                        break;
		case "edit":
			$result = update_user($id, $posted_list);
			if($result == TRUE) {
				setstatus("<font color='green'><strong>OK</font> - Gebruiker $id aangepast", 'success');		
			} else {
				setstatus("<font color='red'><strong>Fout bij de update van gebruiker $id", 'danger');
			}
			header("Location: ".$rootpath."users/overview.php");
			exit;
			break;
	}
} else {
	echo "<font color='red'><strong>Fout: ";
        foreach($error_list as $key => $value){
		echo $value;
		echo " | ";
	}
	echo "</strong></font>";
}



///////////// FUNCTIONS //////////////////
function update_user($id, $posted_list){
    global $db;
    $posted_list["mdate"] = date("Y-m-d H:i:s");
    $result = $db->AutoExecute("users", $posted_list, 'UPDATE', "id=$id");
    return $result;
}

function set_pw($id, $posted_list){
        global $db;
        //$posted_list["password"]= 
	$posted_list["adate"] = date("Y-m-d H:i:s");
	$result = $db->AutoExecute("users", $posted_list, 'UPDATE', "id=$id");
        return $result;
}

function insert_user($posted_list){
        global $db;
	global $s_id;
	$posted_list["cdate"] = date("Y-m-d H:i:s");
	$posted_list["adate"] = date("Y-m-d H:i:s");
	$posted_list["creator"] = $s_id;
        $result = $db->AutoExecute("users", $posted_list, 'INSERT');
	return $result;
}

function create_contact($abbrev, $value, $id){
	global $db;
	$contacttype = get_contacttype($abbrev);
	$posted_list["id_type_contact"] = $contacttype["id"];
	$posted_list["value"] = $value;
	$posted_list["id_user"] = $id;
	$posted_list["flag_public"] = 1;
	$result = $db->AutoExecute("contact", $posted_list, 'INSERT');
        return $result;
}

function validate_username($name, &$error_list){
        if (!isset($name)|| $name==""){
		$error_list["name"]="Naam is niet ingevuld";
	}
}

function validate_letscode($letscode, $id, &$error_list){
	global $db;
	$query = "SELECT * FROM users ";
	$query .= "WHERE TRIM(letscode)  <> '' ";
	$query .= "AND TRIM(letscode) = '".$letscode."'";
	$query .= " AND status <> 0 ";
	$query .= " AND id <> '".$id."'";
	$rs=$db->Execute($query);
	$number2 = $rs->recordcount();

	if ($number2 !== 0){
		$error_list["letscode"]="Letscode $letscode bestaat al";
	}
}

function validate_login($login, $id, &$error_list){
	global $db;
	$query = "SELECT * FROM users WHERE login = '".$login."' AND id <> '".$id."'";
	$rs=$db->Execute($query);
	$number = $rs->recordcount();

	if ($number !== 0){
		$error_list["login"]="Login ".$login." bestaat al!";
		//$user = get_user_maildetails($id);
		//$email = $user["emailaddress"];	
		//$error_list["login"].=" <br>Suggestie: Het e-mail adres van deze gebruiker ";
		//$error_list["login"].=" (".$email.")";
		//$error_list["login"].=" is een geschikte kandidaat om als unieke login naam te dienen.";
	}
}

function validate_input_onedit($id, $posted_list){
	$error_list = array();
	validate_username($posted_list["name"], $error_list);
	validate_letscode($posted_list["letscode"], $id, $error_list);
        if (!empty($posted_list["login"])){
		validate_login($posted_list["login"], $id, $error_list);
        }
	return $error_list;
}

function validate_input($posted_list){
        $error_list = array();

	validate_username($posted_list["name"], $error_list);
	validate_letscode($posted_list["letscode"], -1, $error_list);
        if (!empty($posted_list["login"])){
		validate_login($posted_list["login"], $id, $error_list);
        }

        //amount may not be empty
        $var = trim($posted_list["minlimit"]);
        if (empty($posted_list["minlimit"])|| (trim($posted_list["minlimit"] )=="")){
                $error_list["minlimit"]="Minlimiet is niet ingevuld";
        //amount amy only contain  numbers between 0 en 9
        }elseif(eregi('^-[0-9]+$', $var) == FALSE){
                $error_list["minlimit"]="Minlimiet moet een negatief getal zijn";
        }

	//if (empty($posted_list["maxlimit"])){
	//	$error_list["maxlimit"]="Maxlimiet is niet ingevuld";
	//}
	return $error_list;
}

function sendadminmail($posted_list, $user){
        global $configuration;

	$mailfrom = trim(readconfigfromdb("from_address"));	
	$mailto = trim(readconfigfromdb("admin"));
	$systemtag = readconfigfromdb("systemtag");

        $mailsubject = "[";
        $mailsubject .= $configuration["system"]["systemtag"];
        $mailsubject .= "] Marva account activatie";

        $mailcontent  = "*** Dit is een automatische mail van het Marva systeem van ";
        $mailcontent .= $systemtag;
        $mailcontent .= " ***\r\n\n";
        $mailcontent .= "De account ";
        $mailcontent .= $user["login"];
        $mailcontent .= " werd geactiveerd met een nieuw passwoord.\n";
        if (!empty($user["emailaddress"])){
                $mailcontent .= "Er werd een mail verstuurd naar de gebruiker op ";
                $mailcontent .= $user["emailaddress"];
                $mailcontent .= ".\n\n";
        } else {
                $mailcontent .= "Er werd GEEN mail verstuurd omdat er geen E-mail adres bekend is voor de gebruiker.\n\n";
        }

        $mailcontent .= "OPMERKING: Vergeet niet om de gebruiker eventueel toe te voegen aan andere LETS programma's zoals mailing lists.\n\n";
        $mailcontent .= "Met vriendelijke groeten\n\nDe Marva account robot\n";

        sendemail($mailfrom,$mailto,$mailsubject,$mailcontent);
        
}


?>


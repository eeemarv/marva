<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_passwords.php");

session_start();
$s_id = $_SESSION["id"];
$s_name = $_SESSION["name"];
$s_letscode = $_SESSION["letscode"];
$s_accountrole = $_SESSION["accountrole"];

if (isset($s_id)){
	$posted_list = array();
	$posted_list["pw1"] = $_POST["pw1"];
	$posted_list["pw2"] = $_POST["pw2"];
	$errorlist = validate_input($posted_list);
			
	if (!empty($errorlist)){
		echo "<font color='red'><strong>Fout: ";
		foreach($errorlist as $key => $value){
			echo $value;
			echo " | ";
		}
		echo "</strong></font>";
	} else {
		if(update_password($s_id, $posted_list) == true){
			echo "<font color='green'><strong>OK</font> - Passwoord opgeslagen</strong>";
		} else {
			echo "<font color='red'><strong>Fout bij het opslaan</strong></font>";
		}
	}
}


////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////

function validate_input($posted_list){
	$errorlist = array();
	if (empty($posted_list["pw1"]) || (trim($posted_list["pw1"]) == "")){
		$errorlist["pw1"] = "Passwoord 1 is niet ingevuld";
	}

	$pwscore = Password_Strength($posted_list["pw1"]);
	$pwreqscore = readconfigfromdb("pwscore");
	if ($pwscore < $pwreqscore){
		$errorlist["pw1"] = "Paswoord is te zwak (score $pwscore/$pwreqscore), kies een passwoord dat lang genoeg is (8 tekens) en gebruik hoofdletters, cijfers en eventueel een leesteken";
	}
	if (empty($posted_list["pw2"]) || (trim($posted_list["pw2"]) == "")){
		$errorlist["pw2"] = "Passwoord 2 is niet ingevuld";
	}
	if ($posted_list["pw1"] !== $posted_list["pw2"]){
		$errorlist["pw3"] = "De 2 passwoorden zijn niet hetzelfde";
	}
	return $errorlist;
}
?>

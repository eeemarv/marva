<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
session_start();
$s_id = $_SESSION["id"];
$s_name = $_SESSION["name"];
$s_letscode = $_SESSION["letscode"];
$s_accountrole = $_SESSION["accountrole"];

include($rootpath."includes/inc_header.php");
include($rootpath."includes/inc_nav.php");

echo "<script type='text/javascript' src='$rootpath/js/moomydetails.js'></script>";

if (isset($s_id)){
	show_ptitle();
	$user = get_user($s_id);
	show_user();
	show_editlink();
	show_pwform();
	show_contact();
	show_contactadd();
	$balance = $user["saldo"];
	show_balance($balance, $user, $configuration["system"]["currency"]);
}else{
	redirect_login($rootpath);
}

// functions


function show_changepwlink($s_id){
	echo "<p>| <a href='mydetails_pw.php?id=" .$s_id. "'>Paswoord veranderen</a> |</p>";
}

function get_type_contacts(){
	global $db;
	$query = "SELECT * FROM type_contact ";
	$result = mysql_query($query) or die("select type_contact lukt niet");
	$typecontactrow = $db->GetArray($query);
	return $typecontactrow;
}


function show_contactadd(){
	global $rootpath;
	global $s_id;
	echo "<div id='contactformdiv' class='hidden'>";
	echo "<form action='". $rootpath ."/userdetails/postcontact.php' id='contactform' method='post'>";
	echo "<input type='hidden' name='contactmode' value='new'>";
	echo "<input type='hidden' name='id_user' value='" .$s_id ."'>";
	echo "<input type='hidden' name='contactid' value=''>";
	echo "<table class='selectbox' cellspacing='0' cellpadding='0' border='0'>\n\n";
        echo "<tr>\n";
        echo "<td valign='top' align='right'>Type</td>\n";
        echo "<td>";
        echo "<select name='id_type_contact'>\n";
	$typecontactrow = get_type_contacts();
        foreach($typecontactrow as $key => $value){
                echo "<option value='".$value["id"]."'>".$value["name"]."</option>\n";
        }
        echo "</select>\n</td>\n";
        
        echo "</tr>\n\n<tr>\n<td></td>\n<td>";
        echo "</td>\n";
        echo "</tr>\n\n";
        
        echo "<tr>\n";
        echo "<td valign='top' align='right'>Waarde</td>\n";
        echo "<td>";
        echo "<input type='text' name='value' size='80'>";
        echo "</td>\n";
        echo "</tr>\n\n<tr>\n<td></td>\n<td>";
        echo "</td>\n";
        echo "</tr>\n\n";
        
        echo "<tr>\n";
        echo "<td valign='top' align='right'>Commentaar</td>\n";
        echo "<td>";
        echo "<input type='text' name='comments' size='50' ";
        echo "</td>\n";
        echo "</tr>\n\n<tr>\n<td></td>\n<td>";
        echo "</td>\n";
        echo "</tr>\n\n";

	echo "<tr>\n";
        echo "<td valign='top' align='right'></td>\n";
        echo "<td>";
        echo "<input type='checkbox' name='flag_public' CHECKED";
        echo " value='1' >Ja, dit contact mag zichtbaar zijn voor iedereen";

        echo "</td>\n";
        echo "</tr>\n\n<tr>\n<td></td>\n<td>";
        echo "</td>\n";
        echo "</tr>\n\n";


        echo "<tr>\n<td colspan='2' align='right'><input type='submit' name='zend' value='Opslaan'>";
        echo "</td>\n</tr>\n\n";
        echo "</table></form></div>";
}

function show_pwform(){
        global $s_id;
        echo "<div id='pwformdiv' class='hidden'>";
	echo "<form action='". $rootpath ."/userdetails/postpassword.php' id='pwform' method='post'>";
        echo "<table class='selectbox' cellspacing='0' cellpadding='0' border='0'>";
        echo "<tr><td valign='top' align='right'>Paswoord</td>";
        echo "<td valign='top'>";
        echo "<input  type='text' id='pw1' name='pw1' size='30'>";
        echo "</td>";
        echo "</tr>";
        echo "<tr><td valign='top' align='right'>Herhaal paswoord</td>";
        echo "<td valign='top'>";
        echo "<input  type='test' id='pw2' name='pw2' size='30'>";
        echo "</td>";
        echo "</tr>";
        echo "<tr><td colspan='2' align='right'>";
        echo "<input type='submit' id='zend' value='Passwoord wijzigen' name='zend'>";
        echo "</td><td>&nbsp;</td></tr>";
        echo "</table>";
        echo "</form>";
        echo "</div>";

}


function show_editlink(){
	global $s_id;
	echo "<table width='100%' border=0><tr><td>";
	echo "<div id='navcontainer'>";
	echo "<ul class='hormenu'>";
	$myurl="mydetails_edit.php?id=" .$s_id;
	echo "<li><a href='#' onclick=window.open('$myurl','details_edit','width=640,height=480,scrollbars=yes,toolbar=no,location=no,menubar=no')>Gegevens aanpassen</a></li>";
	$myurl="mydetails_pw.php";
	echo "<li><a href='#' id='showpwform'>Passwoord wijzigen</a></li>";
	echo "<script type='text/javascript'>function AddPic () { OpenTBox('" ."/userdetails/upload_picture.php" ."'); } </script>";
    echo "<li><a href='javascript: AddPic()'>Foto toevoegen</a></li>";
	echo "<script type='text/javascript'>function RemovePic() {  OpenTBox('" ."/userdetails/remove_picture.php?id=" .$s_id ."'); } </script>";
	echo "<li><a href='javascript: RemovePic();'>Foto verwijderen</a></li>";
	echo "</ul>";
	echo "</div>";
	echo "</td></tr></table>";
}

function show_user(){
	global $rootpath;
        $url = "render_user.php";
        echo "<div id='userdiv'></div>";
        echo "<script type='text/javascript'>showsmallloader('userdiv');loaduser('$url');</script>";
}

function get_user($s_id){
	global $db;
	$query = "SELECT * FROM users ";
	$query .= "WHERE id=".$s_id;
	$user = $db->GetRow($query);
	return $user;
}

function get_contact($s_id){
	global $db;
	$query = "SELECT *, ";
	$query .= " contact.id AS cid, users.id AS uid, type_contact.id AS tcid, ";
	$query .= " type_contact.name AS tcname, users.name AS uname ";
	$query .= " FROM users, type_contact, contact ";
	$query .= " WHERE users.id=".$s_id;
	$query .= " AND contact.id_type_contact = type_contact.id ";
	$query .= " AND users.id = contact.id_user ";
	$contact = $db->GetArray($query);
	return $contact;
}

function show_balance($balance, $user, $currency){
	echo "<div class='border_b'>";
	echo "<table class='memberview' cellpadding='0' cellspacing='0' border='0' width='99%'>";
	echo "<tr class='memberheader'><td colspan='2'>";
	echo "<strong>{$currency}stand</strong></td></tr>";
	echo "<tr>";
	echo "<td width='50%'>Huidige {$currency}stand: </td>";
	echo "<td width='50%'>";
	echo $balance;
	echo "</td></tr>";
	echo "<tr>";
	echo "<td width='50%'>Limiet minstand: </td>";
	echo "<td width='50%'>";
	echo $user["minlimit"];
	echo "</td></tr>";
	echo "<td width='50%'>Limiet maxstand: </td>";
        echo "<td width='50%'>";
        echo $user["maxlimit"];
        echo "</td></tr>";

	echo "</table>";
}

function show_contact(){
	global $rootpath;
	$url = "rendercontact.php";
	echo "<div id='contactdiv'></div>";
	echo "<script type='text/javascript'>showsmallloader('contactdiv');loadcontact('$url');</script>";
	echo "<table width='100%' border=0><tr><td>";
	echo "<ul class='hormenu'>";
	$myurl="mydetails_cont_add.php";
        echo "<li><a id='showcontactform' href='#'>Contact toevoegen</a></li>";
	echo "</ul>";
	echo "</td></tr></table>";
}

function show_ptitle(){
	global $rootpath;
        echo "<h1>Mijn gegevens</h1>";
	echo "<script type='text/javascript' src='" .$rootpath ."js/mydetails.js'></script>";
}


function redirect_login($rootpath){
	header("Location: ".$rootpath."login.php");
}
include($rootpath."includes/inc_sidebar.php");
include($rootpath."includes/inc_footer.php");
?>

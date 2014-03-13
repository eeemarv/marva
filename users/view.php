<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath.'includes/request.php');

$req = new request('admin');

include($rootpath."includes/inc_header.php");



if (isset($_GET["id"])){
	$id = $_GET["id"];
	$user = get_user($id);
	show_ptitle();
	show_changepwlink($id);

	show_activatelink($id);
		
	show_user($user,$rootpath);
	$user_id = $user["id"];
	$contact = get_contact($id);
	show_contact($contact, $user_id);
	$balance = $user["saldo"];
	show_balance($balance,$configuration["system"]["currency"]);
}else{
	redirect_overview();
}


////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////



function chop_string($content, $maxsize){
$strlength = strlen($content);
    //geef substr van kar 0 tot aan 1ste spatie na 30ste kar
    //dit moet enkel indien de lengte van de string groter is dan 30
    if ($strlength >= $maxsize){
        $spacechar = strpos($content," ", 60);
        if($spacechar == 0){
            return $content;
        }else{
            return substr($content,0,$spacechar);
        }
    }else{
        return $content;
    }
}



function show_changepwlink($s_id){
	echo "<p>| <a href='editpw.php?id=" .$s_id. "'>Paswoord veranderen</a> |";
}

function show_deletelink($s_id){
	echo " <a href='delete.php?id=" .$s_id. "'>Delete</a> |";
}

function show_activatelink($s_id){
        echo " <a href='activate.php?id=" .$s_id. "'>Activeren</a> |";
}

function get_numberoftransactions($user_id){
	global $db;

	$query_min = "SELECT count(*) ";
	$query_min .= " FROM transactions ";
	$query_min .= " WHERE id_from = ".$user_id ." or id_to = ".$user_id ;
	$numberoftrans = $db->GetRow($query_min);
	return $numberoftrans["count(*)"];
}

function show_balance($balance,$currency){
	//echo "<div class='border_b'>";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='99%'>";
	echo "<tr><td>&#160;</td></tr>";
	echo "<tr class='even_row'>";
	echo "<td><strong>{$currency}stand</strong></td></tr>";
	echo "<tr><td>";
	echo $balance;
	echo "</td></tr></table>";
}

function redirect_login($rootpath){
	header("Location: ".$rootpath."login.php");
}

function show_ptitle(){
	echo "<h1>Gebruiker</h1>";
}

function show_user($user,$rootpath){
	global $baseurl;
	global $dirbase;
	
	echo "<table cellpadding='0' cellspacing='0' border='0' width='99%'>";
	echo "<tr class='even_row'><td colspan='2'><strong>".htmlspecialchars($user["name"],ENT_QUOTES)." (";
	echo trim($user["letscode"]).")</strong></td></tr>";

	// Wrap arround another table to show user picture
        echo "<td width='170' align='left'>";
	if($user["PictureFile"] == NULL) {
                echo "<img src='" .$rootpath ."gfx/nouser.png' width='150'></img>";
        } else {
                echo "<img src='" .$rootpath ."sites/$dirbase/userpictures/" .$user["PictureFile"] ."' width='150'></img>";
        }
        echo "</td>";

        // inline table
        echo "<td>";
		echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		echo "<tr><td>Naam: </td>";
	        echo "<td>".$user["fullname"]."</td></tr>";

		echo "<tr><td>Postcode: </td>";
		echo "<td>".$user["postcode"]."</td></tr>";

		echo "<tr><td>Geboortedatum: </td>";
		echo "<td>".$user["birthday"]."</td></tr>";
		
		echo "<tr><td valign='top'>Hobbies/interesses: </td>";
		echo "<td>".nl2br(htmlspecialchars($user["hobbies"],ENT_QUOTES))."</td></tr>";
	
		echo "<tr><td valign='top'>Commentaar: </td>";
		echo "<td>".nl2br(htmlspecialchars($user["comments"],ENT_QUOTES))."</td></tr>";
	
		echo "<tr><td valign='top'>Login: </td>";
		echo "<td>".htmlspecialchars($user["login"],ENT_QUOTES)."</td></tr>";
		echo "<tr><td valign='top'>Datum aanmaak: </td>";
                echo "<td>" .$user["cdate"]."</td></tr>";
		echo "<tr><td valign='top'>Datum activering: </td>";
                echo "<td>" .$user["adate"]."</td></tr>";
		echo "<tr><td valign='top'>Laatste login: </td>";
		echo "<td>".$user["logdate"]."</td></tr>";
		echo "<tr><td valign='top'>Rechten:</td>";
		echo "<td>".$user["accountrole"]."</td></tr>";
		echo "<tr><td valign='top'>Status: </td>";
		echo "<td>";
		if($user["status"]==0){
			echo "Gedesactiveerd";
		}elseif ($user["status"]==1){
			echo "Actief";
		}elseif ($user["status"]==2){
			echo "Uitstapper";
		}elseif ($user["status"]==3){
			echo "Instapper";
		}elseif ($user["status"]==5){
			echo "Infopakket";
		}elseif ($user["status"]==6){
			echo "Infoavond";
		}elseif ($user["status"]==7){
			echo "Extern";
		}
		echo "</td></tr>";

		echo "<tr><td valign='top'>Commentaar van de admin: </td>";
		echo "<td>".nl2br(htmlspecialchars($user["admincomment"],ENT_QUOTES))."</td></tr>";
	
		echo "<tr><td valign='top'>Limiet minstand:</td>";
		echo "<td>".$user["minlimit"]."</td></tr>";

		echo "<tr><td valign='top'>Saldo mail:  </td>";
		if($user["cron_saldo"] == 1){
                        echo "<td valign='top'>Aan</td>";
                } else {
                        echo "<td valign='top'>Uit</td>";
                }
		echo "</tr>";
		
		echo "</table>";
	echo "</td>";

	echo "<tr><td colspan='2'>&#160;</td></tr>";
	echo "<tr><td colspan='2'>";
	echo "| <a href='edit.php?mode=edit&id=" .$user["id"]. "'>Aanpassen</a> | ";
	echo "</td></tr>";
	echo "</table>";
}

function get_user($id){
	global $db;
	$query = "SELECT *, ";
	$query .= " DATE_FORMAT(cdate, '%d-%m-%Y') AS date, ";
	$query .= " DATE_FORMAT(lastlogin, '%d-%m-%Y %H:%i:%s') AS logdate ";
	$query .= " FROM users ";
	$query .= " WHERE id='".$id."'";
	$user = $db->GetRow($query);
	return $user;
}


function get_contact($id){
	global $db;
	$query = "SELECT *, ";
	$query .= " contact.id AS cid, users.id AS uid, type_contact.id AS tcid, ";
	$query .= " type_contact.name AS tcname, users.name AS uname ";
	$query .= " FROM users, type_contact, contact ";
	$query .= " WHERE users.id=".$id;
	$query .= " AND contact.id_type_contact = type_contact.id ";
	$query .= " AND users.id = contact.id_user ";
	
	$contact = $db->GetArray($query);
	return $contact;
}

function show_contact($contact, $user_id ){
	echo "<div >";
	echo "<table cellpadding='0' cellspacing='0' border='1' width='99%' class='data'>";
	
	echo "<tr class='even_row'>";
	echo "<td colspan='5'><p><strong>Contactinfo</strong></p></td>";
	echo "</tr>";
echo "<tr>";
echo "<th valign='top'>Type</th>";
echo "<th valign='top'>Waarde</th>";
echo "<th valign='top'>Commentaar</th>";
echo "<th valign='top'>Publiek</th>";
echo "<th valign='top'></th>";
echo "</tr>";

	foreach($contact as $key => $value){
		echo "<tr>";
		echo "<td valign='top'>".$value["abbrev"].": </td>";
		echo "<td valign='top'>".htmlspecialchars($value["value"],ENT_QUOTES)."</td>";
		echo "<td valign='top'>".htmlspecialchars($value["comments"],ENT_QUOTES)."</td>";
		echo "<td valign='top'>";
		if (trim($value["flag_public"]) == 1){
				echo "Ja";	
		}else{
				echo "Nee";
		}
		echo "</td>";
		echo "<td valign='top' nowrap>|";
		echo "<a href='cont_edit.php?cid=".$value["id"]."&uid=".$value["id_user"]."'>";
		echo " aanpassen </a> |";
		echo "<a href='cont_delete.php?cid=".$value["id"]."&uid=".$value["id_user"]."'>";
		echo "verwijderen </a>|";
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td colspan='5'><p>&#160;</p></td></tr>";
	echo "<tr><td colspan='5'>| ";
	echo "<a href='cont_add.php?uid=".$user_id."'>";
	echo "Contact toevoegen</a> ";
	echo "|</td></tr>";
	echo "</table></div>";
}

function redirect_overview(){
	header("Location: overview.php");
}

include($rootpath."includes/inc_footer.php");
?>


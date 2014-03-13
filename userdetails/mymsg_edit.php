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

 

	if (isset($s_id)){

	show_ptitle();
	$id = $_GET["id"];
	if(isset($id)){

	 	$user_list = get_user();
		$cat_list = get_cat();
		if(isset($_POST["zend"])){
			$validity = $_POST["validity"];
			$vtime = count_validity($validity);
			$posted_list = array();

                	$posted_list["vtime"] = $vtime;
			$posted_list["content"] = $_POST["content"];
			$posted_list["msg_type"] = $_POST["msg_type"];
			$posted_list["id_user"] = $_POST["id_user"];
			$posted_list["id_category"] = $_POST["id_category"];
			$posted_list["id"] = $_GET["id"];
			$error_list = validate_input($posted_list);
			if (!empty($error_list)){
				$msg = get_msg($id);
				show_form($msg, $error_list, $user_list, $cat_list);
			}else{
				update_msg($id, $posted_list, $s_id);
				redirect_overview();
			}
		}else{
			$msg = get_msg($id);
			show_form($msg, $error_list, $user_list, $cat_list);
		}
	}else{ 
		redirect_overview();
	}

}else{
	redirect_login($rootpath);
}


////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////

function show_ptitle(){
	echo "<h1>Mijn vraag / aanbod aanpassen</h1>";
}

function validate_input($posted_list){
	$error_list = array();
	if (empty($posted_list["content"]) || (trim($posted_list["content"]) == ""))
		$error_list["content"] = "<font color='#F56DB5'>Vul <strong>inhoud</strong> in!</font>";
	return $error_list;
}

function count_validity($validity){
	$valtime = time() + ($validity*30*24*60*60);
        $vtime =  date("Y-m-d H:i:s",$valtime);
        return $vtime;
}

function update_msg($id, $posted_list, $s_id){
	global $db;
	$posted_list["validity"] = $posted_list["vtime"];
	$posted_list["mdate"] = date("Y-m-d H:i:s");
	$posted_list["id_user"] = $s_id;
    	$result = $db->AutoExecute("messages", $posted_list, 'UPDATE', "id=$id");
}

function show_form($msg, $error_list, $user_list, $cat_list){
	echo "<div class='border_b'>";
	echo "<form action='mymsg_edit.php?id=".$msg["id"]."' method='POST'>";
	echo "<table  cellspacing='0' cellpadding='0' border='0'>\n\n";
	
	echo "<tr>\n<td>\n";
	echo "Type:";
	echo "</td>\n<td>";
	echo "<select name='msg_type'>\n";
	if($msg["msg_type"] == 0 ){
		echo "<option value='0' SELECTED >Vraag</option>\n";
	}else{
		echo "<option value='0' >Vraag</option>\n";
	}
	if ($msg["msg_type"] == 1){
		echo "<option value='1' SELECTED >Aanbod</option>\n";
	}else{
		echo "<option value='1'>Aanbod</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n</tr>\n\n<tr>\n<td></td>\n<td></td>\n</tr>\n\n";
		
	echo "<tr>\n<td>";
	echo "Categorie: ";
	echo "</td>\n<td>";
	echo "<select name='id_category'>\n";
	foreach($cat_list as $value3){
		if ($msg["id_category"] == $value3["id"]){
			echo "<option value='". $value3["id"] ."' SELECTED >";
		}else{
			echo "<option value='".$value3["id"]."' >";
		}
		echo htmlspecialchars($value3["fullname"],ENT_QUOTES);
		echo "</option>\n"; 
	}
	echo "</select>\n";
	echo "</td>\n</tr>\n\n<tr>\n<td></td>\n<td></td>\n</tr>\n\n";
	
	echo "<tr>\n<td valign='top'>Inhoud </td>\n<td>";
	echo "<textarea name='content' rows='1' cols='40'>";
	echo htmlspecialchars($msg["content"],ENT_QUOTES);  
	echo "</textarea>";
	echo "</td>\n</tr>\n\n<tr>\n<td></td>\n<td>";
	if (isset($error_list["content"])){
		echo $error_list["content"];
	}
	echo "</td>\n</tr>\n\n";

        echo "<tr>\n<td valign='top' align='right'>Geldigheid </td>\n";

        echo "<td>";
	echo "<input type='text' name='validity' size='4' value='12'> maanden\n";

        echo "</td>\n</tr>\n\n<tr>\n<td></td>\n<td>";
        echo "</td>\n</tr>\n";

	echo "<tr>\n<td colspan='2'>";
	echo "<input type='submit' value='OK' name='zend'>";
	echo "</td>\n</tr>\n\n</table>";
	echo "</form>";
	echo "</div>";
}



function get_user(){
    global $db;
	$query = "SELECT * FROM users";
	$user_list = $db->GetArray($query);
	return $user_list; 
}



function get_cat(){
	global	$db;
	$query = "SELECT * FROM categories WHERE leafnote=1 order by fullname";
	$cat_list = $db->GetArray($query);
	return $cat_list;
}

function get_msg($id){
global	$db;
	$query = "SELECT * FROM messages WHERE id=".$id;
	$msg = $db->GetRow($query);
	return $msg;
}


function redirect_overview(){
	header("Location:  mymsg_overview.php");
}

function redirect_login($rootpath){
	header("Location: ".$rootpath."login.php");
}

include($rootpath."includes/inc_sidebar.php");
include($rootpath."includes/inc_footer.php");
?>


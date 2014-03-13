<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_userinfo.php");

require_once($rootpath.'includes/request.php');

$req = new request('admin');

include($rootpath."includes/inc_header.php");


showlinks($rootpath);
show_ptitle1();
$groups = get_letsgroups();
show_groups($groups);
show_comment();


////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////


function showlinks($rootpath){
	global $s_id;
        echo "<table width='100%' border=0><tr><td>";
        echo "<div id='navcontainer'>";
        echo "<ul class='hormenu'>";
        echo "<li><a href='edit.php?mode=new' class='admin' ')>Groep toevoegen</a></li>";
	echo "<li><a href='renderqueue.php' class='admin'>Interlets Queue</a></li>";
        echo "</ul>";
        echo "</div>";
        echo "</td></tr></table>";
}
	
function show_outputdiv(){
	echo "<div id='output'><img src='/gfx/ajax-loader.gif' ALT='loading'>";
	echo "<script type=\"text/javascript\">loadurl('rendergroups.php');</script>";
	echo "</div>";
}

function show_comment(){
	echo "<p><small><i>";
	echo "Belangrijk: er moet zeker een interletsrekening bestaan van het type internal om Marva toe te laten met zichzelf te communiceren.  Deze moet een geldige SOAP URL en Apikey hebben.";
	echo "</i></small></p>";
}

function redirect_login($rootpath){
	header("Location: ".$rootpath."login.php");
}

function show_ptitle1(){
	echo "<h1>Overzicht LETS groepen</h1>";
}

function show_groups($groups){
	echo "<div class='border_b'><table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>";
	echo "<tr class='header'>";
	echo "<td valign='top'><strong>";
	echo "ID";
	echo "</strong></td>";
	echo "<td valign='top'><strong>";
	echo "Groepnaam";
	echo "</strong></td>";
	echo "<td valign='top'><strong>";
	echo "API";
	echo "</strong></td>";
	echo "</tr>\n\n";
	$rownumb=0;
	$groups = getgroups();
	foreach($groups as $key => $value){
		$rownumb=$rownumb+1;
		echo "<tr";
		if($rownumb % 2 == 1){
			echo " class='uneven_row'";
		}else{
	        	echo " class='even_row'";
		}
		echo ">";

		echo "<td>" .$value['id'] ."</td>";
		echo "<td><a href='view.php?id=" .$value['id'] ."'>" .$value['groupname'] ."</a></td>";
		echo "<td>" .$value['apimethod'] ."</td>";
	}
	echo "</tr>";
	echo "</table>";
		
}

function getgroups(){
	global $db;
        $query = "SELECT * FROM letsgroups";
        $groups = $db->GetArray($query);
        return $groups;
}

include($rootpath."includes/inc_sidebar.php");
include($rootpath."includes/inc_footer.php");
?>

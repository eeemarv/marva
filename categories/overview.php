<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/request.php");

$req = new request('admin');


include($rootpath."includes/inc_header.php");

echo "<div id='navcontainer'>";
echo "<ul class='hormenu'>";
echo '<li><a href="new.php")>Categorie Toevoegen</a></li>';
echo "</ul>";
echo '</div>';

echo "<h1>Categorie&#235;n</h1>";

$cats = get_all_cats();
show_all_cats($cats);

include($rootpath."includes/inc_footer.php");


// functions






function show_all_cats($cats){
	echo "<div class='border_b'>";
	echo "<table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>";
	echo "<tr class='header'>";
	echo "<td><strong>Categorie</strong></td>";
	echo "</tr>";
	$rownumb=0;
	foreach($cats as $key => $value){
				
		if ($value["id_parent"] == 0){
			echo "<tr class='even_row'>";
			echo "<td valign='top'><strong><a href='view.php?id=".$value["id"]."'>";
			echo htmlspecialchars($value["fullname"],ENT_QUOTES);
			echo "</a></strong></td>";
			echo "</tr>";
		}else{
			echo "<tr class='uneven_row'>";
			echo "<td valign='top'><a href='view.php?id=".$value["id"]."'>";
			echo htmlspecialchars($value["fullname"],ENT_QUOTES);
			echo "</a></td>";
			echo "<tr>";
		}
	}
	echo "</table></div>";
}


function get_all_cats(){
	global $db;
	$query = "SELECT *, DATE_FORMAT(cdate, ('%m/%d/%y')) AS date FROM categories ";
	$query .= "ORDER BY fullname ";
	$cats = $db->GetArray($query);
	return $cats;
}




?>

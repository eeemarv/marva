<?php
ob_start();
$rootpath = "./";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

require_once('./includes/request.php');
require_once('./includes/data_table.php');

$req = new request('admin');

$req->add('confirm_password', '', 'post', array('type' => 'password', 'size' => 10, 'maxlength' => 20, 'label' => 'Paswoord (extra veiligheid)', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'password'))
	->addSubmitButtons()
	->cancel();

$configs = $db->GetArray('select * from config order by category, setting');


foreach($configs as $config){
	$req->add('config-'.$config['setting'], $config['value'], 'post', array('type' => 'text', 'size' => 8, 'maxlength' => 60));
}

if ($req->get('create')){

}


include($rootpath."includes/inc_header.php");

echo '<h1><a href="config.php">Instellingen</a></h1>';




echo '<p>Tijdzone: UTC'.date('O').'</p>'; 



	show_prefs($configs);


//functions




function show_prefs($prefs){
	global $rootpath;
	echo "<div class='border_b'>";
        echo "<table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>";
        echo "<tr class='header'>";
	echo "<td nowrap valign='top'><strong>Categorie</strong></td>";
        echo "<td nowrap valign='top'><strong>Instelling</strong></td>";
	echo "<td nowrap valign='top'><strong>Waarde</strong></td>";
	echo "<td nowrap valign='top'><strong>Omschrijving</strong></td>";
        echo "</tr>";

        foreach($prefs as $key => $value){
                $rownumb=$rownumb+1;
                if($rownumb % 2 == 1){
                        echo "<tr class='uneven_row'>";
                }else{
                        echo "<tr class='even_row'>";
                }
                echo "<td nowrap valign='top'>";
                echo $value["category"];
                echo "</td>";
	        echo "<td nowrap valign='top'>";
		$mysetting = $value["setting"];
		$myurl = $rootpath."preferences/editconfig.php?setting=$mysetting";
		echo "<a href='#' onclick=\"javascript:window.open('$myurl','config','width=600,height=700,scrollbars=yes,toolbar=no,location=no,menubar=no')\">$mysetting</a>";
                echo "</td>";

		if($value["default"] == 1){
			echo "<td nowrap valign='top' bgcolor='red'>";
		} else {
			echo "<td nowrap valign='top'>";
		}

                echo $value["value"];
                echo "</td>";
                echo "<td wrap valign='top'>";
                echo $value["description"];
                echo "</td>";
                echo "</tr>";
        }
        echo "</table></div>";
	echo "<P>Waardes in het rood moeten nog gewijzigd (of bevestigd) worden</P>";

}


	

include($rootpath.'includes/inc_footer.php');
?>

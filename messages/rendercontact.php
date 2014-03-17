<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/default.php");

require_once($rootpath."includes/inc_userinfo.php");   
require_once($rootpath.'includes/request.php');

$req = new request('guest');
$req->add('id', 0, 'get');


$contact = get_contact($req->get('id'));
$mailuser = get_user_maildetails($message["id_user"]);
$usermail = $mailuser["emailaddress"];
$balance = $user["saldo"];
show_contact($contact);


////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////

function show_contact($contact){
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr class='even_row'><td colspan='3'><p><strong>Contactinfo</strong></p></td></tr>";
	foreach($contact as $key => $value){
		echo "<tr><td>".$value["abbrev"].": </td>";
                if($value["abbrev"] == "mail"){
                        echo "<td><a href='mailto:".$value["value"]."'>".$value["value"]."</a></td>";
                }elseif($value["abbrev"] == "adr"){
                        echo "<td><a href='http://maps.google.be/maps?f=q&source=s_q&hl=nl&geocode=&q=".$value["value"]."' target='new'>".$value["value"]."</a></td>";
                } else {
                        echo "<td>".$value["value"]."</td>";
                }
		echo "<td></td>";
		echo "</tr>";
	}
	echo "</table>";
}
	
?>

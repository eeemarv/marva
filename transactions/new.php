<?php
ob_start();
$rootpath = "../";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

require_once($rootpath."includes/inc_transactions.php");
require_once($rootpath."includes/inc_userinfo.php"); 


require_once($rootpath.'includes/request.php');
require_once($rootpath.'includes/data_table.php');

$currency = readconfigfromdb('currency');

$req = new request('user');
$req
	->add('from_user_id', $s_id, 'post', array('type' => 'select', 'label' => 'Van', 'option_set' => 'active_users', 'admin_enable_only' => true))
	->add('date', date('Y-m-d'), 'post', array('type' => 'text', 'size' => 10, 'disabled' => 'disabled', 'label' => 'Datum'))
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('amount', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 6, 'label' => 'Aantal '.$currency , 'autocomplete' => 'off'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('confirm_password', '', 'post', array('type' => 'password', 'size' => 20, 'maxlength' => 20, 'label' => 'Paswoord (extra veiligheid)', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'password'))
	->add('zend', '', 'post', array('type' => 'submit', 'label' => 'Voer transactie uit.'))
	->add('cancel', '', 'post', array('type' => 'submit', 'label' => 'Annuleer'))
	->add('transid', generate_transid(), 'post', array('type' => 'hidden'));

if ($req->get('cancel')){
	header('Location: '.$rootpath.'transactions.php');
}


if ($req->get('zend') && !$req->errors()){


	
	
}	

include($rootpath.'includes/inc_header.php');


echo '<h1>Nieuwe Transactie</h1>';
echo '<h2>'.$currency.' uitschrijven</h1>';

$user = get_user($s_id);
$balance = $user["saldo"];
	 
$list_users = get_users($s_id);


$minlimit = $user['minlimit'];

echo "<div id='baldiv'>";	
echo "<p><strong>Huidige {$currency}stand: ".$balance."</strong> || ";
echo "<strong>Limiet minstand: ".$minlimit."</strong></p>";
echo "</div>";


echo '<form method="post" class="trans">';
echo '<table cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('from_user_id', 'date', 'letscode_to', 'amount', 'description', 'confirm_password', 'transid'));
echo '<tr>';
$req->set_output('td')->render(array('zend', 'cancel'));
echo '</tr></table></form>';

echo <<<EOT
<script>
$('document').ready(function(){
	$('#letscode_to').typeahead({
		name:'default',
		prefetch: 
			{ url:'typeahead_users.php',
			  filter: function(data){
					return data.map(function(user){
						return { 
							value : user.c + ' ' + user.n,
							tokens : [ user.c, user.n ],
							class : (user.e) ? 'warning' : ((user.s) ? 'info' : ((user.le) ? 'error' : ((user.a) ? 'success' : ''))),
							balance : user.b,
							limit : user.l
						};
					});
				},
			  ttl: 100000	
			},
		{% verbatim %}	
		template: '<p class="{{ class }}">{{ value }}</p>',
		{% endverbatim %}
		engine: Hogan
		});
	});
</script>
EOT;



echo "<script type='text/javascript' src='/js/userinfo.js'></script>";
echo "<div id='transformdiv'>";

echo "<input name='balance' id='balance' type='hidden' value='".$balance."' >";
echo "<input name='minlimit' type='hidden' id='minlimit' value='".$user["minlimit"]."' >";
echo "<table cellspacing='0' cellpadding='0' border='0'>";
echo "<tr><td align='right'>";
echo "Van";
echo "</td><td>";

echo "<select name='letscode_from' id='letscode_from' accesskey='2'\n";
if($s_accountrole != "admin") {
			echo " DISABLED";
}
echo " onchange=\"javascript:document.getElementById('baldiv').innerHTML = ''\">";
foreach ($list_users as $value){
	echo "<option value='".$value["letscode"]."' >";
	echo htmlspecialchars($value["fullname"],ENT_QUOTES) ." (" .$value["letscode"] .")";
	echo "</option>\n";
}
echo "</select>\n";

echo "</td><td width='150'><div id='fromoutputdiv'></div>";
echo "</td></tr>";

echo "<tr><td valign='top' align='right'>Datum</td><td>";
	echo "<input type='text' name='date' id='date' size='18' value='" .$date ."'";
if($s_accountrole != "admin") {
			echo " DISABLED";
	}
	echo ">";
	echo "</td><td>";
	echo "</td></tr><tr><td></td><td>";
	echo "</td></tr>";

echo "<tr><td align='right'>";
	echo "Aan LETS groep";
	echo "</td><td>";
	echo "<select name='letgroup' id='letsgroup' onchange=\"document.getElementById('letscode_to').value='';\">\n";
$letsgroups = get_letsgroups();
foreach($letsgroups as $key => $value){
	$id = $value["id"];
	$name = $value["groupname"];
	echo "<option value='$id'>$name</option>";
}
echo "</select>";
echo "</td><td>";
echo "</td></tr><tr><td></td><td>";
echo "<tr><td align='right'>";
echo "Aan LETSCode";
echo "</td><td>";
echo "<input type='text' name='letscode_to' id='letscode_to' size='10' onchange=\"javascript:showsmallloader('tooutputdiv');loaduser('letscode_to','tooutputdiv')\">";
echo "</td><td><div id='tooutputdiv'></div>";
echo "</td></tr><tr><td></td><td>";
echo "</td></tr>";

echo "<tr><td valign='top' align='right'>Aantal {$currency}</td><td>";
echo "<input type='text' id='amount' name='amount' size='10' ";
echo ">";
echo "</td><td>";
echo "</td></tr>";
echo "<tr><td></td><td>";
echo "</td></tr>";

echo "<tr><td valign='top' align='right'>Dienst</td><td>";
echo "<input type='text' name='description' id='description' size='40' MAXLENGTH='60' ";
echo ">";
echo "</td><td>";
echo "</td></tr><tr><td></td><td>";
echo "</td></tr>";
echo "<tr><td colspan='3' align='right'>";
echo "<input type='submit' name='zend' id='zend' value='Overschrijven'>";
echo "</td></tr></table>";
echo "</form>";
echo "<script type='text/javascript'>loaduser('letscode_from','fromoutputdiv')</script>";
echo "</div>";

echo "<script type='text/javascript'>document.getElementById('letscode_from').value = '$s_letscode';</script>";

echo "<table border=0 width='100%'><tr><td align='left'>";
$myurl="userlookup.php";
echo "<form id='lookupform'><input type='button' id='lookup' value='LETSCode opzoeken' onclick=\"javascript:newwindow=window.open('$myurl','Lookup','width=600,height=500,scrollbars=yes,toolbar=no,location=no,menubar=no');\"></form>";
echo "</td></tr></table>";


include($rootpath."includes/inc_footer.php");


?>

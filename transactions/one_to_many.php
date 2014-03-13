<?php
ob_start();
$rootpath = '../';
require_once($rootpath.'includes/inc_default.php');
require_once($rootpath.'includes/inc_adoconnection.php');

require_once($rootpath.'includes/inc_transactions.php');
require_once($rootpath.'includes/request.php');
require_once($rootpath.'includes/data_table.php');

//status 0: inactief
//status 1: letser
//status 2: uitstapper
//status 3: instapper
//status 4: secretariaat
//status 5: infopakket
//status 6: stapin
//status 7: extern

$req = new request('admin');
$req->add('fixed', 10, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 3, 'label' => 'Vast bedrag'), array('match' => 'positive'))
	->add('percentage', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage'))
	->add('percentage_base', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage saldo-basis'))
	->add('fill_in', '', 'post', array('type' => 'submit', 'label' => 'Vul in'))
	->add('no_newcomers', '', 'post', array('type' => 'checkbox', 'label' => 'Geen instappers.'), array())
	->add('no_leavers', '', 'post', array('type' => 'checkbox', 'label' => 'Geen uitstappers.'), array())
	->add('no_max_limit', '', 'post', array('type' => 'checkbox', 'label' => 'Geen saldo\'s boven de maximum limiet'), array())
	->add('letscode_from', '', 'post', array('type' => 'text', 'size' => 5, 'maxlength' => 10, 'label' => 'Van LetsCode', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'active_letscode'))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('confirm_password', '', 'post', array('type' => 'password', 'size' => 10, 'maxlength' => 20, 'label' => 'Paswoord (extra veiligheid)', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'password'))
	->add('create', '', 'post', array('type' => 'submit', 'label' => 'Voer alle transacties uit'))
	->add('transid', generate_transid(), 'post', array('type' => 'hidden'));


$query = 'SELECT id, fullname, letscode, accountrole, status, saldo, minlimit, maxlimit, adate  
	FROM users 
	WHERE status = 1 
		OR status = 2 
		OR status = 3 
		OR status = 4 
	ORDER BY letscode';
$active_users = $db->GetArray($query);


$letscode_from = $req->get('letscode_from');
$from_user_id = null;

foreach($active_users as $user){
	$req->add('amount-'.$user['id'], 0, 'post', array('type' => 'text', 'size' => 3, 'maxlength' => 3, 'onkeyup' => 'recalc_table_sum(this);'), array('match' => 'positive'));
	if ($letscode_from && $user['letscode'] == $letscode_from){
		$from_user_id = $user['id'];
		$from_user_fullname = $user['fullname'];
	}
}

if ($req->get('create') && !$req->errors() && $from_user_id){
	$notice = '';
	$description = $req->get('description');
	$transid = $req->get('transid');
	$duplicate = check_duplicate_transaction($transid);
	if ($duplicate){
		$notice .= '<p><font color="red"><strong>Een dubbele boeking van een transactie werd voorkomen</strong></font></p>';
	} else {	
		foreach($active_users as $user){
			$amount = $req->get('amount-'.$user['id']);
			if (!$amount || $from_user_id == $user['id']){
				continue;
			}	
			$trans = array(
				'id_from' => $from_user_id,
				'id_to' => $user['id'],
				'amount' => $amount,
				'description' => $description,
				'date' => date('Y-m-d H:i:s'));
			$checktransid = insert_transaction($trans, $transid);
			$notice_text = 'Transactie van gebruiker '.$from_user_fullname.' ( '.$letscode_from.' ) naar '.$user['fullname'].' ( '.$user['letscode'].' ) met bedrag '.$amount.' ';
			if($checktransid == $transid){
				mail_transaction($posted_list, $mytransid);
				$notice .= '<p><font color="green"><strong>OK - '.$notice_text.'opgeslagen</strong></font></p>';			
			} else {
				$notice .= '<p><font color="red"><strong>'.$notice_text.'Mislukt</strong></font></p>';
			}
			$transid = generate_transid();
		}
	}
	$req->set('letscode_from', '');
	$req->set('description', '');
	
	 // redirect here 	
}
	
$fixed = $req->get('fixed');
$fixed = $req->get('fixed');
$percentage = $req->get('percentage');
$percentage_base = $req->get('percentage_base');
$perc = 0;

if ($req->get('fill_in') && ($fixed || $percentage)){
	foreach ($active_users as $user){
		if ($user['letscode'] == $req->get('letscode_from') 
			|| (check_newcomer($user['adate']) && $req->get('no_newcomers')) 
			|| ($user['status'] == 2 && $req->get('no_leavers'))
			|| ($user['saldo'] > $user['maxlimit'] && $req->get('no_max_limit'))){
			$req->set('amount-'.$user['id'], 0);	
		} else {
			if ($percentage){
				$perc = round(($user['saldo'] - $percentage_base)*$percentage/100);
				$perc = ($perc > 0) ? $perc : 0;
			}	
			$req->set('amount-'.$user['id'], $fixed + $perc);
		}
	}
}

$req->set('amount-'.$from_user_id, 0);
$req->set('confirm_password', '');

$data_table = new data_table();
$data_table->set_data($active_users)->set_input($req)
	->add_column('letscode', array('title' => 'Aan LetsCode', 'render' => 'status'))
	->add_column('fullname', array('title' => 'Naam'))
	->add_column('accountrole', array('title' => 'Rol', 'footer_text' => 'TOTAAL', 'render' => 'admin'))
	->add_column('saldo', array('title' => 'Saldo', 'footer' => 'sum', 'render' => 'limit'))
	->add_column('amount', array('title' => 'Bedrag', 'input' => 'id', 'footer' => 'sum'))
	->add_column('maxlimit', array('title' => 'Max.Limiet'));

include($rootpath.'includes/inc_header.php');

if ($notice) {
	echo '<div style="background-color: #DDDDFF;padding: 10px;">'.$notice.'</div>';
}	


echo '<h1><a href="../transactions.php">Transacties</a></h1>';	
echo '<h1><a href="transactions/one_to_many.php">Massa-Transactie ingeven. "Eén naar Veel".</a></h1>';	
echo '<form method="post">';
echo '<div class="trans"><table cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('letscode_from', 'description'));	
echo '</table><br/>';
$data_table->render();	
echo '<table cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('confirm_password', 'create', 'transid'));
echo '</table></div>';
echo '<div style="background-color:#ffdddd; padding: 10px;">';
echo '<p><strong>Een vast bedrag invullen voor alle rekeningen.</strong></p>';
echo '<table  cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('fixed', 'percentage', 'percentage_base', 'no_newcomers', 'no_leavers', 'no_max_limit', 'fill_in', 'transid'));
echo '</table>';
echo '<p>Je kan een vast bedrag en/of een percentage voor de saldi invullen. Als een percentage wordt ingevuld, worden de bedragen berekend t.o.v. percentage saldo basis.
	Positieve percentages hebben alleen betrekking op een positief verschil tussen saldo en het percentage saldo basis en negatieve percentages alleen op een negatief verschil.</p>';
echo '<p>Bedrag = Vast Bedrag + ((Percentage / 100 x (Saldo - Percentage Saldo Basis)) > 0)</p>';		
echo '<p><strong><i>Van LETSCode</i></strong> wordt altijd automatisch overgeslagen. Alle bedragen blijven individueel aanpasbaar alvorens de massa-transactie uitgevoerd wordt.</p>';	
echo '</div><br/></form>';	

include($rootpath.'includes/inc_footer.php');

	
// functions


function check_newcomer($adate){
	global $configuration;
	$now = time();
	$limit = $now - ($configuration['system']['newuserdays'] * 60 * 60 * 24);
	$timestamp = strtotime($adate);
	return  ($limit < $timestamp) ? 1 : 0;
}


?>

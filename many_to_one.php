<?php
// copyleft 2014 martti <info@martti.be>

ob_start();

$rootpath = './';
require_once 'includes/default.php';

require_once 'includes/transactions.php';
require_once 'includes/request.php';
require_once 'includes/data_table.php';

//status 0: inactive
//status 1: active member
//status 2: leaving
//status 3: new member
//status 4: secretariat
//status 5: info
//status 6: step-in 
//status 7: external

$req = new request('admin');
$req->add('fixed', 10, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 3, 'label' => 'Vast bedrag'), array('match' => 'positive'))
	->add('percentage', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage'))
	->add('percentage_base', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage saldo-basis'))
	->add('percentage', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage'))
	->add('percentage_base', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage saldo-basis'))
	->add('fill_in', '', 'post', array('type' => 'submit', 'label' => 'Vul in', 'class' => 'btn btn-default'))
	->add('no_newcomers', '', 'post', array('type' => 'checkbox', 'label' => 'Geen instappers.'), array())
	->add('no_leavers', '', 'post', array('type' => 'checkbox', 'label' => 'Geen uitstappers.'), array())
	->add('no_min_limit', '', 'post', array('type' => 'checkbox', 'label' => 'Geen saldo\'s onder de minimum limiet.'), array())
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 5, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'active_letscode'))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('confirm_password', '', 'post', array('type' => 'password', 'size' => 10, 'maxlength' => 20, 'label' => 'Paswoord (extra veiligheid)', 'autocomplete' => 'off'), array('not_empty' => true, 'match' => 'password'))
	->add('create', '', 'post', array('type' => 'submit', 'label' => 'Voer alle transacties uit.'))
	->add('transid', generateUniqueId(), 'post', array('type' => 'hidden'));


$query = 'SELECT id, fullname, letscode, accountrole, status, saldo, minlimit, maxlimit, adate  
	FROM users 
	WHERE status = 1 
		OR status = 2 
		OR status = 3 
		OR status = 4 
	ORDER BY letscode';
$active_users = $db->fetchAll($query);


$letscode_to = $req->get('letscode_to');
$to_user_id = null;

foreach($active_users as $user){
	$req->add('amount-'.$user['id'], 0, 'post', array('type' => 'text', 'size' => 3, 'maxlength' => 3, 'onkeyup' => 'recalc_table_sum(this);'), array('match' => 'positive'));
	if ($letscode_to && $user['letscode'] == $letscode_to){
		$to_user_id = $user['id'];
		$to_user_fullname = $user['fullname'];
	}
}

if ($req->get('create') && !$req->errors() && $to_user_id){
	$notice = '';
	$description = $req->get('description');
	$transid = $req->get('transid');
	$duplicate = check_duplicate_transaction($transid);
	if ($duplicate){
		$notice .= '<p><font color="red"><strong>Een dubbele boeking van een transactie werd voorkomen</strong></font></p>';
	} else {	
		foreach($active_users as $user){
			$amount = $req->get('amount-'.$user['id']);
			if (!$amount || $to_user_id == $user['id']){
				continue;
			}	
			$trans = array(
				'id_to' => $to_user_id,
				'id_from' => $user['id'],
				'amount' => $amount,
				'description' => $description,
				'date' => date('Y-m-d H:i:s'));
			$checktransid = insert_transaction($trans, $transid);
			$notice_text = 'Transactie van gebruiker '.$user['letscode'].' '.$user['fullname'].' naar '.$letscode_to.' '.$to_user_fullname.' met bedrag '.$amount.' ';
			if($checktransid == $transid){
				mail_transaction($posted_list, $mytransid);
				$notice .= '<p><font color="green"><strong>OK - '.$notice_text.'opgeslagen</strong></font></p>';			
			} else {
				$notice .= '<p><font color="red"><strong>'.$notice_text.'Mislukt</strong></font></p>';
			}
			$transid = generateUniqueId();
		}
	}
	$req->set('letscode_to', '');
	$req->set('description', ''); 	
	
	// redirect here
}
	
$fixed = $req->get('fixed');
$percentage = $req->get('percentage');
$percentage_base = $req->get('percentage_base');
$perc = 0;


if ($req->get('fill_in') && ($fixed || $percentage)){
	foreach ($active_users as $user){
		if ($user['letscode'] == $req->get('letscode_to') 
			|| (check_newcomer($user['adate']) && $req->get('no_newcomers')) 
			|| ($user['status'] == 2 && $req->get('no_leavers'))
			|| ($user['saldo'] < $user['minlimit'] && $req->get('no_min_limit'))){
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

$req->set('amount-'.$to_user_id, 0);
$req->set('confirm_password', '');

$data_table = new data_table();
$data_table->set_data($active_users)->set_input($req)
	->add_column('letscode', array('title' => 'Van LetsCode', 'render' => 'status'))
	->add_column('fullname', array('title' => 'Naam'))
	->add_column('accountrole', array('title' => 'Rol', 'footer_text' => 'TOTAAL', 'render' => 'admin'))
	->add_column('saldo', array('title' => 'Saldo', 'footer' => 'sum', 'render' => 'limit'))
	->add_column('amount', array('title' => 'Bedrag', 'input' => 'id', 'footer' => 'sum'))
	->add_column('minlimit', array('title' => 'Min.Limiet'));

include($rootpath.'includes/header.php');

if ($notice) {
	echo '<div style="background-color: #DDDDFF;padding: 10px;">'.$notice.'</div>';
}	


echo '<h1><a href="transactions.php">Transacties</a></h1>';	
echo '<h1><a href="many_to_one.php">Massa-Transactie ingeven. "Veel naar Eén".</a></h1><p>bvb. voor leden-bijdrage.</p>';	

echo '<form method="post">';
echo '<h3>Hulp om alle bedragen snel in te geven.</h3>';
echo '<table  cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('fixed', 'percentage', 'percentage_base', 'no_newcomers', 'no_leavers', 'no_min_limit', 'fill_in'));
echo '</table>';
echo '<p>Je kan een vast bedrag en/of een percentage voor de saldi invullen. Als een percentage wordt ingevuld, worden de bedragen berekend t.o.v. percentage saldo basis.
	Positieve percentages hebben alleen betrekking op een positief verschil tussen saldo en het percentage saldo basis en negatieve percentages alleen op een negatief verschil.</p>';
echo '<p>Bedrag = Vast Bedrag + ((Percentage / 100 x (Saldo - Percentage Saldo Basis)) > 0)</p>';	
echo '<p><strong><i>Aan LETSCode</i></strong> wordt altijd automatisch overgeslagen. Alle bedragen blijven individueel aanpasbaar alvorens de massa-transactie uitgevoerd wordt.</p>';		
echo '</form>';
echo '<form method="post" class="trans">';	
$data_table->render();	
$req->set_output('formgroup')->render(array('letscode_to', 'description', 'confirm_password'));
$req->set_output('no_label')->render(array('create', 'cancel', 'transid'));
echo '</form>';	

include 'includes/footer.php';

	
// functions


function check_newcomer($adate){
	global $parameters;
	$now = time();
	$limit = $now - ($parameters['new_user_days'] * 86400);
	$timestamp = strtotime($adate);
	return  ($limit < $timestamp) ? 1 : 0;
}



?>

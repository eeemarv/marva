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
$req->add('fixed', 10, 'get', array('type' => 'number', 'size' => 4, 'maxlength' => 3, 'label' => 'Vast bedrag'), array('match' => 'positive'))
	->add('percentage', 0, 'get', array('type' => 'number', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage'))

	->add('percentage_base', 0, 'get', array('type' => 'number', 'size' => 4, 'maxlength' => 4, 'label' => 'Percentage saldo-basis'))
	->add('fill_in', '', 'get', array('type' => 'submit', 'label' => 'Vul in', 'class' => 'btn btn-default'))
	->add('no_newcomers', '', 'get', array('type' => 'checkbox', 'label' => 'Geen instappers.'), array())
	->add('no_leavers', '', 'get', array('type' => 'checkbox', 'label' => 'Geen uitstappers.'), array())
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 5, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off'), array('required' => true, 'match' => 'active_letscode'))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('required' => true))
	->add('confirm_password', '', 'post', array('type' => 'password', 'size' => 10, 'maxlength' => 20, 'label' => 'Paswoord (extra veiligheid)', 'autocomplete' => 'off'), array('required' => true, 'match' => 'password'))
	->add('transid', generateUniqueId(), 'post', array('type' => 'hidden'))
	->addSubmitButtons();

if ($req->get('cancel')){
	header('location: transactions.php');
	exit;
}

$query = 'select id, fullname, letscode, accountrole, status, saldo, maxlimit, unix_timestamp(adate) as unix 
	from users 
	where status in (1, 2) 
	order by letscode';
$active_users = $db->fetchAll($query);

$system_user = $db->fetchAssoc('select id, fullname, letscode, accountrole, status, saldo, maxlimit 
	from users where status = 4');

if (!$system_user){
	setstatus('Er is geen systeemrekening gedefiniëerd. Voor massatransacties, moet er een systeemrekening zijn, 
		waarnaar de transacties uitgeschreven kunnen worden.', 'warning'); 
}

foreach($active_users as $user){
	$req->add('amount-'.$user['id'], 0, 'post', array('type' => 'number', 'size' => 3, 'maxlength' => 3, 'onkeyup' => 'recalc_table_sum(this);'), array('match' => 'positive'));
}

if ($req->get('create') && !$req->errors() && $system_user){
	
	$notice = '';
	$transid = $req->get('transid');
	$description = $req->get('description');		
	$db->beginTransaction(); 
	try {		
		foreach($active_users as $user){
			$amount = $req->get('amount-'.$user['id']);
			if (!$amount){
				continue;
			}	
			$trans = array(
				'id_to' => $system_user['id'],
				'id_from' => $user['id'],
				'amount' => $amount,
				'description' => $description,
				'date' => date('Y-m-d H:i:s'),
				'cdate' => date('Y-m-d H:i:s'),
				'creator' => $req->getSid(),
				'transid' => $transid);
			
			if ($db->fetchColumn('select id from transactions where transid = ?', array($transid))){
				throw new Exception('Dubbele boeking van een transactie werd voorkomen.');
			}
			$db->insert('transactions', $trans);
			$db->update('users', array('saldo' => $system_user['saldo'] + $amount), array('id' => $system_user['id'])); 
			$db->update('users', array('saldo' => $user['saldo'] - $amount), array('id' => $user['id']));
			
			$transid = generateUniqueId();
			$notice .= 'Transactie van gebruiker '.$user['letscode'].' '.$user['fullname'].' naar '.$system_user['letscode'].' '.$system_user['fullname'].' met bedrag '.$amount."\n\r";
		}
		$db->commit(); 
		$req->setSuccess();	
			
	} catch (Exception $e) {
		$db->rollback();
		setstatus($e->getMessage(), 'danger');
		log_event('', 'Trans', 'failed mass-transaction.');						
	}
	
	if ($req->isSuccess()){
		setstatus($notice, 'success');
		
		// mail here 
	}		
	$req->set('description', '');
	$req->set('confirm_password', '');
}

if ($req->isSuccess()){
	header('location: transactions.php');
	exit;
}

	
$fixed = $req->get('fixed');
$percentage = $req->get('percentage');
$percentage_base = $req->get('percentage_base');
$perc = 0;


if ($req->get('fill_in') && ($fixed || $percentage)){
	foreach ($active_users as $user){
		if ($percentage){
			$perc = round(($user['saldo'] - $percentage_base)*$percentage/100);
			$perc = ($perc > 0) ? $perc : 0;
		}
		$amount = $fixed + $perc;			
		if ($user['letscode'] == $req->get('letscode_to') 
			|| (((time() - ($parameters['new_user_days'] * 86400)) < $user['unix']) && $req->get('no_newcomers')) 
			|| ($user['status'] == 2 && $req->get('no_leavers'))
			|| ((-$user['saldo'] + $amount) > $user['maxlimit'])){
			$req->set('amount-'.$user['id'], 0);	
		} else {
			$req->set('amount-'.$user['id'], $amount);
		}
	}
}

$req->set('amount-'.$to_user_id, 0);
$req->set('confirm_password', '');

$table = new data_table();
$table->set_data($active_users)->set_input($req)
	->add_column('letscode', array('title' => 'Van LetsCode', 'href_id' => 'id', 'href_base' => 'users.php'))
	->add_column('fullname', array('title' => 'Naam', 'href_id' => 'id', 'href_base' => 'users.php'))
	->add_column('accountrole', array('title' => 'Rol', 'footer_text' => 'TOTAAL'))
	->add_column('saldo', array('title' => 'Saldo', 'footer' => 'sum'))
	->add_column('amount', array('title' => 'Bedrag', 'input' => 'id', 'footer' => 'sum'))
	->add_column('maxlimit', array('title' => 'Limiet +/-'));

$table->setRenderRowOptions(function ($row){
	$class = getUserClass($row);		
	return ($class) ? ' class="'.$class.'"' : '';
});



include($rootpath.'includes/header.php');

if ($notice) {
	echo '<div style="background-color: #DDDDFF;padding: 10px;">'.$notice.'</div>';
}	


echo '<h1><a href="transactions.php">Transacties</a></h1>';	
echo '<h1><a href="many_to_one.php">Massa-Transactie van actieve gebruikers naar de systeem-rekening.</a></h1><p>bvb. voor leden-bijdrage.</p>';	

echo '<form method="get" class="trans">';
echo '<h3>Hulp om alle bedragen snel in te geven.</h3>';
echo '<table  cellspacing="5" cellpadding="0" border="0">';
$req->set_output('tr')->render(array('fixed', 'percentage', 'percentage_base', 'no_newcomers', 'no_leavers', 'fill_in'));
echo '</table>';
echo '<p>Je kan een vast bedrag en/of een percentage voor de saldi invullen. Als een percentage wordt ingevuld, worden de bedragen berekend t.o.v. percentage saldo basis.
	Positieve percentages hebben alleen betrekking op een positief verschil tussen saldo en het percentage saldo basis en negatieve percentages alleen op een negatief verschil.</p>';
echo '<p>Bedrag = Vast Bedrag + ((Percentage / 100 x (Saldo - Percentage Saldo Basis)) > 0)</p>';	
echo '<p>Saldo\'s te dicht bij de limiet worden altijd overgeslagen.</p>';		
echo '</form>';
echo '<form method="post" class="trans form-horizontal" role="form"><div class="bg-white">';	
$table->render();	
echo '</div>';
if ($system_user){
	echo '<p>Alle bedragen worden overgeschreven aan de systeemrekening '.$system_user['letscode'].' '.$system_user['fullname'].'</p>';
} else {
	echo '<p>Er is geen systeemrekening gedefiniëerd, dus zijn massa-transacties niet mogelijk.</p>';
	$req->setDisabled(array('description', 'confirm_password', 'create'));
}
$req->set_output('formgroup')->render(array('description', 'confirm_password'));
echo '<div>';
$req->set_output('nolabel')->render(array('create', 'cancel', 'transid'));
echo '</div></form>';	

include 'includes/footer.php';

?>

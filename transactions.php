<?php

ob_start();
require('./includes/default.php');


require_once($rootpath.'includes/inc_transactions.php');
require_once($rootpath.'includes/inc_userinfo.php'); 
require_once($rootpath.'includes/inc_mailfunctions.php');

require('./includes/request.php');
require('./includes/data_table.php');
require('./includes/pagination.php');

$currency = readconfigfromdb('currency');

$req = new request('user');

$req->setEntityTranslation('Transactie')
	->setEntity('transactions')
	->setUrl('transactions.php')
	
	->add('orderby', 'date', 'get')
	->add('asc', 0, 'get')
	->add('userid', 0, 'get', array('type' => 'select', 'label' => 'Lid', 'option_set' => 'active_users'))
	->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'))
	->add('limit', 25, 'get')
	->add('start', 0, 'get')
	->add('mode', '', 'get')
	->add('from_user_id', $s_id, 'post', array('type' => 'select', 'label' => 'Van', 'option_set' => 'active_users', 'admin' => true), array('not_empty' => true))
	->add('date', date('Y-m-d'), 'post')
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('amount', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 6, 'label' => 'Aantal '.$currency , 'autocomplete' => 'off'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('transid', generate_transid(), 'post', array('type' => 'hidden'))		
	->addSubmitButtons()
	
	->cancel()
	->setOwnerParam('from_user_id')
	->query()
	->queryOwner();
	
$new = false;	
	
if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	$new = $req->errorsCreate(array('from_user_id', 'date', 'letscode_to', 'amount', 'description', 'cdate', 'date'));	
}	
		
if ($req->isSuccess()){
	header('location: transactions.php');
	exit;	
}	

	
include('./includes/header.php');


echo '<ul class="hormenu">';
if ($req->isUser() && !$req->get('mode')){	
	echo '<li><a href="./transactions.php?mode=new")>Toevoegen</a></li>';
}
if ($req->isAdmin() && !$req->get('mode')){
	echo '<li><a class="admin" href="./transactions/many_to_one.php")>Massa-Transactie</a></li>';	
}		
echo '</ul>';


echo '<h1><a href="transactions.php">Transacties</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;

if ($new && $req->isUser())
{
	echo '<h1>Toevoegen</h1>';
	echo '<form method="post" class="trans" action="transactions.php">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	$from_user_id = ($req->isAdmin()) ? 'from_user_id' : 'non_existing_dummy';
	$req->set_output('tr')->render(array($from_user_id, 'letscode_to',  'amount', 'description', 'confirm_password', 'transid'));
	echo '<tr><td colspan="2">';
	$req->set_output('nolabel')->render(array('create', 'create_plus', 'cancel'));
	echo '</td></tr></table></form>';	
		
} else {


	echo '<form method="GET" class="trans"><table>';
	$req->set_output('tr')->render(array('userid', 'filter'));
	echo '</tr></table></form>';

	$orderby = $req->get('orderby');
	$userid = $req->get('userid');
	$asc = $req->get('asc');

	$orderby = ($orderby == 'fromusername' || $orderby == 'tousername') ? $orderby : 'transactions.'.$orderby;
		
	$pagination = new Pagination($req);
	$where = ($userid) ? ' where id_to ='.$userid.' or id_from ='.$userid : '';
	$pagination->set_query('transactions'.$where);
	$query = 'SELECT *, 
		fromusers.id AS fromuserid, tousers.id AS touserid, 
		fromusers.name AS fromusername, tousers.name AS tousername, 
		fromusers.letscode AS fromletscode, tousers.letscode AS toletscode, 
		DATE_FORMAT(transactions.date, \'%d-%m-%Y\') AS date
		FROM transactions, users  AS fromusers, users AS tousers
		WHERE transactions.id_to = tousers.id
		AND transactions.id_from = fromusers.id ';
	$query .= ($userid) ?  ' AND (fromusers.id = '.$userid.' OR tousers.id = '.$userid.' )' : '';
	$query .= ' ORDER BY '.$orderby. ' ';
	$query .= ($asc) ? 'ASC ' : 'DESC ';
	$query .= $pagination->get_sql_limit();	
	$transactions = $db->GetArray($query);

	$table = new data_table();
	$table->set_data($transactions)->enable_no_results_message();

	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'date'	=> array_merge($asc_preset_ary, array(
			'title' => 'Datum')),
		'fromusername' => array_merge($asc_preset_ary, array(
			'title' => 'Van',
			'href_id' => 'fromuserid',
			'href_base' => './users.php',
			'prefix' => 'fromletscode', 
			)),
		'tousername' => array_merge($asc_preset_ary, array(
			'title' => 'Aan',
			'href_id' => 'touserid',
			'href_base' => './users.php',
			'prefix'     => 'toletscode',
			)),	
		'amount' => array_merge($asc_preset_ary, array(
			'title' => 'Bedrag',
			'cond_td_class' => 'right',
			'cond_param' => 'touserid',
			'cond_equals' => $req->get('userid'))),
		'description' => array_merge($asc_preset_ary, array(
			'title' => 'Omschrijving')));

	$table_column_ary[$req->get('orderby')]['asc'] = ($req->get('asc')) ? 0 : 1;
	$table_column_ary[$req->get('orderby')]['title_suffix'] = ($req->get('asc')) ? '&nbsp;&#9650;' : '&nbsp;&#9660;';

	foreach ($table_column_ary as $key => $data){
	
		$data['title_params'] = array_merge($req->get(array('userid')), array(
						'orderby' => $key,
						'asc' => $data['asc'],
						));
		$table->add_column($key, $data);
	}

	$pagination->render();
	$table->render();
	$pagination->render();

}

include('./includes/footer.php');


?>

<?php

ob_start();
require 'includes/default.php';


require_once($rootpath.'includes/inc_transactions.php');
require_once($rootpath.'includes/inc_userinfo.php'); 


require 'includes/request.php';
require 'includes/data_table.php';
require 'includes/pagination.php';


$req = new request('user');

$req->setEntityTranslation('Transactie')
	->setEntity('transactions')
	->setUrl('transactions.php')
	
	->add('orderby', 'date', 'get')
	->add('asc', 0, 'get')
	->add('userid', 0, 'get', array('type' => 'select', 'label' => 'Gebruiker', 'option_set' => 'active_users'))
	->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'))
	->add('limit', 25, 'get')
	->add('start', 0, 'get')
	->add('show', 'all', 'get', array('type' => 'hidden'))
	->add('mode', '', 'get')
	->add('id_from', $req->getSid(), 'post', array('type' => 'select', 'label' => 'Van', 'option_set' => 'active_users_without_interlets', 'admin' => true), array('not_empty' => true))
	->add('id_to', 0, 'post')
	->add('date', date('Y-m-d'), 'post')
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off', 'class' => 'typeahead-users'), array('not_empty' => true, 'match' => 'active_letscode'))
	->add('amount', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 6, 'label' => 'Aantal '.$parameters['currency_plural'] , 'autocomplete' => 'off'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('transid', generateUniqueId(), 'post', array('type' => 'hidden'))
			
	->addSubmitButtons()
	
	->cancel();
	
/*	->setOwnerParam('from_user_id')
	->query()
	->queryOwner(); */
	
$new = false;	
	
if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	list($letscode_to) = explode(' ', trim($req->get('letscode_to')));
	$user_from = $db->getRow('select * from users where id = '.$req->get('id_from').' and status in (1, 2, 4)');
	$max_from = $user_from['maxlimit'] + $user_from['saldo'];
	if (!$user_from){
		setstatus('Uitschrijvende gebruiker niet gevonden.', 'danger');
		
	} else if ($req->get('amount') > $max_from){
		setstatus('Het bedrag overschrijdt de limiet van de uitschrijvende rekening. Maximaal '.
			$max_from.' '.$parameters['currency_plural'].' kunnen uitgeschreven worden.' , 'danger');	
		
	} else if ((substr_count($letscode_to, '/'))){
		// interlets



		
	} else {	// local transaction
		$letscode_to = getLocalLetscode($letscode_to);
		$user_to = $db->getRow('select * from users where letscode = '.$letscode_to.' and status in (1, 2, 4)');
		$max_to = $user_to['maxlimit'] - $user_from['maxlimit'];
		
		if (!$user_to){
			setstatus('Gebruiker niet gevonden. (ongeldig letscode)', 'danger');
			
		} else if ($req->get('amount') > $max_to){
			setstatus('Het bedrag overschrijdt de limiet van de bestemmeling. De bestemmeling kan maximaal '.$max_to.
				' '.$parameters['currency_plural'].' ontvangen.', 'danger');
		
		} else {
		
			$req->set('id_to', $user_to['id']);
			$new = $req->errorsCreate(array('id_from', 'id_to', 'amount', 'description', 'cdate', 'date', 'transid'));	
			if (!$new){
				$db->Execute('update users set saldo = saldo + '.$req->get('amount').' where id = '.$req->get('id_to'));
				$db->Execute('update users set saldo = saldo - '.$req->get('amount').' where id = '.$req->get('id_from'));
				$mail_description = '\n\r
					Omschrijving: '.$req->get('description').'\n\r	
					transactie-id: '.$req->get('transid').'\n\r\n\r';
				sendemail(null, $req->get('id_to'),
					'['.$parameters['letsgroup_code'].'] Nieuwe Transactie ontvangen',
					'Dit is een automatisch bericht, niet antwoorden aub. \n\r\n\r'.
					$user_from['letscode'].' '.$user_from['name'].' schreef '.$req->get('amount').' '.$parameters['currency_plural'].' naar je over. \n\r
					Je nieuwe saldo bedraagt nu '.($user_to['saldo'] + $req->get('amount')).' '.$parameters['currency_plural'].
					$mail_descriiption);
				sendemail(null, $req->get('id_from'), 
					'['.$parameters['letsgroup_code'].'] Nieuwe Transactie uitgeschreven', 
					'Dit is een automatisch bericht, niet antwoorden aub. \n\r\n\r
					Je schreef '.$req->get('amount').' '.$parameters['currency_plural'].' naar '.$user_to['letscode'].' '.$user_to['name'].' over. \n\r
					Je nieuwe saldo bedraagt nu '.($user_to['saldo'] - $req->get('amount')).' '.$parameters['currency_plural'].
					$mail_description);
			} 				
		}
	}
}	


if ($req->isSuccess()){
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: transactions.php'.$param);
	exit;	
}





	
include 'includes/header.php';

echo '<div class="row"><div class="col-md-12">';

if ($req->isAdmin() && !$req->get('mode')){
	echo '<a href="transactions/many_to_one.php" class="btn btn-success pull-right">[admin] Massa-Transactie</a>';	
}
if ($req->isUser() && !$req->get('mode')){	
	echo '<a href="transactions.php?mode=new"  class="btn btn-success pull-right">Toevoegen</a>';
}
		
echo '<h1><a href="transactions.php">Transacties</a></h1>';
echo '</div></div>';

$new = ($req->get('mode') == 'new') ? true : $new;

if ($new && $req->isUser())
{
	echo '<h1>Toevoegen</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$from_user_id = ($req->isAdmin()) ? 'from_user_id' : 'non_existing_dummy';
	$req->set_output('formgroup')->render(array($from_user_id, 'letscode_to',  'amount', 'description', 'confirm_password', 'transid'));
	echo '<div>';
	$req->set_output('nolabel')->render(array('create', 'create_plus', 'cancel'));
	echo '</div></form>';
	
	echo '<script type="text/javascript" src="js/typeahead_users.js"></script>';
		
		
} else {


	echo '<form method="GET" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render('userid');
	echo '<div>';
	$req->set_output('nolabel')->render('filter');
	echo '</div></form>';

	$orderby = $req->get('orderby');
	$userid = $req->get('userid');
	$asc = $req->get('asc');
	$show = $req->get('show');

	$tabs = array(
		'all' => array('text' => 'Alle', 'class' => 'bg-white', 
			'where' => ''),	
		'system' => array('text' => 'Systeem', 'class' => 'bg-info',
			'where' => '= 4'),
		'interlets' => array('text' => 'Interlets', 'class' => 'bg-warning',
			'where' => '= 7'),
/*		'active' => array('text' => 'Actief', 'class' => 'bg-white', 
			'where' => 'UNIX_TIMESTAMP(adate) > '.(time() - 86400*$parameters['new_user_days']).' and status = 1 '),						
		'inactive' => array('text' => 'Inactief', 'class' => 'bg-inactive',
			'where' => 'in (3, 5, 6, 8, 9)'),   */
		);

	echo'<ul class="nav nav-tabs">';
	foreach ($tabs as $key => $filter){
		if ($filter['admin'] && !$req->isAdmin()){
			continue;
		}
		$class = ($show == $key) ? 'active '.$filter['class'] : $filter['class'];
		$class = ($class) ? ' class="'.$class.'"' : '';
		echo '<li'.$class.'><a href="transactions.php?show='.$key.'">'.$filter['text'].'</a></li>';
	}		
	echo '</ul><p></p>';

	$query_show = ($tabs[$show]['where']) ? ' and (fromusers.status '.$tabs[$show]['where'].' or tousers.status '.$tabs[$show]['where'].') ' : '';
	$query_userid = ($userid) ?  ' and (fromusers.id = '.$userid.' OR tousers.id = '.$userid.' )' : '';

	$orderby = ($orderby == 'fromusername' || $orderby == 'tousername') ? $orderby : 'transactions.'.$orderby;
		
	$pagination = new Pagination($req);
	$where = ($userid) ? ' where id_to ='.$userid.' or id_from ='.$userid : '';
	$pagination->set_query('transactions'.$where);
	$query = 'SELECT *, 
		fromusers.id AS fromuserid, tousers.id AS touserid, 
		fromusers.name AS fromusername, tousers.name AS tousername, 
		fromusers.letscode AS fromletscode, tousers.letscode AS toletscode, 
		fromusers.status as fromstatus, tousers.status as tostatus,
		DATE_FORMAT(transactions.date, \'%d-%m-%Y\') AS date
		FROM transactions, users  AS fromusers, users AS tousers
		WHERE transactions.id_to = tousers.id
		AND transactions.id_from = fromusers.id ';
	$query .= $query_userid.$query_show;
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

	$table->setRenderRowOptions(function ($row){
		global $parameters;
		$class = ($row['tostatus'] == 4 || $row['fromstatus'] == 4) ? 'info' : '';		
		$class = ($row['tostatus'] == 7 || $row['fromstatus'] == 7) ? 'warning' : $class;		
		return ($class) ? ' class="'.$class.'"' : '';
	});

	$pagination->render();
	$table->render();
	$pagination->render();

}

include 'includes/footer.php';


?>

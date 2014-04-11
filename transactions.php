<?php

ob_start();
require 'includes/default.php';

require_once 'includes/transactions.php';
require_once 'includes/userinfo.php'; 
require_once 'includes/mail.php'; 

require 'includes/request.php';
require 'includes/data_table.php';
require 'includes/pagination.php';


$req = new request('user');

$req->setEntityTranslation('Transactie')
	->setEntity('transactions')
	->setUrl('transactions.php')
	
	->add('orderby', 'cdate', 'get')
	->add('asc', 0, 'get')
	->add('limit', 25, 'get')
	->add('start', 0, 'get')	
	
	->add('q', '', 'get', array('type' => 'text', 'label' => 'Trefwoord', 'size' => 25, 'maxlength' => 25))	
	->add('postcode', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 8, 'label' => 'Postcode' ))	
	->add('userid', 0, 'get', array('type' => 'select', 'label' => 'Gebruiker', 'option_set' => 'active_users'))	
	->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'))

	->add('show', 'all', 'get', array('type' => 'hidden'))
	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->add('id_from', $req->getSid(), 'post', array('type' => 'select', 'label' => 'Van', 'option_set' => 'active_users_without_interlets', 'admin' => true), 
		array('not_empty' => true))
	->add('creator', $req->getSid(), 'post')
	->add('id_to', 0, 'post')
	->add('date', date('Y-m-d'), 'post')
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('letscode_to', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 10, 'label' => 'Aan LetsCode', 'autocomplete' => 'off', 'class' => 'typeahead-users'), 
		array('not_empty' => true, 'match' => 'active_letscode'))
	->add('amount', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 6, 'label' => 'Aantal '.$parameters['currency_plural'] , 'autocomplete' => 'off'), 
		array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'text', 'size' => 40, 'maxlength' => 60, 'label' => 'Omschrijving', 'autocomplete' => 'off'), array('not_empty' => true))
	->add('transid', generateUniqueId(), 'post', array('type' => 'hidden'))
			
	->addSubmitButtons()
	->cancel();

	
$new = false;	
	
if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	list($letscode_to) = explode(' ', trim($req->get('letscode_to')));
	$user_from = $db->fetchAssoc('select * from users where id = ? and status in (1, 2, 4)', array($req->get('id_from')));
	$max_from = $user_from['maxlimit'] + $user_from['saldo'];
	$local_letscode_to = getLocalLetscode($letscode_to);
	$user_to = $db->fetchAssoc('select * from users where letscode = ? and status in (1, 2, 4, 7)', array($local_letscode_to));
	$max_to = $user_to['maxlimit'] - $user_to['saldo'];	
	
	if (!$user_from){
		setstatus('Uitschrijvende gebruiker niet gevonden.', 'danger');
		
	} else if ($req->get('amount') > $max_from){
		setstatus('Het bedrag overschrijdt de limiet van de uitschrijvende rekening. 
			Maximaal '.$max_from.' '.$parameters['currency_plural'].' kunnen uitgeschreven worden.
			Saldo: '.$user_from['saldo'].', limiet: '.$user_from['maxlimit'] , 'danger');	
		
	} else if (!$user_to){
		setstatus('Bestemmeling niet gevonden. (ongeldige letscode)', 'danger');
			
	} else if ($user_to['id'] == $user_from['id']){
		setstatus('Uitschrijver en bestemmeling kunnen niet dezelfde zijn.', 'danger');
			
	} else if ($req->get('amount') > $max_to){
		setstatus('Het bedrag overschrijdt de limiet van de (locale) bestemmeling. De bestemmeling kan maximaal '.$max_to.
			' '.$parameters['currency_plural'].' ontvangen. Saldo: '.
			$user_to['saldo'].' limiet: '.$user_to['maxlimit'], 'danger');
		
	} else {
		$req->set('id_to', $user_to['id']);
		$params = array('id_from', 'id_to', 'amount', 'description', 'cdate', 'date', 'transid', 'creator');
		$new = $req->errors($params);	
		if (!$new){
			$db->beginTransaction(); 
			try {
				// local
				if ($db->fetchColumn('select id from transactions where transid = ?', array($req->get('transid')))){
					throw new Exception('Dubbele boeking van een transactie werd voorkomen.');
				}
				$db->insert('transactions', $req->get($params));
				$db->update('users', array('saldo' => $user_to['saldo'] + $req->get('amount')), array('id' => $req->get('id_to'))); 
				$db->update('users', array('saldo' => $user_from['saldo'] - $req->get('amount')), array('id' => $req->get('id_from'))); 
				// interlets
				if ((substr_count($letscode_to, '/'))){
					if ($user_to['status'] != 7){  // eLAS soap
						throw new Exception('Bestemmeling is geen interlets.');
					}
					$soap_url = get_contact($req->get('id_to'), 'web').'/soap/wsdlelas.php?wsdl';

					$apikey = $myletsgroup["remoteapikey"];
					$from_letscode = $parameters['letsgroup_code'];
					$client = new nusoap_client($soap_url, true);
					$error = $client->getError();
					if ($error){
						throw new Exception('Er kon geen verbinding worden gemaakt met de interlets groep.');
					}
					$result = $client->call('dopayment', array(
						'apikey' => "$myapikey", 
						'from' => "$from", 
						'real_from' => "$real_from", 
						'to' => "$letscode_to", 
						'description' => "$description", 
						'amount' => $amount, 
						'transid' => "$transid", 
						'signature' => "$signature"));
					if ($result != 'SUCCESS'){
						throw new Exception('De interlets transactie werd afgebroken met bericht: '.$result);	
					}
				}

				$db->commit(); 
				$req->setSuccess();
				
			} catch (Exception $e) {
				$db->rollback();
				setstatus($e->getMessage(), 'danger');
				log_event("","Soap","APIKEY failed for Transaction $transid");						
				//throw $e;
			}
			$req->renderStatusMessage('create');
			if ($req->isSuccess()){
				$n = "\r\n";
				$mail_description = $n.$n.'
					Omschrijving: '.$req->get('description').$n.'	
					transactie-id: '.$req->get('transid').$n.$n;
				sendemail(null, $req->get('id_to'),
					'['.$parameters['letsgroup_code'].'] Nieuwe Transactie ontvangen',
					'Dit is een automatisch bericht, niet antwoorden aub.'.$n.$n.
					$user_from['letscode'].' '.$user_from['name'].' schreef '.$req->get('amount').' '.$parameters['currency_plural'].' naar je over. '.$n.'
					Je nieuwe saldo bedraagt nu '.($user_to['saldo'] + $req->get('amount')).' '.$parameters['currency_plural'].
					$mail_description);
				sendemail(null, $req->get('id_from'), 
					'['.$parameters['letsgroup_code'].'] Nieuwe Transactie uitgeschreven', 
					'Dit is een automatisch bericht, niet antwoorden aub.'.$n.$n.'
					Je schreef '.$req->get('amount').' '.$parameters['currency_plural'].' naar '.$user_to['letscode'].' '.$user_to['name'].' over. '.$n.'
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
	echo '<a href="many_to_one.php" class="btn btn-success pull-right">[admin] Massa-Transactie</a>';	
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
	$from_user_id = ($req->isAdmin()) ? 'id_from' : 'non_existing_dummy';
	$req->set_output('formgroup')->render(array($from_user_id, 'letscode_to',  'amount', 'description', 'confirm_password', 'transid'));
	echo '<div>';
	$req->set_output('nolabel')->render(array('create', 'create_plus', 'cancel', 'mode'));
	echo '</div></form>';
	
	echo '<script type="text/javascript" src="js/typeahead_users.js"></script>';
		
		
} else {


	echo '<form method="GET" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render(array('q', 'postcode', 'userid'));
	echo '<div>';
	$req->set_output('nolabel')->render('filter');
	echo '</div></form>';

	$orderby = $req->get('orderby');
	$q = $req->get('q');
	$postcode = $req->get('postcode');
	$userid = $req->get('userid');
	$asc = $req->get('asc');
	$show = $req->get('show');

	$tabs = array(
		'all' => array('text' => 'Alle', 'class' => 'bg-white', 
			'where' => '1 = 1'),	
		'system' => array('text' => 'Systeem', 'class' => 'bg-info',
			'where' => 'ut.status = 4 or uf.status = 4'),
		'interlets' => array('text' => 'Interlets', 'class' => 'bg-warning',
			'where' => 'ut.status = 7 or uf.status = 7'),
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

	$where = ($tabs[$show]['where']) ? $tabs[$show]['where'] : '1 = 1';	

	$pagination = new Pagination($req);
	
	$qb = $db->createQueryBuilder();
	
	$qb->select('t.id', 't.amount', 't.cdate', 't.id_from', 't.id_to', 't.real_from', 't.real_to', 't.description',
		'ut.name as username_to', 'ut.letscode as letscode_to', 
		'uf.name as username_from', 'uf.letscode as letscode_from') 
		->from('transactions', 't')
		->join('t', 'users', 'ut', 'ut.id = t.id_to')
		->join('t', 'users', 'uf', 'uf.id = t.id_from')
		->where($where);
	if ($userid){
		$qb->andWhere($qb->expr()->orX(
			$qb->expr()->eq('ut.id', $userid),
			$qb->expr()->eq('uf.id', $userid)
		));	
	}
	if ($q){
		$qb->andWhere($qb->expr()->like('t.description', '\'%'.$q.'%\''));
	}
	if ($postcode){
		$qb->andWhere($qb->expr()->orX(
			$qb->expr()->eq('ut.postcode', $postcode),
			$qb->expr()->eq('uf.postcode', $postcode)
			));
	}

	$pagination->setQuery($qb);
		
	$qb->orderBy('t.'.$req->get('orderby'), ($req->get('asc')) ? 'asc ' : 'desc ')
		->setFirstResult($pagination->getStart())
		->setMaxResults($pagination->getLimit());

	$transactions = $db->fetchAll($qb);


	$table = new data_table();
	$table->set_data($transactions)->enable_no_results_message();

	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'cdate'	=> array_merge($asc_preset_ary, array(
			'title' => 'Datum',
			'func' => function($row){ 
				return date('d-m-Y', strtotime($row['cdate']));
			}, 
			)),
		'username_from' => array_merge($asc_preset_ary, array(
			'title' => 'Van',
			'href_id' => 'id_from',
			'href_base' => 'users.php',
			'prefix' => 'letscode_from', 
			)),
		'username_to' => array_merge($asc_preset_ary, array(
			'title' => 'Aan',
			'href_id' => 'id_to',
			'href_base' => 'users.php',
			'prefix'     => 'letscode_to',
			)),	
		'amount' => array_merge($asc_preset_ary, array(
			'title' => 'Bedrag',
			'cond_td_class' => 'right',
			'cond_param' => 'id_to',
			'cond_equals' => $req->get('userid'),
			)),
		'description' => array_merge($asc_preset_ary, array(
			'title' => 'Omschrijving',
			)));

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

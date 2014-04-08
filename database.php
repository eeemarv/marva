<?php

use Symfony\Component\Yaml\Yaml;

ob_start();

require_once 'includes/default.php';

require_once 'includes/request.php';

$req = new request('admin');

$req->setEntity('database')
	->setUrl('database.php')
	->add('download', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Download'))
	->add('store', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Bewaar op de server'))
	->add('gzip', 'checked', 'post', array('type' => 'checkbox', 'label' => 'gzip compressie')) 
	
	->add('count_messages', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Hertel berichten in categorieën'))	
	->add('sum_saldo', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Herbereken saldo\'s'))	
	->addSubmitButtons()	
	->cancel();


//$req->set('gzip', false);

if ($req->get('store') || ($req->get('download') && $parameters['allow_db_download'])){

	include 'parameters.php';
	 
	$parameters = array_merge_recursive(Yaml::parse('site/parameters.yml'), $parameters); 

	$filename = 'backup__'.$parameters['db']['dbname'].'__'.date('Y-m-d_H_i_s').'__.sql'.(($req->get('gzip')) ? '.gz' : '');
	
	$command = 'mysqldump --user='.$parameters['db']['user'].
		' --password='.$parameters['db']['password'].
		' --host='.$parameters['db']['host'].
		' '.$parameters['db']['dbname'].
		' --ignore-table='.$parameters['db']['dbname'].'.eventlog --ignore-table='.$parameters['db']['dbname'].'.city_distance';
		
	$command .= ($req->get('gzip')) ? ' | gzip --best' : '';
	$command .= ($req->get('download')) ? '' : ' >site/backup/'.$filename;
	

	if ($req->get('download')){
		$mime = ($req->get('gzip')) ? 'application/x-gzip' : 'plain/text';
		header('Content-Type: ' . $mime );
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		passthru($command);
		exit;
	} else {
		exec($command);
		setstatus('Backup commando uitgevoerd. Bestandsnaam: '.$filename, 'success');
		if (!file_exists('site/backup/'.$filename)){
			setstatus('File kon niet bewaard worden.', 'danger');
		}	
		$req->setSuccess();
	}		
}

if ($req->get('count_messages')){
	$mismatch = false;
	$categories = $db->fetchAll('select id, stat_msgs_wanted, stat_msgs_offers, name from categories');
	foreach ($categories as $category){
		$category_id = $category['id'];
		$count_wants = $category['stat_msgs_wanted'];
		$count_offers = $category['stat_msgs_offers'];
		$name = $category['name'];
		$new_count_offers = $db->fetchColumn('select count(id) from messages where msg_type = 1 and id_category = ?', array($category_id));
		$new_count_wants = $db->fetchColumn('select count(id) from messages where msg_type = 0 and id_category = ?', array($category_id));

		if ($new_count_offers != $count_offers){
			$mismatch = true;
			$db->update('categories', array('stat_msgs_offers' => $new_count_offers), array('id' => $category_id));
			setstatus('Voormalig aantal aanbod berichten : '.$count_offers.', nieuw aantal: '.$new_count_offers.' voor categorie '.$name, 'info');	
		}
		if ($new_count_wants != $count_wants){
			$mismatch = true;
			$db->update('categories', array('stat_msgs_wanted' => $new_count_wants), array('id' => $category_id));
			setstatus('Voormalig aantal vraag berichten : '.$count_wants.', nieuw aantal: '.$new_count_wants.' voor categorie '.$name, 'info');	
		}
		
	}
	if (!$mismatch){
		setstatus('Alle aantallen van berichten in categorieën waren correct.', 'success');
	}
	$req->setSuccess();	
	
}

if ($req->get('sum_saldo')){
	$mismatch = false;
	$users = $db->fetchAll('select id, letscode, name from users');
	foreach ($users as $user){
		$user_id = $user['id'];
		$balance = $db->fetchColumn('select saldo from users where id = ?', array($user_id));
		$min = $db->fetchColumn('select sum(amount) from transactions where id_from = ?', array($user_id));
		$plus = $db->fetchColumn('select sum(amount) from transactions where id_to = ?', array($user_id));
		
		$new_balance = $plus - $min;

		if ($new_balance != $balance){
			$mismatch = true;
			$db->update('users', array('saldo' => $new_balance), array('id' => $user_id));
			setstatus('Oud-saldo : '.$balance.', nieuw saldo: '.$new_balance.' voor gebruiker '.$user['letscode'].' '.$user['name'], 'info');	
		}
		
	}
	if (!$mismatch){
		setstatus('Alle saldo\'s waren correct.', 'success');
	}
	$req->setSuccess();
}



if ($req->isSuccess()){
	header('location: database.php');
	exit;	
}


include 'includes/header.php';


echo '<h1><a href="database.php">Database</a></h1>';

echo '<h1>Backup</h1>';

echo '<form method="post" class="form-horizontal trans" role="form">';

if (!$parameters['allow_db_download']){
	$req->setDisabled('download');
}

$req->set_output('formgroup')->render('gzip');
$req->set_output('nolabel')->render(array('store', 'download'));
echo '</form>';		

echo '<ul><li>download-functionaliteit kan worden aan- of afgezet worden met de parameter <b>allow_db_download</b></li>';
echo '<li>Tabellen <b>city_distance</b> en <b>eventlog</b> worden niet opgenomen in de backup.</li></ul>';


echo '<h1>Resyncroniseer</h1>';

echo '<form method="post" class="form-horizontal trans" role="form">';
$req->set_output('nolabel')->render(array('count_messages', 'sum_saldo'));
echo '</form>';	



include 'includes/footer.php';



?>

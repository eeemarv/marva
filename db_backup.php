<?php

use Symfony\Component\Yaml\Yaml;

ob_start();

require_once 'includes/default.php';

require_once 'includes/request.php';

$req = new request('admin');

$req->setEntity('db_backup')
	->setUrl('db_backup.php')
	->add('download', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Download'))
	->add('store', '', 'post', array('type' => 'submit', 'class' => 'btn btn-primary', 'label' => 'Bewaar op de server'))
	->add('gzip', 'checked', 'post', array('type' => 'checkbox', 'label' => 'gzip compressie')) 
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


if ($req->isSuccess()){
	header('location: db_backup.php');
	exit;	
}


include('includes/header.php');


echo '<h1><a href="db_backup.php">Database backup</a></h1>';

echo '<form method="post" class="form-horizontal trans" role="form">';

if (!$parameters['allow_db_download']){
	$req->setDisabled('download');
}

$req->set_output('formgroup')->render('gzip');
$req->set_output('nolabel')->render(array('store', 'download', 'cancel'));
echo '</form>';		

echo '<ul><li>download-functionaliteit kan worden aan- of afgezet worden met de parameter <b>allow_db_download</b></li>';
echo '<li>Tabellen <b>city_distance</b> en <b>eventlog</b> worden niet opgenomen in de backup.</li></ul>';

include 'includes/footer.php';



?>

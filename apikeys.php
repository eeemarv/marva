<?php
ob_start();
$rootpath = './';
require_once($rootpath.'includes/default.php');
require_once($rootpath.'includes/request.php');
require_once($rootpath.'includes/data_table.php');

$req = new request('admin');

$req->setEntityTranslation('Apikey')
	->setEntity('apikeys')
	->setUrl('apikeys.php')
	
	->add('id', 0, 'get|post', array('type' => 'hidden'))
	->add('mode', '', 'get')
	->add('apikey', sha1(uniqid().microtime()), 'post', array('type' => 'text', 'label' => 'Apikey', 'size' => 50, 'maxlength' => 40), array('not_empty' => true, 'min_length' => 30))
	->add('comment', '', 'post', array('type' => 'text', 'label' => 'commentaar', 'size' => 50, 'maxlength' => 60), array('not_empty' => true))
	->add('type', 'interlets', 'post', array('type' => 'hidden'))
	->addSubmitButtons()	
	->cancel()
	->query();

if ($req->get('delete') && $req->get('id')){
	$req->delete();
	
} else if ($req->get('create') && $req->isUser()){
	$new = $req->errorsCreate(array('apikey', 'type', 'comment'));
	
} 

if ($req->isSuccess()){
	header('location: apikeys.php');
	exit;	
}	

include($rootpath.'includes/header.php');

echo '<h1><a href="apikeys.php">Apikeys</a></h1>';

if ($req->get('mode') == 'delete' && $req->get('id')){
	
	$apikey = $req->getItem();
	
	echo '<h1>Verwijderen?</h1>';
	echo '<form method="post" action="apikeys.php" class="trans"><table cellspacing="5" cellpadding="0" border="0">';
	echo '<tr></tr><p>id : '.$apikey['id'].'</p>';
	echo '<p>apikey : '.$apikey['apikey'].'</p>';
	echo '<p>Commentaar : '.$apikey['comment'].'</p></tr><tr>';
	$req->set_output('td')->render(array('delete', 'cancel', 'id'));		
	echo '</tr></table></form>';
		
} else {
	$apikeys = $db->GetArray('select * from apikeys');

	$table = new data_table();

	$table->set_data($apikeys)
		->enable_no_results_message()
		->add_column('id', array('title' => 'id'))
		->add_column('apikey', array('title' => 'apikey'))
		->add_column('created', array('title' => 'Creatietijdstip'))
		->add_column('comment', array('title' => 'Commentaar'))
		->add_column('delete', array('title' => 'Verwijderen', 
				'text' => 'Verwijderen', 
				'href_id' => 'id', 
				'href_static_param' => '&mode=delete'))
		->render();

	echo '<h1>Apikey toevoegen</h1>';
	echo '<form method="post" class="trans"><table>';
	$req->set_output('tr')->render(array('apikey', 'type', 'comment', 'create'));
	echo '</table></form>';
}

include($rootpath.'includes/footer.php');

?>

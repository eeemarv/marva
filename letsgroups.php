<?php

ob_start();

require_once('./includes/inc_default.php');
require_once('./includes/inc_adoconnection.php');
 

require_once('./includes/request.php');
require_once('./includes/data_table.php');

$req = new request('user');



$req->setEntityTranslation('Letsgroep')
	->setEntity('letsgroups')
	->setUrl('letsgroups.php')
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->addSubmitButtons()	
	->cancel()
	->query();


$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	$req->delete();
	$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers'; // todo error check before update count
	$db->Execute('update categories set '.$column.' = '.$column.' - 1 where id = '.$req->get('id_category'));
	
} else if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	$new = $req->errorsCreate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'cdate', 'mdate'));
	if (!$new){
		$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers';
		$db->Execute('update categories set '.$column.' = '.$column.' + 1 where id = '.$req->get('id_category'));
	}	
	
} else if ($req->get('edit') && $req->get('id') && $req->isOwnerOrAdmin()){
	$edit = $req->errorsUpdate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'mdate'));
	
}	 


if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: letsgroups.php'.$param);
	exit;	
}		

include('./includes/inc_header.php');

if($req->isAdmin() && !$req->get('mode')){	
	echo '<ul class="hormenu"><li><a href="./messages.php?mode=new")>Toevoegen</a></li></ul>';
} 

echo '<h1><a href="letsgroups.php">Interlets Groepen</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('msg_type', 'id_category', 'content', 'description', 'amount'));
}

if (($new && $edit || $delete) && $req->isAdmin())
{
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="trans" action="letsgroups.php">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	if ($delete){
		echo '<tr><td colspan="2"><h2><a href="letsgroups.php?id='.$req->get('id').'">';
		echo ': '.$req->get('content').'</a></h2></td></tr><tr><td colspan="2">Door: ';
		$req->renderOwnerLink();
		echo '</td></tr><tr><td colspan="2"><p>'.$req->get('description').'</p></td></tr>';
	} else {
		$id_user = ($req->isAdmin()) ? 'id_user' : 'non_existing_dummy';
		$req->set_output('tr')->render(array($id_user, 'msg_type', 'id_category', 'content', 'description', 'amount'));
	}
	echo '<tr><td colspan="2">';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id', 'mode'));
	echo '</td></tr></table></form>';		
}



if (!$req->get('id') && !($new || $edit || $delete)){

	$letsgroups = $db->GetArray('select * from letsgroups where apimethod <> \'internal\'');

	$table = new data_table();
	$table->set_data($letsgroups)->enable_no_results_message();

	$table->add_column('shortname', array(
		'title' => 'Code',
		'href_id' => 'id',
		'href_target' => '_blank',
		));
	$table->add_column('groupname', array(
		'title' => 'Naam',
		'href_id' => 'id',
		'href_target' => '_blank',
		));

	$table->render();	

}

if ($req->get('id') && !($edit || $delete || $new)){
	$letsgroup = $req->getItem();





	
}

include('./includes/inc_footer.php');

?>



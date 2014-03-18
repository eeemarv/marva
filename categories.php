<?php
ob_start();
require_once('includes/default.php');

require_once('includes/request.php');
require_once('includes/data_table.php');

$req = new request('admin');

$req->setEntityTranslation('Categorie')
	->setEntity('categories')
	->setUrl('categories.php')
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->add('name', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Titel', 'maxlength' => 40), array('not_empty' => true, 'max_length' => 40))
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('id_creator', $req->getSid(), 'post')
	->add('id_parent', 0, 'get|post', array('type' => 'select', 'label' => 'Ouder-category', 'option_set' => 'maincategories'))
	->addSubmitButtons()	
	->cancel();

$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id')){
	if (sizeof($db->GetArray('select id from messages where id_category = '.$req->get('id')))){
		setstatus('De categorie kan niet worden verwijderd want ze bevat berichten.', 'error');
	} else {
		$req->delete();
	}
} else if ($req->get('create') || $req->get('create_plus')){
	$new = $req->errorsCreate(array('id_parent', 'cdate', 'id_creator', 'name'));	
} else if ($req->get('edit') && $req->get('id')){
	if ($req->get('id_parent') && sizeof($db->GetArray('select id from messages where id_category = '.$req->get('id')))){
		setstatus('De categorie kan geen oudercategory worden want ze bevat berichten.', 'error');	
	} else {
		$edit = $req->errorsUpdate(array('id_parent', 'name'));
	}
}

if ($req->isSuccess()){
	$param = ($req->get('create_plus')) ? '?mode=new' : '';	
	header('location: categories.php'.$param);
	exit;	
}


include('includes/header.php');

echo '<a href="categories.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';

echo '<h1><a href="categories.php">Categorie&#235;n</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('name', 'id_parent'));
}

if (($new || $edit || $delete) && $req->isAdmin()){
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="trans">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	if ($delete){
		if ($req->get('id_parent')){
			$parent = $db->getOne('select name from categories where id = '.$req->get('id_parent')). ' - ';
		} else {
			$parent = '';
		}
		echo '<tr><td colspan="2"><h2>'.$parent.$req->get('name').'</h2></td></tr>';
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

	$rows = $db->GetArray('SELECT * FROM categories ORDER BY name');
	$cats = $cat_children = array();
	foreach ($rows as $cat){
		$cat_children[$cat['id_parent']][] = $cat;	
	}	
	foreach ($cat_children[0] as $maincat){	
		$cats[] = $maincat;
		end($cats);
		$maincat_key = key($cats);
		$maincat_msg_num = 0;
		if (sizeof($cat_children[$maincat['id']])){
			foreach ($cat_children[$maincat['id']] as $cat){
				$cat['msg_num'] = $cat['stat_msgs_wanted'] + $cat['stat_msgs_offers'];
				$cat['prefix'] = ($cat['id_parent']) ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : ''; 
				$cats[] = $cat;
				$maincat_msg_num += $cat['msg_num'];
			}	
		}
		$cats[$maincat_key]['msg_num'] = $maincat_msg_num;
		$cats[$maincat_key]['children_num'] = sizeof($cat_children[$maincat['id']]);	
	}

	$table = new data_table();
	$table->set_data($cats)
		->enable_no_results_message()
		->add_column('name', array(
			'title' => 'Categorie', 
			'prefix' => 'prefix'))
		->add_column('msg_num', array(
			'title' => 'Vraag & Aanbod', 
			'href' => 'messages.php', 
			'href_param' => 'catid', 
			'href_id' => 'id',
			'show_when' => 'msg_num'))
		->add_column('delete', array(
			'title' => 'Verwijderen', 
			'text' => 'Verwijderen', 
			'href_id' => 'id', 
			'href_static_param' => '&mode=delete',
			'not_show_when' => 'msg_num'))
		->render();

}

include 'includes/footer.php';



?>

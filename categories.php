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
	->add('name', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Naam', 'maxlength' => 40), array('not_empty' => true, 'max_length' => 40))
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('id_creator', $req->getSid(), 'post')
	->add('id_parent', 0, 'get|post', array('type' => 'select', 'label' => 'Ouder-category', 'option_set' => 'maincategories'))
	->addSubmitButtons()	
	->cancel(false)
	->query();

$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id')){
	if ($db->fetchColumn('select id from messages where id_category = ?', array($req->get('id')))){
		setstatus('De categorie kan niet worden verwijderd want ze bevat berichten.', 'danger');
	} else if ($db->fetchColumn('select id from categories where id_parent = ?', array($req->get('id')))){
		setstatus('De categorie kan niet worden verwijderd want ze bevat subcategorieën.', 'danger');
	} else {
		$req->delete();
	}
} else if ($req->get('create') || $req->get('create_plus')){
	$new = $req->errorsCreate(array('id_parent', 'cdate', 'id_creator', 'name'));	
} else if ($req->get('edit') && $req->get('id')){
	if ($req->get('id_parent') && ($db->fetchColumn('select id from messages where id_category = ?', array($req->get('id'))))){
		setstatus('De categorie kan geen oudercategory worden want ze bevat berichten.', 'danger');	
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

if(!$req->get('mode')){
	echo '<a href="categories.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';
}

echo '<h1><a href="categories.php">Categorie&#235;n</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('name', 'id_parent'));
}

if (($new || $edit || $delete) && $req->isAdmin()){
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="form-horizontal trans" role="form" action="categories.php">';

	if ($delete){
		if ($req->get('id_parent')){
			$parent = $db->fetchColumn('select name from categories where id = ?', array($req->get('id_parent')));
		} else {
			$parent = '';
		}
		echo '<h2>'.$parent.$req->get('name').'</h2>';
	} else {
		$req->set_output('formgroup')->render(array('name', 'id_parent'));
	}
	echo '<tr><td colspan="2">';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id', 'mode'));
	echo '</td></tr></table></form>';		
}



if (!$req->get('id') && !($new || $edit || $delete)){

	$rows = $db->fetchAll('select * from categories order by name');
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
			'title' => 'Categorie (edit)', 
			'prefix' => 'prefix',
			'href_id' => 'id',
			'href_static_param' => '&mode=edit'))
		->add_column('msg_num', array(
			'title' => 'Vraag & Aanbod',
			'func' => function ($row) {
				if ($row['msg_num']){
					return '<a href="messages.php?catid='.$row['id'].'">'.$row['msg_num'].'</a>';
				}
				return '';
			},
			))
		->add_column('delete', array(
			'title' => 'Verwijderen', 
			'func' => function ($row){
				if ($row['msg_num'] || $row['children_num']){
					return '';
				}
				return '<a href="categories.php?mode=delete&id='.$row['id'].'">Verwijderen</a>';
			},	
			))
		->render();

}

include 'includes/footer.php';



?>

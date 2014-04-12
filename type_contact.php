<?php
ob_start();
$rootpath = "./";
require_once $rootpath.'includes/default.php';

require_once $rootpath.'includes/request.php';
require_once $rootpath.'includes/data_table.php';

$protected = array('mail', 'tel', 'gsm', 'adr', 'web');

$req = new request('admin');

$req->setUrl('type_contact.php')
	->setEntity('type_contact')
	->setEntityTranslation('Contact type')
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->add('abbrev', '', 'post', array('type' => 'text', 'size' => 10, 'label' => 'Afkorting', 'maxlength' => 10), array('not_empty' => true, 'max_length' => 10))
	->add('name', '', 'post', array('type' => 'hidden'))
	->add('protect', 0, 'post', array('type' => 'hidden'))
	->addSubmitButtons()	
	->cancel(false)
	->query();	


$new = $delete = false;

if ($req->get('delete') && $req->get('id')){
	if ($db->fetchColumn('select id from contact where id_type_contact = ?', array($req->get('id')))){
		setstatus('Het contact type kon niet verwijderd worden want ze bevat contacten.', 'danger');
	} else {
		$req->delete();
	}
} else if ($req->get('create') || $req->get('create_plus')){
	$new = $req->errorsCreate(array('abbrev', 'name', 'protect'));	
} 

if ($req->isSuccess()){
	$param = ($req->get('create_plus')) ? '?mode=new' : '';	
	header('location: type_contact.php'.$param);
	exit;	
}


include($rootpath."includes/header.php");

if(!$req->get('mode')){
	echo '<a href="type_contact.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';
}

echo '<h1><a href="type_contact.php.php">Contact-Types</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('abbrev'));
}

if (($new || $delete) && $req->isAdmin()){
	echo '<h1>'.(($new) ? 'Toevoegen' : 'Verwijderen?').'</h1>';
	echo '<form method="post" class="form-horizontal trans" role="form" action="type_contact.php">';

	if ($delete){
		echo '<h2>'.$req->get('abbrev').'</h2>';
	} else {
		$req->set_output('formgroup')->render('abbrev');
	}
	echo '<tr><td colspan="2">';
	$submit = ($new) ? 'create' : 'delete';
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id', 'mode', 'name', 'protect'));
	echo '</td></tr></table></form>';		
}

if (!($new || $delete)){

	$contacttypes = $db->fetchAll('select abbrev, id from type_contact');;


	$table = new data_table();
	$table->set_data($contacttypes)
		->enable_no_results_message()
		->add_column('abbrev')
		->add_column('delete', array(
			'func' => function($row) use ($protected, $db){
				if (in_array($row['abbrev'], $protected)){
					return '<font color="red">Beschermd</font>';
				}
				if ($count = $db->fetchColumn('select count(id) from contact where id_type_contact = ?', array($row['id']))){
					return $count.' contacten';
				}
				return '<a href="type_contact.php?mode=delete&id='.$row['id'].'">Verwijderen</a>';
			}
			))
		->render();
}

include $rootpath.'includes/footer.php';


?>

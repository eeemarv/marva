<?php
ob_start();
require_once 'includes/default.php';

require_once 'includes/inc_userinfo.php'; 
require_once 'includes/mail.php'; 


require_once 'includes/request.php';
require_once 'includes/data_table.php';
require_once 'includes/pagination.php';

$accountrole_options = array(
	'user' => array('text' => 'user'),
	'admin' => array('text' => 'admin'),
	);
//	'interlets' => array('text' => 'interlets'),
	
	
$status_options = array(
	'new' => array('text' => 'nieuw (inactief)'), 
	'info' => array('text' => 'infopakket (inactief)'),
	'infomoment' => array('text' => 'infomoment (inactief)'), 	
	'active' => array('text' => 'actief'),
	'leaving' => array('text' => 'uitstapper (actief)'),	
	'inactive' => array('text' => 'gedesactiveerd (inactief)'),	
	);
//	'interlets_group' => array('text' => 'interlets groep')

$req = new request('user');

$req->setEntityTranslation('Gebruiker')
	->setEntity('users')
	->setUrl('users.php')
		
	->add('orderby', 'letscode', 'get')
	->add('asc', 1, 'get')
	->add('limit', 25, 'get')
	->add('start', 0, 'get')	

	->add('q', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 20, 'label' => 'Code of Naam'))
	->add('postcode_filter', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 8, 'label' => 'Postcode' ))
	
	->add('show', 'active', 'get', array('type' => 'hidden'))
	->add('view', 'account', 'get')
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('mdate', date('Y-m-d H:i:s'), 'post')	
	->add('adate', date('Y-m-d H:i:s'), 'post')	
	->add('name', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 50, 'label' => 'Gebruikersnaam', 'admin' => true), 
		array('not_empty' => true, 'unique' => true))
	->add('fullname', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 100, 'label' => 'Voor- en Achternaam', 'admin' => true), 
		array('not_empty' => true))
	->add('letscode', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 8, 'label' => 'Letscode', 'admin' => true), 
		array('not_empty' => true, 'unique' => true))
	->add('postcode', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 8, 'label' => 'Postcode', 'admin' => true), 
		array('not_empty' => true))
	->add('birthday', '', 'post', 
		array('type' => 'text', 'label' => 'Geboortedatum', 'placeholder' => 'dd-mm-jjjj', 'size' => 10, 'admin' => true, 
			'data-provide' => 'datepicker', 'data-date-format' => 'dd-mm-yyyy', 'data-date-week-start' => '1', 
			'data-date-view-mode' => '2', 'data-date-start-view' => '2', 'data-date-language' => 'nl'), 
		array('not_empty' => true, 'date' => true))
	->add('hobbies', '', 'post', array('type' => 'textarea', 'cols' => 50, 'rows' => 7, 'label' => 'Hobbies/Interesses'))
	->add('comments', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 100, 'label' => 'Commentaar'))	
	->add('admincomment', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 200, 'label' => 'Commentaar vd admin', 'admin' => true))	
	->add('login', sha1(uniqid().microtime()), 'post')
	->add('accountrole', 'user', 'post', array('type' => 'select', 'label' => 'Rechten', 'options' => $accountrole_options, 'admin' => true), 
		array('not_empty' => true))
	->add('status', 'new', 'post', array('type' => 'select', 'label' => 'Status', 'options' => $status_options, 'admin' => true), 
		array('not_empty' => true))
//	->add('minlimit', , 'post', array('type' => 'text', 'label' => 'Min limiet', 'size' => 10, 'admin' => true), array())
	->add('maxlimit', $parameters['default_limit'], 'post', array('type' => 'text', 'label' => 'Limiet +/-', 'size' => 10, 'admin' => true), 
		array('match' => 'positive_or_zero'))
	->add('mail', '', 'post', array('type' => 'text', 'label' => 'E-mail', 'size' => 50, 'maxlength' => 100), array('not_empty' => true, 'email' => true))
	->add('adr', '', 'post', array('type' => 'text', 'label' => 'Adres', 'size' => 50, 'maxlength' => 100, 'placeholder' => 'Voorbeeldstraat 86, 4572 Voorbeeldplaatsnaam'), 
		array('not_empty' => true))
	->add('tel', '', 'post', array('type' => 'text', 'label' => 'Telefoon', 'size' => 50, 'maxlength' => 20))
	->add('gsm', '', 'post', array('type' => 'text', 'label' => 'Gsm', 'size' => 50, 'maxlength' => 20))
	->add('web', '', 'post', array('type' => 'text', 'label' => 'Website', 'size' => 50, 'maxlength' => 100, 'placeholder' => 'http://voorbeeld.com'))
	->add('presharedkey', '', 'post', array('type' => 'text', 'label' => 'Preshared Key', 'size' => 50, 'maxlength' => 80, 'admin' => true, 'placeholder' => 'enkel voor interlets groepen'))
	->add('creator', $req->getSid(), 'post')
	->add('password', '', 'post')
	
	->add('mail_body', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 8), array('not_empty' => true, 'min_length' => 15))
	->add('mail_cc', 'checked', 'post', array('type' => 'checkbox', 'label' => 'Stuur een kopie naar mezelf'))
	->add('mail_send', '', 'post', array('type' => 'submit', 'label' => 'Versturen', 'class' => 'btn btn-primary'))	
	
	->add('image_file', '', 'post', array('type' => 'file', 'label' => 'Foto formaat .jpg of .jpeg max. 300kB', 'class' => 'btn btn-default'))
	->add('image_send', '', 'post', array('type' => 'submit', 'label' => 'Foto toevoegen', 'class' => 'btn btn-success'))
	->add('image_delete', '', 'post', array('type' => 'submit', 'label' => 'Foto verwijderen', 'class' => 'btn btn-danger'))
	
	->add('interlets_file', '', 'post', array('type' => 'file', 'label' => 'File formaat .yml', 'class' => 'btn btn-default'))
	->add('interlets_import', '', 'post', array('type' => 'submit', 'label' => 'Importeer', 'class' => 'btn btn-primary'))
	->add('interlets_export', '', 'post', array('type' => 'submit', 'label' => 'Exporteer', 'class' => 'btn btn-primary'))

	->addSubmitButtons()
	->cancel()
	->setDataTransform('birthday', function ($in, $reverse = false){
			return dateFormatTransform($in, $reverse);
		})	

	->setOwnerParam('id')
	->query();

$new = $edit = $delete = false;

if ($req->get('mode') == 'new'){
	$req->setSecurityLevel('admin');	
}

if ($req->get('id')){
	$transaction_num = $db->fetchColumn('select count(id) from transactions where id_from = ? or id_to = ?', array($req->get('id'), $req->get('id')));
}

$user = $req->getItem();

if ($req->get('delete') && $req->get('id') && $req->isAdmin()){
	if ($transaction_num){
		setstatus('Een gebruiker die reeds transacties gedaan heeft, kan niet worden verwijderd.', 'danger');
		
	} else {	
		$req->delete();
		$user = $req->getItem();
		if ($user['PictureFile']){
			unlink('site/images/users/'.$user['PictureFile']);
		}
		$db->delete('contact', array('id_user' => $req->get('id')));
		$messages = $db->fetchAll('select * from messages where id_user = ?', array($req->get('id')));
		foreach ($messages as $message){
			$db->update('categories', array($column => $column.' - 1'),  array('id' => $message['id_category']));
			$message_images = $db->GetArray('select * from msgpictures where msgid = '.$message['id']);
			foreach ($message_images as $image){
				unlink('site/images/messages/'.$image['PictureFile']);
			}
			$db->delete('msgpictures', array('msgid' => $message['id']));
		}
		$db->delete('messages', array('id_user' => $req->get('id')));
		$db->delete('news', array('id_user' => $req->get('id')));
	}
	
} else if (($req->get('create') || $req->get('create_plus')) && $req->isAdmin()){
	$params = array('cdate', 'mdate', 'creator', 'comments', 'hobbies', 'name', 'birthday', 
			'letscode', 'postcode', 'login', 'accountrole', 'status', 'minlimit', 'maxlimit', 'fullname', 'admincomment',
			'adate');
	$contact_params = array('mail', 'tel', 'gsm', 'adr', 'web');
	$new = $req->errors(array_merge($params, $contact_params));
	
	if (!$new){
		$db->beginTransaction();
		try{
			$db->insert('users', $req->get($params));
			foreach($contact_params as $param => $value){
				if (!$value){
					continue;
				}
				$type_id = $db->fetchColumn('select id from type_contact where abbrev = \'?\'', array($param));
				$db->insert('contact', array('id_type_contact' => $type_id, 'value' => $value));
			}
			$req->setSuccess();
					
		} catch (Exception $e) {
			$db->rollback();
			throw $e;
		}
		$req->renderStatusMessage('create');	
			
	}
		
		
/*		$req->create(array('cdate', 'mdate', 'creator', 'comments', 'hobbies', 'name', 'birthday', 
			'letscode', 'postcode', 'login', 'accountrole', 'status', 'minlimit', 'maxlimit', 'fullname', 'admincomment',
			'adate'));
		if ($req->get('id')){ 
			
			$req->create_contact(array('mail', 'tel', 'gsm', 'adr'));
				
			$mail_id = $db->GetRow('select id from type_contact where abbrev = \'mail\'');
			$tel_id = $db->GetRow('select id from type_contact where abbrev = \'tel\'');
			$gsm_id = $db->GetRow('select id from type_contact where abbrev = \'gsm\'');
			$adr_id = $db->GetRow('select id from type_contact where abbrev = \'adr\'');
		
/////////////////////////////////////////			
			
			
			$db->execute('insert into contacts set id = ');
		}
	}	
	
*/
		
	
	
} else if ($req->get('edit') && $req->get('id') && $req->isAdmin()){
	
	$edit = $req->errorsUpdate(array('mdate', 'comments', 'hobbies', 'name', 'birthday', 
		'letscode', 'postcode', 'login', 'accountrole', 'status', 'minlimit', 'maxlimit', 'fullname', 'admincomment',
		'presharedkey', 'adate'));
		
} else if ($req->get('edit') && $req->get('id') && $req->isOwner()){
	
	$edit = $req->errorsUpdate(array('mdate', 'comments', 'hobbies'));

} else if ($req->get('create_letsgroup')){


	
} else if ($req->get('image_send') && $req->get('id') && $req->isOwnerOrAdmin()){
	$filename = $_FILES['image_file']['name'];
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	$size = $_FILES['image_file']['size'] / 1024;
	$type = $_FILES['image_file']['type'];
	$error = $_FILES['image_file']['error'];
	$tmp_name = $_FILES['image_file']['tmp_name'];	
	if (!$filename){
		setstatus('Selecteer eerst een foto-bestand alvorens op te laden.', 'danger');
		
	} elseif (!in_array($ext, array('jpg', 'JPG', 'jpeg', 'JPEG'))){
		setstatus('Ongeldige bestands-extensie. De bestands-extensie moet jpg of jpeg zijn.', 'danger');
		
	} elseif (!in_array($type, array('image/jpeg', 'image/jpg', 'image/pjpeg'))) {
		setstatus('Ongeldig bestands-type.', 'danger');
		
	} elseif ($size > 300){
		setstatus('Te groot bestand. De maximum grootte is 300 kB.', 'danger');
		
	} elseif ($error) { 
		setstatus('Bestands-fout: '.$error, 'danger');
		
	} else {
		if ($user['PictureFile']){
			unlink('site/images/users/'.$user['PictureFile']);
		}	
		$filename = generateUniqueId().'-'.$req->get('id').'.'.strtolower($ext);			
		if (move_uploaded_file($tmp_name, $_SERVER[DOCUMENT_ROOT].'/site/images/users/'.$filename)){
			$db->update('users', array('PictureFile' => $filename), array('id' => $req->get('id')));
			log_event($req->get('id'),'Pict','User-Picture '. $filename.' uploaded');
			setstatus('Foto toegevoegd.', 'success');				
			$req->setSuccess();	
		} else {
			setstatus('Foto opladen is niet gelukt.', 'danger');
		}
	}
			
} else if ($req->get('image_delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	$result = $db->update('users', array('PictureFile' => null), array('id' => $req->get('id')));
	if ($result){
		unlink('site/images/users/'.$user['PictureFile']);		
		setstatus('Foto verwijderd', 'success');
	} else {
		setstatus('Fout bij het verwijderen.', 'danger');
	}	
	$req->setSuccess();
}

if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: users.php'.$param);
	exit;	
}	
	
	
include 'includes/header.php';


if ($req->isAdmin() && !$req->get('mode')){	
	echo '<a href="users.php?mode=interlets" class="btn btn-success pull-right">[admin] Interlets</a>';			
	echo '<a href="users.php?mode=new" class="btn btn-success pull-right">[admin] Toevoegen</a>';
}


echo '<h1><a href="users.php">Gebruikers</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('letscode', 'name', 'fullname', 'postcode', 'birthday', 'hobbies', 'comments', 'accountrole',
	'status', 'maxlimit', 'admincomment', 'presharedkey'));
}


if (($new && $req->isAdmin()) || (($edit && $req->isOwnerOrAdmin()) || ($delete && $req->isAdmin())))
{
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	if ($delete){
		echo '<h2><a href="users.php?id='.$req->get('id').'">'.$req->get('letscode').' '.$req->get('name').'</h2>';
	} else {
		$id_user = ($req->isAdmin()) ? 'id_user' : 'non_existing_dummy';
		$req->set_output('formgroup')->render(array($id_user, 'name', 'fullname', 
			'letscode', 'postcode', 'birthday', 'hobbies',  'comments', 
			'accountrole', 'status', 'maxlimit', 'admincomment', 
			'mail', 'adr', 'tel', 'gsm', 'web'));
	}
	echo '<div>';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id', 'mode'));
	echo '</div></form>';		
}


$image_delete = ($req->get('mode') == 'image_delete') ? true : false;

if ($image_delete && $req->isOwnerOrAdmin()){
	$plural = (sizeof($images) > 1) ? '\'s' : '';
	echo '<h1>Foto verwijderen?</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	echo '<div class="row">';			
	echo '<div class="col-md-4"><div class="thumbnail">';
	echo '<img src="site/images/users/'.$user['PictureFile'].'" alt="foto"/></div></div>';
	echo '</div>';
	$req->set_output('nolabel')->render(array('image_delete', 'cancel', 'id', 'mode'));
	echo '</div></form>';		
}

$interlets = ($req->get('mode') == 'interlets') ? true : false;

if ($interlets && $req->isAdmin()){
	echo '<h2>[admin]</h2>';
	echo '<h1>Interlets importeren</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render('interlets_file');
	$req->set_output('nolabel')->render(array('interlets_import', 'cancel', 'mode'));
	echo '</form>';
	echo '<h1>Interlets exporteren</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';

	$req->set_output('nolabel')->render(array('interlets_export', 'cancel', 'mode'));
	echo '</form>';			
}


if (!($new || $edit || $delete || $image_delete || $interlets)){
	echo '<form method="GET" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render(array('q', 'postcode_filter'));
	echo '<div>';
	$req->set_output('nolabel')->render('filter', 'show');
	echo '</div></form>';		
}


if (!$req->get('id') && !($new || $edit || $delete || $image_delete || $interlets)){
	
	$tabs = array(
		'active' => array('text' => 'Alle', 'class' => 'bg-white', 
			'where' => 'status in (1, 2, 4, 7) '),
		'new' => array('text' => 'Instappers', 'class' => 'bg-success', 
			'where' => 'UNIX_TIMESTAMP(adate) > '.(time() - 86400*$parameters['new_user_days']).' and status = 1 '),	
		'leaving' => array('text' => 'Uitstappers', 'class' => 'bg-danger',
			'where' => 'status = 2 '),
		'system' => array('text' => 'Systeem', 'class' => 'bg-info',
			'where' => 'status = 4 '),
		'interlets' => array('text' => 'Interlets', 'class' => 'bg-warning',
			'where' => 'status = 7'),
		'inactive' => array('text' => '[admin] Inactief', 'class' => 'bg-inactive', 'admin' => true,
			'tabs' => array(
				
			)),
		);

	echo'<ul class="nav nav-tabs">';
	foreach ($tabs as $key => $filter){
		if ($filter['admin'] && !$req->isAdmin()){
			continue;
		}
		$class = ($req->get('show') == $key) ? 'active '.$filter['class'] : $filter['class'];
		$class = ($class) ? ' class="'.$class.'"' : '';
		echo '<li'.$class.'><a href="users.php?show='.$key.'">'.$filter['text'].'</a></li>';
	}		
	echo '</ul><p></p>';	
	
	$inactive_tabs = array(
		'all' => array('text' => 'Alle', 'class' => 'inactive', 'admin' => true),
		'newly_registered' => array('text' => 'Nieuw geregistreerd', 'class' => 'inactive', 'admin' => true),
		'info_1' => array('text' => 'Info-pakket', 'class' => 'inactive', 'admin' => true),
		'info_2' => array('text' => 'Info-moment', 'class' => 'inactive', 'admin' => true),
		'deactivated' => array('text' => 'Gedesactiveerd', 'class' => 'inactive', 'admin' => true), 
		);		
	
	
	$q = $req->get('q');
	$orderby = $req->get('orderby');
	$asc = $req->get('asc');
	$postcode = $req->get('postcode_filter');
	$show = $req->get('show');
	
	
	$where = ($tabs[$show]['where']) ? $tabs[$show]['where'] : '1 = 1';	

	$pagination = new Pagination($req);

	$qb = $db->createQueryBuilder();
	
	$qb->select('id, letscode, fullname, saldo, postcode, status, 
			unix_timestamp(adate) as unix')
		->from('users', 'u')
		->where($where);
	if ($q){
		$qb->andWhere($qb->expr()->orX(
			$qb->expr()->like('u.fullname', '\'%'.$q.'%\''), 
			$qb->expr()->like('u.name', '\'%'.$q.'%\''),
			$qb->expr()->like('u.letscode', '\'%'.$q.'%\'')));
	}	
	if ($postcode){
		$qb->andWhere($qb->expr()->eq('u.postcode', $postcode));
	}
	
	$pagination->setQuery($qb);
	$pagination->setSum($qb, 'saldo', 'Totaal saldo: ');	

	$qb->orderBy('u.'.$req->get('orderby'), ($req->get('asc')) ? 'asc ' : 'desc ')
		->setFirstResult($pagination->getStart())
		->setMaxResults($pagination->getLimit());

	$users = $db->fetchAll($qb);


	$table = new data_table();

	$table->set_data($users)
		->enable_no_results_message();
		
	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'letscode'	=> array_merge($asc_preset_ary, array(
			'title' => 'Code',
			'href_id' => 'id',
			)),
		'fullname' => array_merge($asc_preset_ary, array(
			'title' => 'Naam',
			'href_id' => 'id',
			)),
		'saldo' => array_merge($asc_preset_ary, array(
			'title' => 'Saldo',
			'href_id' => 'id',
			'href_param' => 'userid',
			'href_base' => 'transactions.php',
		//	'footer' => 'sum',
			)),	
		'postcode' => array_merge($asc_preset_ary, array(
			'title' => 'Postcode')),
		);
	
	$table_column_ary[$req->get('orderby')]['asc'] = ($req->get('asc')) ? 0 : 1;
	$table_column_ary[$req->get('orderby')]['indicator'] = ($req->get('asc')) ? '&nbsp;&#9650;' : '&nbsp;&#9660;';

	foreach ($table_column_ary as $key => $data){
		
		$table->add_column($key, array(
			'title' => $data['title'],
			'title_suffix' => $data['indicator'],
			'title_params' => array_merge($req->get(array('q', 'postcode')), array(
				'orderby' => $key,
				'asc' => $data['asc'],
				)),
			'href_id' => $data['href_id'],
			'href_base' => $data['href_base'],
			'href_param' => $data['href_param'],
			));
	}
	
	
	$table->setRenderRowOptions(function ($row){
		$class = getUserClass($row);		
		return ($class) ? ' class="'.$class.'"' : '';
	});
	
	$pagination->render();

	$table->render();
	$pagination->render();

/*
	$views = array(	
		'account' => array('text' => 'Saldo\'s'),
		'phone' => array('text' => 'Telefoonnummers'),
		'address' => array('text' => 'Adressen'),
		'email' => array('text' => 'Email'),
		'transactions' => array('text' => 'Transacties');
	
	echo '<div class="panel panel-default"><h3>[admin] weergave</h3><ul class="nav nav-pills">';
	foreach ($views as $key => $view){
		$class = ($req->get('view') == $key) ? ' class="active"' : '';
		echo '<li'.$class.'><a href="users.php?view='.$key.'">'.$view['text'].'</a></li>';	
	}	
	echo '</ul></div>';
*/

	if (sizeof($users) == 1){
		$req->set('id', $users[0]['id'])
			->query();
		$transaction_num = $db->fetchColumn('select count(id) from transactions where id_from = ? or id_to = ?', array($req->get('id'), $req->get('id')));	
	}	
}
	
if ($req->get('id') && !($edit || $delete || $new || $image_delete)){


	$id = $req->get('id');
	
	echo '<link rel="stylesheet" type="text/css" href="vendor/jqplot/jqplot/jquery.jqplot.min.css" />
		<script type="text/javascript">var user_id = '.$id.';</script>	
		<script src="vendor/jqplot/jqplot/jquery.jqplot.min.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.donutRenderer.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.cursor.min.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
		<script src="vendor/jqplot/jqplot/plugins/jqplot.highlighter.min.js"></script>	
		<script src="js/graph_user_transactions.js"></script>';

	$req->setItemValue('unix', strtotime($req->getItemValue('adate')));
	$user = $req->getItem();
	
			
	if ($req->isAdmin()){
		$disabled = ($transaction_num) ? ' disabled="disabled"' : '';
		echo '<a href="users.php?mode=delete&id='.$req->get('id').'" class="btn btn-danger pull-right"'.$disabled.'>'.$req->getAdminLabel().'Verwijderen</a>';
	}
	if ($req->isOwnerOrAdmin()){			
		echo '<a href="users.php?mode=edit&id='.$req->get('id').'" class="btn btn-primary pull-right">'.$req->getAdminLabel().'Aanpassen</a>';
	}	

	$class = getUserClass($user);
	$class = ($class) ? ' class="bg-'.$class.'"' : '';
	echo '<div'.$class.'>';
	echo '<h1>'.trim($user['letscode']).'&nbsp;'.htmlspecialchars($user['fullname'],ENT_QUOTES).'</h1>';

	echo '<div class="row"><div class="col-md-4">';
	if ($user['PictureFile']){
		echo '<div class="thumbnail">';
		echo '<img src="site/images/users/'.$user['PictureFile'].'" alt="foto">';
		if ($req->isOwnerOrAdmin()){
			echo '<div class="caption"><p>';		
			echo '<a href="users.php?mode=image_delete&id='.$req->get('id').'" class="btn btn-danger">';
			echo $req->getAdminLabel().'Foto verwijderen</a></p></div>';			
		}
		echo '</div>';		
	}
	if ($req->isOwnerOrAdmin()){
		$label = ($user['PictureFile']) ? 'Foto vervangen' : $req->getLabel('image_send');	
		if ($req->isAdmin()){
			$req->setLabel('image_send', $req->getAdminLabel().$label);
		}		
		echo '<form method="post" class="trans form-horizontal" role="form" enctype="multipart/form-data">';
		$req->set_output('formgroup')->render('image_file');
		$req->set_output('nolabel')->render(array('image_send', 'id'));
		echo '</form>';			
	}
	echo '</div>';
	
	$want_count = $db->fetchColumn('select count(*) from messages where msg_type = 0 and id_user = ?', array($req->get('id')));
	$offer_count = $db->fetchColumn('select count(*) from messages where msg_type = 1 and id_user = ?', array($req->get('id')));
	$message_count = $want_count + $offer_count;
	
	echo '<div class="col-md-4">';
	echo '<div class="panel panel-default"><div class="panel-heading">Saldo</div>';
	echo '<div class="panel-body"><p><a href="transactions.php?userid='.$req->get('id').'">';
	echo getCurrencyText($user['saldo']).'</a></p>';
	echo '<p>limiet: +/-'.getCurrencyText($user['maxlimit']).'</p>';
//	echo '<p>transacties: <a href="transactions.php?userid='.$req->get('id').'">'.$transaction_num.'</a></p>';
	echo '</div></div>';
	
	echo '<div class="panel panel-default"><div class="panel-heading">';
	echo '<a href="messages.php?userid='.$req->get('id').'">';
	echo ($message_count).' Berichten</a></div><div class="panel-body">';
	echo '<p><a href="messages.php?userid='.$req->get('id').'&ow=w">'.$want_count.' Vraag</a></p>';
	echo '<p><a href="messages.php?userid='.$req->get('id').'&ow=o">'.$offer_count.' Aanbod</a></p></div>';	
	echo '</div></div>';
	
	
	echo '<div class="col-md-4"><div id="chartdiv2"></div><p>Transacties met andere gebruikers het laatste jaar</p></div>';

	echo '</div>';
	
	$contacts = get_contacts($req->get('id'), !$req->isOwnerOrAdmin());
	
	$contact_table = new data_table();

	$adr = '';
	$mail = '';
	foreach ($contacts as $key => $val){
		if ($val['abbrev'] == 'adr'){
			$adr = $val['value'];
			$adr = str_replace(',', '', $adr);
			$adr = str_replace(' ', '+', $adr);			
			continue;
		}
		if ($val['abbrev'] == 'mail'){
			$mail = $val['value'];
			continue;
		}		
	}	
		
	$contact_table->setRenderRowOptions(function ($row){
		return ($row['flag_public']) ? '' : ' class="inactive"';		
	});
	
	$contact_table->set_data($contacts)
		->add_column('abbrev')
		->add_column('value', array(
			'href_mail' => true,
			'href_adr' => true));	
	
	echo '<div class="row">';
		
	echo '<div class="col-md-4"><div id="chartdiv1"></div><p>Saldoverloop het laatste jaar</p></div>';
	echo '<div class="col-md-8">';
	echo '<div class="panel panel-default"><div class="panel-heading">Contact info</div>';
	$contact_table->render();
	
	if ($adr){
		echo '<iframe frameborder="0" style="border:0" width="100%" heigth="450"
			src="https://www.google.com/maps/embed/v1/place?key='.$parameters['google_maps_api_key'].'&q='.$adr.'"></iframe>';
	}
	
	echo '</div></div></div>';	

		
	echo '<div class="row">';
	
	$col = ($req->isOwnerOrAdmin()) ? '8' : '12';
	
	echo '<div class="col-md-'.$col.'">';

	echo '<div class="panel panel-default"><div class="panel-heading">Interesses en hobbies</div>';
	echo '<div class="panel-body">'.$user['hobbies'].'</div></div>';	
	
	echo '<div class="panel panel-default"><div class="panel-heading">Commentaar</div>';
	echo '<div class="panel-body">'.$user['comments'].'</div></div></div>';		
	
	if ($req->isOwnerOrAdmin()){
		echo '<div class="col-md-4"><div class="panel panel-default">';
		echo '<div class="panel-heading">'.$req->getAdminLabel().'Geboortedatum</div>';
		echo '<div class="panel-body">'.$user['birthday'].'</div></div>';
		echo '<div class="panel panel-default">';
		echo '<div class="panel-heading">'.$req->getAdminLabel().'Postcode</div>';
		echo '<div class="panel-body">'.$user['postcode'].'</div></div>';
		echo '</div>';
	}

	echo '</div>';
	
	if ($req->isAdmin() && $user['admincomment']){
		echo '<div class="row"><div class="col-md-12">';
		echo '<div class="panel panel-default"><div class="panel-heading">[admin] Commentaar van de Admin</div>';
		echo '<div class="panel-body">'.$user['admincomment'].'</div></div>';	
		echo '</div></div>';
	}
		

	if (empty($mail) || !$req->isUser() || $req->isOwner()){
		$req->setDisabled(array('mail_send', 'mail_body', 'mail_cc'));
	}
	$req->setLabel('mail_body', 'Je bericht naar '.$user['letscode'].' '.$user['name']);	
	
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render(array('mail_body', 'mail_cc'));
	echo '<div>';
	$req->set_output('nolabel')->render(array('mail_send', 'id'));
	echo '</div></form>';
		
	echo '</div>';
	
}		

include 'includes/footer.php';



?>

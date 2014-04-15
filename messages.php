<?php

ob_start();

require_once 'includes/default.php';

require_once 'includes/userinfo.php'; 
require_once 'includes/mail.php'; 

require_once 'includes/request.php';
require_once 'includes/data_table.php';
require_once 'includes/pagination.php';

$req = new request('guest');

$currency = $parameters['currency_plural'];

$offer_want_options = array(
	'ow' => array('text' => ''),
	'w' => array('text' => 'Vraag'),
	'o' => array('text' => 'Aanbod'),
	);

$req->setEntityTranslation('Bericht')
	->setEntity('messages')
	->setUrl('messages.php')
	
	->add('orderby', 'cdate', 'get')
	->add('asc', 0, 'get')
	->add('limit', 25, 'get')
	->add('start', 0, 'get')	
	->add('q', '', 'get', array('type' => 'text', 'label' => 'Trefwoord', 'size' => 25, 'maxlength' => 25))
	->add('ow', 'ow', 'get', array('type' => 'select', 'label' => 'Vraag-Aanbod', 'options' => $offer_want_options))
	->add('userid', 0, 'get', array('type' => 'select', 'label' => 'Gebruiker', 'option_set' => 'active_users'))
	->add('catid', 0, 'get', array('type' => 'select', 'label' => 'Categorie', 'option_set' => 'categories'))
	->add('postcode', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 8, 'label' => 'Postcode' ))
	->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'))
	->add('id', 0, 'get|post', array('type' => 'hidden'))		
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->add('msg_type', 'ow', 'post', array('type' => 'select', 'label' => 'Vraag-Aanbod', 'options' => $offer_want_options), 
		array('not_empty' => true, 'match' => array('o', 'w')))
	->add('id_user', $req->getSid(), 'post', array('type' => 'select', 'label' => $req->getAdminLabel().'Van', 'option_set' => 'active_users'), 
		array('not_empty' => true, 'match' => 'active_user'))
	->add('id_category', 0, 'post', array('type' => 'select', 'label' => 'Categorie', 'option_set' => 'subcategories'), 
		array('not_empty' => true, 'match' => 'subcategory'))
	->add('content', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Titel'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 15, 'label' => 'Inhoud'), 
		array('not_empty' => true))	
	->add('amount', 0, 'post', array('type' => 'number', 'size' => 4, 'maxlength' => 3, 'label' => 'Richtprijs ('.$currency.')'), 
		array('match' => 'positive'))
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('mdate', date('Y-m-d H:i:s'), 'post')
	
	->add('mail_body', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 8), array('not_empty' => true, 'min_length' => 15))
	->add('mail_cc', 'checked', 'post', array('type' => 'checkbox', 'label' => 'Stuur een kopie naar mezelf'))
	->add('mail_send', '', 'post', array('type' => 'submit', 'label' => 'Versturen', 'class' => 'btn btn-primary'))	
	
	->add('image_file', '', 'post', array('type' => 'file', 'label' => 'Foto bestand', 'help' => 'formaat .jpg of .jpeg maximaal 300kB'))
	->add('image_send', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen', 'class' => 'btn btn-success'))
	->add('image_delete', '', 'post', array('type' => 'submit', 'label' => 'Verwijderen', 'class' => 'btn btn-danger'))
	->add('image_id', 0, 'get|post', array('type' => 'hidden'))
	
	->addSubmitButtons()
	->cancel()
	
	->setDataTransform('msg_type', array(0 => 'w', 1 => 'o'))	
	
	->setOwnerParam('id_user')
	->query()
	->queryOwner()
	->renameItemParams(array('Description' => 'description'));

//	->dataTransform();

$images = array();
if ($req->get('image_id')){
	$image = $db->fetchAssoc('select * from msgpictures where id = ?', array($req->get('image_id')));
	if ($image){
		$images[] = $image;
	}
} else {
	$images = $db->fetchAll('select * from msgpictures where msgid = ?', array($req->get('id')));	
}	


$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	$db->beginTransaction();
	try{
		$db->delete('messages', array('id' => $req->get('id')));
		$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers'; 
		$db->update('categories', array($column => $column.' - 1'), array('id' => $req->get('id_category')));
		$db->delete('msgpictures', array('msgid' => $req->get('id')));		
		$db->commit();
		foreach($images as $image){
			unlink('site/images/messages/'.$image['PictureFile']);
		}
		$req->setSuccess();  
	} catch(Exception $e) {
		setstatus('Fout, bericht niet verwijderd.', 'danger');
		$conn->rollback();
		throw $e;
	}
	
} else if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	$new = $req->errorsCreate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'cdate', 'mdate'));
	if (!$new){
		$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers';
		$db->update('categories', array($column => $column.' + 1'), array('id' => $req->get('id_category')));
	}	
	
} else if ($req->get('edit') && $req->get('id') && $req->isOwnerOrAdmin()){
	$edit = $req->errorsUpdate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'mdate'));
	
} else if ($req->get('mail_send') && $req->get('id')){
	
	if (!$req->errors(array('mail_body', 'mail_cc', 'id'))){

		$letsgroup_code = $parameters['letsgroup_code'];
		
		$owner = $req->getOwner();
		$me = get_user($req->getSid());
		$contacts = get_contacts($req->getSid());

		$subject .= '['.$letsgroup_code .'] - Reactie op je V/A ' .$req->getItemValue('content');
		$from = $req->getSid();

		$to =  $req->getOwnerId();
		if ($req->get('mailcc')){
			$to[] = ($req->get('mailcc'));
		} 
		$ow = ($req->getItemValue('msg_type') == 'w') ? 'vraag' : 'aanbod';
		$content = 'Beste ' .$owner['fullname'] .'\r\n\n
			-- '.$me['fullname'].' heeft een reactie op je <a href="http://'.$_SERVER['HTTP_HOST'].
			'/messages.php?id='.$req->get('id').'">
			'.$ow.' '.$req->getItemValue('content').'</a> 
			verstuurd via Marva --\r\n\n'.$reactie.'\n\n
			* Om te antwoorden kan je gewoon reply kiezen of de contactgegevens hieronder gebruiken\n
			* Contactgegevens van '.$me['fullname'] .':\n';
		
		foreach($contacts as $key => $value){
			$content .= '* '.$value['abbrev'] .'\t' .$value['value'] .'\n';
		}
		
		$mailstatus = sendemail($from, $to, $subject, $content);
		
		$req->setSuccess();
	}	
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
		$filename = generateUniqueId().'-'.$req->get('id').'.'.strtolower($ext);			
		if (move_uploaded_file($tmp_name, $_SERVER[DOCUMENT_ROOT].'/site/images/messages/'.$filename)){
			$db->insert('msgpictures', array('msgid' => $req->get('id'), 'PictureFile' =>  $filename));
			log_event($userid, 'Pict', 'Message-Picture '. $filename.' uploaded');
			setstatus('Foto toegevoegd.', 'success');				
			$req->setSuccess();	
		} else {
			setstatus('Foto opladen is niet gelukt.', 'danger');
		}
	}
			
} else if ($req->get('image_delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	if ($req->get('image_id')){
        $result = $db->delete('msgpictures', array('id' => $req->get('image_id')));
	} else {
		$result = $db->delete('msgpictures', array('msgid' => $req->get('id')));
	}
	if (!$result){
		setstatus('Fout bij het verwijderen.', 'danger');
	} else if (sizeof($images)){
		foreach($images as $image){
			unlink('site/images/messages/'.$image['PictureFile']);
		}
		$plural = (sizeof($images) > 1) ? '\'s' : '';	
		setstatus('Verwijderen foto'.$plural.' voltooid', 'success');
	} else {
		setstatus('Geen foto gevonden', 'danger');
	}	
	$req->setSuccess();
}	 


if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: messages.php'.$param);
	exit;	
}		

include 'includes/header.php';

if($req->isUser() && !$req->get('mode')){	
	echo '<a href="messages.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';
} 

echo '<h1><a href="messages.php">Vraag & Aanbod</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount'));
}

if (($new && $req->isUser()) || (($edit || $delete) && $req->isOwnerOrAdmin()))
{
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	if ($delete){
		echo '<h2><a href="messages.php?id='.$req->get('id').'">';
		echo ($req->get('msg_type') == 'w') ? 'Vraag' : 'Aanbod';
		echo ': '.$req->get('content').'</a></h2><p>Door: ';
		$req->renderOwnerLink();
		echo '</p><p>'.$req->get('description').'</p>';
	} else {
		$id_user = ($req->isAdmin()) ? 'id_user' : 'non_existing_dummy';
		$req->set_output('formgroup')->render(array($id_user, 'msg_type', 'id_category', 'content', 'description', 'amount'));
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
	echo '<h1>Foto'.$plural.' Verwijderen?</h1>';
	echo '<form method="post" class="trans form-horizontal" role="form">';
	echo '<div class="row">';			
	foreach ($images as $image){
		echo '<div class="col-md-2"><div class="thumbnail">';
		echo '<img src="site/images/messages/'.$image['PictureFile'].'" alt="foto"/></div></div>';
	}
	echo '</div>';		
	$image_id = ($req->get('image_id')) ? 'image_id' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array('image_delete', 'cancel', 'id', 'mode', $image_id));
	echo '</div></form>';		
}


if (!($new || $edit || $delete || $image_delete)){
	echo '<form method="GET" class="form-horizontal trans" role="form">';
	$req->set_output('formgroup')->render(array('q', 'postcode', 'catid', 'userid', 'ow'));
	echo '<div>';
	$req->set_output('nolabel')->render('filter');
	echo '</div></form>';
}

if (!$req->get('id') && !($new || $edit || $delete || $image_delete)){

	$userid = $req->get('userid');
	$catid = $req->get('catid');
	$q = $req->get('q');
	$ow = $req->get('ow');
	$postcode = $req->get('postcode');

	$pagination = new Pagination($req);

	$qb = $db->createQueryBuilder();
	
	$qb->select('m.msg_type', 'm.content', 'm.cdate', 'm.id', 
		'date_format(m.cdate,\'%d-%m-%Y\') as date', 
		'u.name as username', 'u.id as userid', 'u.letscode')
		->from('messages', 'm')
		->join('m', 'users', 'u', 'u.id = m.id_user');
	if ($catid){
		$qb->join('m', 'categories', 'c', 'c.id = m.id_category');
	} 
	$qb->where('u.status in (1, 2, 4)');
	if ($userid){
		$qb->andWhere($qb->expr()->eq('u.id', $userid));	
	}
	if ($q){
		$qb->andWhere($qb->expr()->like('m.content', '\'%'.$q.'%\''));
	}
	if ($postcode){
		$qb->andWhere($qb->expr()->eq('u.postcode', $postcode));
	}
	if ($ow != 'ow'){
		$qb->andWhere($qb->expr()->eq('m.msg_type',  ($ow == 'w') ? 0 : 1));
	}	
	if ($catid){
		$qb->andWhere($qb->expr()->orX(
			$qb->expr()->eq('c.id', $catid), 
			$qb->expr()->eq('c.id_parent', $catid)
			));
	}

	$pagination->setQuery($qb);
		
	$qb->orderBy($req->get('orderby'), ($req->get('asc')) ? 'asc ' : 'desc ')
		->setFirstResult($pagination->getStart())
		->setMaxResults($pagination->getLimit());

	$messages = $db->fetchAll($qb);

	$table = new data_table();
	$table->set_data($messages)->enable_no_results_message();

	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'msg_type' => array_merge($asc_preset_ary, array(
			'title' => 'V/A',
			'string_array' => array('Vraag', 'Aanbod'),
			)),
		'content' => array_merge($asc_preset_ary, array(
			'title' => 'Omschrijving',
			'href_id' => 'id',
			)),				
		'username' => array_merge($asc_preset_ary, array(
			'title' => 'Wie',
			'href_id' => 'userid',
			'href_base' => 'users.php',
			'prefix'     => 'letscode',
			)),
		'cdate'	=> array_merge($asc_preset_ary, array(
			'title' => 'Datum',
			'replace_by' => 'date',
			)),		
		);	

	$table_column_ary[$req->get('orderby')]['asc'] = ($req->get('asc')) ? 0 : 1;
	$table_column_ary[$req->get('orderby')]['title_suffix'] = ($req->get('asc')) ? '&nbsp;&#9650;' : '&nbsp;&#9660;';

	foreach ($table_column_ary as $key => $data){
		$data['title_params'] = array_merge($req->get(array('userid', 'catid', 'ow', 'q', 'postcode')), array(
						'orderby' => $key,
						'asc' => $data['asc'],
						));
		$table->add_column($key, $data);
	}

	$pagination->render();
	$table->render();
	$pagination->render();

	if (count($messages) == 1){
		$req->set('id', $messages[0]['mid'])
			->query()
			->queryOwner()
			->renameItemParams(array('Description' => 'description'));
//			->dataTransform();
	}
}

if ($req->get('id') && !($edit || $delete || $new || $image_delete)){
	$message = $req->getItem();
	$owner = $req->getOwner();
	$category = $db->fetchAssoc('select name from categories where id = ?', array($message['id_category']));
	
	if ($req->isOwnerOrAdmin()){
		echo '<a href="messages.php?mode=delete&id='.$req->get('id').'" class="btn btn-danger pull-right">'.$req->getAdminLabel().'Verwijderen</a>';
		echo '<a href="messages.php?mode=edit&id='.$req->get('id').'" class="btn btn-primary pull-right">'.$req->getAdminLabel().'Aanpassen</a>';
	}
		
	$title = $message["content"];

	$contacts = get_contacts($owner['id']);
	
	$emailOwner = getEmailAddressFromUserId($owner['id']);

	echo '<h1>'.(($message['msg_type'] == 'o') ? 'Aanbod' : 'Vraag').': '.htmlspecialchars($message['content']).'</h1>';
	echo '<p>Ingegeven door: '; 
	$req->renderOwnerLink();
	echo '- <i> saldo: <a href="'.$rootpath.'transactions.php?userid='.$owner['id'].'">' .$owner['saldo'];
	echo '</a> ' .getCurrencyText($owner['saldo'], false);
	echo '</i> - <a href="messages.php?userid='.$owner['id'].'">Toon alle vraag en aanbod van ';
	echo $owner['letscode'].' '.$owner['name'].'</a></p>';
	
	echo '<p>Categorie: <a href="messages.php?catid='.$message['id_category'].'">'.$category['name'].'</a></p>'; // 
	
	$directurl = 'http://'.$_SERVER['HTTP_HOST'].'/messages.php?id='.$req->get('id');
	echo '<p>Link: <a href="'.$directurl.'">' .$directurl .'</a></p>';
	 
	echo '<div class="row">';

	if (sizeof($images)){
		
		echo '<div class="col-md-6">';
		echo '<div id="images-carousel" class="carousel slide messages-carousel" data-ride="carousel">';
		echo '<ol class="carousel-indicators">';
		foreach ($images as $key => $image){
			echo '<li data-target="#images-carousel" data-slide-to="'.$key.'" class="active"></li>';
		}
		echo '</ol>';
		echo '<div class="carousel-inner">';
		foreach ($images as $key => $image){
			echo '<div'.(($key) ? ' class="item"' : ' class="item active"').'>';
			$url = 'site/images/messages/'.$image['PictureFile'];
			echo '<img src="'.$url.'" alt="foto"></div>';
		}	
		echo '</div>';
		echo '<a class="left carousel-control" href="#images-carousel" data-slide="prev">';
		echo '<span class="glyphicon glyphicon-chevron-left"></span></a>';
		echo '<a class="right carousel-control" href="#images-carousel" data-slide="next">'; 
		echo '<span class="glyphicon glyphicon-chevron-right"></span></a>';
		echo '</div></div>';
	}

	echo '<div class="col-md-6">';
	
	$message_description = ($message['description']) ? nl2br(htmlspecialchars($message['description'],ENT_QUOTES)) : 
		'<p class="text-danger">Er werd geen omschrijving ingegeven</p>';
	$amount_text = ($message['amount']) ? 'Richtprijs: '.getCurrencyText($message['amount']) : 
		'<p class="text-danger">Er werd geen richtprijs ingegeven</p>';
	echo '<div class="panel panel-default"><div class="panel-heading">Omschrijving</div>';
	echo '<div class="panel-body">'.$message_description.'</div>';
	echo '<div class="panel-footer">'.$amount_text.'</div></div>';	

	$contact_table = new data_table();
	$contact_table->set_data($contacts)
		->add_column('abbrev')
		->add_column('value', array(
			'href_mail' => true,
			'href_adr' => true));	
	
	$contact_table->render();
	echo '</div></div>';

	if (sizeof($images) && $req->isOwnerOrAdmin()){
		if ($req->isAdmin()){
			echo '<div class="row"><div class="col-md-12"><h3>[admin]</h3></div></div>';
		}
		echo '<div class="row">';			
		foreach ($images as $image){
			echo '<div class="col-md-2"><div class="thumbnail">';
			echo '<img src="site/images/messages/'.$image['PictureFile'].'" alt="foto">';
			echo '<div class="caption"><p>';
			echo '<a href="messages.php?mode=image_delete&id='.$req->get('id').'&image_id='.$image['id'].'" class="btn btn-danger">';
			echo 'Verwijderen</a></p></div></div></div>';
		}
		echo '</div>';
		if (sizeof($images) > 1){			
			echo '<div class="row"><div class="col-md-12">';
			echo '<a href="messages.php?mode=image_delete&id='.$req->get('id').'" class="btn btn-danger">';
			echo $req->getAdminLabel().'Alle foto\'s verwijderen.</a></div></div>';
		}
	}


	if ($req->isOwnerOrAdmin()){
		if (sizeof($images) > 5){
			$req->setDisabled(array('image_file', 'image_send'));
		}
		echo '<div class="row"><div class="col-md-12">';
		if ($req->isAdmin()){
			$req->setLabel('image_send', $req->getAdminLabel().$req->getLabel('image_send'));
		}		
		echo '<form method="post" class="trans form-horizontal" role="form" enctype="multipart/form-data">';
		$req->set_output('formgroupfile')->render('image_file');
		$req->set_output('nolabel')->render(array('image_send', 'id'));
		echo '</form></div></div>';	
	}
	

	if (empty($emailOwner) || !$req->isUser() || $req->isOwner()){
		$req->setDisabled(array('mail_send', 'mail_body', 'mail_cc'));
	}
	$req->setLabel('mail_body', 'Je reactie naar '.$owner['letscode'].' '.$owner['name']);	
	
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render('mail_body');
	$req->set_output('formgroupcheckbox')->render('mail_cc');
	echo '<div>';
	$req->set_output('nolabel')->render(array('mail_send', 'id'));
	echo '</div></form>';	
	
}

include 'includes/footer.php';

?>



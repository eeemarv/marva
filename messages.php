<?php

ob_start();

require_once 'includes/default.php';


require_once('./includes/inc_userinfo.php'); 
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
	->add('msg_type', 'ow', 'post', array('type' => 'select', 'label' => 'Vraag-Aanbod', 'options' => $offer_want_options), array('not_empty' => true, 'match' => array('o', 'w')))
	->add('id_user', $req->getSid(), 'post', array('type' => 'select', 'label' => '[admin] Van', 'option_set' => 'active_users'), array('not_empty' => true, 'match' => 'active_user'))
	->add('id_category', 0, 'post', array('type' => 'select', 'label' => 'Categorie', 'option_set' => 'subcategories'), array('not_empty' => true, 'match' => 'subcategory'))
	->add('content', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Titel'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 15, 'label' => 'Inhoud'), array('not_empty' => true))	
	->add('amount', 0, 'post', array('type' => 'text', 'size' => 4, 'maxlength' => 3, 'label' => 'Richtprijs ('.$currency.')'), array('match' => 'positive'))
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('mdate', date('Y-m-d H:i:s'), 'post')
	->add('mailbody', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 8), array('not_empty' => true, 'min_length' => 15))
	->add('mailcc', 'checked', 'post', array('type' => 'checkbox', 'label' => 'Stuur een kopie naar mezelf'))
	->add('image_file', '', 'post', array('type' => 'file', 'label' => 'Foto formaat .jpg of .jpeg max. 300kB', 'class' => 'btn btn-default'))
	->add('image_send', '', 'post', array('type' => 'submit', 'label' => 'Toevoegen', 'class' => 'btn btn-success'))
	->addSubmitButtons()
	
	->cancel()
	->setOwnerParam('id_user')
	->query()
	->queryOwner()
	->renameItemParams(array('Description' => 'description'))
	->setDataTransform('msg_type', array(0 => 'w', 1 => 'o'))
	->dataTransform();

$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	$req->delete();
	$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers'; 
	$db->Execute('update categories set '.$column.' = '.$column.' - 1 where id = '.$req->get('id_category'));
	
} else if (($req->get('create') || $req->get('create_plus')) && $req->isUser()){
	$new = $req->errorsCreate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'cdate', 'mdate'));
	if (!$new){
		$column = ($req->get('msg_type') == 'w') ? 'stat_msgs_wanted' : 'stat_msgs_offers';
		$db->Execute('update categories set '.$column.' = '.$column.' + 1 where id = '.$req->get('id_category'));
	}	
	
} else if ($req->get('edit') && $req->get('id') && $req->isOwnerOrAdmin()){
	$edit = $req->errorsUpdate(array('id_user', 'msg_type', 'id_category', 'content', 'description', 'amount', 'mdate'));
	
} else if ($req->get('send') && $req->get('id')){
	
	if (!$req->errors(array('mailbody', 'mailcc', 'id'))){

		$systemtag = $parameters['letsgroup_code'];
		
		$user = get_user($req->getItemValue('id_user'));
		$me = get_user($req->getSid());
		$contact = get_contact($req->getSid());
		$usermail = get_user_maildetails($req->getItemValue('id_user'));
		$my_mail = get_user_maildetails($req->getSid());

		$subject .= '[Marva-'.$systemtag .'] - Reactie op je V/A ' .$req->getItemValue('content');
		$from = $my_mail['emailaddress'];

		$to =  $usermail['emailaddress'];
		$to .= ($req->get('mailcc')) ? ', '.$my_mail['emailaddress'] : '';

		$content = 'Beste ' .$user['fullname'] .'\r\n\n
			-- '.$me['fullname'].' heeft een reactie op je vraag/aanbod verstuurd via Marva --\r\n\n'.$reactie.'\n\n
			* Om te antwoorden kan je gewoon reply kiezen of de contactgegevens hieronder gebruiken\n
			* Contactgegevens van '.$me['fullname'] .':\n';
		
		foreach($contact as $key => $value){
			$content .= '* '.$value['abbrev'] .'\t' .$value['value'] .'\n';
		}
		
		$mailstatus = sendemail($from, $to, $subject, $content);
		
		$req->setSuccess();
	}	
} else if ($req->get('image_send') && $req->get('id') && $req->isOwnerOrAdmin()){
	$filename = $_FILES['image_file']['name'];
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	var_dump($req->get('image_file'), $_FILES['image_file']['name'], $ext);
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
		$filename = strtr(base64_encode(uniqid().microtime()), '+/=', '---').'-'.$req->get('id').'.'.strtolower($ext);
		var_dump('site/images/messages/'.$filename);			
		if (move_uploaded_file($tmp_name, $_SERVER[DOCUMENT_ROOT].'/site/images/messages/'.$filename)){
			$db->Execute('insert into msgpictures (msgid, PictureFile) values (\''.$req->get('id').'\', \''.$filename.'\')');
			log_event($userid,'Pict','Message-Picture '. $filename.' uploaded');
			setstatus('Foto toegevoegd.', 'success');				
			$req->setSuccess();	
		} else {
			setstatus('Foto opladen is niet gelukt.', 'danger');
		}
	}		
}		 


if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: messages.php'.$param);
	exit;	
}		

include 'includes/header.php';

if($req->isUser() && !$req->get('mode')){	
	echo '<a href="./messages.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';
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

if (!($new || $edit || $delete)){
	echo '<form method="GET" class="form-horizontal trans" role="form">';
	$req->set_output('formgroup')->render(array('q', 'postcode', 'catid', 'userid', 'ow'));
	echo '<div>';
	$req->set_output('nolabel')->render('filter');
	echo '</div></form>';
}

if (!$req->get('id') && !($new || $edit || $delete)){

	$userid = $req->get('userid');
	$catid = $req->get('catid');
	$q = $req->get('q');
	$ow = $req->get('ow');
	$postcode = $req->get('postcode');

	$pagination = new Pagination($req);

	$where_user = ($userid) ? 'and users.id = '.$userid.' ' : '';
	$where_q = ($q) ? 'AND messages.content like \'%' .$q .'%\' ' : '';
	$where_cat = ($catid) ? 'and (messages.id_category = '.$catid.' OR categories.id_parent = '.$catid .') ' : '';
	$where_ow = ($ow == 'ow') ? '' : ' and messages.msg_type = '.(($ow == 'w') ? '0' : '1');
	$where_postcode = ($postcode) ? 'and users.postcode = '.$postcode.' ' : '';

	$query_1 = 'select messages.msg_type, messages.content, messages.cdate, 
		messages.id AS mid, DATE_FORMAT(messages.cdate, \'%d-%m-%Y\') AS date, 
		users.name AS username, users.id AS userid, users.letscode ';
	$query_1 .= ($catid) ? ', categories.id_parent AS parent_id ' : '';
	$query_1 .= 'from ';

	$query_2 = 'messages, users'.(($catid) ? ', categories ' : ' ');
	$query_2 .= 'where messages.id_user = users.id ';
	$query_2 .= ($catid) ? 'and messages.id_category = categories.id ' : '';
	$query_2 .= 'and (users.status = 1 OR users.status = 2 OR users.status = 3) ';
	$query_2 .= $where_cat . $where_user . $where_q . $where_ow . $where_postcode;
		
	$pagination->set_query($query_2);

	$query = $query_1.$query_2.' ';
	$query .= 'order by '.$req->get('orderby'). ' ';
	$query .= ($req->get('asc')) ? 'asc ' : 'desc ';
	$query .= $pagination->get_sql_limit();

	$messages = $db->GetArray($query);

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
			'href_id' => 'mid',
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
			->renameItemParams(array('Description' => 'description'))
			->dataTransform();
	}
}

if ($req->get('id') && !($edit || $delete || $new)){
	$message = $req->getItem();
	$owner = $req->getOwner();
	
	if ($req->isOwnerOrAdmin()){
		$admin = ($req->isAdmin()) ? '[admin] ' : '';
		echo '<a href="messages.php?mode=delete&id='.$req->get('id').'" class="btn btn-danger pull-right">'.$admin.'Verwijderen</a>';
		echo '<a href="messages.php?mode=edit&id='.$req->get('id').'" class="btn btn-primary pull-right">'.$admin.'Aanpassen</a>';
	}
	
	
	$title = $message["content"];
	
	$contact = get_contact($owner['id']);
	
	$mailuser = get_user_maildetails($owner['id']);	
	

	echo '<h1>'.(($message['msg_type'] == 'o') ? 'Aanbod' : 'Vraag').': '.htmlspecialchars($message['content']).'</h1>';
	echo '<p>Ingegeven door: '; 
	$req->renderOwnerLink();
	echo '- <i> saldo: <a href="'.$rootpath.'transactions.php?userid='.$owner['id'].'">' .$owner['saldo'];
	echo '</a> ' .getCurrencyText($owner['saldo'], false);
	echo '</i> - <a href="messages.php?userid='.$owner['id'].'">Toon alle vraag en aanbod van ';
	echo $owner['letscode'].' '.$owner['name'].'</a></p>';
	
	echo '<p>Categorie: </p>'; // 
	
	$directurl = 'http://'.$_SERVER['HTTP_HOST'].'/messages.php?id='.$req->get('id');
	echo '<p>Link: <a href="'.$directurl.'">' .$directurl .'</a></p>';
	 

	$query = 'select contact.value, type_contact.abbrev
		from type_contact, contact
		where contact.id_user='.$req->getOwnerId().'
		and contact.id_type_contact = type_contact.id
		and contact.flag_public = 1';
	$contacts = $db->GetArray($query);
	
	$contact_table = new data_table();
	$contact_table->set_data($contacts)
		->add_column('abbrev')
		->add_column('value', array(
			'href_mail' => true,
			'href_adr' => true));
	
	$contact_table->render();


	$images = $db->GetArray('SELECT * FROM msgpictures WHERE msgid = ' .$req->get('id'));

	if (count($images)){
		
		echo '<div id="images-carousel" class="carousel slide" data-ride="carousel">';
		echo '<ol class="carousel-indicators">';
		foreach ($images as $key => $row){
			echo '<li data-target="#images-carousel" data-slide-to="'.$key.'" class="active"></li>';
		}
		echo '</ol>';
		echo '<div class="carousel-inner">';
		foreach ($images as $key => $row){
			echo '<div'.(($key) ? '' : ' class="active"').'>';
			echo '<img src="site/images/messages/'.$row['PictureFile'].'" alt="foto"></div>';
		}	
		echo '</div>';
		echo '<a class="left carousel-control" href="#images-carousel" data-slide="prev">';
		echo '<span class="glyphicon glyphicon-chevron-left"></span></a>';
		echo '<a class="right carousel-control" href="#images-carousel" data-slide="next">'; 
		echo '<span class="glyphicon glyphicon-chevron-right"></span></a>';
		echo '</div>';
		if ($req->isOwnerOrAdmin()){

			if ($req->isAdmin()){
				echo '<div class="row"><div class="col-md-12"><p>[admin]</p></div></div>';
			}
			echo '<div class="row">';			
			foreach ($images as $key => $row){
				echo '<div class="col-md-2"><div class="thumbnail">';
				echo '<img src="site/images/messages/'.$row['PictureFile'].'" alt="foto">';
				echo '<div class="caption"><p>';
				echo '<a href="messages.php?mode=image_delete&id='.$req->get('id').'&image_id='.$row['id'].'" class="btn btn-danger">';
				echo 'X</a></p></div></div>';
			}
			echo'</div>';			

		}
	}
	
	if ($req->isOwnerOrAdmin()){
		echo '<div class="row"><div class="col-md-12">';
		if ($req->isAdmin()){
			$req->setLabel('image_send', '[admin] '.$req->getLabel('image_send'));
		}		
		echo '<form method="post" class="trans form-horizontal" role="form" enctype="multipart/form-data">';
		$req->set_output('formgroup')->render('image_file');
		$req->set_output('nolabel')->render(array('image_send', 'id'));
		echo '</form></div></div>';	
	}
	
				

	$message_description = ($message['description']) ? nl2br(htmlspecialchars($message['description'],ENT_QUOTES)) : 
		'<p class="text-danger">Er werd geen omschrijving ingegeven</p>';
	$amount_text = ($message['amount']) ? 'Richtprijs: '.getCurrencyText($message['amount']) : 
		'<p class="text-danger">Er werd geen richtprijs ingegeven</p>';
	echo '<div class="panel panel-default"><div class="panel-heading">Omschrijving</div>';
	echo '<div class="panel-body">'.$message_description.'</div>';
	echo '<div class="panel-footer">'.$amount_text.'</div></div>';


	
	if (empty($mailuser['emailaddress']) || $s_accountrole == 'guest'){
		$req->setDisabled(array('sendmail', 'mailbody', 'mailcc'));
	}
	$req->setLabel('mailbody', 'Je reactie naar '.$owner['letscode'].' '.$owner['name']);	
	
	echo '<form method="post" class="trans form-horizontal" role="form">';
	$email = ($req->getSid()) ? 'non_existing_dummy_1' : 'email';
	$recaptcha = ($req->getSid()) ? 'non_existing_dummy_2' : 'recaptcha';
	$req->set_output('formgroup')->render(array('mailbody', 'mailcc'));
	echo '<div>';
	$req->set_output('nolabel')->render(array('send', 'id'));
	echo '</div></form>';	
	
}

include 'includes/footer.php';

?>



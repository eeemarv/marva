<?php

ob_start();

require_once('./includes/default.php');


require_once('./includes/inc_userinfo.php'); 
require_once('./includes/inc_mailfunctions.php'); 

require_once('./includes/request.php');
require_once('./includes/data_table.php');
require_once('./includes/pagination.php');

$req = new request('guest');

$currency = readconfigfromdb('currency');

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
	->add('userid', 0, 'get', array('type' => 'select', 'label' => 'Lid', 'option_set' => 'active_users'))
	->add('catid', 0, 'get', array('type' => 'select', 'label' => 'Categorie', 'option_set' => 'categories'))
	->add('postcode', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 8, 'label' => 'Postcode' ))
	->add('filter', '', 'get', array('type' => 'submit', 'label' => 'Toon'))
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	->add('msg_type', 'ow', 'post', array('type' => 'select', 'label' => 'Vraag-Aanbod', 'options' => $offer_want_options), array('match' => array('o', 'w')))
	->add('id_user', $req->getSid(), 'post', array('type' => 'select', 'label' => '[admin] Van', 'option_set' => 'active_users'), array('match' => 'active_user'))
	->add('id_category', 0, 'post', array('type' => 'select', 'label' => 'Categorie', 'option_set' => 'subcategories'), array('match' => 'subcategory'))
	->add('content', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Titel'), array('not_empty' => true))
	->add('description', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 15, 'label' => 'Inhoud'), array('not_empty' => true))	
	->add('amount', 0, 'post', array('type' => 'text', 'size' => 3, 'maxlength' => 3, 'label' => 'Vraagprijs ('.$currency.')'), array('match' => 'positive'))
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('mdate', date('Y-m-d H:i:s'), 'post')
	->add('sendmail', '', 'post', array('type' => 'submit', 'label' => 'Versturen'))
	->add('mailbody', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 8), array('not_empty' => true, 'min_length' => 15))
	->add('mailcc', 'checked', 'post', array('type' => 'checkbox', 'label' => 'Stuur een kopie naar mezelf'))
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
	
} else if ($req->get('sendmail') && $req->get('id')){
	
	if (!$req->errors(array('mailbody', 'mailcc', 'id'))){

		$systemtag = readconfigfromdb('systemtag');
		
		$user = get_user($req->getItemValue('id_user'));
		$me = get_user($req->getSid());
		$contact = get_contact($req->getSid());
		$usermail = get_user_maildetails($req->getItemValue('id_user'));
		$my_mail = get_user_maildetails($req->getSid());

		$mailsubject .= '[Marva-'.$systemtag .'] - Reactie op je V/A ' .$req->getItemValue('content');
		$mailfrom = $my_mail['emailaddress'];

		$mailto =  $usermail['emailaddress'];
		$mailto .= ($req->get('mailcc')) ? ', '.$my_mail['emailaddress'] : '';

		$mailcontent = 'Beste ' .$user['fullname'] .'\r\n\n
			-- '.$me['fullname'].' heeft een reactie op je vraag/aanbod verstuurd via Marva --\r\n\n'.$reactie.'\n\n
			* Om te antwoorden kan je gewoon reply kiezen of de contactgegevens hieronder gebruiken\n
			* Contactgegevens van '.$me['fullname'] .':\n';
		
		foreach($contact as $key => $value){
			$mailcontent .= '* '.$value['abbrev'] .'\t' .$value['value'] .'\n';
		}
		
		$mailstatus = sendemail($mailfrom,$mailto,$mailsubject,$mailcontent,1);
		
		$req->setSuccess();
	}	
}	 


if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: messages.php'.$param);
	exit;	
}		

include('./includes/header.php');

if($req->isUser() && !$req->get('mode')){	
	echo '<ul class="hormenu"><li><a href="./messages.php?mode=new")>Toevoegen</a></li></ul>';
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
	echo '<form method="post" class="trans" action="messages.php">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	if ($delete){
		echo '<tr><td colspan="2"><h2><a href="messages.php?id='.$req->get('id').'">';
		echo ($req->get('msg_type') == 'w') ? 'Vraag' : 'Aanbod';
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

if (!($new || $edit || $delete)){
	echo '<form method="GET" class="trans"><table >';
	$req->set_output('tr')->render(array('q', 'postcode', 'catid', 'userid', 'ow', 'filter'));
	echo '</table></form>';
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
	$msgpictures = $db->GetArray('SELECT * FROM msgpictures WHERE msgid = ' .$req->get('id'));	


    echo '<ul class="hormenu">';		
	if ($req->isOwnerOrAdmin()){
		$class = ($req->isAdmin()) ? ' class="admin"' : '';
		echo '<li><a href="messages.php?mode=delete&id='.$req->get('id').'"'.$class.'>Verwijderen</a></li>';
		echo '<li><a href="messages.php?mode=edit&id='.$req->get('id').'"'.$class.'>Aanpassen</a></li>';
		$myurl='messages/upload_picture.php?msgid='.$req->get('id');
		echo "<li><a href='#' onclick=window.open('$myurl','upload_picture','width=640,height=480,scrollbars=yes,toolbar=no,location=no,menubar=no')>Foto toevoegen</a></li>";	
	}
	echo '</ul>';	


	$title = $message["content"];
	
	$contact = get_contact($owner['id']);
	$mailuser = get_user_maildetails($owner['id']);	
	

	echo '<h1>'.(($message['msg_type'] == 'o') ? 'Aanbod' : 'Vraag').': '.htmlspecialchars($message['content']).'</h1>';
	echo '<p>Ingegeven door: '; 
	$req->renderOwnerLink();
	echo '<i> saldo: <a href="'.$rootpath.'transactions.php?userid='.$owner['id'].'">' .$owner['saldo'];
	echo '</a> ' .$currency .'</i> - <a href="messages.php?userid='.$owner['id'].'">Toon alle vraag en aanbod van ';
	echo $owner['letscode'].' '.$owner['name'].'</a></p>';

	
	echo "<script type='text/javascript' src='". $rootpath ."js/msgpicture.js'></script>";
	echo "<table class='data' border='1' width='95%'><tr>";

	// The picture table is nested
	echo "<td valign='top'>";
	echo "<table class='data' border='1'>";
	echo "<tr><td colspan='4' align='center'><img id='mainimg' src='" .$rootpath ."gfx/nomsg.png' width='200'></img></td></tr>";
	echo "<tr>";
	$picturecounter = 1;
	foreach($msgpictures as $key => $value){
		$file = $value["PictureFile"];
		$url = $rootpath ."/sites/" .$dirbase ."/msgpictures/" .$file;
		echo "<td>";
		if($picturecounter == 1) {
			 echo "<script type='text/javascript'>loadpic('$url')</script>";
		}
		if ($picturecounter <= 4) {
			$picurl="showpicture.php?id=" .$value["id"];
			echo "<img src='/sites/" .$dirbase ."/msgpictures/$file' width='50' onmouseover=loadpic('$url') onclick=window.open('$picurl','Foto','width=800,height=600,scrollbars=yes,toolbar=no,location=no')></td>";
		}
		$picturecounter += 1;
	}
	echo '</tr></td></table></td>';
	// end picture table

	// Show message
	echo '<td valign="top">';
	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%">';
	
	// empty row
    echo '<tr><td>&nbsp</td></tr>';

	echo "<tr><td>";
	if (!empty($message['description'])){
		echo nl2br(htmlspecialchars($message['description'],ENT_QUOTES));
	} else {
		echo "<i>Er werd geen omschrijving ingegeven</i>";
	}
	echo "</td></tr>";

	// 2x empty row
    echo "<tr><td>&nbsp</td></tr><tr><td>&nbsp</td></tr>";

	echo "<tr class='even_row'><td valign='bottom'>";
	if (!empty($message["amount"])){
		echo "De (vraag)prijs is " .$message["amount"] ." " .$currency;
	} else { 
		echo "Er werd geen vraagprijs ingegeven";
	}
	echo "</td></tr>";

	//Direct URL
	echo '<tr class="even_row"><td>';
	$directurl='http://'.$baseurl.'/messages.php?id='.$req->get('id');
	echo 'Directe link: <a href="' .$directurl .'">' .$directurl .'</a>';
	echo '<br><i><small>Deze link brengt leden van je groep rechtstreeks bij dit V/A</small></i>';
	echo '</td></tr></table></td>';

	// End message

	echo '</tr><tr>';



	//Contact info goes here
	echo '<td width="254" valign="top">';
	$userid = $message["id_user"];
	echo "<div id='contactinfo'></div>";
	echo "<script type='text/javascript'>showsmallloader('contactinfo');loadurlto('messages/rendercontact.php?id=$userid', 'contactinfo')</script>";
	echo "</td>";
	//End contact info

	//Response form
	if (empty($mailuser['emailaddress']) || $s_accountrole == 'guest'){
		$req->setDisabled(array('sendmail', 'mailbody', 'mailcc'));
	}
	$req->setLabel('mailbody', 'Je reactie naar '.$owner['letscode'].' '.$owner['name']);
	
	echo '<td><form action="messages.php" method="post"><table border="0">';	
	$req->set_output('trtr')->render('mailbody')
		->set_output('trtd')->render(array('mailcc', 'sendmail', 'id'));
	echo '</table></form></td>';
	//


	echo '</tr></table>';
	
}

include('./includes/footer.php');

?>



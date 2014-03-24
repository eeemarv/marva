<?php
ob_start();
require_once 'includes/default.php';

require_once 'includes/request.php';
require_once 'includes/data_table.php';

$accountrole_options = array(
	'user' => array('text' => 'user'),
	'admin' => array('text' => 'admin'),
	'interlets' => array('text' => 'interlets'),
	);
	
$status_options = array(
	'inactive' => array('text' => 'inactief'),
	'info' => array('text' => 'infopakket'),
	'infomoment' => array('text' => 'infomoment'), 	
	'active' => array('text' => 'actief'),
	'leaving' => array('text' => 'uitstapper'),
	'interlets_group' => array('text' => 'interlets groep'));

$req = new request('user');

$req->setEntityTranslation('Gebruiker')
	->setEntity('users')
	->setUrl('users.php')
	
	->add('q', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 20, 'label' => 'Code of Naam'))
	->add('postcode_filter', '', 'get', array('type' => 'text', 'size' => 25, 'maxlength' => 8, 'label' => 'Postcode' ))
	->add('orderby', 'letscode', 'get')
	->add('asc', 1, 'get')
	->add('show', 'active', 'get')
	->add('view', 'account', 'get')
	->add('id', 0, 'get|post', array('type' => 'hidden'))	
	->add('mode', '', 'get|post', array('type' => 'hidden'))
	
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('mdate', date('Y-m-d H:i:s'), 'post')	
	->add('adate', date('Y-m-d H:i:s'), 'post')	
	->add('name', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 50, 'label' => 'Gebruikersnaam', 'admin' => true), array('not_empty' => true, 'unique' => true))
	->add('fullname', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 100, 'label' => 'Voor- en Achternaam', 'admin' => true), array('not_empty' => true))
	->add('letscode', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 8, 'label' => 'Letscode', 'admin' => true), array('not_empty' => true, 'unique' => true))
	->add('postcode', '', 'post', array('type' => 'text', 'size' => 10, 'maxlength' => 8, 'label' => 'Postcode', 'admin' => true), array('not_empty' => true))
	->add('birthday', '', 'post', array('type' => 'text', 'label' => 'Geboortedatum', 'placeholder' => 'jjjj-mm-dd', 'size' => 10, 'admin' => true), array('not_empty' => true, 'date' => true))
	->add('hobbies', '', 'post', array('type' => 'textarea', 'cols' => 50, 'rows' => 7, 'label' => 'Hobbies/Interesses'))
	->add('comments', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 100, 'label' => 'Commentaar'))	
	->add('admincomment', '', 'post', array('type' => 'text', 'size' => 50, 'maxlength' => 200, 'label' => 'Commentaar vd admin', 'admin' => true))	
	->add('login', sha1(uniqid().microtime()), 'post')
	->add('accountrole', 'user', 'post', array('type' => 'select', 'label' => 'Rechten', 'options' => $accountrole_options, 'admin' => true), array('not_empty' => true))
	->add('status', 0, 'post', array('type' => 'select', 'label' => 'Status', 'options' => $status_options, 'admin' => true), array('not_empty' => true))
//	->add('minlimit', , 'post', array('type' => 'text', 'label' => 'Min limiet', 'size' => 10, 'admin' => true), array())
	->add('maxlimit', $parameters['default_limit'], 'post', array('type' => 'text', 'label' => 'Limiet +/-', 'size' => 10, 'admin' => true), array())
	->add('mail', '', 'post', array('type' => 'text', 'label' => 'E-mail', 'size' => 50, 'maxlength' => 100), array('not_empty' => true, 'email' => true))
	->add('adr', '', 'post', array('type' => 'text', 'label' => 'Adres', 'size' => 50, 'maxlength' => 100, 'placeholder' => 'Voorbeeldstraat 86, 4572 Voorbeeldplaatsnaam'), array('not_empty' => true))
	->add('tel', '', 'post', array('type' => 'text', 'label' => 'Telefoon', 'size' => 50, 'maxlength' => 20))
	->add('gsm', '', 'post', array('type' => 'text', 'label' => 'Gsm', 'size' => 50, 'maxlength' => 20))
	->add('web', 'http://', 'post', array('type' => 'text', 'label' => 'Website', 'size' => 50, 'maxlength' => 100, 'placeholder' => 'http://voorbeeld.com'))
	->add('presharedkey', '', 'post', array('type' => 'text', 'label' => 'Preshared Key', 'size' => 50, 'maxlength' => 80, 'admin' => true, 'placeholder' => 'enkel voor interlets groepen'))
	->add('creator', $req->getSid(), 'post')
	->add('password', '', 'post')
	
	->addSubmitButtons()
	
	->cancel()
	->query();

$new = $edit = $delete = false;

if ($req->get('mode') == 'new'){
	$req->setSecurityLevel('admin');	
}


if ($req->get('delete') && $req->get('id') && $req->isAdmin()){

	$transactions = $db->GetArray('select id from transactions where id_from = '.$req->get('id').' or id_to = '.$req->get('id'));
	
	if (count($transactions)){
		setstatus('Een gebruiker die reeds transacties gedaan heeft, kan niet worden verwijderd.', 'error');
	} else {	
		$req->delete();
		// contacts, messages, msgpictures & picture to be deleted here or in cronjob.
	}
	
} else if (($req->get('create') || $req->get('create_plus')) && $req->isAdmin()){
	
	$new = $req->errors();
	
	if (!$new){
		$req->create(array('cdate', 'mdate', 'creator', 'comments', 'hobbies', 'name', 'birthday', 
			'letscode', 'postcode', 'login', 'accountrole', 'status', 'minlimit', 'maxlimit', 'fullname', 'admincomment',
			'presharedkey', 'adate'));
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
	

		
	
	
} else if ($req->get('edit') && $req->get('id') && $req->isAdmin()){
	
	$edit = $req->errorsUpdate(array('mdate', 'comments', 'hobbies', 'name', 'birthday', 
		'letscode', 'postcode', 'login', 'accountrole', 'status', 'minlimit', 'maxlimit', 'fullname', 'admincomment',
		'presharedkey', 'adate'));
		
} else if ($req->get('edit') && $req->get('id') && $req->isOwner()){
	
	$edit = $req->errorsUpdate(array('mdate', 'comments', 'hobbies', 'login'));
}

if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: users.php'.$param);
	exit;	
}	
	
	
include 'includes/header.php';



if ($req->isAdmin() && !$req->get('mode')){			
	echo '<a href="./users.php?mode=new" class="btn btn-success pull-right">[admin] Toevoegen</a>';
}


echo '<h1><a href="users.php">Gebruikers</a></h1>';

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('letscode', 'name'));
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
			'accountrole', 'status', 'maxlimit', 'admincomment', 'presharedkey',
			'mail', 'adr', 'tel', 'gsm', 'web'));
	}
	echo '<div>';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id', 'mode'));
	echo '</div></form>';		
}



if (!($new || $edit || $delete)){
	echo '<form method="GET" class="trans form-horizontal" role="form">';
	$req->set_output('formgroup')->render(array('q', 'postcode_filter'));
	echo '<div>';
	$req->set_output('nolabel')->render('filter');
	echo '</div></form>';
}


if (!$req->get('id') && !($new || $edit || $delete)){
	$q = $req->get('q');
	$orderby = $req->get('orderby');
	$asc = $req->get('asc');
	$postcode = $req->get('postcode_filter');
	
	$query = 'select id, letscode, fullname, saldo, postcode from users 
		where ( status = 1 or status = 2 or status = 3 )
		and users.accountrole <> \'guest\' ';
	$query .= ($q) ? 'and (fullname like \'%' .$q .'%\' or name like \'%'.$q.'%\' or letscode like \'%'.$q.'%\') ' : '';
	$query .= ($postcode) ? 'and postcode = \''.$postcode.'\' ' : '';
	if ($orderby){
		$query .= 'order by ' .$orderby.' ';
		$query .= ($asc) ? 'asc ' : 'desc '; 
	}
	$users = $db->GetArray($query); 

	$table = new data_table();

	$table->set_data($users)
		->enable_no_results_message();
		
	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'letscode'	=> array_merge($asc_preset_ary, array(
			'title' => 'Code',
			'render' => 'status')),
		'fullname' => array_merge($asc_preset_ary, array(
			'title' => 'Naam',
			'href_id' => 'id',
			)),
		'saldo' => array_merge($asc_preset_ary, array(
			'title' => 'Saldo',
			'render' => 'limit',
			'href_id' => 'id',
			'href_param' => 'userid',
			'href_base' => 'transactions.php',
//			'footer' => 'sum',
			)),	
		'postcode' => array_merge($asc_preset_ary, array(
			'title' => 'Postcode')));
	
	$table_column_ary[$req->get('orderby')]['asc'] = ($req->get('asc')) ? 0 : 1;
	$table_column_ary[$req->get('orderby')]['indicator'] = ($req->get('asc')) ? '&nbsp;&#9650;' : '&nbsp;&#9660;';

	foreach ($table_column_ary as $key => $data){
		
		$table->add_column($key, array(
			'title' => $data['title'],
			'title_suffix' => $data['indicator'],
			//'title_href' => '',
			'title_params' => array_merge($req->get(array('q', 'postcode')), array(
				'orderby' => $key,
				'asc' => $data['asc'],
				)),
			'href_id' => $data['href_id'],
			'href_base' => $data['href_base'],
			'href_param' => $data['href_param'],
			'code'		=> $data['code'],
			'render'	=> $data['render'],
			));
	}
	

	
	
		
			
	$tabs = array(
		'active' => array('text' => 'Actief', 'class' => 'bg-white'),
		'new' => array('text' => 'Instappers', 'class' => 'bg-success'),	
		'leaving' => array('text' => 'Uitstappers', 'class' => 'bg-danger'),
//		'system' => array('text' => 'Systeem', 'class' => 'bg-info'),
		'interlets' => array('text' => 'Interlets groepen', 'class' => 'bg-warning'),
		'inactive' => array('text' => '[admin] Inactief', 'class' => 'bg-inactive', 'admin' => true),
		);
			
		
	$inactive_tabs = array(
		'all' => array('text' => 'Alle', 'class' => 'inactive', 'admin' => true),
		'newly_registered' => array('text' => 'Nieuw geregistreerd', 'class' => 'inactive', 'admin' => true),
		'info_1' => array('text' => 'Info-pakket', 'class' => 'inactive', 'admin' => true),
		'info_2' => array('text' => 'Info-moment', 'class' => 'inactive', 'admin' => true),
		'deactivated' => array('text' => 'Gedesactiveerd', 'class' => 'inactive', 'admin' => true), 
		);
	
	
		
	
	
	echo'<ul class="nav nav-tabs">';
	foreach ($tabs as $key => $filter){
		$class = ($req->get('show') == $key) ? 'active '.$filter['class'] : $filter['class'];
		$class = ($class) ? ' class="'.$class.'"' : '';
		echo '<li'.$class.'><a href="users.php?show='.$key.'">'.$filter['text'].'</a></li>';
	}	
		
		  
	  
	echo '</ul><p></p>';


	$table->render();

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
		$req->set('id', $users[0]['id']);
	}	
}
	
if ($req->get('id') && !($edit || $delete || $new)){


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

	$query = 'SELECT * FROM users ';
	$query .= 'WHERE id='.$req->get('id').' ';
	$query .= 'AND ( status = 1 OR status = 2 OR status = 3 )';
	$user = $db->GetRow($query);	


    $admin = ($req->isAdmin()) ? '[admin] ' : '';		
	if ($req->isAdmin()){	
		echo '<a href="users.php?mode=delete&id='.$req->get('id').'" class="btn btn-danger pull-right">'.$admin.'Verwijderen</a>';
	}
	if ($req->isOwnerOrAdmin()){			
		echo '<a href="users.php?mode=edit&id='.$req->get('id').'" class="btn btn-primary pull-right">'.$admin.'Aanpassen</a>';
	}	
		
//		$myurl='messages/upload_picture.php?msgid='.$req->get('id');
//		echo "<li><a href='#' onclick=window.open('$myurl','upload_picture','width=640,height=480,scrollbars=yes,toolbar=no,location=no,menubar=no')>Foto toevoegen</a></li>";	




	echo '<h1>'.trim($user['letscode']).'&nbsp;'.htmlspecialchars($user['fullname'],ENT_QUOTES).'</h1>';
	
	
	echo '<table cellpadding="0" cellspacing="0" border="0" width="99%">';
	echo '<tr class="even_row">';
	echo '<td colspan="2" valign="top"><strong>';
	echo ($user['status'] == 2) ? ' <font color="#F56DB5">Uitstapper </font>' : '';	
	echo '</strong></td></tr>';
	echo '<tr><td width="170" align="left"><img src="' .$rootpath;
	echo ($user['picturefile']) ? 'sites/'.$dirbase.'/userpictures/' .$user['picturefile'] : 'gfx/nouser.png';
    echo '" width="150"></img></td>';
	echo '<td>';
	echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
	echo '<tr><td width="50%" valign="top">Naam: </td><td width="50%" valign="top">'.$user['fullname'].'</td></tr>';
	echo '<tr><td width="50%" valign="top">Postcode: </td><td width="50%" valign="top">'.$user['postcode'].'</td></tr>';
	echo ($user['birthday']) ? '<tr><td width="50%" valign="top">Geboortedatum:  </td><td width="50%" valign="top">'.$user['birthday'].'</td></tr>' : '';
	echo '</table></td></table>';


	echo "<table  cellpadding='0' cellspacing='0' border='0'  width='99%'>";
	echo "<tr class='even_row'>";
	echo "<td><strong>{$currency}stand</strong></td><td></td><td><strong>Transactie-Interacties</strong></td></tr>";
	echo "<tr><td>";
	echo "<strong>".$balance."</strong>";
	echo "</td><td><div id='chartdiv1' style='height:200px;width:300px;'></div></td>";
	echo "<td><div id='chartdiv2' style='height:200px;width:200px;'></div></td></tr></table>";

	$query = "SELECT *, ";
	$query .= " contact.id AS cid, users.id AS uid, type_contact.id AS tcid, ";
	$query .= " type_contact.name AS tcname, users.name AS uname ";
	$query .= " FROM users, type_contact, contact ";
	$query .= " WHERE users.id=".$req->get('id');
	$query .= " AND contact.id_type_contact = type_contact.id ";
	$query .= " AND users.id = contact.id_user ";
	$query .= " AND contact.flag_public = 1";
	$contact = $db->GetArray($query);

	/*
	$contact_table = new data_table();
	$contact_table->set_data($contact)->enable_no_results_message();
	$contact_table->add_column('', array();
	*/

	echo "<table cellpadding='0' cellspacing='0' border='0' width='99%'>";
	echo "<tr ><td colspan='3'><p>&#160;</p></td></tr>";
	echo "<tr class='even_row'><td colspan='3'><p><strong>Contactinfo</strong></p></td></tr>";
	foreach($contact as $key => $value){
		echo "<tr><td>".$value["name"].": </td>";
		if($value["abbrev"] == "mail"){
			echo "<td><a href='mailto:".$value["value"]."'>".$value["value"]."</a></td>";
		}elseif($value["abbrev"] == "adr"){
			echo "<td><a href='http://maps.google.be/maps?f=q&source=s_q&hl=nl&geocode=&q=".$value["value"]."' target='new'>".$value["value"]."</a></td>";
		} else {
			echo "<td>".$value["value"]."</td>";
		}

		echo "<td></td>";
		echo "</tr>";
	}
	echo "<tr><td colspan='3'><p>&#160;</p></td></tr>";
	echo "</table>";

	
	
}		

include('./includes/footer.php');

?>

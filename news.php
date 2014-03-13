<?php

ob_start();
require('./includes/inc_default.php');
require('./includes/inc_adoconnection.php');

require('./includes/request.php');
require('./includes/data_table.php');
require('./includes/pagination.php');

$req = new request('guest');

$req->setEntityTranslation('Nieuwsbericht')
	->setEntity('news')
	->setUrl('news.php')
	
	->add('limit', 25, 'get')
	->add('start', 0, 'get')
	->add('orderby', 'itemdate', 'get')
	->add('asc', 0, 'get')
	->add('id', 0, 'get|post', array('type' => 'hidden', 'entity_id' => true))	
	->add('mode', '', 'get')
	->add('itemdate', date('Y-m-d'), 'post', array('type' => 'text', 'label' => 'Datum', 'size' => 10), array('not_empty' => true, 'date' => true))
	->add('headline', '', 'post', array('type' => 'text', 'size' => 40, 'label' => 'Titel'), array('not_empty' => true))
	->add('newsitem', '', 'post', array('type' => 'textarea', 'cols' => 60, 'rows' => 15, 'label' => 'Inhoud'), array('not_empty' => true))
	->add('sticky', '', 'post', array('type' => 'checkbox', 'label' => 'Niet vervallen'))
	->set('sticky', ($req->get('sticky')) ? 1 : 0)
	->add('cdate', date('Y-m-d H:i:s'), 'post')
	->add('id_user', $req->getSid(), 'post')
	->addSubmitButtons()
	
	->cancel()
	->setOwnerParam('id_user')
	->query()
	->queryOwner();	
	
$itemdate = new Datetime($req->getItemValue('itemdate'));
$req->setItemValue('itemdate', $itemdate->format('Y-m-d'));  

$new = $edit = $delete = false;

if ($req->get('delete') && $req->get('id') && $req->isOwnerOrAdmin()){
	$req->delete();
	
} else if (($req->get('create') || $req->get('create_plus'))  && $req->isUser()){
	$new = $req->errorsCreate(array('itemdate', 'headline', 'newsitem', 'sticky', 'cdate', 'id_user'));
	if (!$req->isAdmin() && $req->isSuccess()){
		$mailsubject = '[Marva-'.readconfigfromdb('systemtag').'] Nieuwsbericht / Agendapunt gecreëerd ';
		$mailcontent = '-- Dit is een automatische mail van het Marva systeem, niet beantwoorden aub --\r\n';
		$mailcontent .= '\nEen lid gaf een nieuwsbericht met titel: '.$req->get('headline');
		sendemail(readconfigfromdb('from_address'),readconfigfromdb('newsadmin'),$mailsubject,$mailcontent);						
	}
									
} else if ($req->get('edit') && $req->get('id') && $req->isOwnerOrAdmin()){
	$edit = $req->errorsUpdate(array('itemdate', 'headline', 'newsitem', 'sticky'));
		
}	

if ($req->isSuccess()){
	$param = ($req->get('id'))? '?id='.$req->get('id') : ''; 
	$param = ($req->get('create_plus')) ? '?mode=new' : $param;	
	header('location: news.php'.$param);
	exit;	
}	


	
include('./includes/inc_header.php');

if($req->isUser() && !$req->get('mode')){	
	echo '<ul class="hormenu"><li><a href="./news.php?mode=new")>Toevoegen</a></li></ul>';
} 

echo '<h1><a href="news.php">Nieuwsberichten / Agendapunten</a></h1>';	

$new = ($req->get('mode') == 'new') ? true : $new;
$edit = ($req->get('mode') == 'edit') ? true : $edit;
$delete = ($req->get('mode') == 'delete') ? true : $delete;

if (($req->get('mode') == 'edit') || $delete){
	$req->resetFromDb(array('itemdate', 'headline', 'newsitem', 'sticky'));
}

if (($new && $req->isUser()) || (($edit || $delete) && $req->isOwnerOrAdmin()))
{
	echo '<h1>'.(($new) ? 'Toevoegen' : (($edit) ? 'Aanpassen' : 'Verwijderen?')).'</h1>';
	echo '<form method="post" class="trans" action="news.php">';
	echo '<table cellspacing="5" cellpadding="0" border="0">';
	if ($delete){
		echo '<tr><td colspan="2"><h2><a href="news.php?id='.$req->get('id').'">';
		echo $req->get('headline').'</a></h2></td></tr>';
		echo '<tr><td colspan=2><p>'.$req->get('newsitem').'</p></td></tr>';
	} else {
		echo '<tr><td colspan="2">Geef de datum in als jjjj-mm-dd</td></tr>';			
		$req->set_output('tr')->render(array('itemdate', 'headline', 'newsitem', 'sticky'));
	}
	echo '<tr><td colspan="2">';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id'));
	echo '</td></tr></table></form>';		
}	

if (!$req->get('id') && !($new || $edit || $delete)){
	$pagination = new Pagination($req);
	$pagination->set_query('news');

	$query = 'select id, itemdate, DATE_FORMAT(itemdate, \'%d-%m-%Y\') AS idate, headline 
		from news ';
	$query .= 'order by '.$req->get('orderby'). ' ';
	$query .= ($req->get('asc')) ? 'asc ' : 'desc ';
	$query .= $pagination->get_sql_limit();
		
	$news = $db->GetArray($query);

	$table = new data_table();
	$table->set_data($news)->enable_no_results_message();

	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'itemdate'	=> array_merge($asc_preset_ary, array(
			'title' => 'Datum',
			'replace_by' => 'idate')),
		'headline' => array_merge($asc_preset_ary, array(
			'title' => 'Titel',
			'href_id' => 'id')),
		);

	
	$table_column_ary[$req->get('orderby')]['asc'] = ($req->get('asc')) ? 0 : 1;
	$table_column_ary[$req->get('orderby')]['indicator'] = ($req->get('asc')) ? '&nbsp;&#9650;' : '&nbsp;&#9660;';

	foreach ($table_column_ary as $key => $data){
		
		$table->add_column($key, array(
			'title' => $data['title'],
			'title_suffix' => $data['indicator'],
			'href_id' => $data['href_id'],
			'replace_by' => $data['replace_by'],
			'title_params' => array(
				'orderby' => $key,
				'asc' => $data['asc'],
				),
			));
	}

	$pagination->render();
	$table->render();
	$pagination->render();
	
	if (count($news) == 1){
		$req->set('id', $news[0]['id']);
	}
}

if ($req->get('id') && !($edit || $delete || $new)){
	$news = $req->getItem();
	$owner = $req->getOwner();
	
	echo '<ul class="hormenu">';
	if($req->isOwnerOrAdmin()){
		$class = ($req->isAdmin()) ? ' class="admin"' : '';
		echo '<li><a href="news.php?mode=delete&id='.$req->get('id').'"'.$class.'>Verwijderen</a></li>';
		echo '<li><a href="news.php?mode=edit&id='.$req->get('id').'"'.$class.'>Aanpassen</a></li>';
	}
	echo '</ul>';
		
	echo '<h1>'.$news['headline'].'</h1>';
	echo '<p>Agenda datum: '.$news['itemdate'].'</p>';
	if ($news['location']){
		echo '<p>Locatie: ' .$news['location'].'</p>';	
	}	
	echo '<p>Ingegeven door: ';
	$req->renderOwnerLink();
	echo '</p><p>';
	echo ($news['sticky']) ? 'Dit bericht blijft behouden na de agenda datum.' : 'Dit bericht wordt automatisch verwijderd na de agenda datum.';
	echo '</p><p><strong>'.nl2br(htmlspecialchars($news["newsitem"],ENT_QUOTES)).'</strong></p>';
}
include('./includes/inc_footer.php');

?>





<?php

ob_start();
require 'includes/default.php';

require 'includes/request.php';
require 'includes/data_table.php';
require 'includes/pagination.php';

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
	->add('itemdate', date('Y-m-d'), 'post', array('type' => 'text', 'label' => 'Datum', 'size' => 10, 'placeholder' => 'jjjj-mm-dd'), array('not_empty' => true, 'date' => true))
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
		$subject = '[Marva-'.$parameters['letsgroup_code'].'] Nieuwsbericht / Agendapunt gecreëerd ';
		$content = '-- Dit is een automatische mail van het Marva systeem, niet beantwoorden aub --\r\n';
		$content .= '\nEen lid gaf een nieuwsbericht met titel: '.$req->get('headline');
		sendemail($parameters['mail']['noreply'], $parameters['mail']['news-admin'], $subject, $content);						
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


	
include 'includes/header.php';

if($req->isUser() && !$req->get('mode')){	
	echo '<a href="./news.php?mode=new" class="btn btn-success pull-right">Toevoegen</a>';
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
	echo '<form method="post" class="trans form-horizontal" role="form">';
	if ($delete){
		echo '<h2><a href="news.php?id='.$req->get('id').'">';
		echo $req->get('headline').'</a></h2>';
		echo '<p>'.$req->get('newsitem').'</p>';
	} else {		
		$req->set_output('formgroup')->render(array('itemdate', 'headline', 'newsitem', 'sticky'));
	}
	echo '<div>';
	$submit = ($new) ? 'create' : (($edit) ? 'edit' : 'delete');
	$create_plus = ($new) ? 'create_plus' : 'non_existing_dummy';
	$req->set_output('nolabel')->render(array($submit, $create_plus, 'cancel', 'id'));
	echo '</div></form>';		
}	

if (!$req->get('id') && !($new || $edit || $delete)){
	
	$pagination = new Pagination($req);
	
	$qb = $db->createQueryBuilder();
	
	$qb->select('id, itemdate, headline')
		->from('news', 'n');
		
	$pagination->setQuery($qb);
		
	$qb->orderBy($req->get('orderby'), ($req->get('asc')) ? 'asc ' : 'desc ')
		->setFirstResult($pagination->getStart())
		->setMaxResults($pagination->getLimit());

	$news = $db->fetchAll($qb);
	
	$table = new data_table();
	$table->set_data($news)->enable_no_results_message();

	$asc_preset_ary = array(
		'asc'	=> 0,
		'indicator' => '');

	$table_column_ary = array(
		'itemdate'	=> array_merge($asc_preset_ary, array(
			'title' => 'Datum',
			'func' => function($row){ 
				return date('d-m-Y', strtotime($row['itemdate']));
			},			
			)),			
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
			'func' => $data['func'],
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
	

	if($req->isOwnerOrAdmin()){
		$admin = ($req->isAdmin()) ? '[admin] ' : '';
		echo '<a href="news.php?mode=delete&id='.$req->get('id').'" class="btn btn-danger pull-right">'.$admin.'Verwijderen</a></li>';
		echo '<a href="news.php?mode=edit&id='.$req->get('id').'" class="btn btn-primary pull-right">'.$admin.'Aanpassen</a></li>';
	}

		
	echo '<h1>'.$news['headline'].'</h1>';
	echo '<p>Agenda datum: '.$news['itemdate'].'</p>';
	if ($news['location']){
		echo '<p>Locatie: ' .$news['location'].'</p>';	
	}	
	echo '<p>Ingegeven door: ';
	$req->renderOwnerLink();
	echo '<div class="panel panel-default"><div class="panel-heading">Bericht</div>';
	echo '<div class="panel-body">'.nl2br(htmlspecialchars($news["newsitem"],ENT_QUOTES));
    echo '</div></div>';
	echo '<p>&nbsp;</p>';
	echo ($news['sticky']) ? 'Dit bericht blijft behouden na de agenda datum.' : 'Dit bericht wordt automatisch verwijderd na de agenda datum.';
	echo '<p>&nbsp;</p>';		
}
include 'includes/footer.php';

?>






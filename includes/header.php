<?php 

$bootstrap = ($parameters['cdn']) ? '//netdna.bootstrapcdn.com/bootstrap/3.1.1' : $rootpath.'vendor/twbs/bootstrap/dist';
$jquery = ($parameters['cdn']) ? 'http://code.jquery.com/jquery-1.11.0.min.js' : $rootpath.'vendor/jquery/jquery.min.js';
$font_awesome = ($parameters['cdn']) ? '//netdna.bootstrapcdn.com/font-awesome/4.0.3' : $rootpath.'vendor/font-awesome';
$datepicker = ($parameters['cdn']) ? '//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0' : $rootpath.'vendor/eternicode/bootstrap-datepicker';
$typeahead = ($parameters['cdn']) ? '//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.2' : $rootpath.'vendor/twitter/typeahead.js/dist';
$handlebars = ($parameters['cdn']) ? '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/2.0.0-alpha.2' : $rootpath.'vendor/components/handlebars.js';

echo '
<!DOCTYPE html>
<html lang="'.$parameters['locale'].'">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="'.$parameters['meta_keywords'].'">
	<meta name="description" content="'.$parameters['meta_description'].'">
    

	<title>'.$parameters['letsgroup_name'].'</title>
	
	<link rel="stylesheet" href="'.$bootstrap.'/css/bootstrap.min.css">
	<link rel="stylesheet" href="'.$font_awesome.'/css/font-awesome.min.css">
	<link rel="stylesheet" href="'.$datepicker.'/css/datepicker.css">
	<link type="text/css" rel="stylesheet" href="css/main.css">';
if (file_exists('site/site.css')){	
	echo '<link type="text/css" rel="stylesheet" href="site/site.css">';
}
	
echo '<script type="text/javascript" src="js/table_sum.js"></script>
			
	<script src="'.$jquery.'"></script>
	<script src="'.$bootstrap.'/js/bootstrap.min.js"></script>
	<script src="'.$typeahead.'/typeahead.bundle.min.js"></script>
	<script src="'.$handlebars.'/handlebars.min.js"></script>	
	<script src="'.$datepicker.'/js/bootstrap-datepicker.js"></script>
	<script src="'.$datepicker.'/js/locales/bootstrap-datepicker.nl.js"></script>
	
</head>
<body>

<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
<div class="container-fluid">
<div class="navbar-header">
	<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
		<span class="sr-only"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
	</button>
	<a class="navbar-brand" href="./" >'.$parameters['letsgroup_name'].'</a>
</div>
<div class="collapse navbar-collapse navbar-ex1-collapse">
  <ul class="nav navbar-nav">';

if ($req->isGuest()){
	$menu = array(
		'messages' => 'Vraag & Aanbod',
		'users'	=> 'Gebruikers',
		'transactions' => 'Transacties',
		'news' => 'Agenda',
		);

	foreach($menu as $entity => $label){
		$active = ($req->getEntity() == $entity) ? ' class="active"' : '';	
		echo '<li'.$active.'><a href="'.$entity.'.php">'.$label.'</a></li>';
		
	}
}
echo '</ul><ul class="nav navbar-nav navbar-right">';
$active = ($req->getEntity() == 'contact') ? ' class="active"' : '';
echo '<li'.$active.'><a href="contact.php">Contact</a></li>';
if ($req->isGuest()){
	if ($req->isUser()){
		echo '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">';
		echo $req->getSCode().' '.$req->getSName().'<b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		echo '<li><a href="messages.php?userid='.$req->getSid().'">Mijn vraag & aanbod</a></li>';
		echo '<li><a href="users.php?id='.$req->getSid().'">Mijn gegevens</a></li>';
		echo '<li><a href="transactions.php?userid='.$req->getSid().'">Mijn transacties</a></li>';
		if ($req->isAdminAccountRole()){
			echo '<li class="divider"></li>';
			$active = ($req->isAdmin()) ? ' class="active"' : '';
			echo '<li '.$active.'><a href="admin.php?location='.urlencode($_SERVER['REQUEST_URI']).'">[admin] functies</a></li>';
		}
		echo '<li class="divider"></li>';
		echo '<li><a href="logout.php">Uitloggen</a></li>';
		echo '</ul></li>';
	} else {
		echo '<li>Ingelogd als gast</li>';
	}
} else {
	$active = ($req->getEntity() == 'login') ? ' class="active"' : '';
	echo '<li'.$active.'><a href="login.php">Inloggen</a></li>';
	
}		
echo '</div></div></nav>';

if ($req->getEntity() == 'index'){
	if (file_exists('site/index_pre_container.html')){
		include 'site/index_pre_container.html';
	} else {
		echo '<div class="jumbotron"><div class="container">';
		echo '<h1>'.$parameters['letsgroup_name'].'</h1>';
		echo '<p>'.$parameters['site_slogan'].'</p></div></div>';
	}		
}	


	
echo '<div class="container-fluid">';



$status_array = (is_array($_SESSION['status'])) ? $_SESSION['status'] : array();
$_SESSION["status"] = array();
foreach ($status_array as $alert){
	echo '<div class="alert alert-'.$alert['type'].'">'.$alert['message'].'</div>';		
}
  
if (isset($req) && $req->isAdminPage()){
	echo '<h3>[admin]</h2>';
}
	
?>
  
  
  

<?php 
echo <<<EOF
<!DOCTYPE html>
<html lang="{$site['locale']}">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="keywords" content="{$parameters['meta_keywords']}">
	<meta name="description" content="{$parameters['meta_description']}">    

	<title>{$parameters['site_name']}</title>
	
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
	<link type="text/css" rel="stylesheet" href="{$rootpath}css/main.css">	
	<link type="text/css" rel="stylesheet" href="{$rootpath}tinybox/tinybox.css">
			

	<!-- ajax.js contains eLAS custom ajax functions that are being migrated to MooTools -->
	
	<script type="text/javascript" src="{$rootpath}/js/ajax.js"></script>
	<script type="text/javascript" src="{$rootpath}/js/mootools-core.js"></script>
	<script type="text/javascript" src="{$rootpath}/js/mootools-more.js"></script>
	<script type="text/javascript" src="{$rootpath}/js/menu_current.js"></script>
	<script type="text/javascript" src="{$rootpath}/tinybox/tinybox.js"></script>
			
	<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<script type='text/javascript'>
	function OpenTBox(url){
		TINY.box.show({url:url,width:0,height:0})
	}
</script>

<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
<div class="navbar-header">
	<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
		<span class="sr-only"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
	</button>
	<a class="navbar-brand" href="{$rootpath}">{$parameters['site_name']}</a>
</div>
<div class="collapse navbar-collapse navbar-ex1-collapse">
	{% if is_granted('ROLE_USER') %}
		{{ knp_menu_render('eeemarv_user_menu', { 'style': 'navbar' }) }}
	{% endif %}	
	
	
	
	{% if locales|length > 1 %}
		{{ knp_menu_render('eeemarv_lang_menu', { 'style': 'navbar-right' }) }}
	{% endif %}        
	{% if is_granted('ROLE_USER') %}
		{{ knp_menu_render('eeemarv_personal_menu', {'style': 'navbar-right'}) }}			
	{% else %}
		{% block inline_login %}
			{% render(controller('FOSUserBundle:Security:inlineLogin')) %}
		{% endblock %}
		{{ knp_menu_render('eeemarv_public_menu', {'style': 'navbar-right'}) }}		
	{% endif %}
			
</div>
</nav>



<div id="wrapper">
	
	
 <div id="header">
	 <a href="./">
  <div id="logo"></div><div id="headertext">
EOF;

	$name = $configuration["system"]["systemname"];
	echo $name;
  ?>
  </div></a>
 </div>
 <div id="main">
	 
	 
  <div id="menu">
	<?php
	
		
		if(isset($s_id)){
			if($s_accountrole == "user" || "admin"){
	?>		 
		<div class='nav'>

			<ul class="vertmenu">
			<?php 
				echo '<li><a href="'.$rootpath.'messages.php">Vraag & Aanbod</a></li>';
				echo '<li><a href="'.$rootpath.'users.php">Leden</a></li>';
				if ($s_accountrole == 'user' || $s_accountrole == 'admin' || $s_accountrole == 'interlets'){
					echo '<li><a href="'.$rootpath.'transactions.php">Transacties</a></li>';
				}
				echo '<li><a href="'.$rootpath.'news.php">Nieuws</a></li>';
				if($s_accountrole == "user" || $s_accountrole == "admin"){
					echo '<li><a href="'.$rootpath.'letsgroups.php">Interlets</a></li>';
				}
			?>
			</ul>	
		</div>
		<div class="nav">
			<ul class="vertmenu">
			<?php 
				if($s_accountrole == "user" || $s_accountrole == "admin"){	
 					echo '<li><a href="'.$rootpath.'messages.php?userid='.$s_id.'">Mijn Vraag & Aanbod</a></li>';
 					echo '<li><a href="'.$rootpath.'users.php?id='.$s_id.'">Mijn gegevens</a></li>';
				}
				if($s_accountrole == 'user' || $s_accountrole == 'admin' || $s_accountrole == 'interlets'){
					echo '<li><a href="'.$rootpath.'transactions.php?userid='.$s_id.'">';
					echo 'Mijn transacties</a></li>';
				}
				if($s_accountrole == "user" || $s_accountrole == "admin"){	
 					echo '<li><a href="'.$rootpath.'transactions.php?mode=new">Nieuwe Transactie</a></li>';
				}


			?>
			</ul>
		</div>
		<div class='nav'>
			<ul class='vertmenu'>
			<?php
				if($s_accountrole == "user" || $s_accountrole == "admin"){
					echo '<li><a href="contact_admin.php"\>Contact beheer</a></li>';
				}
			?>
			</ul>
               </div>

		
	<?php
	}
	if($s_accountrole == "admin"){
	?>	
		<div class="nav admin">
			<ul class="vertmenu">
			<?php 
				echo "<li><a href='".$rootpath."users/overview.php?user_orderby=letscode'>Gebruikers</a></li>";
				echo "<li><a href='".$rootpath."categories/overview.php'>Categorien</a></li>";
				echo "<li><a href='".$rootpath."interlets/overview.php'>LETS Groepen</a></li>";
				echo "<li><a href='".$rootpath."apikeys.php'>Apikeys</a></li>";
				echo "<li><a href='".$rootpath."contact_types.php'>Contact-Types</a></li>";
				echo "<li><a href='".$rootpath."config.php'>Instellingen</a></li>";
// reports, hosting, messages link removed
				echo "<li><a href='".$rootpath."eventlog.php'>Log</a></li>";
			?>
			</ul>
		</div>	
		
	<?php
		}
	}elseif(!$s_id || !$s_accountrole){
		echo "<ul class='vertmenu'>";
		echo '<li><a href="'.$rootpath.'login.php">Login</a></li>';

		echo '<li><a href="'.$rootpath.'contact_admin.php">Contact Beheer</a></li>';
		echo "</ul>";
	}
	?>
  </div> <div id='log'><div id='log_res'></div></div>
  <div id="content">
	  
	  
<?php 
$status_array = $_SESSION["status"];
$_SESSION["status"] = array();
foreach ($status_array as $alert){
	echo '<div class="alert alert-'.$alert['type'].'">'.$alert['message'].'</div>';		
}
  
if (isset($req) && $req->isAdminPage()){
	echo '<h2><font color="#8888FF"><b><i>[admin]</i></b></font></h2>';
}
	
?>
  
  
  

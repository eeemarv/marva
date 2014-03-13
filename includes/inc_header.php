<?php
header('X-UA-Compatible: IE=EmulateIE8');
header('Content-Type:text/html;charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo $configuration["system"]["systemname"] ?></title>
		<?php
			echo '<link type="text/css" rel="stylesheet" href="'.$rootpath.'css/main.css">';	
			echo '<link type="text/css" rel="stylesheet" href="'.$rootpath.'tinybox/tinybox.css">';
			

			//ajax.js contains eLAS custom ajax functions that are being migrated to MooTools
			echo '<script type="text/javascript" src="'.$rootpath.'/js/ajax.js"></script>';
			echo '<script type="text/javascript" src="'.$rootpath.'/js/mootools-core.js"></script>';
			echo '<script type="text/javascript" src="'.$rootpath.'/js/mootools-more.js"></script>';
			echo "<script type='text/javascript' src='/js/menu_current.js'></script>";
			echo '<script type="text/javascript" src="'.$rootpath.'/tinybox/tinybox.js"></script>';

		?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>



<script type='text/javascript'>
	function OpenTBox(url){
		TINY.box.show({url:url,width:0,height:0})
	}
</script>

<div id="wrapper">
	
	
 <div id="header">
	 <a href="./">
  <div id="logo"></div><div id="headertext">
  <?php
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
					echo '<li><a href="help.php"\>Probleem melden</a></li>';
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
		echo '<li><a href="'.$rootpath.'passwordlost.php">Passwoord vergeten</a></li>';
		echo '<li><a href="'.$rootpath.'help.php">Help</a></li>';
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
  
  
  

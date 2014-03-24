<?php

echo '</div>';

if ($req->isAdmin()){

	echo '<nav class="navbar navbar-bottom navbar-admin" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex2-collapse">
					<span class="sr-only"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<p class="navbar-brand">[admin]</p>
		</div>
		<div class="collapse navbar-collapse navbar-ex2-collapse">
		<ul class="nav navbar-nav">';
	$menu = array(
		'categories' => 'Categorieën',
		'apikeys'	=> 'Apikeys',
		'type_contact' => 'Contact-types',
		'eventlog' => 'Logs',
		'db_backup' => 'Database backup',
		);	
		
	foreach($menu as $entity => $label){
		$active = ($req->getEntity() == $entity) ? ' class="active"' : '';	
		echo '<li'.$active.'><a href="'.$entity.'.php">'.$label.'</a></li>';
		
	}
	echo '</ul></div></nav>';	
}


echo '<footer class="footer"><div class="container">';
echo '<p><a href="https://github.com/marttii/marva"><i class="fa fa-github fa-lg"></i> marva ';
echo exec('git describe --long --abbrev=10 --tags');			
echo '</a></p></footer>';
echo '</body></html>';

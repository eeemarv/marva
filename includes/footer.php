<?php

echo '</div>';

if ($req->isAdmin()){
	echo '<div class="admin">';
	echo '<div class="row"><div class="col-md-12"><h4>[admin]</h4></div></div>';
	echo '<div class="row">';	
	$menu = array(
		'categories' => 'Categorieën',
		'apikeys'	=> 'Apikeys',
		'type_contact' => 'Contact-types',
		'eventlog' => 'Logs',
		);

	foreach($menu as $entity => $label){
		$active = ($req->getEntity() == $entity) ? ' class="active"' : '';	
		echo '<div class="col-md-3"><a href="'.$entity.'.php"'.$active.'>'.$label.'</a></div>';
		
	}
	echo '</div></div>';
/*	echo '<nav class="navbar navbar-inverse navbar-bottom" role="navigation">';
	echo '<div class="container">';
	echo '<p class="navbar-text navbar-right">Signed in as <a href="#" class="navbar-link">Mark Otto</a></p>';
	echo '</div></nav>'; 

*/
	
	
	
	
}


echo '<footer class="footer"><div class="container">';
echo '<p><a href="https://github.com/marttii/marva"><i class="fa fa-github fa-lg"></i>marva ';
echo exec('git describe --long --abbrev=10 --tags');			
echo '</a></p></div></footer>';
echo '</body></html>';

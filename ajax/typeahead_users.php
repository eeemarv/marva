<?php
ob_start();

$rootpath = '../';
require_once $rootpath.'includes/default.php';

require_once $rootpath.'includes/request.php';

require_once $rootpath.'includes/inc_userinfo.php';

$req = new request('user');

$typeahead_users = getTypeAheadUsers();

header('Content-type: application/json');
echo json_encode($typeahead_users);
	
?>


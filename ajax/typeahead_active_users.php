<?php
ob_start();

$rootpath = '../';
require_once($rootpath.'includes/default.php');

require_once($rootpath.'includes/request.php');

$req = new request('user');


$query = 'select letscode as c, 
	fullname as n, 
	maxlimit as l,
	saldo as b,
	unix_timestamp(adate) as a,
	status
	from users 
	where status in (1, 2, 4, 7)';
	
$typeahead_users = $db->getArray($query); 
	

$newUserTime = time() - 86400 * $parameter['new_user_days'];

foreach ($typeahead_users as &$row){
	$row['a'] = ($row['a'] > $newUserTime) ? 1 : 0;
	$row['le'] = ($row['le'] == 2) ? 1 : 0;
	$row['s'] = ($row['status'] == 4) ? 1 : 0;
	$row['e'] = ($row['status'] == 7) ? 1 : 0;
//	$row['c'] .= ($row['e']) ? '/' : '';
	unset($row['status']);						
}	

header('Content-type: application/json');
echo json_encode($typeahead_users);
	
?>


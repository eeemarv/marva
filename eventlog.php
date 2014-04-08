<?php
ob_start();
$rootpath = "./";
require_once 'includes/default.php';

require_once 'includes/request.php';
require_once 'includes/data_table.php';


$req = new request('admin');

$req->setEntity('eventlog')
	->add('userid', 0, 'get')
	->add('limit', 20, 'get')
	->add('type', '', 'get'); 

$userid = $req->get('userid');
$limit = $req->get('limit');
$type = $req->get('type');

include 'includes/header.php';

echo '<h1><a href="eventlog.php">Event log</a></h1>';


$qb = $db->createQueryBuilder();
$qb->select('e.*, u.name')
	->from('eventlog', 'e')
	->join('e', 'users', 'u', 'e.userid = u.id');
if ($userid){
	echo "Showing only user with id " .$userid."<br>";		
		
	$qb->where($qb->expr()->eq('e.userid', $userid));
}
if ($type){
	echo "Showing only type ".$type."<br>";
	$qb->where($qb->expr()->eq('e.type', '\''.$type.'\''));
}
$qb->orderBy('e.timestamp', 'desc')
	->setMaxResults($limit);

$logs = $db->fetchAll($qb);


if (isset($limit)){
	echo "Showing " .$limit. " records.";
	echo "<br>Show ";
	echo "<a href='eventlog.php?limit=10'>10</a> - <a href='eventlog.php?limit=20'>20</a> - ";
	echo "<a href='eventlog.php?limit=50'>50</a> - <a href='eventlog.php?limit=100'>100</a> - ";
	echo "<a href='eventlog.php?limit=200'>200</a> - <a href='eventlog.php?limit=500'>500</a> - ";
	echo "<a href='eventlog.php?limit=1000'>1000</a>";
}

echo "<br>Show type: ";
echo "<a href='eventlog.php?type=Login'>Login</a>";
echo " - ";
echo "<a href='eventlog.php?type=LogFail'>LogFail</a>";
echo " - ";
echo "<a href='eventlog.php?type=Mail'>Mail</a>";
echo " - ";
echo "<a href='eventlog.php?type=Trans'>Trans</a>";
echo " - ";
echo "<a href='eventlog.php?type=Delete'>Delete</a>";
echo " - ";
echo "<a href='eventlog.php?type=Pict'>Pict</a>";

$table = new data_table();
$table->set_data($logs)
	->enable_no_results_message()
	->add_column('timestamp', array('title' => 'Timestamp'))
	->add_column('type', array('title' => 'Type'))
	->add_column('name', array('title' => 'User', 'href_id' => 'userid', 'href_param' => 'userid'))
	->add_column('event', array('title' => 'Event'))
	->add_column('ip', array('title' => 'IP'))
	->render();



include 'includes/footer.php';

?>

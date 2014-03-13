<?php
ob_start();
$rootpath = "./";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

require_once('./includes/request.php');


$req = new request('admin');




include($rootpath."includes/inc_header.php");




if(isset($_GET["user_userid"])){
	$user_userid = $_GET["user_userid"];
} else {
	$user_userid = "";
}
if(isset($_GET["user_show"])){
	$user_show = $_GET["user_show"];
} else {
	$user_show = 1000;
}
if(isset($_GET["user_type"])){
	$user_type = $_GET["user_type"];
} else {
	$user_type = "";
}


if(!isset($user_show)){
	$user_show = 100;
}

if(isset($s_id) && ($s_accountrole == "admin")){
	show_ptitle();

	$logrows = get_logs($user_userid,$user_type);
	show_logs($logrows,$user_show);
}else{
	header('location : .');
}

include($rootpath."includes/inc_footer.php");

// functions



function show_ptitle(){
	echo "<h1>Event log</h1>";
}

function get_logs($user_userid,$user_type){;
        global $db;
        $query = "SELECT * FROM eventlog";

	if (!empty($user_userid)){
			echo "Showing only user " .$user_userid;
			echo "<br>";
	                $query .= " WHERE eventlog.userid = " .$user_userid;
	}

	if (!empty($user_type)){
                        echo "Showing only type ".$user_type;
			echo "<br>";
                        $query .= " WHERE eventlog.type = '" .$user_type;
			$query .= "'";
	}
			
	
        $query .= " ORDER BY eventlog.timestamp DESC LIMIT 1000";
	//echo $query;

        $logrows = $db->GetArray($query);
        return $logrows;
}

function get_login($userid){
        global $db;
        $query = "SELECT login";
        $query .= " FROM users";
	$query .= " WHERE id = ".$userid;
        $users = $db->GetRow($query);
	
        $login = $users["login"];

        return $login;
}

function show_logs($logrows,$user_show){
	$rowcount = 0;

	if (isset($user_show)){
		echo "Showing " .$user_show. " records.";
		echo "<br>Show ";
		echo "<a href='eventlog.php?user_show=10'>10</a> - <a href='eventlog.php?user_show=20'>20</a> - ";
		echo "<a href='eventlog.php?user_show=50'>50</a> - <a href='eventlog.php?user_show=100'>100</a> - ";
		echo "<a href='eventlog.php?user_show=200'>200</a> - <a href='eventlog.php?user_show=500'>500</a> - ";
		echo "<a href='eventlog.php?user_show=1000'>1000</a>";
	}

	echo "<br>Show type: ";
	echo "<a href='eventlog.php?user_type=Login'>Login</a>";
	echo " - ";
	echo "<a href='eventlog.php?user_type=LogFail'>LogFail</a>";
        echo " - ";
	echo "<a href='eventlog.php?user_type=Mail'>Mail</a>";
	echo " - ";
        echo "<a href='eventlog.php?user_type=Trans'>Trans</a>";
	echo " - ";
	echo "<a href='eventlog.php?user_type=Delete'>Delete</a>";
        echo " - ";
        echo "<a href='eventlog.php?user_type=Pict'>Pict</a>";

        echo "<div class='border_b'><table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>\n";
        echo "<tr class='header'>\n";
	echo "<td>Timestamp</td>\n";
	echo "<td>Type</td>\n";
	echo "<td>User</td>\n";
	echo "<td>Event</td>\n";
	echo "<td><a href='eventlog.php?user_orderby=ip'>IP address</a></td>\n";
	echo "</tr>";
	
	foreach($logrows as $key => $value){

		if($rowcount < $user_show){
			echo "<tr>";
			
			echo "<td>";
                        echo $value["timestamp"];
                        echo "</td>";

			echo "<td>";
                        echo $value["type"];
                        echo "</td>";
	
			$login = get_login($value["userid"]);
			echo "<td>";
			echo "<a href='eventlog.php?user_userid=";
			echo $value["userid"];
			echo "'>";
			echo $login;
			echo "</a>";
			echo " (";
			echo $value["userid"];
			echo ") ";
			echo "</td>";

			echo "<td>";
                        echo $value["event"];
                        echo "</td>";
	
			echo "<td>";
			echo $value["ip"];
			echo "</td>";

			echo "</tr>";
		}
		$rowcount++;
	}

	echo "</table></div>";
}

function get_all_active_users($user_orderby,$prefix_filterby,$searchname,$sortfield){
	global $db;
	$query = "SELECT * FROM users ";
	$query .= "WHERE (status = 1  ";
	$query .= "OR status =2 OR status = 3)  ";
	$query .= "AND users.accountrole <> 'guest' ";
	if ($prefix_filterby <> 'ALL'){
		 $query .= "AND users.letscode like '" .$prefix_filterby ."%'";
	}
	if(!empty($searchname)){
		$query .= " AND (fullname like '%" .$searchname ."%' OR name like '%" .$searchname ."%')";
	}
	if(!empty($sortfield)){
		$query .= " ORDER BY " .$sortfield;
	}

	//echo $query;
	$userrows = $db->GetArray($query);
	return $userrows;
}





?>

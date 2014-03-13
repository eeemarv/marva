<?php
//Write log entry
function log_event($id,$type,$event){
    	global $db;
	global $elasdebug;
	global $dirbase;
	global $rootpath;
	
	$ip = $_SERVER['REMOTE_ADDR'];
 
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
   	$ts = date("Y-m-d H:i:s");
	$mytype = strtolower($type);	
   	$query = sprintf("INSERT INTO eventlog (userid,type,timestamp,event,ip) VALUES (%d, '%s','%s', '%s', '%s')", $id, $mytype, $ts, $event, $ip);
	if($mytype == "debug" && $elasdebug == 0){
		// Do nothing
	} else {
		$db->Execute($query);
	}
}

?>

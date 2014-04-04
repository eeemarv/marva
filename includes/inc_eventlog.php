<?php
//Write log entry
function log_event($id,$type,$event){
    global $db, $elasdebug, $dirbase, $rootpath;
	
	$ip = $_SERVER['REMOTE_ADDR'];
 
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
   	$ts = new \DateTime();
	$mytype = strtolower($type);	

	$id = ($id) ? $id : 0;
	
	if($mytype != "debug" && $elasdebug != 0){
		$db->insert('eventlog', 
			array('userid' => $id, 'type' => $mytype, 'timestamp' => $ts, 'event' => $event, 'ip' => $ip),
			array(\PDO::PARAM_INT, \PDO::PARAM_STR, 'datetime', \PDO::PARAM_STR, \PDO::PARAM_STR)
		); //
	}
}

?>

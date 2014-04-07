<?php
$rootpath="../";

//require_once '../vendor/nusoap/nusoap/lib/nusoap.php'; --> is autoloaded

require_once $rootpath.'includes/default.php';

require_once $rootpath.'includes/inc_userinfo.php';
require_once $rootpath.'includes/inc_transactions.php';
require_once $rootpath.'includes/mail.php';

$server = new soap_server();
$server->configureWSDL('interletswsdl', 'urn:interletswsdl');

$server->register('gettoken',                // method name
    array('apikey' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:interletswsdl',                      // namespace
    'urn:interletswsdl#gettoken',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Get a login token'            // documentation
);


$server->register('userbyletscode',                // method name
    array('apikey' => 'xsd:string', 'letscode' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:interletswsdl',                      // namespace
    'urn:interletswsdl#userbyletscode',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Get the user'            // documentation
);
/*
$server->register('userbylogin',                // method name    // not Implemented in Marva
    array('apikey' => 'xsd:string', 'login' => 'xsd:string', 'hash' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:interletswsdl',                      // namespace
    'urn:interletswsdl#userbyletscode',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Get the user'            // documentation
);
*/
$server->register('userbyname',                // method name
    array('apikey' => 'xsd:string', 'name' => 'xsd:string', 'hash' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:interletswsdl',                      // namespace
    'urn:interletswsdl#userbyletscode',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Get the user'            // documentation
);

$server->register('getstatus',      // method name
   array('apikey' => 'xsd:string'),
   array('return' => 'xsd:string'),
   'urn:interletswsdl',                      // namespace
   'urn:interletswsdl#getstatus',
   'rpc',                                // style
   'encoded',                            // use
   'Get the eLAS status'
);

$server->register('apiversion',                // method name
   array('apikey' => 'xsd:string'),
   array('return' => 'xsd:string'),
   'urn:interletswsdl',                      // namespace
   'urn:interletswsdl#apiversion',
   'rpc',                                // style
   'encoded',                            // use
   'Get the eLAS SOAP API version'
);

$server->register('dopayment',
   array('apikey' => 'xsd:string', 'from' => 'xsd:string', 'real_from' => 'xsd:string', 'to' => 'xsd:string', 'description' => 'xsd:string', 'amount' => 'xsd:float', 'transid' => 'xsd:string', 'signature' => 'xsd:string'),
   array('return' => 'xsd:string'),
   'urn:interletswsdl',                      // namespace
   'urn:interletswsdl#dopayment',
   'rpc',                                // style
   'encoded',                            // use
   'Commit an interlets transaction'
);

$server->register('gettypeaheadusers',
   array('apikey' => 'xsd:string')
);

function gettypeaheadusers($apikey){
	$typeahead_users = (checkApikey($apikey)) ? getTypeAheadUsers(false) : array();
	return json_encode($typeahead_users);
}


function gettoken($apikey){
	log_event("","debug","Token request");
	if(checkApikey($apikey)){
		$token = generateUniqueId();
		$db->insert('tokens', array('token' => $token, 'validity' => date('Y-m-d H:i:s', time() + 600), 'type' => 'guestlogin'));
		log_event("","Soap","Token $token generated");
	} else {
		$token = "---";
		log_event("","Soap","APIkey rejected, no token generated");
	}
	return $token;
}


function dopayment($apikey, $from, $real_from, $to, $description, $amount, $transid, $signature){
	global $parameters;
	
	// Possible status values are SUCCESS, FAILED, DUPLICATE and OFFLINE
	
	log_event("","debug","Transaction request");
	if (!check_apikey($apikey)){
		return "APIKEYFAIL";
		log_event("","Soap","APIKEY failed for Transaction $transid");		
	}
		
	if (check_duplicate_transaction($transid)) {
		log_event("","Soap","Transaction $transid is a duplicate");
		return "DUPLICATE";
	}

	if($parameters['maintenance']){ 
		log_event("","Soap","Transaction $transid deferred (offline)");
		return "OFFLINE";
	}
	 
	$user_from => get_user_by_letscode($from);
	$user_to = get_user_by_letscode($to);

	if(empty($user_to['letscode']) 
		|| !in_array($user_to['status'], array(1, 2))) {
		log_event('','Soap','Transaction '.$transid.', unknown user');
		return "NOUSER";
	}
	
	$amount = $parameters['currency_rate'] * $amount;
	
	$params = array(
		'transid' => $transid,
		'date' => date('Y-m-d H:i:s'),
		'description' => $description,
		'id_from' => $user_from['id'],
		'real_from' => $real_from,
		'id_to' => $user_to['id'],
		'amount' => $amount,
		'letscode_to' => $user_to['letscode'],
		'amount' => $amount,
		'creator' => $user_from['id'],
	)

	if(sign_transaction($params, $fromuser["presharedkey"]) != $signature){
		log_event("","Soap","Transaction $transid, invalid signature");
		return "SIGFAIL";
	}

	try{
		$db->insert('transactions', $req->get($params));
		$db->update('users', array('saldo' => $user_to['saldo'] + $amount, array('id' => $user_to['id'])); 
		$db->update('users', array('saldo' => $user_from['saldo'] - $amount, array('id' => $user_from['id']));
		
	} catch (Exception $e) {
		$db->rollback();
		log_event('','Soap','Transaction '.$transid.' FAILED, message:'.$e->getMessage());
		return 'FAILED';
	}

	
			
			if($mytransid == $transid){
				$result = "SUCCESS";
				log_event("","Soap","Transaction $transid processed");
				$posted_list["amount"] = round($posted_list["amount"]);
				mail_transaction($posted_list, $transid);
			} else {
				
				$result = "FAILED";	
			}
			return $result;
		}

}

function userbyletscode($apikey, $letscode){
	log_event("","debug","Lookup request for $letscode");
	if(!checkApikey($apikey)){
		return '---';			
	}
	$user = get_user_by_letscode($letscode);
	return ($user['name']) ? $user['name'] : 'Onbekend';
}

function userbyname($apikey, $name){
	log_event("","debug","Lookup request for user $name");
	if(!checkApikey($apikey)){
		return "---";
	}
	$user = get_user_by_name($name);
	return ($user['name']) ? $user['letscode'] : 'Onbekend';
}

function getstatus($apikey){
	global $parameters;
	if(!checkApikey($apikey)){
		return 'APIKEYFAIL';		
	}
	return ($parameters['maintenance']) ? 'OFFLINE' : 'OK - Marva '.exec('git describe --long --abbrev=10 --tags');
}

function apiversion($apikey){
	if(checkApikey($apikey)){
		return 1200; // was global variable $soapversion
	}
}

function checkApikey($apikey, $type){
	global $db;
	if (!$apikey){
		return false;
	}
	return $db->fetchColumn('select id from apikeys where apikey = ?', array($apikey)) ? true : false;
}


// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

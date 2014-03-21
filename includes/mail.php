<?php
/**
 * copyleft 2014 martti <info@martti.be>
 * 
 *
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  * GNU General Public License for more details.
*/

// require_once($rootpath."contrib/includes/SwiftMail/lib/swift_required.php"); --> is autoloaded

require_once($rootpath.'includes/inc_eventlog.php');



function sendemail($from, $to, $subject, $content, $receipt = null){
	global $parameters;
	
	if($parameters['mail_disabled']){
		
		setstatus('Mail functies zijn uitgeschakeld', 'warning');
		log_event('', 'mail', 'Mail '.$subject.' not sent, mail functions are disabled');		
		return false;
	}
	
	if(empty($from) || empty($to) || empty($subject) || empty($content)){
		
		setstatus('Fout: mail niet verstuurd, ontbrekende velden', 'danger');
		$logline = 'Mail '.$subject.' not sent, missing fields\n';
		$logline .= 'From: '.$from.'\nTo: '.$to.'\nSubject: '.$subject.'\nContent: '.$content;
		log_event('', 'mail', $logline);
		return false;
		
	}
	
	$transport_parameters = ($parameters['debug']) ? $parameters['debug_mail'] : $parameters['mail'];
	
	if ($transport_parameters['transport'] == 'gmail'){	
		$transport_parameters['host'] = 'smtp.gmail.com';
		$transport_parameters['encryption'] = 'ssl';
		$transport_parameters['port'] = 465;
	}
	
	if ($transport_parameters['transport'] == 'sendmail'){
		$transport = Swift_SendmailTransport::newInstance();	
	} else {
		$transport = Swift_SmtpTransport::newInstance($transport_parameters['host'], 
			$transport_parameters['port'], 
			$transport_parameters['encryption'])
			->setUsername($transport_parameters['username'])
			->setPassword($transport_parameters['password']);	
	}

	$mailer = Swift_Mailer::newInstance($transport);
	
	$message = Swift_Message::newInstance();
	$message->setSubject($subject);

	$from = ($transport_parameters['from']) ? $transport_parameters['from'] : $from; 
	
	try {
		$message->setFrom($from);
	}
	catch (Exception $e) {
		$emess = $e->getMessage();
		setstatus('Fout: mail naar '.$to.' niet verstuurd.', 'danger');
		log_event('', 'mail', 'Mail '.$subject.' not send, mail command said '.$emess);
		return;
	}
	
	$content .= ($transport_parameters['delivery_address']) ? '\r\n Original delivery address: '.$to.'\r\n' : '';
		
	$to = ($transport_parameters['delivery_address']) ? $transport_parameters['delivery_address'] : $to;
	
	if (is_array($to)){	
		$to_array = $to;
		$to = implode(', ', $to);
	} else {			
		$to = trim($to, ',');
		$to_array = explode(',', $to);
	}
	
	try {
		$message->setTo($to_array);
	}
	catch (Exception $e) {
		$emess = $e->getMessage();
		setstatus('Fout: mail naar '.$to.' niet verstuurd.', 'danger');
		log_event('', 'mail', 'Mail '.$subject.' not send, mail command said '.$emess);
		return;
	}
			
	try {
		$message->setBody($content);
	}
	catch (Exception $e) {
		$emess = $e->getMessage();
		setstatus('Fout:  naar '.$to.' niet verstuurd.', 'danger');
		log_event('', 'mail', 'Mail '.$subject.' not send, mail command said '.$emess);
		return;
	}

	try {
		$mailer->send($message);
	} 
	catch (Exception $e) {
		$emess = $e->getMessage();
		setstatus('Fout: mail naar '.$to.' niet verstuurd.' , 'danger');
		log_event('', 'mail', 'Mail '.$subject.' not send, mail command said '.$emess);
		return;
	}
	
	setstatus('OK - Mail verstuurd', 'success');
	log_event('', 'mail', 'Mail '.$subject.' sent to '.$to);

	return;
}

?>

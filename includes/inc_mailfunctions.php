<?php
/**
 * copyleft 2014 martti <info@martti.be>
 * 
 * Class to perform eLAS Mail operations
 *
 * This file is part of eLAS http://elas.vsbnet.be
 *
 * Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
 *
 * eLAS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  * GNU General Public License for more details.
*/
/** Provided functions:
 * sendemail($mailfrom,$mailto,$mailsubject,$mailcontent)	Immediately send out an e-mail
*/

// require_once($rootpath."contrib/includes/SwiftMail/lib/swift_required.php");

require_once($rootpath."includes/inc_eventlog.php");

function qmail($mailto,$mailsubject,$mailcontent,$mailfrom){
}

function sendemail($mailfrom,$mailto,$mailsubject,$mailcontent,$receipt=0){
	global $elasversion;
	// return 0 on success, 1 on failure
	
	$transport = Swift_SendmailTransport::newInstance();
	$mailer = Swift_Mailer::newInstance($transport);
	
	if(readconfigfromdb("mailenabled") == 1){
		if(empty($mailfrom) || empty($mailto) || empty($mailsubject) || empty($mailcontent)){
			$mailstatus = "Fout: mail niet verstuurd, ontbrekende velden";
			setstatus($mailstatus, 1);
			$logline = "Mail $mailsubject not sent, missing fields\n";
			$logline .= "From: $mailfrom\nTo: $mailto\nSubject: $mailsubject\nContent: $mailcontent";
			log_event("", "mail", $logline);
		} else {
			$message = Swift_Message::newInstance();
			$message->setSubject("$mailsubject");


			try {
				$message->setFrom('$mailfrom');
			}
			catch (Exception $e) {
				$emess = $e->getMessage();
				setstatus('Fout: mail naar '.$mailto.' niet verstuurd.', 'danger');
				log_event('', 'mail', 'Mail '.$mailsubject.' not send, mail command said '.$emess);
				$status = 0;
			}
				
			//Filter off leading and trailing commas to avoid errors
			$mailto = preg_replace('/^,/i', '', $mailto);
			$mailto = preg_replace('/,$/i', '', $mailto);
			
			$toarray = explode(",", $mailto);
			try {
				$message->setTo($toarray);
			}
			catch (Exception $e) {
				$emess = $e->getMessage();
				setstatus('Fout: mail naar '.$mailto.' niet verstuurd.', 'danger');
				log_event("", "mail", "Mail $mailsubject not send, mail command said $emess");
				$status = 0;
			}
			
			try {
				$message->setBody("$mailcontent");
			}
			catch (Exception $e) {
				$emess = $e->getMessage();
				$mailstatus = "Fout: mail naar $mailto niet verstuurd.";
				setstatus($mailstatus, 'danger');
				log_event("", "mail", "Mail $mailsubject not send, mail command said $emess");
				$status = 0;
			}
		
			$status = 1;
			try {
				$mailer->send($message);
			} 
			catch (Exception $e) {
				$emess = $e->getMessage();
				setstatus("Fout: mail naar $mailto niet verstuurd." , 'danger');
				log_event("", "mail", "Mail $mailsubject not send, mail command said $emess");
				$status = 0;
			}
			if($status == 1) {
				setstatus("OK - Mail verstuurd", 'success');
				log_event("", "mail", "Mail $mailsubject sent to $mailto");
			}
		}
	} else {
		$mailstatus = 'Mail functies zijn uitgeschakeld';
		setstatus($mailstatus, 'warning');
		log_event('', 'mail', 'Mail '.$mailsubject.' not sent, mail functions are disabled');
	}

	return $mailstatus;
}

?>

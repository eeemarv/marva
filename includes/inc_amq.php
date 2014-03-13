<?php
/**
 * Class to perform eLAS AMQ operations
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
 * amq_addmessage ($queue, $message)	Add a message to a queue
 * maildroid_queue($json)				Queue to maildroid
*/

function amq_processhosting(){
	global $provider;
	global $rootpath;
	global $baseurl;
	$systemtag = readconfigfromdb("systemtag");
	
	//echo "DEBUG: Connecting to amqhost " .$provider->amqhost ."\n";
	$cnn = new AMQPConnection();
    $cnn->setHost($provider->amqhost);
    $cnn->connect();
     
    // Create a channel
    $ch = new AMQPChannel($cnn);
    // Declare a new exchange
    $ex = new AMQPExchange($ch);
    //echo "DEBUG: Connecting to Exchange $systemtag\n";
    $ex->setName($systemtag);
    $ex->setType('direct');
    $ex->setFlags(AMQP_DURABLE);
    $ex->declare();
    
    // Create a new queue
    $hq = new AMQPQueue($ch);
    $qn = $systemtag .".hosting";
    //echo "DEBUG: Connecting to queue incoming\n";
    $hq->setName($qn);
    $hq->setFlags(AMQP_DURABLE);
    $hq->declare();	
    $hq->bind($systemtag, $systemtag .".hosting");
            
    #$msg = $q->consume();
    $file = "$rootpath/sites/$baseurl/json/contract.json";
    $msgcount = 0;
    while($message = $hq->get(AMQP_AUTOACK)) {
		//var_dump($message);
		$msgcount = $msgcount + 1;
		echo "    Processing hosting message #" . $msgcount ."\n";
		file_put_contents($file, $message->getBody());
	}
	$cnn->disconnect();
}


function amq_processincoming(){
	global $provider;
	$systemtag = readconfigfromdb("systemtag");
	
	//echo "DEBUG: Connecting to amqhost " .$provider->amqhost ."\n";
	$cnn = new AMQPConnection();
    $cnn->setHost($provider->amqhost);
    $cnn->connect();
     
    // Create a channel
    $ch = new AMQPChannel($cnn);
    // Declare a new exchange
    $ex = new AMQPExchange($ch);
    //echo "DEBUG: Connecting to Exchange $systemtag\n";
    $ex->setName($systemtag);
    $ex->setType('direct');
    $ex->setFlags(AMQP_DURABLE);
    $ex->declare();
    
    // Create a new queue
    $mq = new AMQPQueue($ch);
    $qn = $systemtag .".incoming";
    //echo "DEBUG: Connecting to queue incoming\n";
    $mq->setName($qn);
    $mq->setFlags(AMQP_DURABLE);
    $mq->declare();
    $mq->bind($systemtag, $systemtag. ".incoming");
    
    #$msg = $q->consume();
    $msgcount = 0;
    while($message = $mq->get(AMQP_AUTOACK)) {
		$msgcount = $msgcount + 1;
		echo "    Processing incoming message #" . $msgcount ."\n";
		echo $message->getBody() ."\n";
		//var_dump($message);
		// ESM example
		// { "systemtag" : "letsdev" , "sitecontact" : "Guy Van Sanden" , "sitemail" : "guy@taurix.net" , "contractstart" : "2013-03-10", "contractend" : "2013-07-06" , "paymenttype" : "dummy" , "contractperiod" : "lightyears" , "cost" : "0.0" , "gracedays" : 20}
		
		//$q->ack($message['delivery_tag']);
	}
	$cnn->disconnect();
}

function esm_mqueue($json) {
	global $provider;
	#var_dump($providerxml);
	
	echo "DEBUG: Connecting to amqhost " .$provider->amqhost ."\n";
	
	$cnn = new AMQPConnection();
    $cnn->setHost($provider->amqhost);
    $cnn->connect();
     
    // Create a channel
    $ch = new AMQPChannel($cnn);
    // Declare a new exchange
    $ex = new AMQPExchange($ch);
    $ex->setName('esm');
    $ex->setType('direct');
    $ex->setFlags(AMQP_DURABLE);
    $ex->declare();
    
    // Create a new queue
    $q = new AMQPQueue($ch);
    $q->setName('mail');
    $q->setFlags(AMQP_DURABLE);
    $q->declare();
       
    $status = $ex->publish($json, 'mail');
    $cnn->disconnect();
    return $status;
}	

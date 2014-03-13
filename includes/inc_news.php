<?php
/**
 * Class to perform eLAS transactions
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

/** Provided functions:
 * mail_news($id)
*/

// Enable logging
global $rootpath;
require_once($rootpath."includes/inc_mailfunctions.php");

function mail_news($id){
        global $db;
        $query = "SELECT *, ";
        $query .= "news.id AS nid, ";
        $query .= " DATE_FORMAT(news.cdate, ('%d-%m-%Y')) AS date, ";
        $query .= " DATE_FORMAT(news.itemdate, ('%d-%m-%Y')) AS idate ";
        $query .= " FROM news, users  ";
        $query .= " WHERE news.id=".$id;
        $query .= " AND news.id_user = users.id ";
        $newsitem = $db->GetRow($query);

        $mailfrom = readconfigfromdb("from_address");
	$systemname = readconfigfromdb("systemname");
        $systemtag = readconfigfromdb("systemtag");

	$mailsubject = "[eLAS-$systemtag Nieuws] " .$newsitem["headline"];

        $mailcontent  = "-- Dit is een automatische mail van het Marva systeem, niet beantwoorden aub --\r\n\n";

	$mailcontent  .= "Er werd een nieuw nieuwsbericht ingegeven in Marva:\n";
	$mailcontent  .= "Onderwerp: " .$newsitem["headline"] ."\n";
	$mailcontent  .= "Locatie " .$newsitem["location"] ."\n";
	$mailcontent  .= "Datum: " .$newsitem["itemdate"] ."\n\n";
	$mailcontent  .= $newsitem["newsitem"] ."\n\n";

	$q2 = "SELECT * from lists where topic = 'news'";
	$lists = $db->Execute($q2);
	//var_dump($lists);

	foreach($lists as $key => $value){
		$mailto = $value["address"];
        	sendemail($mailfrom,$mailto,$mailsubject,$mailcontent);
        	log_event($s_id,"Mail","News mail sent to $mailto");
	}
}


?>

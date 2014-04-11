<?php 
# Perform a DB update from CRON

function doupgrade($version){	
	global $db;
	global $configuration;
	
	$ran = 0;
	log_event("","DB","Running DB upgrade $version");
	switch($version){
		case 2100:
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('cron',
					'msgcleanupenabled', '1', 'V/A automatisch opruimen', 1)";
			executequery($query);
			
			$ran = 1;
			break;
		case 2190:
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('system', 
				'share_enabled', '0', 'Sharing enabled', 1)";
			executequery($query);
			
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('mail',
				'news_announce', '', 'Announce news to this address', 1)";
			executequery($query);
			
			$query = "ALTER TABLE  `news` ADD  `published` TINYINT( 1 ) NULL AFTER  `approved`";
			executequery($query);
			
			$query = "ALTER TABLE  `apikeys` ADD  `type` VARCHAR( 15 ) NOT NULL AFTER  `created` , ADD INDEX (  `type` );";
			executequery($query);
			
			$query = "UPDATE `apikeys` SET `type` = 'interlets'";
			executequery($query);
			
			$query = "ALTER TABLE `contact` CHANGE `value` `value` VARCHAR( 130 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
			executequery($query);
			
			$query = "DELETE FROM `tokens`";
			executequery($query);
			
			$query = "ALTER TABLE  `tokens` ADD  `type` VARCHAR( 15 ) NOT NULL";
			executequery($query);
			
			$query = "ALTER TABLE  `users` ADD  `lang` VARCHAR( 5 ) NOT NULL AFTER  `locked`";
			executequery($query);
			
			$query = "ALTER TABLE  `messages` ADD  `local` BOOL NULL DEFAULT  '0'";
			executequery($query);
			
			$query = "ALTER TABLE  `messages` ADD INDEX (  `local` )";
			executequery($query);
			
			$query = "CREATE TABLE `lists` (
				`listname` VARCHAR( 25 ) NOT NULL ,
				`address` VARCHAR( 50 ) NOT NULL,
				`type` VARCHAR( 25 ) NOT NULL,
				`topic` VARCHAR( 25 ) NOT NULL,
				`description` VARCHAR( 128 ) NOT NULL ,
				`auth` VARCHAR( 20 ) NOT NULL ,
				`subscribers` VARCHAR( 20 ) NOT NULL ,
				PRIMARY KEY (  `listname` )
			)";
			executequery($query);
			
			$ran = 1;	
			break;
		case 2191:
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza
', 'plaza_enabled', '1', 'LETS Plaza enabled', 0)";
			executequery($query);
			
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza
', 'plaza_domain', 'letsplaza.net', 'LETS Plaza domain', 0)";
			executequery($query);
		 
			$ran = 1;	
			break;
		case 2192:
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza','ostatus_url','http://chatter.letsplaza.net', 'URL van Statusnet instance', 0)";
			executequery($query);

			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza','ostatus_user','default', 'Statusnet username', 0)";
			executequery($query);

			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza','ostatus_password','default', 'Statusnet password', 0)";
			executequery($query);

			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('plaza','ostatus_group','default', 'Statusnet group for this instance', 0)";
                        executequery($query);
			
			$ran = 1;
			break;
		case 2193:
			$query = "CREATE TABLE `ostatus_queue` (
					`id` INT( 11 ) NOT NULL auto_increment,
					`message` VARCHAR( 140 ) NOT NULL ,
					`url` VARCHAR( 100 ) ,
					`pushed` TINYINT( 1 ) NULL,
					PRIMARY KEY (  `id` )
			)";
			executequery($query);
			$ran = 1;
			break;
		case 2194:
			$query = "ALTER TABLE `users` ADD `adate` DATETIME";
			executequery($query);
			$query = "UPDATE `users` SET `adate` = `cdate`";
			executequery($query);
			
			$ran = 1;
			break;
		case 2195:
			$query = "ALTER TABLE `users` ADD `ostatus_id` VARCHAR ( 50 )";
			executequery($query);
			
			$ran = 1;
			break;
		case 2196:
			$query = "CREATE TABLE `openid` (
                                        `id` INT( 11 ) NOT NULL auto_increment,
                                        `user_id` INT( 11 ) NOT NULL ,
                                        `openid` VARCHAR( 128 ) NOT NULL ,
                                        PRIMARY KEY (  `id` )
                        )";
            executequery($query);
			$ran = 1;
            break;
		case 2197:
			# Remove unused config setting news_announce
			$query = "DELETE FROM `config` WHERE `description` = 'news_announce'";
			executequery($query);
            $ran = 1;
            break;
        case 2198:
			$query = "CREATE TABLE `stats` (`key` VARCHAR( 25 ) NOT NULL , `description` VARCHAR( 250 ) NOT NULL , `value` FLOAT NOT NULL ,
PRIMARY KEY (  `key` ) ) CHARACTER SET utf8 COLLATE utf8_general_ci";
			executequery($query);
			$ran = 1;
			
			break;
		case 2199:
			$query = "INSERT INTO `stats` (`key`, `description`, `value`) VALUES ('activeusers', 'Totaal actieve gebruikers', '0')";
			executequery($query);
			
			$query = "INSERT INTO `stats` (`key`, `description`, `value`) VALUES ('totaltransactions', 'Totaal aantal transacties', '0')";
			executequery($query);
			
			$ran = 1;
			
			break;
			
		case 2200:
			$query = "ALTER TABLE `users` ADD `pubkey` TEXT";
			executequery($query);
			$query = "ALTER TABLE `users` ADD `privkey` TEXT";
			executequery($query);
			$ran = 1;
			
			break;
			
		case 2201:
			$query = "ALTER TABLE `letsgroups` ADD `pubkey` TEXT";
			executequery($query);
			$ran = 1;
			
			break;

		case 2202: 
			$query = "ALTER TABLE  `users` CHANGE  `password`  `password` VARCHAR( 150 )";
			executequery($query);
                        $ran = 1;

                        break;	

		case 2203:
			# FIX for UTF-8 compatibility
			$tables = array("apikeys", "categories", "config", "contact", "cron", "eventlog", "interletsq", "letsgroups", "lists", "messages", "msgpictures", "news", "openid", "ostatus_queue", "parameters", "stats", "tokens", "transactions", "type_contact", "users"
);

			foreach ($tables as &$value) {
				$query = "ALTER TABLE $value CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci";
    				executequery($query);
			}
			
			$ran = 1;

            break;
            
        case 2204:
			// Check if there are duplicate transactions
			// select id, transid, count(transid) as cnt from transactions group by transid having cnt > 1
			$query = "select `id`, `transid`, count(`transid`) as `cnt` from `transactions` group by `transid` having cnt > 1";
			$transactions = $db->GetArray($query);
			if(count($transactions)){
					echo "ERROR: Duplicate transaction ID's found!!!\r";
					log_event("","DB","ERROR: Duplicate transaction ID's found!!!");
					$mailto = readconfigfromdb("admin");
					$mailfrom = readconfigfromdb("admin");
					// Include redmine in each report
					$mailto .= ", support@taurix.net";
					$mailsubject = "[eLAS " . $configuration["system"]["systemtag"] ."] DB Update FAILED";
			
					$mailcontent = "Duplicate transaction ID's found in database.";
					sendemail($mailfrom,$mailto,$mailsubject,$mailcontent);
					
					// Stop upgrading
					exit; 
			}
			//echo "dupcount: " .count($transactions);
			//var_dump($transactions);
				
			$ran = 1;
			break; 
			
        case 2205:
			// Generate a transactionid for each legacy transaction to be compliant with update 2206
			$query = "SELECT * FROM `transactions` WHERE `transid` is NULL";
			$transactions = $db->GetArray($query);
			//echo "nullcount: " .count($transactions);
			//var_dump($transactions);
			foreach ($transactions AS $key => $value){
					echo "\rGenerating transid for " .$value["id"];
					$transid = generate_oldtransid();
					$query = "UPDATE `transactions` SET `transid` = '" .$transid ."' WHERE `id` = " .$value["id"];
					executequery($query);
			}
			
			$ran = 1;
			break;
			
        case 2206:
			# Force transid's to be unique
			$query = "ALTER TABLE  `transactions` DROP INDEX  `transid`";
			executequery($query);
			$query = "ALTER TABLE  `transactions` ADD UNIQUE (`transid`)";
			executequery($query);
            $ran = 1;

            break;
            
            
            
         case 2207:
			# Create the locout parameter for ESM integration
			$query = "INSERT INTO `parameters` (`parameter`, `value`) VALUES ('lockout','0')";
			executequery($query);
            $ran = 1;

            break;
 
///////////////////////////////////////// -> for marva schemaversion is assumed to be at least 2206 
 
            
        case 2208:
			# Create the subscription table
			$query = "CREATE TABLE `listsubscriptions` (`listname` VARCHAR(25) NOT NULL, `user_id` INT(11) NOT NULL, PRIMARY KEY(`listname`,`user_id`))";
			executequery($query);
            $ran = 1;

            break;
            
        case 2209:
			# MailQ is removed before final
			
			#$query = "CREATE TABLE `mailq` (`msgid` VARCHAR(25) NOT NULL, `listname` VARCHAR(25) NOT NULL, `from` VARCHAR(25) NOT NULL, `message` TEXT NOT NULL,  `sent` BOOL NULL DEFAULT  '0', PRIMARY KEY(`msgid`), INDEX (`sent`))";
			#executequery($query);
            $ran = 1;

            break;
            
        case 2210:
			# MailQ is removed before final
			# Add subject field to mailq!
			#$query = "ALTER TABLE `mailq` ADD `subject` VARCHAR(200) NULL";
			#executequery($query);
            $ran = 1;

            break;
            
        case 2211:
			# Add uuid field to messages
			$query = "ALTER TABLE `messages` ADD `uuid` VARCHAR(30) NULL, ADD INDEX ( `uuid` )";
			executequery($query);
            $ran = 1;

            break;
            
        case 2212:
			# Add a setting to toggle the use of mailing lists, on by default
			$query = "INSERT INTO `config` (`category`,`setting`,`value`,`description`,`default`) VALUES('mail','mailinglists_enabled','1', 'Enable mailing list functionality', 0)";
            executequery($query);
			
			$ran = 1;
			break;
        
        case 2213:
			$query = "ALTER TABLE `messages` ADD `noannounce` BOOL NULL DEFAULT  '0', ADD INDEX ( `noannounce` )";
			executequery($query);
            $ran = 1;

            break;
        case 2214:
			$systemtag = readconfigfromdb("systemtag");
			$uuid = uniqid($systemtag. "_", true);
			
			$query = "INSERT INTO `parameters` (`parameter`, `value`) VALUES ('uuid','" .$uuid ."')";
			executequery($query);
            $ran = 1;

            break;
        case 2215:
			$query = "ALTER TABLE `lists` ADD `moderation` BOOL NULL DEFAULT  '0', ADD INDEX ( `moderation` )";
			executequery($query);
            $ran = 1;
            
			break;
			
		case 2216:
			$query = "ALTER TABLE `lists` ADD `moderatormail` VARCHAR(70)";
			executequery($query);
            $ran = 1;
            
		case 2217:
			$query = "UPDATE `config` SET value = 0 WHERE setting = 'mailinglists_enabled'"; 
			executequery($query);
            $ran = 1;
            
        case 2219:
			// FIXME: We need to repeat 2205 and 2206 to fix imported transactions after those updates
			break;           
            
            
            
            
            
            
            
	}
	
	// Finay, update the schema version
	if($ran == 1){
		echo "Executed upgrade version $version\n";	
		$query = "UPDATE `parameters` SET `value` = $version WHERE `parameter` = 'schemaversion'";
		executequery($query);
		return TRUE;
	} else {
		return FALSE;
	}
}

function executequery($query) {
	global $db;
	global $elas;
	global $configuration;
	global $elasversion;
	
	echo "\nExecuting: $query: ";
	$result = $db->Execute($query);

	if($result == FALSE){
			echo "FAILED executing $query\n";
			log_event("","DB","FAILED upgrade query");
			
			$mailto = readconfigfromdb("admin");
			$mailfrom = readconfigfromdb("admin");
			// Include redmine in each report

			$mailsubject = "[eLAS " . $configuration["system"]["systemtag"] ."] DB Update FAILED";
			
			$mailcontent = "A query failed during the upgrade of your eLAS database!\n  This report has been copied to the eLAS developers.";
			$mailcontent .= "\nFailed query: $query\n";
			$mailcontent .= "\r\n";
			$mailcontent .= "eLAS versie: " .$elas->version ."-" .$elas->branch ."-r" .$elas->revision ."\r\n";
			
			$mailcontent .= "De eLAS update robot";
			
			if($elas->branch != 'dev'){
				sendemail($mailfrom,$mailto,$mailsubject,$mailcontent);
			}
			exit;
	} else {
			echo "OK\n";
			log_event("","DB", "OK upgrade query");
	}
	return $result;
}

function generate_oldtransid(){
        global $baseurl;
        global $s_id;
        $genid = "E1" .sha1($s_id .microtime()) .$_SESSION["id"] ."@" . $baseurl;
        return $genid;
}

?>

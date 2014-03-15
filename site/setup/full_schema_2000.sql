-- phpMyAdmin SQL Dump
-- version 2.11.9.4
-- http://www.phpmyadmin.net
--
-- Host: mysql.vsbnet.be
-- Generation Time: Apr 12, 2010 at 07:51 AM
-- Server version: 5.0.88
-- PHP Version: 5.2.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `letsa`
--

-- --------------------------------------------------------

--
-- Table structure for table `apikeys`
--

CREATE TABLE IF NOT EXISTS `apikeys` (
  `id` int(11) NOT NULL auto_increment,
  `apikey` varchar(80) NOT NULL,
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `comment` varchar(200) default NULL,
  PRIMARY KEY  (`id`),
  KEY `apikey` (`apikey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `apikeys`
--


-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(40) NOT NULL default '',
  `id_parent` int(4) NOT NULL default '0',
  `description` varchar(60) default NULL,
  `cdate` datetime default NULL,
  `id_creator` int(4) NOT NULL default '0',
  `fullname` varchar(100) default NULL,
  `leafnote` int(4) NOT NULL default '0',
  `stat_msgs_wanted` int(4) default '0',
  `stat_msgs_offers` int(4) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `id_parent`, `description`, `cdate`, `id_creator`, `fullname`, `leafnote`, `stat_msgs_wanted`, `stat_msgs_offers`) VALUES
(1, 'Algemeen', 0, NULL, NULL, 0, 'Algemeen', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `category` varchar(50) NOT NULL,
  `setting` varchar(60) NOT NULL,
  `value` varchar(60) default NULL,
  `description` varchar(140) default NULL,
  `comment` varchar(140) default NULL,
  `default` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`setting`),
  KEY `category` (`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`category`, `setting`, `value`, `description`, `comment`, `default`) VALUES
('system', 'currency', 'lets', 'LETS-eenheid voor de groep', NULL, 1),
('system', 'systemtag', 'mijngroep', 'Systeemtag', NULL, 1),
('system', 'systemname', 'LETS locatie', 'Systeemnaam', NULL, 1),
('system', 'emptypasswordlogin', '0', 'Lege passwoorden toelaten', NULL, 0),
('system', 'pwscore', '50', 'Minimum score bij wijzigen password', NULL, 0),
('system', 'maintenance', '0', 'Onderhoudsmodus (alleen admins kunnen inloggen)', NULL, 0),
('system', 'newuserdays', '30', 'Aantal dagen dat een gebruiker als instapper getoond wordt', NULL, 1),
('cron', 'saldofreqdays', '7', 'Saldo-mails worden om dit aantal dagen verstuurd', NULL, 1),
('cron', 'adminmsgexp', '0', 'Stuur de admin een overzichtmail met vervallen messages', NULL, 1),
('cron', 'adminmsgexpfreqdays', '30', 'Elke hoeveel dagen moet de overzichtsmail naar de admin verstuurd worden', NULL, 1),
('cron', 'msgexpwarnenabled', '1', 'Mails versturen bij verval van messages', NULL, 1),
('cron', 'msgexpwarningdays', '7', 'Hoeveel dagen voor het vervallen wordt de user verwittigd', NULL, 1),
('cron', 'msgexpcleanupdays', '30', 'Hoeveel dagen na het vervallen worden messages verwijderd', NULL, 1),
('users', 'minlimit', '-2000', 'Limiet voor minstand voor nieuwe gebruikers', NULL, 1),
('mail', 'mailenabled', '1', 'Mail functionaliteit aanzetten', NULL, 0),
('mail', 'admin', '', 'Mailadres van de site admin', NULL, 1),
('mail', 'support', '', 'Mailadres van de support persoon of groep', NULL, 1),
('mail', 'from_address', 'noreply', 'From adres op algemene mails', NULL, 1),
('mail', 'from_address_transactions', 'noreply', 'From adres op transactie-mails', NULL, 1),
('system', 'currencyratio', '60', 'letseenheden per uur', NULL, 1),
('mail', 'newsadmin', '', 'email adres van de nieuws-beheerder', NULL, 1),
('users', 'maxlimit', '2000', 'Limiet voor maxstand voor nieuwe gebruikers', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(11) NOT NULL auto_increment,
  `id_type_contact` int(4) NOT NULL default '0',
  `comments` varchar(50) default NULL,
  `value` varchar(50) NOT NULL default '',
  `id_user` int(4) NOT NULL default '0',
  `flag_public` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `contact`
--


-- --------------------------------------------------------

--
-- Table structure for table `cron`
--

CREATE TABLE IF NOT EXISTS `cron` (
  `cronjob` varchar(20) NOT NULL,
  `lastrun` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  KEY `cronjob` (`cronjob`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cron`
--


-- --------------------------------------------------------

--
-- Table structure for table `eventlog`
--

CREATE TABLE IF NOT EXISTS `eventlog` (
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `userid` int(11) NOT NULL,
  `type` varchar(15) NOT NULL,
  `event` text NOT NULL,
  `ip` varchar(30) NOT NULL,
  KEY `type` (`type`),
  KEY `timestamp` (`timestamp`),
  KEY `userid` (`userid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `eventlog`
--


-- --------------------------------------------------------

--
-- Table structure for table `interletsq`
--

CREATE TABLE IF NOT EXISTS `interletsq` (
  `transid` varchar(80) NOT NULL,
  `date_created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `id_from` int(11) NOT NULL,
  `letsgroup_id` int(11) NOT NULL,
  `letscode_to` varchar(20) NOT NULL,
  `amount` float NOT NULL,
  `description` varchar(60) NOT NULL,
  `signature` varchar(80) NOT NULL,
  `retry_until` timestamp NOT NULL default '0000-00-00 00:00:00',
  `retry_count` int(3) NOT NULL,
  `last_status` varchar(15) default NULL,
  PRIMARY KEY  (`transid`),
  KEY `id_from` (`id_from`),
  KEY `letsgroup_id` (`letsgroup_id`),
  KEY `letscode_to` (`letscode_to`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `interletsq`
--


-- --------------------------------------------------------

--
-- Table structure for table `letsgroups`
--

CREATE TABLE IF NOT EXISTS `letsgroups` (
  `id` int(11) NOT NULL auto_increment,
  `groupname` varchar(128) NOT NULL,
  `shortname` varchar(50) NOT NULL,
  `prefix`  VARCHAR( 5 ) NULL,
  `apimethod` varchar(20) NOT NULL,
  `remoteapikey` varchar(80) default NULL,
  `localletscode` varchar(20) NOT NULL,
  `myremoteletscode` varchar(20) NOT NULL,
  `url` varchar(256) default NULL,
  `elassoapurl` varchar(256) default NULL,
  `presharedkey` varchar(80) default NULL,
  PRIMARY KEY  (`id`),
  KEY `groupname` (`groupname`),
  KEY `shortname` (`shortname`),
  KEY `localletscode` (`localletscode`),
  KEY `myremoteletscode` (`myremoteletscode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `letsgroups`
--


-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL auto_increment,
  `cdate` datetime default NULL,
  `mdate` datetime default NULL,
  `validity` datetime default NULL,
  `id_category` int(4) NOT NULL default '0',
  `id_user` int(4) NOT NULL default '0',
  `content` text NOT NULL,
  `Description` text,
  `amount` int(11) default NULL,
  `units` varchar(15) default NULL,
  `msg_type` int(4) NOT NULL default '0',
  `exp_user_warn` tinyint(1) NOT NULL default '0',
  `exp_admin_warn` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `validity` (`validity`),
  KEY `user` (`id_user`),
  KEY `exp_user_warn` (`exp_user_warn`,`exp_admin_warn`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `messages`
--


-- --------------------------------------------------------

--
-- Table structure for table `msgpictures`
--

CREATE TABLE IF NOT EXISTS `msgpictures` (
  `id` int(11) NOT NULL auto_increment,
  `msgid` int(11) NOT NULL,
  `PictureFile` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `msgid` (`msgid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `msgpictures`
--


-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL auto_increment,
  `id_user` int(4) NOT NULL default '0',
  `headline` varchar(200) NOT NULL default '',
  `newsitem` text NOT NULL,
  `cdate` datetime default NULL,
  `itemdate` datetime NOT NULL default '0000-00-00 00:00:00',
  `approved` tinyint(1) NOT NULL,
  `sticky` tinyint(1) default NULL,
  `location` varchar(128) default NULL,
  PRIMARY KEY  (`id`),
  KEY `approved` (`approved`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `news`
--


-- --------------------------------------------------------

--
-- Table structure for table `parameters`
--

CREATE TABLE IF NOT EXISTS `parameters` (
  `parameter` varchar(60) NOT NULL,
  `value` varchar(60) default NULL,
  PRIMARY KEY  (`parameter`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `parameters`
--

INSERT INTO `parameters` (`parameter`, `value`) VALUES
('schemaversion', '2000');

-- --------------------------------------------------------


--
-- Table structure for table `regions`
--

CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `abbrev` varchar(11) NOT NULL default '',
  `comments` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `regions`
--


-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE IF NOT EXISTS `tokens` (
  `token` varchar(50) NOT NULL,
  `validity` datetime NOT NULL,
  PRIMARY KEY  (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tokens`
--


-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(4) NOT NULL auto_increment,
  `amount` int(11) NOT NULL default '0',
  `description` varchar(60) NOT NULL default '0',
  `id_from` int(4) NOT NULL default '0',
  `id_to` int(4) NOT NULL default '0',
  `real_from` varchar(80) default NULL,
  `real_to` varchar(80) default NULL,
  `transid` varchar(80) default NULL,
  `creator` int(4) NOT NULL default '0',
  `cdate` datetime default NULL,
  `date` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `transid` (`transid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `transactions`
--


-- --------------------------------------------------------

--
-- Table structure for table `type_contact`
--

CREATE TABLE IF NOT EXISTS `type_contact` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(20) NOT NULL default '',
  `abbrev` varchar(11) NOT NULL default '',
  `protect` tinyint(1) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `type_contact`
--

INSERT INTO `type_contact` (`id`, `name`, `abbrev`, `protect`) VALUES
(3, 'E-mail', 'mail', NULL),
(5, 'Fax', 'fax', NULL),
(1, 'Telefoon', 'tel', NULL),
(2, 'gsm', 'gsm', NULL),
(4, 'Adres', 'adr', 1),
(6, 'Website', 'web', NULL),
(7, 'Jabber/Gtalk', 'jabber', NULL),
(8, 'Skype', 'skype', NULL),
(9, 'ICQ', 'icq', NULL),
(10, 'MSN', 'msn', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL auto_increment,
  `cdate` datetime default NULL,
  `mdate` datetime default NULL,
  `id_region` int(4) NOT NULL default '0',
  `creator` int(4) NOT NULL default '0',
  `comments` varchar(100) default NULL,
  `hobbies` text NOT NULL,
  `name` varchar(50) NOT NULL default '',
  `birthday` date default NULL,
  `letscode` varchar(20) NOT NULL default '',
  `postcode` varchar(6) NOT NULL default '',
  `login` varchar(50) NOT NULL default '',
  `cron_saldo` tinyint(1) default NULL,
  `password` varchar(50) NOT NULL default '',
  `accountrole` varchar(20) NOT NULL default '',
  `status` int(4) NOT NULL default '0',
  `saldo` int(11) NOT NULL default '0',
  `lastlogin` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `minlimit` int(11) NOT NULL default '0',
  `maxlimit` int(11) default NULL,
  `fullname` varchar(100) default NULL,
  `admincomment` varchar(200) default NULL,
  `PictureFile` varchar(128) default NULL,
  `presharedkey` varchar(80) default NULL,
  `pwchange` tinyint(1) default NULL,
  `locked` tinyint(1) default NULL,
  PRIMARY KEY  (`id`),
  KEY `cron_saldo` (`cron_saldo`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `cdate`, `mdate`, `id_region`, `creator`, `comments`, `hobbies`, `name`, `birthday`, `letscode`, `postcode`, `login`, `cron_saldo`, `password`, `accountrole`, `status`, `saldo`, `lastlogin`, `minlimit`, `maxlimit`, `fullname`, `admincomment`, `PictureFile`, `presharedkey`, `pwchange`, `locked`) VALUES
(1, NULL, NULL, 0, 4, '', 'beheerder', 'admin', NULL, '100', '', 'admin', NULL, 'e2e84334ff0e7c6ca4582991dc0d6384', 'admin', 1, 0, '2010-04-12 07:42:37', -1, 1, 'admin', NULL, NULL, NULL, NULL, NULL);

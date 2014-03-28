<?php

// Copyleft(C) 2014 martti <info@martti.be>

// Copyright(C) 2009 Guy Van Sanden <guy@vsbnet.be>
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 
 
ob_start();

require_once('includes/default.php');
require_once('includes/request.php');

$req = new request('anonymous');

$req->setEntity('index');
	
require('includes/header.php');

if (file_exists('site/index_content.html')){
	include 'site/index_content.html';
}

require('./includes/footer.php');


?>

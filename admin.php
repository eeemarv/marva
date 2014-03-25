<?php

require_once 'includes/request.php';

$req = new request('admin');

$req->setEntity('admin')
	->setUrl('admin.php')
	->add('location', 'messages.php', 'get')
	->toggleAdmin();

$location = ltrim(urldecode($req->get('location')), '/');
$location = ($location) ? $location : 'messages.php';
header('Location: '.$location);
exit;

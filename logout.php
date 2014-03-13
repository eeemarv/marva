<?php
require_once('./includes/inc_default.php');
session_start();
$_SESSION = array();
session_destroy();
header('Location: login.php');
?>




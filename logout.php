<?php
require_once('./includes/default.php');
session_start();
$_SESSION = array();
session_destroy();
header('Location: .');
?>




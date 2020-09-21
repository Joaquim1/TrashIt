<?php
session_start();

include(dirname(dirname(__DIR__)) . "/includes/init.php");

$webFunctions = new webkit($db);

$webFunctions->logoutUser();
session_destroy();

header("Location: " . $webFunctions->getSiteURL() . '/login');
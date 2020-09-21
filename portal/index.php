<?php
session_start();

include(dirname(__DIR__) . "/includes/init.php");

$webFunctions = new webkit($db);

if(!$webFunctions->userLoggedIn()) { // If the user is not logged in
	header("Location: " . $webFunctions->getSiteURL() . '/login');
	return;
}

$webFunctions->updateSessionVars();

foreach($_GET as $key => $value)
{
	$pageInfo[$key] = $value;
}

if(isset($pageInfo['page']))
{
	if($webFunctions->loadPortalPage('pages/' . $pageInfo['page'] . '.html', $pageInfo['page']))
	{
		return;
	}
	else
	{
		if($webFunctions->loadPortalPage('pages/' . $pageInfo['page'] . '.php', $pageInfo['page']))
			return;
		else
			$webFunctions->loadPortalPage('pages/errors/404.html');
	}
	$webFunctions->loadPortalPage('pages/' . $pageInfo['page'], $pageInfo['page']);
}
else
	$webFunctions->loadPortalPage('pages/home.html');
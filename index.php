<?php
include("includes/init.php");

$webFunctions = new webkit($db);

foreach($_GET as $key => $value)
{
	$pageInfo[$key] = $value;
}

if(isset($pageInfo['page']))
{
	if($webFunctions->loadPage('pages/' . $pageInfo['page'] . '.html', $pageInfo['page']))
	{
		return;
	}
	else
	{
		if($webFunctions->loadPage('pages/' . $pageInfo['page'] . '.php', $pageInfo['page']))
			return;
		else
			$webFunctions->loadPage('pages/errors/404.html');
	}
	$webFunctions->loadPage('pages/' . $pageInfo['page'], $pageInfo['page']);
}
else
	$webFunctions->loadPage('pages/home.html');
<?php

include(dirname(__DIR__) . "/includes/init.php");
$webFunctions = new webkit($db);

if(date("w") != 0)
	die;

// Go through database to see who has an active subscription
$query = $db->prepare("SELECT id FROM accounts WHERE activesubscription = 1");
$query->execute();
$results = $query->fetchAll(PDO::FETCH_ASSOC);
$subsCanceled = 0;
foreach($results as $key => $value)
{
	// Reset number of pickups theyve had this week
	$webFunctions->resetWeekPickups($value['id']);
	$accountsReset++;
}

echo 'Executed successfully. ' . $accountsReset . ' pickups have been reset';
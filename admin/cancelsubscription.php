<?php

include(dirname(__DIR__) . "/includes/init.php");
$webFunctions = new webkit($db);
$mailFunctions = new mailkit($mgClient, $domain, $db);

// Go through database to see who's subscription needs to be canceled
$query = $db->prepare("SELECT id FROM accounts WHERE subscription_end = :enddate");
$today = date("Y-m-d"); // Set end date to match how it is in database. Example: 2017-06-18
$query->execute(array(
		':enddate' => $today
	));

$results = $query->fetchAll(PDO::FETCH_ASSOC);
$subsCanceled = 0;
foreach($results as $key => $value)
{
	$webFunctions->cancelSubscription($value['id']);
	$mailFunctions->userCanceledAccount($value['id']);
	$subsCanceled++;
}

echo 'Executed successfully. ' . $subsCanceled . ' subscriptions have been canceled';
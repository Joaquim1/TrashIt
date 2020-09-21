<?php

include(dirname(__DIR__) . "/includes/init.php");
$webFunctions = new webkit($db);

// Check if this script was already ran today
$query = $db->prepare("SELECT pickup_updated FROM serverinfo WHERE id = 1");
$query->execute();
$result = $query->fetch(PDO::FETCH_ASSOC);

// If it has already been ran today
if($result['pickup_updated'] == date("Y-m-d"))
	die;

// Empty the pickups for today
$query = $db->prepare("TRUNCATE TABLE day_pickups");
$query->execute();

// Go through database to see who's subscription needs to be canceled
$query = $db->prepare("SELECT id, curweek_pickups, pickup_day1, pickup_day2, timeslot_day1, timeslot_day2 FROM accounts WHERE activesubscription = 1 AND (pickup_day1 = :today OR pickup_day2 = :today)");
$query->execute(array(
		':today' => date("N")
	));
$results = $query->fetchAll(PDO::FETCH_ASSOC);

// Go through all results
foreach($results as $key => $value)
{
	// If the user has more than 2 pickups this weeks, skip this user
	if($value['curweek_pickups'] >= 2)
		continue;

	// Update current user to +1 pickups
	$updateUser = $db->prepare("UPDATE accounts SET curweek_pickups = curweek_pickups + 1 WHERE id = :id");
	$updateUser->execute(array(
			':id' => $value['id']
		));

	if($value['pickup_day1'] == date("N"))
		$timeslot = $value['timeslot_day1'];
	else
		$timeslot = $value['timeslot_day2'];

	// Insert them into today's pickups
	$insertUser = $db->prepare("INSERT INTO day_pickups VALUES (NULL, :id, NOW(), :timeslot, 0)");
	$insertUser->execute(array(
			':id' => $value['id'],
			':timeslot' => $timeslot,
		));
}

// Update last time this script was run so it wont mess up people's subscriptions
$query = $db->prepare("UPDATE serverinfo SET pickup_updated = NOW() WHERE id = 1");
$query->execute();
<?php

include(dirname(dirname(__DIR__)) . "/includes/init.php");

$webFunctions = new webkit($db);

if(!isset($_POST["bt_signature"]) || !isset($_POST["bt_payload"]))
    die;


// Get webhook information
try {
    $webhookNotification = Braintree_WebhookNotification::parse(
        $_POST["bt_signature"], $_POST["bt_payload"]
    );
} catch(Braintree_Exception_InvalidSignature $e) {
    die;
}

// If the webhook is invalid, kill page
if($webhookNotification->kind != "subscription_charged_successfully")
    die;

// Get user information based on subscription ID
$query = $db->prepare("SELECT id, pastdue FROM accounts WHERE subscriptionid = :subid");
$query->execute(array(
        ':subid' => $webhookNotification->subscription->id
    ));
$result = $query->fetch(PDO::FETCH_ASSOC);


// Create the transaction
$webFunctions->createTransaction($result['id'], $webhookNotification->subscription->transactions[0]->id, $webhookNotification->subject['subscription']['transactions'][0]['amount'], $webhookNotification->timestamp->format("Y-m-d H:i:s"), $webhookNotification->subscription->transactions[0]->creditCardDetails->last4);
// If change in price, update it here
$webFunctions->updateSubscriptionAmount($result['id'], $webhookNotification->subscription->price);

if($result['pastdue'] == 1)
	$webFunctions->updatePastDue($result['id'], 0);
<?php

include(dirname(dirname(__DIR__)) . "/includes/init.php");

$webFunctions = new webkit($db);
$mailFunctions = new mailkit($mgClient, $domain, $db);

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
if($webhookNotification->kind != "subscription_went_past_due")
    die;

// Get user information based on subscription ID
$query = $db->prepare("SELECT id FROM accounts WHERE subscriptionid = :subid");
$query->execute(array(
        ':subid' => $webhookNotification->subscription->id
    ));
$result = $query->fetch(PDO::FETCH_ASSOC);

// Set their account to past due
$webFunctions->updatePastDue($result['id'], 1);
$mailFunctions->pastDueAccount($result['id']); // Send them an email saying that their account is past due
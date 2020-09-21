<?php

include(dirname(dirname(__DIR__)) . "/includes/init.php");
$webFunctions = new webkit($db);
$mailFunctions = new mailkit($mgClient, $domain, $db);
$transFunctions = new transactionKit();

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

// If the webhook wasn't for the subscription going past due, kill the page
if($webhookNotification->kind != "subscription_canceled") 
    die;

// Get the user ID based on the subscription ID
$query = $db->prepare("SELECT id, activesubscription, pastdue FROM accounts WHERE subscriptionid = :subid");
$query->execute(array(
        ':subid' => $webhookNotification->subscription->id,
    ));
$result = $query->fetch(PDO::FETCH_ASSOC);

// Double check that this was user done, if it is already 0, user had canceled it already
// If the account wasn't paste due, it was user done
if($result['activesubscription'] == 0 || $result['pastdue'] == 0)
    die;

// Cancel the subscription
$webFunctions->cancelSubscription($result['id']);
$transFunctions->deleteUserID($result['id']);
$mailFunctions->remoteCanceledAccount($result['id']); // Send email
<?php
session_start();

include(dirname(dirname(__DIR__)) . "/includes/init.php");
$webFunctions = new webkit($db);
$transactions = new transactionKit();
$mailFunctions = new mailkit($mgClient, $domain, $db);

if(isset($_POST['action'])) {
	switch($_POST['action'])
	{
		case 'add-information':
		{
			$addInformation = $webFunctions->addUserInformation($_SESSION['id'], $_POST); // Add inputted user info

			if($addInformation != 1) { // There was an error with the information provided
				if($addInformation == 2) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/bad-form");
					return;
				} 
			}
			// Update checkout variables
			$_SESSION['subscription']['information'] = 1;
			$_SESSION['subscription']['address'] = 0;
			$_SESSION['subscription']['payment'] = 0; 
			// Get price of option based on apartment style
			switch($_POST['apartment-style'])
			{
				case '1': $_SESSION['subscription']['price'] = 9.99; break;
				case '2': $_SESSION['subscription']['price'] = 19.99; break;
				case '3': $_SESSION['subscription']['price'] = 29.99; break;
				case '4': $_SESSION['subscription']['price'] = 39.99; break;
				default: $_SESSION['subscription']['price'] = 500.99; break;
			}
			header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/address");

			break;
		}
		case 'add-address':
		{
			$addAddress = $webFunctions->addAddress($_SESSION['id'], $_POST); // Add address information

			// 1 = good, 2 = bad form submission, 3 = more than 3 miles away, 4 = bad address
			if($addAddress != 1) {
				if($addAddress == 2) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/address/bad-form");
					return;
				} elseif($addAddress == 3) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/address/bad-distance");
					return;
				} elseif($addAddress == 4) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/address/bad-address");
					return;
				}
			}

			$_SESSION['subscription']['address'] = 1;
			$_SESSION['subscription']['payment'] = 0;

			header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/payment-information");

			break;
		}
		case 'payment-information':
		{
			$paymentToken = $transactions->createUser($_POST['payment_nonce']); // Create Braintree user

			if(!$paymentToken) {
				header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/payment-information/bad-payment");
				die;
			}
			$createSubscription = $transactions->createSubscription($paymentToken, $_SESSION['apartment_rooms']); // Create user subscription
			//Subscription was successfully created
			if($createSubscription)	{
				$webFunctions->updateSubscriptionStatus(1); // Update that user has subscription in DB
				$webFunctions->updateBillingDay($_SESSION['subscription']['billingDay']); // Put day of the month the user gets charged on
				$webFunctions->updateLast4($_SESSION['subscription']['last4']); // Update users last 4 digits of CC
				$webFunctions->updateSubscriptionAmount($_SESSION['id'], $_SESSION['subscription']['price']); // Update the users profile to reflect how much they are paying each month
				$webFunctions->updateSubscriptionID($_SESSION['subscription']['subscriptionID']);
				$webFunctions->updateSubscriptionDate();
				$mailFunctions->newSubscription($_SESSION['id'], $_SESSION['subscription']['transactionID']);

				$_SESSION['subscription']['purchase-success'] = true;
				header("Location: " . $webFunctions->getSiteURL() . "/portal/purchase-complete");
			} else {
				$transactions->deleteUser(); // Delete user from Braintree Vault
				$webFunctions->updateSubscriptionStatus(0); // Update that user does not have subscription in DB
				header("Location: " . $webFunctions->getSiteURL() . "/portal/subscribe/payment-information/bad-payment");
			}

			break;
		}
		case 'updatesettings':
		{
			$error = 0;

			foreach($_POST as $key => $value)
			{
				if(strlen($value) == 0) // A field is empty, error
				{
					header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/bad-form");
					return;
				}
			}

			if(strtolower($_POST['email']) != strtolower($_SESSION['email']) && !$webFunctions->checkValidEmail($_POST['email']))
			{
				// Invalid email
				header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/bad-email");
				return;
			}

			if(strtolower($_POST['email']) != strtolower($_SESSION['email']) && !$webFunctions->checkAvailableEmail($_POST['email']))
			{
				// email is already in use
				header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/used-email");
				return;
			}

			if(($_POST['phonenumber'] != $_SESSION['phonenumber'] && strlen($_POST['phonenumber']) != 10) || !is_numeric($_POST['phonenumber']))
			{
				// Invalid phone number
				header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/bad-phone");
				return;
			}

			if(!$webFunctions->attemptLogin($_SESSION['email'], $_POST['curpass']))
			{
				// Bad password
				header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/bad-password");
				return;
			}

			$webFunctions->updateSettings($_POST); // Update user settings with array

			header("Location: " . $webFunctions->getSiteURL() . "/portal/settings/success"); // Redirect
			break;
		}
		case 'updatepass':
		{
			if(!$webFunctions->attemptLogin($_SESSION['email'], $_POST['curpass'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/portal/password/bad-password");
				die;
			}

			if($_POST['newpass'] != $_POST['confpass']) {
				header("Location: " . $webFunctions->getSiteURL() . "/portal/password/bad-password-match");
				die;
			}

			if(!$webFunctions->verifyPasswordCreds($_POST['newpass'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/portal/password/pass-creds");
				die;
			}

			$updatePass = $webFunctions->updatePassword($_SESSION['id'], $_POST['newpass']);
			header("Location: " . $webFunctions->getSiteURL() . "/portal/password/success");

			break;
		}
		case 'update-payment':
		{
			// Will return the token of the new payment method
			$updateCard = $transactions->updateUserCard($_POST['payment_nonce']);
			if($updateCard != 0)
			{
				$webFunctions->updateLast4($_SESSION['temp_last4']); // Update user's last4 for CC number
				$updateSubscription = $transactions->updateSubscription($updateCard); // Give new CC token to subscription
				header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/success");
			} else {
				header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/error");
			}

			break;
		}
		case 'updatepickup':
		{
			$updateInformation = $webFunctions->updateUserInformation($_SESSION['id'], $_POST); // Add inputted user info

			if($updateInformation != 1) { // There was an error with the information provided
				if($updateInformation == 2) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/bad-form");
					return;
				} elseif($updateInformation == 3) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/bad-distance");
					return;
				} elseif($updateInformation == 4) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/bad-address");
					return;
				} elseif($updateInformation == 0) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/error");
					return;
				}
			}
			header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/success");

			break;
		}
		case 'cancelsubscription':
		{
			if(!$webFunctions->attemptLogin($_SESSION['email'], $_POST['curpass']))
			{
				header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/bad-password");
				die;
			}

			$transactions->cancelSubscription(); // Cancel subscription in Braintree
			$transactions->deleteUser(); // Delete user from Braintree vault

			// get last day of active subscription
			if(date("j") >= $_SESSION['billingday']) // If last bill is next month
				$lastDay = date("Y-m-", strtotime("+1 month")) . $_SESSION['billingday'];
			else // If last bill is this month
				$lastDay = date("Y-m-") . $_SESSION['billingday'];

			$webFunctions->updateLastDay($lastDay);
			//Success
			header("Location: " . $webFunctions->getSiteURL() . "/portal/subscription/success"); // Redirect
			break;
		}
	}
}
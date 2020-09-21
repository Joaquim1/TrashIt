<?php

class transactionKit {
	public function createUser($paymentNonce)
	{
		try {
			$result = Braintree_Customer::create([
				'id' => $_SESSION['id'],
			    'firstName' => $_SESSION['firstname'],
			    'lastName' => $_SESSION['lastname'],
			    'email' => $_SESSION['email'],
			    'phone' => $_SESSION['phonenumber'],
			    'paymentMethodNonce' => $paymentNonce,
			]);

			if($result->success) {
				if(isset($result->customer->paymentMethods[0]->token))
					return $result->customer->paymentMethods[0]->token;
			} else {
				return 0;
			}
		} catch(Exception $e) {
			return 0;
		}

		return 0;
	}

	public function deleteUser()
	{
		// Delete the user from BT vault
		try {
			$result = Braintree_Customer::delete($_SESSION['id']);
		} catch(Exception $e)
		{
		} finally {
			return $result->success;
		}

	}

	public function deleteUserID($id)
	{
		// Delete the user from BT vault
		$result = Braintree_Customer::delete($id);

		return $result->success;
	}

	public function createSubscription($paymentToken, $planType)
	{
		$planId = "Invalid";
		switch($planType) // Get the plan ID for the subscription
		{
			case '1':
			{
				$planId = "single";
				break;
			}
			case '2':
			{
				$planId = "double";
				break;
			}
			case '3':
			{
				$planId = "triple";
				break;
			}
			case '4':
			{
				$planId = "quad";
				break;
			}
		}
		
		$result = Braintree_Subscription::create([
				'paymentMethodToken' => $paymentToken,
				'planId' => $planId,
			]);

		if($result->success) {
			// Get these variables so we can store them for later
			$_SESSION['subscription']['subscriptionID'] = $result->subscription->id;
			$_SESSION['subscription']['price'] = $result->subscription->price;
			$_SESSION['subscription']['transactionID'] = $result->subscription->transactions[0]->id;
			$_SESSION['subscription']['billingDay'] = $result->subscription->billingDayOfMonth;
			$_SESSION['subscription']['last4'] = $result->subscription->transactions[0]->creditCardDetails->last4;
		}

		return $result->success;
	}

	public function cancelSubscription()
	{
		$result = Braintree_Subscription::cancel($_SESSION['subscriptionid']);

		return $result->success;
	}

	public function updateUserCard($payment_nonce)
	{
		$result = Braintree_Customer::update(
		  $_SESSION['id'],
		  [
		    'creditCard' => [
		        'paymentMethodNonce' => $payment_nonce
		     ]
		  ]
		);
		
		if(!$result->success)
			return 0;

		$customer = Braintree_Customer::find($_SESSION['id']);
		$_SESSION['temp_last4'] = $customer->creditCards[0]->last4;
		return $customer->creditCards[0]->token; // Return the token of the payment method that was just added
	}

	public function updateSubscription($paymentToken)
	{
		$result = Braintree_Subscription::update($_SESSION['subscriptionid'], [
		    'paymentMethodToken' => $paymentToken,
		]);

		return $result->success;
	}
}
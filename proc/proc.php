<?php
session_start();

include(dirname(__DIR__) . "/includes/init.php");

$webFunctions = new webkit($db);
$mailFunctions = new mailkit($mgClient, $domain, $db);

if(isset($_POST['action'])) {
	switch($_POST['action'])
	{
		case 'login':
		{
			$attemptLogin = $webFunctions->attemptLogin($_POST['email'], $_POST['password']);

			if($attemptLogin) {
				$loginUser = $webFunctions->loginUser($_POST['email']);

				if($loginUser) {
					header("Location: " . $webFunctions->getSiteURL() . "/portal/");
					die;
				}
			}
			else {
				header("Location: " . $webFunctions->getSiteURL() . "/login/badlogin");
				die;
			}
			break;
		}
		case 'register':
		{
			$userInfo = array(
					'firstname' => $_POST['firstname'],
					'lastname' => $_POST['lastname'],
					'email' => $_POST['email'],
					'password' => $_POST['password'],
					'ipaddress' => $_SERVER['REMOTE_ADDR'],
				);

			if(!$webFunctions->verifyPasswordCreds($_POST['password'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/register/pass-creds");
				die;
			}

			if(!$webFunctions->checkValidEmail($userInfo['email'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/register/invalid-email");
				die;
			}
			
			if(!$webFunctions->checkAvailableEmail($userInfo['email'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/register/used-email");
				die;
			}

			$registerUser = $webFunctions->registerUser($userInfo);

			if($registerUser) {
				$webFunctions->loginUser($userInfo['email']);
				$mailFunctions->newAccount($_SESSION['id']);
				header("Location: " . $webFunctions->getSiteURL() . "/portal");
				die;
			} else {
				header("Location: " . $webFunctions->getSiteURL() . "/register");
				die;
			}

			break;
		}
		case 'forgot':
		{
			if($webFunctions->checkAvailableEmail($_POST['email'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/forgot/bad-email");
				die;
			}

			$verificationKey = $webFunctions->newVerificationKey($_POST['email']); // Create new verification key
			$mailFunctions->forgotPassword($_POST['email'], $verificationKey); // Send email with information
			header("Location: " . $webFunctions->getSiteURL() . "/forgot/check-email"); // Redirect to a page saying to check their email
			break;
		}
		case 'resetpassword':
		{
			// Doesn't meet password credentials
			if(!$webFunctions->verifyPasswordCreds($_POST['newpass'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/forgot/reset/" . $_POST['verification_key'] . "/pass-creds");
				die;
			}

			// Verify the verification key
			if(!$webFunctions->validateKey($_POST['verification_key'])) {
				header("Location: " . $webFunctions->getSiteURL() . "/forgot/reset/" . $_POST['verification_key'] . "/bad-key");
				die;
			}

			if($_POST['newpass'] != $_POST['confpass'])	{
				header("Location: " . $webFunctions->getSiteURL() . "/forgot/reset/" . $_POST['verification_key'] . "/bad-pass");
				die;
			}

			// Reset the password & redirect
			$resetPassword = $webFunctions->resetUserPassword($_POST['verification_key'], $_POST['newpass']);
			header("Location: " . $webFunctions->getSiteURL() . "/forgot/success");
			die;
		}
	}
}
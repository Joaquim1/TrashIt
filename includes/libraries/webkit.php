<?php

class webkit {
	private $dbConn, $siteInfo;

	public function __construct($connectionData = NULL) {
		$this->dbConn = $connectionData;
		$this->loadServerData();
	}

	/* LOGIN FUNCTIONS */

	public function attemptLogin($email, $password) {
		$encryptedPassword = $this->encryptPassword($email, $password);
		$checkValidAccount = $this->dbConn->prepare("SELECT id FROM accounts WHERE email = :email AND password = :password");
		$checkValidAccount->execute(array(
				':email' => $email,
				':password' => $encryptedPassword
			));

		if($checkValidAccount->rowCount() == 1)
			return 1;
		else
			return 0;
	}

	public function encryptPassword($email, $password) {
		$retrievePassToken = $this->dbConn->prepare("SELECT passwordtoken FROM serverinfo WHERE id = 1");
		$retrievePassToken->execute();

		$passToken = $retrievePassToken->fetch(PDO::FETCH_ASSOC);

		$passToken = $passToken['passwordtoken'];
		$email = strtolower($email);

		$password = hash("Whirlpool", $email . $passToken . $password);
		return $password;
	}

	public function loginUser($email) {
		$retrieveUserInfo = $this->dbConn->prepare("SELECT * FROM accounts WHERE email = :email");
		$retrieveUserInfo->execute(array(
				':email' => $email
			));

		if($retrieveUserInfo->rowCount() == 0) // Email doesn't exist
			return 0;

		if(isset($_SESSION)) {
			$this->logoutUser();
		}

		$userInfo = $retrieveUserInfo->fetch(PDO::FETCH_ASSOC);

		foreach($userInfo as $key => $value)
		{
			$_SESSION[$key] = $value;
		}

		$this->updateAccountActive($userInfo['id'], 1);

		$_SESSION['loggedIn'] = true; // So we can tell this user is logged in
		unset($_SESSION['password']); // Dont want to store password in local variables

		return 1;
	}

	public function updateSessionVars() {
		if(!$this->userLoggedIn())
			return 0;

		$retrieveUserInfo = $this->dbConn->prepare("SELECT * FROM accounts WHERE id = :id");
		$retrieveUserInfo->execute(array(
				':id' => $_SESSION['id']
			));

		if($retrieveUserInfo->rowCount() == 0)
			return 0;

		$userInfo = $retrieveUserInfo->fetch(PDO::FETCH_ASSOC);

		foreach($userInfo as $key => $value)
		{
			$_SESSION[$key] = $value;
		}

		unset($_SESSION['password']);
	}

	public function logoutUser() {
		if(!$this->userLoggedIn())
			return 0;

		$this->updateAccountActive($_SESSION['id'], 0);

		foreach($_SESSION as $key => $value)
		{
			unset($_SESSION[$key]);
		}

		return true;
	}

	public function updateAccountActive($id, $active)
	{
		$updateAccount = $this->dbConn->prepare("UPDATE accounts SET active = :active, lastlogin = NOW(), ipaddress = :ip WHERE id = :id");
		$updateAccount->execute(array(
				':active' => $active,
				':id' => $_SESSION['id'],
				':ip' => $_SERVER['REMOTE_ADDR'],
			));

		if($updateAccount->rowCount() == 1)
			return 1;

		return 0;
	}

	public function resetUserPassword($ver_key, $newpass)
	{
		// Get user information from verification key; user = user id
		$query = $this->dbConn->prepare("SELECT user FROM verification_keys WHERE ver_key = :ver_key AND active = 1");
		$query->execute(array(
				':ver_key' => $ver_key
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		// Updates user password & encrypts it
		$updatePassword = $this->updatePassword($result['user'], $newpass);
		// Disable verification key
		$query = $this->dbConn->prepare("UPDATE verification_keys SET active = 0 WHERE ver_key = :ver_key");
		$query->execute(array(
				':ver_key' => $ver_key
			));

		return 1;
	}

	/* REGISTRATION FUNCTIONS */

	public function registerUser($userInfo) {
		if(!isset($userInfo['firstname']) || !isset($userInfo['lastname']) || !isset($userInfo['email']) || !isset($userInfo['password'])) {
			echo 'not everything defined';
			return 0;
		}

		if(!$this->checkAvailableEmail($userInfo['email']))
			return 0;

		$insertUser = $this->dbConn->prepare("INSERT INTO accounts (email, password, ipaddress, lastlogin, firstname, lastname) VALUES (:email, :password, :ipaddress, NOW(), :firstname, :lastname)");
		$insertUser->execute(array(
				':email' => $userInfo['email'],
				':password' => $this->encryptPassword($userInfo['email'], $userInfo['password']),
				':ipaddress' => $userInfo['ipaddress'],
				':firstname' => $userInfo['firstname'],
				':lastname' => $userInfo['lastname']
			));

		if(!$insertUser)
			return 0;

		return 1;
	}

	public function checkAvailableEmail($email) {
		$checkEmail = $this->dbConn->prepare("SELECT ID FROM accounts WHERE email = :email");
		$checkEmail->execute(array(
				':email' => $email
			));

		if($checkEmail->rowCount() == 0)
			return 1;

		return 0;
	}

	/* PORTAL FUNCTIONS */

	public function loadPortalPage($pageName, $usedPage = "") {
		if(!file_exists($pageName))
			return 0;

		$requestedPage = file_get_contents($pageName);
		if($requestedPage) {
			echo $this->fillPortalTemplate($requestedPage);
		}

		return 1;
	}

	public function fillPortalTemplate($page)
	{
		$templatePage = file_get_contents('pages/template.html');
		$page = $this->fillTemplate($templatePage, $page); // Fill the template page with the current page
		$page = $this->loadPortalNav($page); // Load the navigation at the top of the page
		$page = $this->loadPortalSideNav($page); // Load the navigation on the side of the main-content
		$page = $this->loadUserVars($page); // Load the variables associated with the user
		$page = $this->loadSubscriptionVars($page); // Load subscription variables into page
		$page = $this->inputSiteInfo($page); // Load [siteurl] and [sitetitle]
		$page = $this->loadIfStatements($page); // Load if statements: {{if}}, {{else}}, {{elseif}}

		return $page;
	}

	public function userLoggedIn() {
		if(isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] != false)
			return true;

		return false;
	}

	public function loadUserVars($page) {
		foreach($_SESSION as $key => $value)
		{
			if(is_array($_SESSION[$key]))
				break;

			$searchKey = '[[' . $key . ']]';
			$page = str_replace($searchKey, $value, $page);
		}

		return $page;
	}

	public function loadSubscriptionVars($page)
	{
		if(!empty($_SESSION['subscription']['price']))
			$page = str_replace('[[price]]', $_SESSION['subscription']['price'], $page);

		if(!empty($_SESSION['subscription']['transactionID']))
			$page = str_replace('[[order-number]]', $_SESSION['subscription']['transactionID'], $page);

		if(!empty($_SESSION['subscription']['billingDay']))
			$page = str_replace('[[billing-day]]', $_SESSION['subscription']['billingDay'], $page);

		$page = str_replace('[[day1]]', $this->getDay($_SESSION['pickup_day1']), $page);

		$page = str_replace('[[day2]]', $this->getDay($_SESSION['pickup_day2']), $page);

		$page = str_replace('[[time1]]', $this->getTime($_SESSION['timeslot_day1']), $page);

		$page = str_replace('[[time2]]', $this->getTime($_SESSION['timeslot_day2']), $page);

		$page = str_replace('[[next_pickup]]', $this->getNextPickup(), $page);

		$page = str_replace('[[nextPayment]]', $this->getNextPaymentDate(), $page);

		$page = str_replace('[[subscription_startdate]]', date("F jS, Y", strtotime($_SESSION['subscription_start'])), $page);

		if(!empty($_SESSION['subscription_end']))
			$page = str_replace('[[subscription_enddate]]', date("F jS, Y", strtotime($_SESSION['subscription_end'])), $page);
		else
			$page = str_replace('[[subscription_enddate]]', "None", $page);

		if(!empty(strpos($page, "[[payments]]")))
			$page = str_replace('[[payments]]', $this->loadPayments($_SESSION['id']), $page);

		return $page;
	}

	public function loadPortalNav($pageData) {
		$navContent = (file_exists("../portal/pages/nav/nav.html") ? file_get_contents("../portal/pages/nav/nav.html") : "");
		$pageData = str_replace("[portal-nav]", $navContent, $pageData);

		return $pageData;
	}

	public function loadPortalSideNav($pageData) {
		$navContent = (file_exists("../portal/pages/nav/left-nav.html") ? file_get_contents("../portal/pages/nav/left-nav.html") : "");
		$pageData = str_replace("[left-nav]", $navContent, $pageData);

		return $pageData;
	}

	public function removeAllElseTags($page)
	{
		$cutReplacement = preg_replace("/{{else}}(.*?){{\/else}}/s", "", $page); // Remove all else tags
		$cutReplacement = preg_replace("/{{elseif:(.*?){\/elseif}}/s", "", $cutReplacement); // Remove all else-if tags

		return $cutReplacement;
	}

	public function checkElseIfStatements($elseIfQuery, &$newContent)
	{
		$querySuccess = 0;
		for($j = 0;$j < sizeof($elseIfQuery[0]);$j++) // Check all else ifs
		{
			if($this->checkIf($elseIfQuery[1][$j]))
			{
				$newContent = $elseIfQuery[2][$j]; // Update content
				$querySuccess = 1;
				break;
			} else {
				$newContent = "";
			}
		}

		return $querySuccess;
	}

	public function loadIfStatements($page) {
		// Retrieve all if-statements
		preg_match_all("/{{if:(.*?)}}(.*?){{\/if}}/s", $page, $query);
		for($i = 0;$i < sizeof($query[0]);$i++) // Go through all if statements: 0 = whole statement, 1 = if statement, 2 = content
		{
			if($this->checkIf($query[1][$i])) // If the statement is true
			{
				$cutReplacement = $this->removeAllElseTags($query[2][$i]);
				$newContent = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", $cutReplacement, $query[0][$i], 1); // Update content
			}
			else { // If the query isnt true
				if(preg_match_all("/{{elseif:(.*?)}}(.*?){{\/elseif}}/s", $query[0][$i], $elseIfQuery)) { // If there is an else if
					$elseIfSuccessfull = $this->checkElseIfStatements($elseIfQuery, $newContent); // Check else if statements

					if($elseIfSuccessfull == 0 && preg_match("/{{else}}(.*?){{\/else}}/s", $query[0][$i], $elseQuery)) { // There is a successful else if
						$newContent = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", $elseQuery[1], $query[0][$i], 1);
					} else if($elseIfSuccessfull == 0) { // If no else ifs are successful
						$newContent = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", "", $query[0][$i], 1);
					}
				}
				else if(preg_match("/{{else}}(.*?){{\/else}}/s", $query[0][$i], $elseQuery)) { // There is an else
					$newContent = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", $elseQuery[1], $query[0][$i], 1);
				} else { // Otherwise, show nothing
					$newContent = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", "", $query[0][$i], 1);
				}
			}

			$page = preg_replace("/{{if:" . $query[1][$i] . "}}(.*?){{\/if}}/s", $newContent, $page, 1);
		}

		return $page;
	}

	public function checkIf($condition)
	{
		switch($condition)
		{
			case 'has-subscription':
			{
				if($_SESSION['activesubscription'])
					return true;

				break;
			}
			case 'bad-login':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "badlogin")
					return true;

				break;
			}
			case 'address-details':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "address" && !empty($_SESSION['subscription']['information']))
					return true;

				break;
			}
			case 'payment-information':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "payment-information" && !empty($_SESSION['subscription']['address']))
					return true;

				break;
			}
			case 'bad-address-form':
			{
				if($this->checkIf("address-details") && isset($_GET['param2']) && $_GET['param2'] == "bad-form")
					return true;

				break;
			}
			case 'bad-address-distance':
			{
				if($this->checkIf("address-details") && isset($_GET['param2']) && $_GET['param2'] == "bad-distance")
					return true;

				break;
			}
			case 'bad-address':
			{
				if($this->checkIf("address-details") && isset($_GET['param2']) && $_GET['param2'] == "bad-address")
					return true;

				break;
			}
			case 'bad-information-form':
			{
				if($_GET['page'] == 'subscribe' && isset($_GET['param1']) && $_GET['param1'] == "bad-form")
					return true;

				break;
			}
			case 'purchase-complete':
			{
				if(!empty($_SESSION['subscription']['purchase-success']))
					return true;

				break;
			}
			case 'bad-setting-form':
			{
				if($_GET['page'] == 'settings' && isset($_GET['param1']) && $_GET['param1'] == "bad-form")
					return true;
				break;
			}
			case 'bad-setting-email':
			{
				if($_GET['page'] == 'settings' && isset($_GET['param1']) && $_GET['param1'] == "bad-email")
					return true;
				break;
			}
			case 'used-setting-email':
			{
				if($_GET['page'] == 'settings' && isset($_GET['param1']) && $_GET['param1'] == "used-email")
					return true;
				break;
			}
			case 'bad-setting-phone':
			{
				if($_GET['page'] == 'settings' && isset($_GET['param1']) && $_GET['param1'] == "bad-phone")
					return true;
				break;
			}
			case 'bad-email':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "bad-email")
					return true;
				break;
			}
			case 'check-email':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "check-email")
					return true;
				break;
			}
			case 'bad-password':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "bad-password")
					return true;
				break;
			}
			case 'subscription-end':
			{
				if(!empty($_SESSION['subscription_end']))
					return true;
				break;
			}
			case 'bad-update-form':
			{
				if($_GET['page'] == 'subscription' && isset($_GET['param1']) && $_GET['param1'] == "bad-form")
					return true;
				break;
			}
			case 'bad-update-distance':
			{
				if($_GET['page'] == 'subscription' && isset($_GET['param1']) && $_GET['param1'] == "bad-distance")
					return true;
				break;
			}
			case 'bad-update-address':
			{
				if($_GET['page'] == 'subscription' && isset($_GET['param1']) && $_GET['param1'] == "bad-address")
					return true;
				break;
			}
			case 'bad-update-error':
			{
				if($_GET['page'] == 'subscription' && isset($_GET['param1']) && $_GET['param1'] == "error")
					return true;
				break;
			}
			case 'update-success':
			{
				if($_GET['page'] == 'subscription' && isset($_GET['param1']) && $_GET['param1'] == "success")
					return true;
				break;
			}
			case 'bad-update-password-match':
			{
				if($_GET['page'] == 'password' && isset($_GET['param1']) && $_GET['param1'] == "bad-password-match")
					return true;
				break;
			}
			case 'invalid-email':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "invalid-email")
					return true;
				break;
			}
			case 'used-email':
			{
				if(isset($_GET['param1']) && $_GET['param1'] == "used-email")
					return true;
				break;
			}
			case 'bad-payment':
			{
				if(isset($_GET['param2']) && $_GET['param2'] == "bad-payment")
					return true;
				break;
			}
			case 'past-due':
			{
				if($_SESSION['pastdue'] == 1)
					return true;
				break;
			}
			case 'is-desktop':
			{
				$mobileDetect = new Mobile_Detect;
				if($mobileDetect->isMobile() != 1)
					return true;
				break;
			}
			case 'reset-code':
			{
				if($_GET['page'] == "forgot" && isset($_GET['param1']) && $_GET['param1'] == "reset" && isset($_GET['param2']) && strlen($_GET['param2']) > 10 && $this->validateKey($_GET['param2']))
					return true;
				break;
			}
			case 'pass-creds':
			{
				if((isset($_GET['param3']) && $_GET['param3'] == "pass-creds") || (isset($_GET['param2']) && $_GET['param2'] == "pass-creds") || (isset($_GET['param1']) && $_GET['param1'] == "pass-creds"))
					return true;

				break;
			}
			case 'bad-key':
			{
				if(isset($_GET['param3']) && $_GET['param3'] == "bad-key")
					return true;

				break;
			}
			case 'bad-pass-match':
			{
				if(isset($_GET['param3']) && $_GET['param3'] == "bad-pass")
					return true;

				break;
			}
			case 'success':
			{
				if((isset($_GET['param1']) && $_GET['param1'] == "success") || (isset($_GET['param2']) && $_GET['param2'] == "success"))
					return true;
				break;
			}
		}

		return false;
	}

	public function loadPayments($id)
	{
		$finalString = "";
		$query = $this->dbConn->prepare("SELECT id, date, amount, card_last4 FROM transactions WHERE user_id = :id ORDER BY date DESC");
		$query->execute(array(
				':id' => $id,
			));

		if($query->rowCount() == 0)
			return "";

		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		$i = 0;
		foreach($results as $key => $value)
		{
			if($i == 0) {
				$finalString .= "<tr class='table-0'>";
				$i = 1;
			}
			else if($i == 1) {
				$finalString .= "<tr class='table-1'>";
				$i = 0;
			}
			$finalString .= "<td>" . date("n/j/o", strtotime($value['date'])) . "</td>";
			$finalString .= "<td>" . $value['id'] . "</td>";
			$finalString .= "<td>$" . $value['amount'] . "</td>";
			$finalString .= "<td>*****" . $value['card_last4'] . "</td>";
			$finalString .= "</tr>";
		}

		return $finalString;
	}

	public function updateSettings($userSettings)
	{
		$newPassword = $this->encryptPassword($_POST['email'], $_POST['curpass']);
		$query = $this->dbConn->prepare("UPDATE accounts SET firstname = :firstname, lastname = :lastname, email = :email, phonenumber = :phonenumber, password = :password WHERE id = :id");
		$query->execute(array(
				':firstname' => $userSettings['firstname'],
				':lastname' => $userSettings['lastname'],
				':email' => $userSettings['email'],
				':phonenumber' => $userSettings['phonenumber'],
				':password' => $newPassword,
				':id' => $_SESSION['id']
			));

		if($query->rowCount() == 1)
			return 1;

		return 0;
	}

	public function updatePassword($id, $newPassword)
	{
		$getEmail = $this->dbConn->prepare("SELECT email FROM accounts WHERE id = :id");
		$getEmail->execute(array(
				':id' => $id
			));
		$result = $getEmail->fetch(PDO::FETCH_ASSOC);
		$encryptedPassword = $this->encryptPassword($result['email'], $newPassword);
		$query = $this->dbConn->prepare("UPDATE accounts SET password = :pass WHERE id = :id");
		$query->execute(array(
				':pass' => $encryptedPassword,
				':id' => $id
			));

		if($query->rowCount() == 1)
			return 1;

		return 0;
	}

	/* SUBSCRIPTION FUNCTIONS */

	public function getDistance($address, $city, $state, $zipcode)
	{
		$destination = str_replace(" ", "+", $address) . "+" . $city . "+" . $state . "+" . $zipcode;
		$url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . 
				$this->siteInfo['originaddress'] . 
				"&destinations=" . 
				$destination . 
				"&units=imperial&key="; // URL for Google Maps API

		$distanceRaw = file_get_contents($url); // Retrieve API information
		$distanceInfo = json_decode($distanceRaw); // Decode the information

		// 1609.36 = meters per mile, if valid response, return miles, otherwise return -1
		$distance = (isset($distanceInfo->rows[0]->elements[0]->distance->value) ? $distanceInfo->rows[0]->elements[0]->distance->value/1609.34 : -1);

		return number_format($distance, 2);
	}

	public function addAddress($id, $formData)
	{
		// Verify form is filled out
		if(!isset($formData['complex-name']) || !isset($formData['address-1']) || !isset($formData['zip-code']))
			return 2;

		// We are only doing Tallahassee as of right now
		$city = "Tallahassee";
		$state = "FL";

		// Update database information
		$updateAddress = $this->dbConn->prepare("UPDATE accounts SET complex_name = :complex_name, address_1 = :address_1, address_2 = :address_2, building_num = :building_num, gate_code = :gate_code, city = :city, state = :state, zip_code = :zip_code WHERE id = :id");
		$updateAddress->execute(array(
				':complex_name' => $formData['complex-name'],
				':address_1' => $formData['address-1'],
				':address_2' => $formData['address-2'],
				':building_num' => $formData['building-num'],
				':gate_code' => $formData['gate-code'],
				':city' => $city,
				':state' => $state,
				':zip_code' => $formData['zip-code'],
				':id' => $id,
			));

		// Check if more than 3 miles away or invalid address
		$distance = $this->getDistance($formData['address-1'], $city, $state, $formData['zip-code']);
		if($distance > 3) {
			return 3;
		} elseif($distance == -1) {
			return 4;
		}

		if($updateAddress->rowcount() == 1)
			return 1;

		return 0;
	}

	public function addUserInformation($id, $formData)
	{
		// Verify form is filled out
		if(!isset($formData['firstname']) || !isset($formData['lastname']) || !isset($formData['phone-number']) || !isset($formData['apartment-style']) || !isset($formData['pickupTime1']) || !isset($formData['pickupTime2']) || sizeof($formData['pickupDays']) != 2 || $formData['pickupDays'][0] > 5 || $formData['pickupDays'][0] < 1 || $formData['pickupDays'][1] > 5 || $formData['pickupDays'][1] < 1 || $formData['pickupTime1'] > 8 || $formData['pickupTime1'] < 0 || $formData['pickupTime2'] > 8 || $formData['pickupTime2'] < 0 || $formData['apartment-style'] < 1 || $formData['apartment-style'] > 4)
			return 2;

		// Update database information
		$updateAddress = $this->dbConn->prepare("UPDATE accounts SET firstname = :firstname, lastname = :lastname,
												apartment_rooms = :apartment_rooms, phonenumber = :phone_number,
												pickup_day1 = :pickupday1, pickup_day2 = :pickupday2, timeslot_day1 = :timeslotday1,
												timeslot_day2 = :timeslotday2 WHERE id = :id");
		$updateAddress->execute(array(
				':firstname' => $formData['firstname'],
				':lastname' => $formData['lastname'],
				':apartment_rooms' => $formData['apartment-style'],
				':phone_number' => $formData['phone-number'],
				':pickupday1' => $formData['pickupDays'][0],
				':pickupday2' => $formData['pickupDays'][1],
				':timeslotday1' => $formData['pickupTime1'],
				':timeslotday2' => $formData['pickupTime2'],
				':id' => $id,
			));

		if($updateAddress->rowcount() == 1)
			return 1;

		return 0;
	}

	public function updateUserInformation($id, $formData)
	{
		// Verify form is filled out
		if(!isset($formData['pickupTime1']) || !isset($formData['pickupTime2']) || sizeof($formData['pickupDays']) != 2) {
			return 2;
		}

		// Check valid address and within 3 miles of FSU
		$distance = $this->getDistance($formData['address-1'], $_SESSION['city'], $_SESSION['state'], $formData['zip-code']);
		if($distance > 3) {
			return 3;
		} elseif($distance == -1) {
			return 4;
		}

		// Update database information
		$updateAddress = $this->dbConn->prepare("UPDATE accounts SET complex_name = :complex, address_1 = :address1, address_2 = :address2, building_num = :buildingnum, gate_code = :gatecode, zip_code = :zip, pickup_day1 = :pickupday1, pickup_day2 = :pickupday2, timeslot_day1 = :timeslotday1, timeslot_day2 = :timeslotday2 WHERE id = :id");
		$updateAddress->execute(array(
				':complex' => $formData['complex-name'],
				':address1' => $formData['address-1'],
				':address2' => $formData['address-2'],
				':buildingnum' => $formData['building-num'],
				':gatecode' => $formData['gate-code'],
				':zip' => $formData['zip-code'],
				':pickupday1' => $formData['pickupDays'][0],
				':pickupday2' => $formData['pickupDays'][1],
				':timeslotday1' => $formData['pickupTime1'],
				':timeslotday2' => $formData['pickupTime2'],
				':id' => $id,
			));

		if($updateAddress->rowcount() == 1)
			return 1;

		return 0;
	}

	public function updateSubscriptionStatus($subscribed)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET activesubscription = :subscribed WHERE id = :id");
		$query->execute(array(
				':subscribed' => $subscribed,
				':id' => $_SESSION['id']
			));

		if($query->rowCount() == 1)
			return 1;

		return 0;
	}

	public function cancelSubscription($id)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET subscriptionid = NULL, subscription_start = NULL, subscription_end = NULL, billingday = NULL, card_last4 = '', subscription_amount = 0, activesubscription = 0, pastdue = 0 WHERE id = :id");
		$query->execute(array(
				':id' => $id
			));

		if($query->rowCount() == 1)
			return 1;

		return 0;
	}

	public function updateBillingDay($day)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET billingDay = :billingDay WHERE id = :id");
		$query->execute(array(
				':billingDay' => $day,
				':id' => $_SESSION['id']
			));

		return 1;
	}

	public function updateLast4($last4)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET card_last4 = :last4 WHERE id = :id");
		$query->execute(array(
				':last4' => $last4,
				':id' => $_SESSION['id']
			));

		return 1;
	}

	public function updateSubscriptionAmount($id, $amount)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET subscription_amount = :amount WHERE id = :id");
		$query->execute(array(
				':amount' => $amount,
				':id' => $id
			));

		return 1;
	}

	public function updateSubscriptionID($subscriptionID)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET subscriptionid = :subid WHERE id = :id");
		$query->execute(array(
				':subid' => $subscriptionID,
				':id' => $_SESSION['id']
			));

		return 1;
	}

	public function updateSubscriptionDate()
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET subscription_start = NOW() WHERE id = :id");
		$query->execute(array(
				':id' => $_SESSION['id']
			));

		return 1;
	}

	public function updateLastDay($lastDay)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET subscription_end = :lastDay WHERE id = :id");
		$query->execute(array(
				':lastDay' => $lastDay,
				':id' => $_SESSION['id']
			));

		return 1;
	}

	public function updatePastDue($id, $pastdue)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET pastdue = :pastdue WHERE id = :id");
		$query->execute(array(
				':lastDay' => $pastdue,
				':id' => $id
			));

		return 1;
	}

	public function resetWeekPickups($id)
	{
		$query = $this->dbConn->prepare("UPDATE accounts SET curweek_pickups = 0 WHERE id = :id");
		$query->execute(array(
				':id' => $id
			));

		return 1;
	}

	public function createTransaction($userID, $transactionID, $amount, $date, $last4)
	{
		// Add transaction to database
		$query = $this->dbConn->prepare("INSERT INTO transactions VALUES (:transactionID, :amount, :last4, :userID, :transDate)");
		$query->execute(array(
				':transactionID' => $transactionID,
				':amount' => $amount,
				':last4' => $last4,
				':userID' => $userID,
				':transDate' => $date
			));

		if($query)
			return 1;

		return 0;
	}

	public function getNextPickup()
	{
		$day1 = $_SESSION['pickup_day1'];
		$day2 = $_SESSION['pickup_day2'];
		// Numerical representation of current day (Sun - Saturday)
		$today = date("N");

		if($_SESSION['curweek_pickups'] == 2 && !$this->userOnPickupList($_SESSION['id']))
		{
			// Next pickup wil be next week
			return date("l, F jS, Y", strtotime("next " . $this->getDay($day1))) . " at " . $this->getTime($_SESSION['timeslot_day1']);
		}

		// If day 1 = today
		if($day1 == $today)
		{
			$subscription_start = strtotime($_SESSION['subscription_start']);
			 // If day 1 = day of subscription signup and pickups were already processed, next pickup will be day2
			if(date("n/d/Y") == date("n/d/Y", $subscription_start) && $this->siteInfo['lastpickupdate'] == date("Y-m-d") && !$this->userOnPickupList($_SESSION['id'])) {
				return date("l, F jS", strtotime($this->getDay($day2))) . " at " . $this->getTime($_SESSION['timeslot_day2']);
			} else {
				// If they are on the list for today, or the script hasnt run yet today, their next pickup is today, otherwise its day 2
				if($this->userOnPickupList($_SESSION['id']) == 1) {
					return date("l, F jS") . " at " . $this->getTime($this->getUserPickupTimeslot($_SESSION['id']));
				} elseif($this->siteInfo['lastpickupdate'] != date("Y-m-d")) {
					return date("l, F jS") . " at " . $this->getTime($_SESSION['timeslot_day1']);
				}
				else
					return date("l, F jS", strtotime($this->getDay($day2))) . " at " . $this->getTime($_SESSION['timeslot_day2']);
			}
		}
		elseif($day2 == $today)
		{
			$subscription_start = strtotime($_SESSION['subscription_start']);
			if(date("n/d/Y") == date("n/d/Y", $subscription_start)) { // If day 1 = day of subscription signup, next pickup will be day1 next week
				return date("l, F jS", strtotime("next " . $this->getDay($day1))) . " at " . $this->getTime($_SESSION['timeslot_day1']);
			} else {
				// Check if they are on list of people to be picked up for today, if so next pickup is today
				if($this->userOnPickupList($_SESSION['id']) == 1) {
					return date("l, F jS") . " at " . $this->getTime($this->getUserPickupTimeslot($_SESSION['id']));
				} elseif($this->siteInfo['lastpickupdate'] != date("Y-m-d")) {
					return date("l, F jS") . " at " . $this->getTime($_SESSION['timeslot_day2']);
				}
				else
					return date("l, F jS", strtotime("next " . $this->getDay($day1))) . " at " . $this->getTime($_SESSION['timeslot_day1']);
			}
		} else { // If neither of those days are today
			// If user is still on list to have garbage be picked up today
			if($this->userOnPickupList($_SESSION['id']))
			{
				return date("l, F jS") . " at " . $this->getTime($this->getUserPickupTimeslot($_SESSION['id']));
			}
			elseif($day1 > $today) // if pickup day 1 hasnt come yet
			{
				return date("l, F jS", strtotime($this->getDay($day1))) . " at " . $this->getTime($_SESSION['timeslot_day1']);
			}
			elseif($day2 > $today) // if pickup day 2 hasnt come yet
			{
				return date("l, F jS", strtotime($this->getDay($day2))) . " at " . $this->getTime($_SESSION['timeslot_day2']);
			}
			else { // If the next pickup day is next week, return day 1 of next week
				return date("l, F jS", strtotime("next " . $this->getDay($day1))) . " at " . $this->getTime($_SESSION['timeslot_day1']);
			}
		}
	}

	public function getNextPaymentDate()
	{
		$date = "";
		if(date("j") >= $_SESSION['billingday'] && empty($_SESSION['subscription_end'])) // If current day has passed billing day, calculate the following month
			$date = date("F", strtotime("+1 month")) . " " . $_SESSION['billingday'];
		elseif(!empty($_SESSION['subscription_end']))
			$date = "None";
		else // This month is next billing day
			$date = date("F") . " " . $_SESSION['billingday'];

		return date("F jS, Y", strtotime($date));
	}

	public function userOnPickupList($id)
	{
		$query = $this->dbConn->prepare("SELECT id FROM day_pickups WHERE user_id = :id AND date = :userDate AND complete = 0");
		$query->execute(array(
				':id' => $id,
				':userDate' => date("Y-m-d")
			));

		return $query->rowCount();
	}

	public function getUserPickupTimeslot($id)
	{
		$query = $this->dbConn->prepare("SELECT timeslot FROM day_pickups WHERE user_id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		return $result['timeslot'];
	}

	/* GENERAL FUNCTIONS */

	public function loadServerData() {
		$fetchServerData = $this->dbConn->prepare("SELECT * FROM serverinfo WHERE id = 1");
		$fetchServerData->execute();
		$serverData = $fetchServerData->fetch(PDO::FETCH_ASSOC);

		$this->siteInfo['sitetitle'] = $serverData['title'];
		$this->siteInfo['siteurl'] = $serverData['siteurl'];
		$this->siteInfo['mapkey'] = $serverData['mapkey'];
		$this->siteInfo['originaddress'] = $serverData['originaddress'];
		$this->siteInfo['lastpickupdate'] = $serverData['pickup_updated'];
	}

	public function loadPage($pageName, $usedPage = "") {
		if(!file_exists($pageName))
			return 0;

		$templatePage = file_get_contents('pages/template.html');
		$requestedPage = file_get_contents($pageName);
		if($requestedPage) {
			echo $this->fillTemplate($templatePage, $requestedPage);
		}

		return 1;
	}

	public function fillTemplate($template, $requestedPage) {
		// Retrieve everything in between {head}{/head} & {content}{/content}
		$requestedHead = preg_match("/{head}(.*?){\/head}/s", $requestedPage, $headMatches);
		$requestedContent = preg_match("/{content}(.*?){\/content}/s", $requestedPage, $contentMatches);

		// Delete {head} & {/head} & {content} & {/content} from the tags
		$headMatches[0] = str_replace("{head}", "", $headMatches[0]);
		$headMatches[0] = str_replace("{/head}", "", $headMatches[0]);
		$contentMatches[0] = str_replace("{content}", "", $contentMatches[0]);
		$contentMatches[0] = str_replace("{/content}", "", $contentMatches[0]);

		// Replace [content] tag with everything between {content} & {/content}
		$pageData = str_replace("[head]", $headMatches[0], $template); // Replace the "[head]" tag with the content from the page
		$pageData = str_replace("[content]", $contentMatches[0], $pageData); // Replace the "[content]" tag with the content from the page

		$pageData = $this->loadNav($pageData);
		$pageData = $this->inputSiteInfo($pageData);
		$pageData = $this->loadIfStatements($pageData);
		$pageData = $this->loadGet($pageData);

		return $pageData;
	}

	public function loadNav($pageData) {
		$navContent = (file_exists("pages/nav/nav.html") ? file_get_contents("pages/nav/nav.html") : "");
		$pageData = str_replace("[nav]", $navContent, $pageData);

		return $pageData;
	}

	public function inputSiteInfo($pageData) {
		$pageData = str_replace("[siteurl]", $this->siteInfo['siteurl'], $pageData);
		$pageData = str_replace("[sitetitle]", $this->siteInfo['sitetitle'], $pageData);

		return $pageData;
	}

	public function loadGet($pageData)
	{
		preg_match_all("/{{get:(.*?)}}/s", $pageData, $query);
		for($i = 0;$i < sizeof($query[0]);$i++) // Go through all gets: 0 = whole statement, 1 = if statement, 2 = content
		{
			if(isset($_GET[$query[1][$i]])) // Checking if the get variable exists
			{
				$pageData = str_replace("{{get:" . $query[1][$i] . "}}", $_GET[$query[1][$i]], $pageData);
			}
		}

		return $pageData;
	}

	public function checkValidEmail($email) {
		$mailgun = new Mailgun\Mailgun('pubkey-b65ff340ac7103bed80f2354f31877af');
		$result = $mailgun->get("address/validate", array('address' => $email));
		return $result->http_response_body->is_valid;
	}

	public function getSiteURL() {
		return $this->siteInfo['siteurl'];
	}

	public function getDay($day)
	{
		switch($day)
		{
			case 1: return "Monday";
			case 2: return "Tuesday";
			case 3: return "Wednesday";
			case 4: return "Thursday";
			case 5: return "Friday";
		}
	}

	public function getTime($time)
	{
		switch($time)
		{
			case 0: return "11:00 AM - 12:00 PM";
			case 1: return "12:00 PM - 1:00 PM";
			case 2: return "1:00 PM - 2:00 PM";
			case 3: return "2:00 PM - 3:00 PM";
			case 4: return "3:00 PM - 4:00 PM";
			case 5: return "4:00 PM - 5:00 PM";
			case 6: return "5:00 PM - 6:00 PM";
			case 7: return "6:00 PM - 7:00 PM";
			case 8: return "10:00 AM - 9:00 PM";
		}
	}

	public function newVerificationKey($email)
	{
		$getID = $this->dbConn->prepare("SELECT id FROM accounts WHERE email = :email");
		$getID->execute(array(
				':email' => $email
			));
		$result = $getID->fetch(PDO::FETCH_ASSOC);


		$verificationKey = uniqid("", true);
		$query = $this->dbConn->prepare("INSERT INTO verification_keys VALUES (null, :key, :user, NOW(), 1)");
		$query->execute(array(
				':key' => $verificationKey,
				':user' => $result['id']
			));

		if($verificationKey)
			return $verificationKey;

		return 0;
	}

	public function validateKey($userKey)
	{
		$query = $this->dbConn->prepare("SELECT id FROM verification_keys WHERE ver_key = :userKey AND active = 1");
		$query->execute(array(
				':userKey' => $userKey
			));

		if($query->rowCount() == 1)
			return 1;

		return 0;
	}

	public function verifyPasswordCreds($password)
	{
		if(preg_match("/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/", $password) && strlen($password) >= 7)
			return true;

		return false;
	}
}
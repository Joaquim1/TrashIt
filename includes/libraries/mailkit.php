<?php

class mailkit {
	private $mailgun, $domain, $dbConn, $siteInfo;

	public function __construct($_mail, $_domain, $db)
	{
		$this->mailgun = $_mail;
		$this->domain = $_domain;
		$this->dbConn = $db;
		$this->loadServerData();
	}

	public function loadServerData() {
		$fetchServerData = $this->dbConn->prepare("SELECT * FROM serverinfo WHERE id = 1");
		$fetchServerData->execute();
		$serverData = $fetchServerData->fetch(PDO::FETCH_ASSOC);

		$this->siteInfo['sitetitle'] = $serverData['title'];
		$this->siteInfo['siteurl'] = $serverData['siteurl'];
		$this->siteInfo['mapkey'] = $serverData['mapkey'];
		$this->siteInfo['originaddress'] = $serverData['originaddress'];
	}

	public function pastDueAccount($id)
	{
		// Get user information
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email FROM accounts WHERE id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		// Format email
		$email = '
		<html>
			<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:30px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px">
					<p>Dear ' . $result['firstname'] . ',<br /><br />We tried to bill the credit card you provided, but it did not go through. The status of your account has been changed to <b>past due</b>. Your trash will continue to be picked up and we will try to charge your card again tomorrow, but after 2 tries we will cancel your subscription.<br /><br />
					<center><a href="' . $this->siteInfo['siteurl'] . '/portal/subscription" style="color:#fff;text-decoration:none"><div style="background: #346856;width: 200px;text-align:center;padding:13px;border-radius: 4px">Update Payment Method</div></p></a></center><br />
					Thank you,<br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Account Past Due',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}

	public function remoteCanceledAccount($id)
	{
		// Get user information
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email FROM accounts WHERE id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		// Format email
		$email = '
		<html>
			<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:30px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px">
					<p>Dear ' . $result['firstname'] . ',<br /><br />Your subscription has been canceled because the credit card you provided failed to go through after 3 attempts.<br /><br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Subscription Canceled',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}

	public function userCanceledAccount($id)
	{
		// Get user information
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email FROM accounts WHERE id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		// Format email
		$email = '
		<html>
			<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:30px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px">
					<p>Dear ' . $result['firstname'] . ',<br /><br />Your subscription has been successfully canceled.<br />If you would like to re-subscribe for our service, just head over to the <a href="' . $this->siteInfo['siteurl'] . '/portal">portal</a> and re-signup.<br /><br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Subscription Successfully Canceled',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}

	public function newAccount($id)
	{
		// Get user information
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email FROM accounts WHERE id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		$email = '
		<html>
			<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:30px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px">
					<p>Dear ' . $result['firstname'] . ',<br /><br />
					Welcome to Trash It! Check out our website to see how we can make your trash disposal easier!<br /><br />
					To sign up for a subscription, head over to the <a href="' . $this->siteInfo['siteurl'] . '/portal">user portal</a> and follow through the sign up process.<br /><br />
					Login with your email:<b> ' . $result['email'] . '</b><br /><br />
					Thank you,<br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Welcome to Trash It',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}

	public function newSubscription($id, $ordernumber = "0")
	{
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email, subscription_amount, apartment_rooms FROM accounts WHERE id = :id");
		$query->execute(array(
				':id' => $id,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		$email = '
		<html>
	  		<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:40px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px;font-family:arial">
					<p>Dear ' . $result['firstname'] . ',<br /><br />
					Thank you for subscribing to Trash It. We are sure you will love our service.
          			<br /><br />
          			Here are a few tips to ensure you get the most out of your subscription:
	          		<ul>
			            <li>Make sure all your trash is in a trash bag or it will not be picked up</li>
			            <li>Ensure your trash is outside during your timeslot</li>
			            <li>If you need to change your address or pickup days, just head to the <a href="' . $this->siteInfo['siteurl'] . '/portal/subscription">My Subscription</a> page and click "Update Pickup Info"</li>
			            <li>To update your credit card information, head to the <a href="' . $this->siteInfo['siteurl'] . '/portal/subscription">My Subscription</a> and click "Update Credit Card"</li>
	          		</ul>
					Here is your receipt:<br /><br />
					<div style="background: #fff;border: 1px solid #9C9C9C;padding:9px">
						<table border="0" cellspacing="5" style="width:100%;">
							<tr><td>Order Number</td><td>Date</td><td>Product</td><td>Price</td></tr>
							<tr><td>' . $ordernumber . '</td><td>' . date("m/d/Y") . '</td><td>' . $result['apartment_rooms'] . 'x' . $result['apartment_rooms'] . ' Apartment</td><td>$' . $result['subscription_amount'] . '</td></tr>
						</table>
					</div>
          			<br /><br />
					Thank you,<br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Subscription Confirmation',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}

	public function forgotPassword($userEmail, $key)
	{
		$query = $this->dbConn->prepare("SELECT firstname, lastname, email FROM accounts WHERE email = :email");
		$query->execute(array(
				':email' => $userEmail,
			));
		$result = $query->fetch(PDO::FETCH_ASSOC);

		$email = '
		<html>
	      	<body style="background: #f2f2f2;font-family:Arial">
				<div style="width: 100%;height: 70px;background: #303030;color: #fff;font-family: Arial;margin-bottom:40px;font-size:20px">
					<div style="margin-left: 3%;float:left">
						<img style="width:153px;height:50px;margin-top:10px" src="' . $this->siteInfo['siteurl'] . '/images/home/logo-white.png" />
					</div>
					<div style="float: right;margin-right: 10%;margin-top:22px">
						' . $result['firstname'] . ' ' . $result['lastname'] . '
					</div>
				</div>
				<div style="margin: auto;width: auto;height: auto;background: #f2f2f2;padding:20px;font-size:17px;font-family:arial">
					<p>Dear ' . $result['firstname'] . ',<br />
					<div>
	            		We have received a request to reset your password.
	            		To reset your password, please press the button below.<br />
	            		<a href="' . $this->siteInfo['siteurl'] . '/forgot/reset/' . $key . '" style="color: #fff;text-decoration:none"><div style="background:#002d47;width:124px;color:#fff;padding:10px 40px;margin: 10px auto;border-radius:3px">Reset Password</div></a>
	            		<br />
	            		If you did not make this request, please disregard this email.
					</div>
          			<br /><br />
					Thank you,<br />Trash It Team
				</div>
			</body>
		</html>';

		// Atempt to send email
		try {
			$result = $this->mailgun->sendMessage($this->domain,
			          array('from'    => 'Trash It <info@mg.trashit.us>',
			                'to'      => $result['firstname'] . ' ' . $result['lastname'] . ' <' . $result['email'] . '>',
			                'subject' => 'Reset Password',
			                'html'    => $email));
		} catch(Exception $e) {
			return 0;
		}
	}
}
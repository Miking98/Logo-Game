<?php
	require_once("model.php");

	class contactModel extends Model {

		public function __construct(mysqli $mysqli) {
			$this->mysqli = $mysqli;
		}

		public function sendEmail($email, $message) {
			$error = false;
			$errorEmptyEmail = false;
			$errorEmptyMessage = false;

			//Sanitize input
			$email = preg_replace("/[\r\n]/", "", $email);
			$message = preg_replace("/[\r\n]/", "", $message);
			if ($email=='') {
				$errorEmptyEmail = true;
			}
			if ($message=='') {
				$errorEmptyMessage = true;
			}

			$serverTime = date("F j, Y, g:i a");

			$error = $errorEmptyEmail||$errorEmptyMessage;

			if (!$error) {
				//Construct Email
				$db_email = "mwornow98@gmail.com";
				$header = "From: Contact Us Submission <$db_email>\r\n";
				$header.= "MIME-Version: 1.0\r\n";
				$header.= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				$message = "<strong>Logo Game Contact Us Submission Received</strong><br><br>".
							"Sent at:".
								"<ul>".
									"<li>Server time: ".preventxss($serverTime)."</li>".
								"</ul><br><br>".
							"<strong>Email:</strong> ".preventxss($email)."<br><br>".
							"<strong>Message:</strong> ".preventxss($message);
				//Send Email
				mail($db_email, "Contact Us Form Submission", $message, $header);
			}

			$messages = array();
			if (!$error) {
				$messages[] = $this->createSuccessMessage("Successfully sent Contact Us message.");
			}
			if ($errorEmptyEmail) {
				$messages[] = $this->createDangerMessage("Invalid email.");
			}
			if ($errorEmptyMessage) {
				$messages[] = $this->createDangerMessage("Invalid message.");
			}
			return array("success" => !$error, "messages" => $messages);
		}
	}

?>
<?php

	class usersView {
		private $usersModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel) {
			$this->usersModel = $usersModel;
		}
		
		//Return createaccount.html
		public function render_createAccount($messages = NULL) {
			$this->additionalScripts = 	[	'https://cdnjs.cloudflare.com/ajax/libs/1000hz-bootstrap-validator/0.11.5/validator.min.js',
											'/scripts/login.js'
										];

			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/createaccount.html";
			include "templates/bottom.html";
		}
		public function render_editAccount($messages = NULL) {
			$this->additionalScripts = 	[	'https://cdnjs.cloudflare.com/ajax/libs/1000hz-bootstrap-validator/0.11.5/validator.min.js',
											'/scripts/editaccount.js'
										];

			$userID = $this->usersModel->getUserID();
			$userName = $this->usersModel->getUserName();
			$userEmail = $this->usersModel->getUserEmail();
			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/editaccount.html";
			include "templates/bottom.html";
		}
		public function render_login($messages = NULL) {
			$this->additionalScripts = 	[	'https://cdnjs.cloudflare.com/ajax/libs/1000hz-bootstrap-validator/0.11.5/validator.min.js',
											'/scripts/login.js'
										];

			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/login.html";
			include "templates/bottom.html";
		}
		public function render_logout($messages = NULL) {
			$this->additionalScripts = 	[	
										];

			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/logout.html";
			include "templates/bottom.html";
		}
	}

?>
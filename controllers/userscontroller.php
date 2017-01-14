<?php

	class usersController {
		private $usersModel;
		private $usersView;

		//Constructor
		public function __construct(usersModel $usersModel, usersView $usersView) {
			$this->usersModel = $usersModel;
			$this->usersView = $usersView;
		}
		
		public function create($username, $email, $password) {
			$results = $this->usersModel->add($username, $email, $password);
			if ($results["success"]) {
				$this->login($email, $password);
			}
			else {
				$this->usersView->render_createAccount($results["messages"]);
			}
		}
		public function edit($userID, $username, $email, $password) {
			$results = $this->usersModel->edit($userID, $username, $email, $password);
			if ($results["success"]) {
				if ($password=="") {
					$this->usersView->render_editAccount($results["messages"]);
				}
				else {
					$this->login($email, $password, 0);
				}
			}
			else {
				$this->usersView->render_editAccount($results["messages"]);
			}
		}

		public function login($emailorusername, $password, $remember) {
			$results = $this->usersModel->login($emailorusername, $password, $remember);
			if ($results["success"]) {
				header("Location: /index.php");
			}
			else {
				$this->usersView->render_login($results["messages"]);
			}
		}

		public function logout() {
			$results = $this->usersModel->logout();
            if ($results["success"]) {
               header("Location: /index.php");
            }
            else {
                $this->usersView->render_logout($results["messages"]);
            }
		}
		
	}
?>
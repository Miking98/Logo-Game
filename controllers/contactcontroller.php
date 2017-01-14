<?php

	class contactController {
		private $usersModel;
		private $contactModel;
		private $contactView;

		function __construct(usersModel $usersModel, contactModel $contactModel, contactView $contactView) {
			$this->usersModel = $usersModel;
			$this->contactModel = $contactModel;
			$this->contactView = $contactView;
		}

		function submit($email, $message) {
			$results = $this->contactModel->sendEmail($email, $message);
			$this->contactView->render_view($results["messages"]);
		}
	}
	
?>
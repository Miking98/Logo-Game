<?php

	class statsController {
		private $usersModel;
		private $statsModel;

		function __construct(usersModel $usersModel, statsModel $statsModel) {
			$this->usersModel = $usersModel;
			$this->statsModel = $statsModel;
		}

	}

?>
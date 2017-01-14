<?php

	class indexController {
		private $usersModel;

		function __construct(usersModel $usersModel) {
			$this->usersModel = $usersModel;
		}

	}

?>
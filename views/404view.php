<?php

	class _404View {
		private $usersModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel) {
			$this->usersModel = $usersModel;
		}

		public function render() {
			$this->additionalScripts = 	[
										];
			include "templates/top.html";
			include "templates/404.html";
			include "templates/bottom.html";
		}
	}

?>
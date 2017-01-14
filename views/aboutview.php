<?php

	class aboutView {
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
			include "templates/about.html";
			include "templates/bottom.html";
		}
	}

?>
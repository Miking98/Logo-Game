<?php

	class indexView {
		private $usersModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel) {
			$this->usersModel = $usersModel;
		}
    	
		public function render() {
			$this->additionalScripts = 	[	'scripts/game.js'
										];
			include "templates/top.html";
			include "templates/index.html";
			include "templates/bottom.html";
		}
	}

?>
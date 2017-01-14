<?php

	class contactView {
		private $usersModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel) {
			$this->usersModel = $usersModel;
		}
    	
		public function render_view($messages = NULL) {
			$this->additionalScripts = 	[	
										];
			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/contact.html";
			include "templates/bottom.html";
		}
	}

?>
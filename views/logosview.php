<?php

	class logosView {
		private $usersModel;
		private $logosModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel, logosModel $logosModel) {
			$this->usersModel = $usersModel;
			$this->logosModel = $logosModel;
		}
    	
		public function render_add($messages = NULL) {
			$this->additionalScripts = 	[	'https://cdnjs.cloudflare.com/ajax/libs/1000hz-bootstrap-validator/0.11.5/validator.min.js'
										];
			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/addlogo.html";
			include "templates/bottom.html";
		}

		public function render_edit($messages = NULL) {
			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/addlogo.html";
			include "templates/bottom.html";
		}

		public function render_view($messages = NULL) {
			$this->additionalScripts = 	[	'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
											'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',
											'/scripts/listlogos.js'
										];
			include "templates/top.html";
			if (!is_null($messages)) {
				include "templates/messages.html";
			}
			include "templates/listlogos.html";
			include "templates/bottom.html";
		}
	}

?>
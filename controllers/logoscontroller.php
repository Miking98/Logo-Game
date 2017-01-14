<?php

	class logosController {
		private $usersModel;
		private $logosModel;
		private $logosView;

		//Constructor
		public function __construct(usersModel $usersModel, logosModel $logosModel, logosView $logosView) {
			$this->usersModel = $usersModel;
			$this->logosModel = $logosModel;
			$this->logosView = $logosView;
		}
		
		public function add($title, $description, $image) {
			$results = $this->logosModel->add($title, $description, $image);
			$this->logosView->render_add($results["messages"]);
		}
		
		public function add_batch_csv($csv, $images) {
			$results = $this->logosModel->add_batch_csv($csv, $images);
			$this->logosView->render_add($results["messages"]);
		}
		
	}
?>
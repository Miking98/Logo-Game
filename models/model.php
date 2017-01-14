<?php
	
	class Model {
		protected $mysqli;
		protected $messages = 	[	"dbfailure" => array("type" => "danger", "messageHead" => "There was an error processing your request.", "messageBody" => "Please try again later.")
									];

		protected function createSuccessMessage($head, $body = "") {
			return $this->createMessage("success", $head, $body);
		}
		protected function createInfoMessage($head, $body = "") {
			return $this->createMessage("info", $head, $body);
		}
		protected function createWarningMessage($head, $body = "") {
			return $this->createMessage("warning", $head, $body);
		}
		protected function createDangerMessage($head, $body = "") {
			return $this->createMessage("danger", $head, $body);
		}
		protected function createMessage($type, $head, $body) {
			return array("type" => $type, "messageHead" => $head, "messageBody" => $body);
		}

		protected function rearrangeUploadedFilesArray(&$file_post) { //http://php.net/manual/en/features.file-upload.multiple.php#53240
			$file_ary = array();
			$file_count = count($file_post['name']);
			$file_keys = array_keys($file_post);

			for ($i=0; $i<$file_count; $i++) {
			    foreach ($file_keys as $key) {
			        $file_ary[$i][$key] = $file_post[$key][$i];
			    }
			}

			return $file_ary;
		}
	}

?>
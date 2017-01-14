<?php
	require_once("model.php");

	class logosModel extends Model {
		private $usersModel;
		private $imageAcceptableFormats = [ 'png', 'jpeg', 'jpg' ];

		public function __construct(mysqli $mysqli, usersModel $usersModel) {
			$this->mysqli = $mysqli;
			$this->usersModel = $usersModel;
		}

		public function add($title, $descriptionWikiTitle, $image) {
			//Validation
			$errorAdmin = !$this->usersModel->checkAdminStatus();
			$errorTitle = !$this->validTitle($title);
			$errorTitleAlreadyTaken = !$this->untakenTitle($title);
			$errordescriptionWikiTitle = !$this->validDescriptionWikiTitle($descriptionWikiTitle);
			$errorImage = !$this->validImage($image);
			$errorImageMove = false;
			$dbfailure = false;
			
			$error = $errorTitle||$errorTitleAlreadyTaken||$errordescriptionWikiTitle||$errorImage||$errorImageMove||$errorAdmin;
			if (!$error) { //Only Admins can upload new logos
				$imageName=$image["name"];
				$imageExtension=end(explode(".", $imageName));
				$newImageName=hash('sha256', uniqid(mt_rand(1, mt_getrandmax()), true)).".".$imageExtension;
				//Create logo
				if ($stmt = $this->mysqli->prepare("INSERT INTO logos (title, descriptionwikititle, location) VALUES (?,?,?)")) {
					$stmt->bind_param('sss', $title, $descriptionWikiTitle, $newImageName);
					$stmt->execute();

					//Move image to images/logos/ folder
					if (move_uploaded_file($image['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/images/logos/'.$newImageName)) {
					}
					else {
						$errorImageMove = true;
					}
				}
				else {
					$dbfailure = true;
				}
			}

			$error = $error||$dbfailure;
			$messages = array();
			if (!$error) {
				$messages[] = $this->createSuccessMessage("Successfully created Logo for ".$title);
			}
			if ($errorAdmin) {
				$messages[] = $this->createDangerMessage("Only Administrators are allowed to upload new Logos.");
			}
			if ($errorTitle) {
				$messages[] = $this->createDangerMessage("Invalid title.");
			}
			if ($errorTitleAlreadyTaken) {
				$messages[] = $this->createDangerMessage("Logo already created.", "A Logo with this Title was already created.");
			}
			if ($errordescriptionWikiTitle) {
				$messages[] = $this->createDangerMessage("Invalid Wikipedia description title.");
			}
			if ($errorImage) {
				$messages[] = $this->createDangerMessage("Invalid image.", "Must be less than 500KB in size. Supported types: ".implode(", ", $this->imageAcceptableFormats));
			}
			if ($errorImageMove) {
				$messages[] = $this->createDangerMessage("Error uploading image.", "Please try again later.");
			}
			if ($dbfailure) {
				$messages[] = $this->messages["dbfailure"];
			}
			return array("success" => !$error, "messages" => $messages);
		}

		public function add_batch_csv($csv, $images) {
			$errorAdmin = !$this->usersModel->checkAdminStatus();
			$dbfailure = false;
			
			$error = $errorAdmin;
			$errorRows = array(); //Indexes of rows in CSV file that can't be read
			$errorRowsImageUpload = array(); //Rows whose iamges can't be uploaded

			if (!$error) { //Only Admins can upload new logos
				$title = "";
				$descriptionWikiTitle = "";
				$newImageName = "";
				$csvFile = fopen($csv['tmp_name'], "r");
				$images = $this->rearrangeUploadedFilesArray($images);

				//Create logo
				if ($stmt = $this->mysqli->prepare("INSERT INTO logos (title, descriptionwikititle, location) VALUES (?,?,?) ON DUPLICATE KEY UPDATE descriptionwikititle=VALUES(descriptionwikititle), location=VALUES(location)")) {
					$stmt->bind_param('sss', $title, $descriptionWikiTitle, $newImageName);

					$rowIndex = 0;
					while ($data = fgetcsv($csvFile, 0, ",")) {
						$title = $data[0];
						$descriptionWikiTitle = $data[1];
						$imageName = !empty($data[2]) ? strtolower($data[2]) : strtolower(preg_replace('/\s/', '', $title)); //If image name isn't specified, default to lowercased version of logo title 
						//Find image corresponding to this row
						$image = null;
						for ($i = 0; $i<count($images); $i++) {
							if (strtolower(pathinfo($images[$i]['name'], PATHINFO_FILENAME))==$imageName) {
								$image = $images[$i];
								break;
							}
						}
						$newImageName=hash('sha256', uniqid(mt_rand(1, mt_getrandmax()), true)).".".pathinfo($image['name'], PATHINFO_EXTENSION);

						//Validation
						$errorTitle = !$this->validTitle($title);
						$errorDescriptionWikiTitle = !$this->validDescriptionWikiTitle($descriptionWikiTitle);
						$errorImage = !$this->validImage($image);
						$errorImageMove = false;
						$errorRow = $errorTitle||$errorDescriptionWikiTitle||$errorImage;

						if (!$errorRow) {
							$stmt->execute();
							//Move image to images/logos/ folder
							if (move_uploaded_file($image['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/images/logos/'.$newImageName)) {
							}
							else {
								$errorImageMove = true;
							}
						}

						if ($errorRow) {
							$errorRowsRead[] = $rowIndex+1;
						}
						if ($errorImageMove) {
							$errorRowsImageUpload[] = $rowIndex+1;
						}

						$rowIndex++;
					}
				}
				else {
					$dbfailure = true;
				}
				fclose($csvFile);
			}

			$error = $error||$dbfailure;
			$messages = array();
			if (!$error) {
				$messages[] = $this->createSuccessMessage("Successfully created Logos!");
			}
			if (!empty($errorRowsRead)) {
				$messages[] = $this->createDangerMessage("Error reading CSV Rows: #", implode(", ", $errorRowsRead));
			}
			if (!empty($errorRowsImageUpload)) {
				$messages[] = $this->createDangerMessage("Error uploading Images: #", implode(", ", $errorRowsImageUpload));
			}
			if ($errorAdmin) {
				$messages[] = $this->createDangerMessage("Only Administrators are allowed to upload new Logos.");
			}
			
			if ($dbfailure) {
				$messages[] = $this->messages["dbfailure"];
			}
			return array("success" => !$error, "messages" => $messages);
		}

		public function edit($id, $title, $descriptionWikiTitle, $image) {
			//Validation
			$errorAdmin = !$this->usersModel->checkAdminStatus();
			$errorTitle = !$this->validTitle($title);
			$errorTitleAlreadyTaken = !$this->untakenTitle($title, $id);
			$errorDescriptionWikiTitle = !$this->validDescription($descriptionWikiTitle);
			$errorImage = !$this->validImage($image);
			$errorImageMove = false;
			$dbfailure = false;
			
			if (!$errorAdmin) { //Only Admins can upload new logos
				//Create logo
				if ($stmt = $this->mysqli->prepare("UPDATE logos SET title=?, descriptionwikititle=?, location=? WHERE id=?")) {
					$stmt->bind_param('sssi', $title, $descriptionWikiTitle, $location, $id);
					$stmt->execute();
					$logoID = (int) $this->mysqli->insert_id;

					//Move image to images/logos/ folder
					if (move_uploaded_file($image['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/images/logos/'.$logoID)) {
					}
					else {
						$errorImageMove = true;
					}
				}
				else {
					$dbfailure = true;
				}
			}

			$error = $errorTitle||$errorTitleAlreadyTaken||$errorDescriptionWikiTitle||$errorImage||$errorImageMove||$errorAdmin||$dbfailure;
			$messages = array();
			if (!$error) {
				$messages[] = $this->createSuccessMessage("Success! ".$title, "now has a Logo.");
			}
			if ($errorAdmin) {
				$messages[] = $this->createDangerMessage("Only Administrators are allowed to upload new Logos.");
			}
			if ($errorTitle) {
				$messages[] = $this->createDangerMessage("Invalid title.");
			}
			if ($errorTitleAlreadyTaken) {
				$messages[] = $this->createDangerMessage("Logo already created.", "A Logo with this Title was already created.");
			}
			if ($errorDescriptionWikiTitle) {
				$messages[] = $this->createDangerMessage("Invalid Wikipedia description title.");
			}
			if ($errorImage) {
				$messages[] = $this->createDangerMessage("Invalid image.", "Must be less than 500KB in size. Supported types: PNG, ");
			}
			if ($errorImageMove) {
				$messages[] = $this->createDangerMessage("Error uploading image.", "Please try again later.");
			}
			if ($dbfailure) {
				$messages[] = $this->messages["dbfailure"];
			}
			return array("success" => !$error, "messages" => $messages);
		}

		public function getPastLogos() {
			list($userID, $guestKey) = $this->usersModel->getUserIdentifier();

			$dbfailure = false;

			$logos = array();

			if ($stmt = $this->mysqli->prepare("SELECT logos.id, logos.title, logos.descriptionwikititle, pastlogos.timetaken, pastlogos.skips FROM pastlogos LEFT JOIN logos ON logos.id=pastlogos.logoid WHERE (pastlogos.userid<=>? AND pastlogos.guestkey<=>?) ORDER BY logos.id")) {
				$stmt->bind_param('is', $userID, $guestKey);
				$stmt->execute();
				$stmt->store_result();
				$rowdata = array();
				stmt_bind_assoc($stmt, $rowdata);
				while ($stmt->fetch()) {
					$logos[] = array(	"id" => $rowdata['id'],
										"title" => $rowdata['title'],
										"descriptionWikiTitle" => $rowdata['descriptionWikiTitle'],
										"timeTaken" => round(($rowdata['timetaken']/1000), 2),
										"timeTakenAboveAverage" => 0,
										"skips" => $rowdata['skips'],
										"skipsAboveAverage" => 0
									);
				}

				//Get Wikipedia descriptions for Logos

			}
			else {
				$dbfailure = true;
			}

			$error = $dbfailure;

			return (!$error ? $logos : NULL);
		}


		public function trimTitle($title) {
			return preg_replace('/[^a-zA-Z]/', '', $title);
		}
		public function validTitle($title) {
			return strlen($this->trimTitle($title))>0;
		}
		public function validDescriptionWikiTitle($descriptionWikiTitle) {
			return strlen($descriptionWikiTitle)>0;
		}
		public function validImage($image) {
			$imageName=$image["name"];
			$imageExtension=strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
			if ($image['error'] > 0){
				return false;
			}
			if (!empty($image['tmp_name'])&&!getimagesize($image['tmp_name'])) { //Returns true if file is NOT an image
				return false;
			}
			if (!in_array($imageExtension, $this->imageAcceptableFormats)) {
				return false;
			}
			if ($image['size'] > 500000) { //500kb size limit
				return false;
			}
			return true;
		}
		private function untakenTitle($title, $id = NULL) {
			if ($stmt = $this->mysqli->prepare("SELECT COUNT(*) FROM logos WHERE title=? AND id<>? LIMIT 1")) {
				$stmt->bind_param('si', $title, $id);
				$stmt->execute();
				$stmt->store_result();
				return $stmt->num_rows()==0; //True if 0 rows returned, meaning email not already taken
			}
			else {
				return false;
			}
		}
	}

?>
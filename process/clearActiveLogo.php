<?php

	require_once("../secure/db_connect.php"); 
	require_once("../secure/functions.php");
	require_once("../models/usersmodel.php");
	sec_session_start();
	
	$usersModel = new usersModel($mysqli);
	list($userID, $guestKey) = $usersModel->getUserIdentifier();

	header("Content-Type: application/json", true);

	$error = false;
	$dbfailure = false;
	$invalidinput = false;

	if (!(is_null($userID)&&is_null($guestKey))) {
		
		$mysqli->begin_transaction();

		//Clear New Logo
		if ($stmt = $mysqli->prepare("DELETE FROM activelogos WHERE (guestkey<=>? AND userid<=>?) LIMIT 1")) {
			$stmt->bind_param('si', $guestKey, $userID);
			$stmt->execute();
		}
		else {
			$dbfailure = true;
		}

		if (!$dbfailure) {
			$mysqli->commit();
		}
		else {
			$mysqli->rollback();
		}

		$error = $error||$dbfailure;

		if (!$error) {
			echo '{ "result" : "success" }';
		}
	}
	else {
		$invalidinput = true;
	}

	$error = $error||$invalidinput;

	if ($error) {
		if ($dbfailure) {
			echo '{ "result" : "dbfailure" }';
		}
		else if ($invalidinput) {
			echo '{ "result" : "invalidinput" }';
		}
	}

?>
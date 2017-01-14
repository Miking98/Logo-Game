<?php

	$startTime = round(microtime(1) * 1000);

	require_once("../secure/db_connect.php"); 
	require_once("../secure/functions.php");
	require_once("../models/usersmodel.php");
	sec_session_start();
	
	$usersModel = new usersModel($mysqli);
	list($userID, $guestKey) = $usersModel->getUserIdentifier();

	header("Content-Type: application/json", true);

	$error = false;
	$dbfailure = false;
	$nomorelogos = false;
	$invalidinput = false;

	if (!(is_null($userID)&&is_null($guestKey))) {
		
		$mysqli->begin_transaction();

		//Fetch New Logo
		if ($stmt = $mysqli->prepare("SELECT logos.id, logos.title, logos.location, limbologos.timetaken FROM logos LEFT JOIN pastlogos ON pastlogos.logoid=logos.id AND (pastlogos.guestkey<=>? AND pastlogos.userid<=>?) LEFT JOIN limbologos ON limbologos.logoid=logos.id AND (limbologos.guestkey<=>? AND limbologos.userid<=>?) WHERE ISNULL(pastlogos.logoid) ORDER BY RAND() LIMIT 1")) {
			$stmt->bind_param('sisi', $guestKey, $userID, $guestKey, $userID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($logoID, $logoTitle, $logoLocation, $timeTaken);
			$stmt->fetch();
			if ($stmt->num_rows>0) {
				$logoID = (int) $logoID;
				$logoEditedTitle = preg_replace('/[a-zA-Z]/', '*', $logoTitle);
				$logoLocation = 'images/logos/'.$logoLocation;
				$timeTaken = is_null($timeTaken) ? 0: (int) $timeTaken;
				//Move Old Logo from Active => Limbo
				//Then move New Logo into Active
				if ($stmt = $mysqli->prepare("SELECT logoid FROM activelogos WHERE (guestkey<=>? AND userid<=>?) LIMIT 1")) {
					$stmt->bind_param('si', $guestKey, $userID);
					$stmt->execute();
					$stmt->store_result();
					$stmt->bind_result($oldLogoID);
					$stmt->fetch();

					//Insert Old Logo into Limbo 
					if ($stmt = $mysqli->prepare("INSERT INTO limbologos (logoid, guestkey, userid, timetaken) SELECT a.logoid, a.guestkey, a.userid, ROUND(UNIX_TIMESTAMP(CURTIME(4))*1000)-a.starttime FROM activelogos AS a WHERE (a.guestkey<=>? AND a.userid<=>?) AND a.logoid=? ON DUPLICATE KEY UPDATE timetaken=timetaken+ROUND(UNIX_TIMESTAMP(CURTIME(4))*1000)-a.starttime, skips=skips+1")) {
						$stmt->bind_param('sii', $guestKey, $userID, $oldLogoID);
						$stmt->execute();

						//Remove Old Logo from Active
						if ($stmt = $mysqli->prepare("DELETE FROM activelogos WHERE logoid=? AND (guestkey<=>? AND userid<=>?) LIMIT 1")) {
							$stmt->bind_param('isi', $oldLogoID, $guestKey, $userID);
							$stmt->execute();

							//
							//Move New Logo into Active
							//
							//Insert into Active
							if ($stmt = $mysqli->prepare("INSERT INTO activelogos (logoid, guestkey, userid, starttime) VALUES (?,?,?,?)")) {
								$stmt->bind_param('isii', $logoID, $guestKey, $userID, $startTime);
								$stmt->execute();


								//Record skip in stats
								$usersSolved = 0;
								$totalTimeTaken = 0;
								$totalSkips = 1;
								if ($stmt = $mysqli->prepare("INSERT INTO statslogos (logoid, userssolved, totaltimetaken, totalskips) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE totalskips=totalskips+1")) {
									$stmt->bind_param('iiii', $oldLogoID, $usersSolved, $totalTimeTaken, $totalSkips);
									$stmt->execute();

									//User
									if ($usersModel->checkLogin()) {
										$logosSolved = 0;
										$lettersSolved = 0;
										if ($stmt = $mysqli->prepare("INSERT INTO statsusers (userid, logossolved, letterssolved, totaltimetaken, totalskips) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE totalskips=totalskips+1")) {
											$stmt->bind_param('iiiii', $userID, $logosSolved, $lettersSolved, $totalTimeTaken, $totalSkips);
											$stmt->execute();
										}
										else {
											$dbfailure = true;
										}
									}
								}
								else {
									$dbfailure = true;
								}
							}
							else {
								$dbfailure = true;
							}
						}
						else {
							$dbfailure = true;
						}
					}
					else {
						$dbfailure = true;
					}
				}
				else {
					$dbfailure = true;
				}
			}
			else { //User has answered all logos - there are no more he can solve
				$nomorelogos = true;
			}
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

		$error = $error||$dbfailure||$nomorelogos;

		if (!$error) {
			echo '{ "result" : "success", "id" : "'.$logoID.'", "title" : "'.$logoEditedTitle.'", "location" : "'.$logoLocation.'", "timeTaken" : "'.$timeTaken.'" }';
		}
	}
	else {
		$invalidinput = true;
	}

	if ($error) {
		if ($dbfailure) {
			echo '{ "result" : "dbfailure" }';
		}
		else if ($invalidinput) {
			echo '{ "result" : "invalidinput" }';
		}
		else if ($nomorelogos) {
			echo '{ "result" : "nomorelogos" }';
		}
	}

?>
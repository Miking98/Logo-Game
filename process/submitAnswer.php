<?php

	$endTime = round(microtime(1) * 1000);

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
	$inactivelogo = false;

	if (!(is_null($userID)&&is_null($guestKey))) {
		if (isset($_POST['answer'])) {
			$answer = strtoupper($_POST['answer']);
			$mysqli->begin_transaction();

			//Get Active Logo information for this user
			if ($stmt = $mysqli->prepare("SELECT logos.id, logos.title, logos.descriptionwikititle, activelogos.starttime, limbologos.skips, limbologos.timetaken FROM activelogos INNER JOIN logos ON logos.id=activelogos.logoid LEFT JOIN limbologos ON logos.id=limbologos.logoid AND (limbologos.guestkey<=>? AND limbologos.userid<=>?) WHERE (activelogos.guestkey<=>? AND activelogos.userid<=>?) LIMIT 1")) {
				$stmt->bind_param('sisi', $guestKey, $userID, $guestKey, $userID);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($logoID, $logoTitle, $logoDescriptionWikiTitle, $startTime, $skips, $timeTaken);
				$stmt->fetch();

				if ($stmt->num_rows==1) { //Logo still active
					$logoEditedTitle = strtoupper(preg_replace('/[^a-zA-Z]/', '', $logoTitle)); //Remove any non-letter chars AND make letters uppercase
					$skips = is_null($skips) ? 0 : $skips;
					$timeTaken = is_null($timeTaken) ? 0 : $timeTaken;
					$totalTimeTaken = $timeTaken + $endTime-$startTime;
					$correctAnswer = $answer==$logoEditedTitle;


					if ($correctAnswer) { //Correct!
						//Move Logo from Active/Limbo => Past
						//Create Past record
						if ($stmt = $mysqli->prepare("INSERT INTO pastlogos (logoid, guestkey, userid, timetaken, skips) VALUES (?,?,?,?,?)")) {
							$stmt->bind_param('isiii', $logoID, $guestKey, $userID, $totalTimeTaken, $skips);
							$stmt->execute();
							//Remove Active record
							if ($stmt = $mysqli->prepare("DELETE FROM activelogos WHERE logoid=? AND (guestkey<=>? AND userid<=>?)")) {
								$stmt->bind_param('isi', $logoID, $guestKey, $userID);
								$stmt->execute();
								//Remove Limbo record
								if ($stmt = $mysqli->prepare("DELETE FROM limbologos WHERE logoid=? AND (guestkey<=>? AND userid<=>?)")) {
									$stmt->bind_param('isi', $logoID, $guestKey, $userID);
									$stmt->execute();

									//Record Stats
									//Logo
									$usersSolved = 1;
									$logoLetters = strlen($logoEditedTitle);
									$initialDifficulty = (-4/(0.5*$skips/$usersSolved+1)+4-1/($totalTimeTaken/$logoLetters+1)+1);
									if ($stmt = $mysqli->prepare("INSERT INTO statslogos (logoid, letters, difficulty, userssolved, totaltimetaken, totalskips) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE userssolved=userssolved+1, totaltimetaken=totaltimetaken+VALUES(totaltimetaken), difficulty=(-4/(0.5*totalskips/userssolved+1)+4-1/(totaltimetaken/letters+1)+1)")) {
										$stmt->bind_param('iidiii', $logoID, $logoLetters, $initialDifficulty, $usersSolved, $totalTimeTaken, $skips);
										$stmt->execute();
										//User
										if ($usersModel->checkLogin()) {
											$logosSolved = 1;
											if ($stmt = $mysqli->prepare("INSERT INTO statsusers (userid, logossolved, letterssolved, totaltimetaken, totalskips) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE logossolved=logossolved+1, letterssolved=letterssolved+VALUES(letterssolved), totaltimetaken=totaltimetaken+VALUES(totaltimetaken), score=5*logossolved+(logossolved/totalskips)-10*totalskips-(totaltimetaken/letterssolved)/1000")) {
												$stmt->bind_param('iiiii', $userID, $logosSolved, $logoLetters, $totalTimeTaken, $skips);
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
					else { //Incorrect
						$logoEditedTitleChars = str_split($logoEditedTitle);
						$answerChars = str_split($answer);
						for ($i = 0; $i<count($logoEditedTitleChars); $i++) {
							if ($logoEditedTitleChars[$i]==$answerChars[$i]) { //Correct char
							}
							else { //Incorrect char
								$answerChars[$i] = "*";
							}
						}
						$editedAnswer = implode("", $answerChars); //NE*YORKYA*K**S
					}
				}
				else {
					$inactivelogo = true;
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

			/*
			//Get Wikipedia description for Logo
			$url = 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&indexpageids=&exintro=&explaintext=&titles='.$logoDescriptionWikiTitle;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, "LogoGame/1.0"); // required by wikipedia.org server; use YOUR user agent with YOUR contact information. (otherwise your IP might get blocked)
			$json = curl_exec($ch);
			$wikiInfo = json_decode($json);
			$wikiInfoPageID = $wikiInfo->query->pageids[0];
			$logoDescription = preg_replace('/\n/', '\\\n', $wikiInfo->{'query'}->{'pages'}->{$wikiInfoPageID}->{'extract'})." (Source: Wikipedia)";
			*/
			
			$error = $invalidinput||$dbfailure||$inactivelogo;
			
			if (!$error) {
				if ($correctAnswer) {
					echo '{ "result" : "success", "response" : "correct", "descriptionWikiTitle" : "'.preventxss($logoDescriptionWikiTitle).'" }';
				}
				else {
					echo '{ "result" : "success", "response" : "incorrect", "updatedAnswer" : "'.preventxss($editedAnswer).'" }';
				}
			}
		}
		else {
			$invalidinput = true;
		}
	}
	else {
		$invalidinput = true;
	}

	$error = $invalidinput||$dbfailure||$inactivelogo;
	
	if ($error) {
		if ($dbfailure) {
			echo '{ "result" : "dbfailure" }';
		}
		else if ($invalidinput) {
			echo '{ "result" : "invalidinput" }';
		}
		else if ($inactivelogo) {
			echo '{ "result" : "inactivelogo" }';
		}
	}

?>
<?php

	require_once("../../secure/db_connect.php"); 
	require_once("../../secure/functions.php");
	require_once("../../models/usersmodel.php");
	sec_session_start();
	
	$usersModel = new usersModel($mysqli);
	list($userID, $guestKey) = $usersModel->getUserIdentifier();
	$totalNumberOfUsers = $usersModel->getCountUsers();

	header("Content-Type: application/json", true);

	$error = false;
	$dbfailure = false;
	$invalidinput = false;

	$output = array(); //JSON echoed back to Datatables

	$draw = (int) $_GET['draw'];
	if (isset($_GET['start'])) {
		$start = (int) $_GET['start'];
		$length = (int) $_GET['length'];
		$end = (int) ($start + $length);
		$searchValue = "%".$_GET['search']['value']."%";
		$orderColumn = "logos.title";
		$orderColumnData = $_GET['columns'][$_GET['order'][0]['column']]['data'];
		if ($orderColumnData=="title") { $orderColumn = "logos.title"; }
		else if ($orderColumnData=="difficulty") { $orderColumn = "difficulty"; }
		else if ($orderColumnData=="percentUsersSolved") { $orderColumn = "statslogos.userssolved"; }
		else if ($orderColumnData=="averageTimeTaken") { $orderColumn = "averagetimetaken"; }
		else if ($orderColumnData=="averageTimeTakenBelowAverageLogo") { $orderColumn = "averagetimetaken"; }
		else if ($orderColumnData=="averageTimeTakenPerLetter") { $orderColumn = "averagetimetakenperletter"; }
		else if ($orderColumnData=="averageTimeTakenPerLetterBelowAverageLogo") { $orderColumn = "averagetimetakenperletter"; }
		else if ($orderColumnData=="averageSkips") { $orderColumn = "averageskips"; }
		else if ($orderColumnData=="averageSkipsBelowAverageLogo") { $orderColumn = "averageskips"; }
		else if ($orderColumnData=="totalTimeTaken") { $orderColumn = "statslogos.totaltimetaken"; }
		else if ($orderColumnData=="totalSkips") { $orderColumn = "statslogos.totalskips"; }
		$orderDir = $_GET['order'][0]['dir']=="desc" ? "DESC" : "ASC";

		$sqlBase = " FROM statslogos INNER JOIN logos ON statslogos.logoid=logos.id LEFT JOIN pastlogos ON statslogos.logoid=pastlogos.logoid AND (pastlogos.guestkey<=>? AND pastlogos.userid=?) WHERE (logos.title LIKE ?)";
		$sqlOrder = " ORDER BY ".$orderColumn." ".$orderDir;
		$sqlLimit = "";
		if ($end>$start) {
			$sqlLimit = " LIMIT ".$start.",".$end;
		}

		//Difficulty formula: 
		// UPDATE statslogos AS a INNER JOIN logos AS b ON a.logoid=b.id SET a.difficulty = (-4/(0.5*a.totalskips/a.userssolved+1)+4-1/(a.totaltimetaken/b.letters+1)+1) WHERE statslogos.logoid=NEW.logoid LIMIT 1
		//(-4/(0.5*averageskips+1)+4-1/(averagetimetakenperletter+1)+1) ; [5,0], 5 = hardest
		$sqlCount = "SELECT COUNT(logos.id)".$sqlBase;
		$sqlAllLogoAverages = "SELECT userssolved, timetaken, timetakenperletter, skips FROM statsalllogosaverages";
		$sqlFetch = "SELECT logos.id, logos.title, logos.descriptionwikititle, logos.location, statslogos.difficulty, statslogos.userssolved, statslogos.totaltimetaken, statslogos.totalskips, (statslogos.totaltimetaken/statslogos.userssolved) AS averagetimetaken, (statslogos.totaltimetaken/statslogos.userssolved/statslogos.letters) AS averagetimetakenperletter, (statslogos.totalskips/statslogos.userssolved) AS averageskips, pastlogos.logoid AS currentUserSolved ".$sqlBase.$sqlOrder.$sqlLimit;

		if ($stmt = $mysqli->prepare($sqlCount)) {
			$stmt->bind_param('sis', $guestKey, $userID, $searchValue);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($totalCount);
			$stmt->fetch();
			$output["draw"] = $draw;
			$output["recordsTotal"] = $totalCount;
			$output["recordsFiltered"] = $totalCount;
			$output["data"] = array();
			if ($stmt=$mysqli->prepare($sqlAllLogoAverages)) {
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($averageLogoUsersSolved, $averageLogoTimeTaken, $averageLogoTimeTakenPerLetter, $averageLogoSkips);
				$stmt->fetch();
				if ($stmt = $mysqli->prepare($sqlFetch)) {
					$stmt->bind_param('sis', $guestKey, $userID, $searchValue);
					$stmt->execute();
					$stmt->store_result();
					$rowdata = array();
					stmt_bind_assoc($stmt, $rowdata);
					while ($stmt->fetch()) {
						$difficulty = (int) $rowdata['difficulty'];
						$currentUserSolved = !is_null($rowdata['currentUserSolved']) ? 1 : 0;
						$percentUsersSolved = number_format($rowdata['userssolved']/$totalNumberOfUsers*100, 0);
						$averageTimeTaken = number_format($rowdata['averagetimetaken']/1000, 3);
						$averageTimeTakenPerLetter = number_format($rowdata['averagetimetakenperletter']/1000, 3);
						$averageSkips = number_format($rowdata['averageskips'], 3);
						$averageTimeTakenBelowAverage = number_format(($rowdata['averagetimetaken']-$averageLogoTimeTaken)/1000, 3);
						$averageTimeTakenPerLetterBelowAverage = number_format(($rowdata['averagetimetakenperletter']-$averageLogoTimeTakenPerLetter)/1000, 3);
						$averageSkipsBelowAverage = number_format($rowdata['averageskips']-$averageLogoSkips, 3);
						$totalTimeTaken = number_format($rowdata['totaltimetaken']/1000, 3);
						$totalSkips = $rowdata['totalskips'];

						if ($averageTimeTakenBelowAverage>0) {
							$averageTimeTakenBelowAverage = "+".$averageTimeTakenBelowAverage;
						}
						if ($averageTimeTakenPerLetterBelowAverage>0) {
							$averageTimeTakenPerLetterBelowAverage = "+".$averageTimeTakenPerLetterBelowAverage;
						}
						if ($averageSkipsBelowAverage>0) {
							$averageSkipsBelowAverage = "+".$averageSkipsBelowAverage;
						}
						$output["data"][] = array(	"DT_RowAttr" => array(	"logoID" => $rowdata['id'],
																			"currentUserSolved" => $currentUserSolved /* 
																				"descriptionWikiTitle" => preventxss($rowdata['descriptionwikititle']),
								



								}												"imagelocation" => preventxss($rowdata['location']) */
																				),
													"title" => preventxss($rowdata['title']),
													"difficulty" => $difficulty,
													"percentUsersSolved" => $percentUsersSolved,
													"averageTimeTaken" => $averageTimeTaken, //Seconds
													"averageTimeTakenBelowAverageLogo" => $averageTimeTakenBelowAverage,
													"averageTimeTakenPerLetter" => $averageTimeTakenPerLetter,
													"averageTimeTakenPerLetterBelowAverageLogo" => $averageTimeTakenPerLetterBelowAverage,
													"averageSkips" => $averageSkips,
													"averageSkipsBelowAverageLogo" => $averageSkipsBelowAverage,
													"totalTimeTaken" => $totalTimeTaken,
													"totalSkips" => $totalSkips,
													);
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
		$invalidinput = true;
	}

	$error = $invalidinput||$dbfailure;

	if ($error) {
		$output["draw"] = $draw;
		$output["recordsTotal"] = 1;
		$output["recordsFiltered"] = 1;
		$output["data"] = array();
		if ($dbfailure) {
			$output["error"] = "Error processing request.";
		}
		else if ($invalidinput) {
			$output["error"] = "Invalid input.";
		}
	}

	echo json_encode($output);

?>
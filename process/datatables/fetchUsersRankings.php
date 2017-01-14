<?php

	require_once("../../secure/db_connect.php"); 
	require_once("../../secure/functions.php");
	require_once("../../models/usersmodel.php");
	sec_session_start();
	
	$usersModel = new usersModel($mysqli);
	list($userID, $guestKey) = $usersModel->getUserIdentifier();

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
		$orderColumn = "statsusers.score";
		$orderColumnData = $_GET['columns'][$_GET['order'][0]['column']]['data'];
		if ($orderColumnData=="rank") { $orderColumn = "statsusers.score"; }
		else if ($orderColumnData=="logosSolved") { $orderColumn = "statsusers.logossolved"; }
		else if ($orderColumnData=="userName") { $orderColumn = "users.username"; }
		else if ($orderColumnData=="averageTimeTaken") { $orderColumn = "averagetimetaken"; }
		else if ($orderColumnData=="averageTimeTakenBelowAverage") { $orderColumn = "averagetimetaken"; }
		else if ($orderColumnData=="averageTimeTakenPerLetter") { $orderColumn = "averagetimetakenperletter"; }
		else if ($orderColumnData=="averageTimeTakenPerLetterBelowAverage") { $orderColumn = "averagetimetakenperletter"; }
		else if ($orderColumnData=="averageSkips") { $orderColumn = "averageskips"; }
		else if ($orderColumnData=="averageSkipsBelowAverage") { $orderColumn = "averageskips"; }
		$orderDir = $_GET['order'][0]['dir']=="desc" ? "DESC" : "ASC";

		$sqlBase = " FROM statsusers INNER JOIN users ON statsusers.userid=users.id WHERE statsusers.logossolved>=10 AND (users.username LIKE ?)"; //Must have at least 10 logos solved to be on leaderboards
		$sqlOrder = " ORDER BY ".$orderColumn." ".$orderDir;
		$sqlLimit = "";
		if ($end>$start) {
			$sqlLimit = " LIMIT ".$start.",".$end;
		}

		$sqlCount = "SELECT COUNT(statsusers.userid)".$sqlBase;
		$sqlAllLogoAverages = "SELECT logossolved, timetaken, timetakenperletter, skips FROM statsallusersaverages";
		$sqlFetch = "SELECT users.id, users.username, statsusers.score, statsusers.logossolved, statsusers.letterssolved, statsusers.totaltimetaken, statsusers.totalskips, (statsusers.totaltimetaken/statsusers.logossolved) AS averagetimetaken, (statsusers.totaltimetaken/statsusers.letterssolved) AS averagetimetakenperletter, (statsusers.totalskips/statsusers.letterssolved) AS averageskips, (SELECT COUNT(DISTINCT b.score) FROM statsusers b WHERE b.score >= statsusers.score) AS rank".$sqlBase.$sqlOrder.$sqlLimit;

		if ($stmt = $mysqli->prepare($sqlCount)) {
			$stmt->bind_param('s', $searchValue);
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
				$stmt->bind_result($averageUserLogosSolved, $averageUserTimeTaken, $averageUserTimeTakenPerLetter, $averageUserSkips);
				$stmt->fetch();
				if ($stmt = $mysqli->prepare($sqlFetch)) {
					$stmt->bind_param('s', $searchValue);
					$stmt->execute();
					$stmt->store_result();
					$rowdata = array();
					stmt_bind_assoc($stmt, $rowdata);
					while ($stmt->fetch()) {
						$rank = (int) $rowdata['rank'];
						$logosSolved = (int) $rowdata['logossolved'];
						$averageTimeTaken = number_format($rowdata['averagetimetaken']/1000, 3);
						$averageTimeTakenPerLetter = number_format($rowdata['averagetimetakenperletter']/1000, 3);
						$averageSkips = number_format($rowdata['averageskips'], 3);
						$averageTimeTakenBelowAverage = number_format(($rowdata['averagetimetaken']-$averageUserTimeTaken)/1000, 3);
						$averageTimeTakenPerLetterBelowAverage = number_format(($rowdata['averagetimetakenperletter']-$averageUserTimeTakenPerLetter)/1000, 3);
						$averageSkipsBelowAverage = number_format($rowdata['averageskips']-$averageUserSkips, 3);
						$totalTimeTaken = number_format($rowdata['totaltimetaken']/1000, 3);
						$totalSkips =  number_format($rowdata['totalskips']/1000, 3);
						
						if ($averageTimeTakenBelowAverage>0) {
							$averageTimeTakenBelowAverage = "+".$averageTimeTakenBelowAverage;
						}
						if ($averageTimeTakenPerLetterBelowAverage>0) {
							$averageTimeTakenPerLetterBelowAverage = "+".$averageTimeTakenPerLetterBelowAverage;
						}
						if ($averageSkipsBelowAverage>0) {
							$averageSkipsBelowAverage = "+".$averageSkipsBelowAverage;
						}
						$output["data"][] = array(	"DT_RowAttr" => array(	"userID" => $rowdata['id']
																			),
													"userName" => preventxss($rowdata['username']),
													"rank" => $rank,
													"logosSolved" => $logosSolved,
													"averageTimeTaken" => $averageTimeTaken, //Seconds
													"averageTimeTakenBelowAverage" => $averageTimeTakenBelowAverage,
													"averageTimeTakenPerLetter" => $averageTimeTakenPerLetter,
													"averageTimeTakenPerLetterBelowAverage" => $averageTimeTakenPerLetterBelowAverage,
													"averageSkips" => $averageSkips,
													"averageSkipsBelowAverage" => $averageSkipsBelowAverage,
													"totalTimeTaken" => $totalTimeTaken,
													"totalSkips" => $totalSkips
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
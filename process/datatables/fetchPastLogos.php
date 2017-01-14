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
	if (!(is_null($userID)&&is_null($guestKey))) {
		if (isset($_GET['start'])) {
			$start = (int) $_GET['start'];
			$length = (int) $_GET['length'];
			$end = (int) ($start + $length);
			$searchValue = "%".$_GET['search']['value']."%";
			$orderColumn = "id";
			$orderColumnData = $_GET['columns'][$_GET['order'][0]['column']]['data'];
			if ($orderColumnData=="id") { $orderColumn = "logos.id"; }
			else if ($orderColumnData=="title") { $orderColumn = "logos.title"; }
			else if ($orderColumnData=="timeTaken") { $orderColumn = "pastlogos.timetaken"; }
			else if ($orderColumnData=="timeTakenBelowAverage") { $orderColumn = "timetakenbelowaverage"; }
			else if ($orderColumnData=="timeTakenPerLetter") { $orderColumn = "timetakenperletter"; }
			else if ($orderColumnData=="timeTakenPerLetterBelowAverage") { $orderColumn = "timetakenperletterbelowaverage"; }
			else if ($orderColumnData=="skips") { $orderColumn = "pastlogos.skips"; }
			else if ($orderColumnData=="skipsBelowAverage") { $orderColumn = "skipsbelowaverage"; }
			$orderDir = $_GET['order'][0]['dir']=="desc" ? "DESC" : "ASC";

			$sqlBase = " FROM pastlogos INNER JOIN statslogos ON pastlogos.logoid=statslogos.logoid INNER JOIN logos ON pastlogos.logoid=logos.id WHERE (pastlogos.guestkey<=>? AND pastlogos.userid<=>?) AND (logos.id LIKE ? OR logos.title LIKE ?)";
			$sqlOrder = " ORDER BY ".$orderColumn." ".$orderDir;
			$sqlLimit = "";
			if ($end>$start) {
				$sqlLimit = " LIMIT ".$start.",".$end;
			}

			$sqlCount = "SELECT COUNT(logos.id)".$sqlBase;
			$sqlFetch = "SELECT logos.id, logos.title, logos.descriptionwikititle, logos.location, pastlogos.timetaken, (pastlogos.timetaken/statslogos.letters) AS timetakenperletter, pastlogos.skips, ((statslogos.totaltimetaken/statslogos.userssolved)-pastlogos.timetaken) AS timetakenbelowaverage, ((statslogos.totaltimetaken/statslogos.userssolved/statslogos.letters)-(pastlogos.timetaken/statslogos.letters)) AS timetakenperletterbelowaverage, ((statslogos.totalskips/statslogos.userssolved)-pastlogos.skips) AS skipsbelowaverage".$sqlBase.$sqlOrder.$sqlLimit;

			if ($stmt = $mysqli->prepare($sqlCount)) {
				$stmt->bind_param('siss', $guestKey, $userID, $searchValue, $searchValue);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($totalCount);
				$stmt->fetch();
				$output["draw"] = $draw;
				$output["recordsTotal"] = $totalCount;
				$output["recordsFiltered"] = $totalCount;
				$output["data"] = array();
				if ($stmt = $mysqli->prepare($sqlFetch)) {
					$stmt->bind_param('siss', $guestKey, $userID, $searchValue, $searchValue);
					$stmt->execute();
					$stmt->store_result();
					$rowdata = array();
					stmt_bind_assoc($stmt, $rowdata);
					while ($stmt->fetch()) {
						$timeTaken = number_format($rowdata['timetaken']/1000, 3);
						$timeTakenPerLetter = number_format($rowdata['timetakenperletter']/1000, 3);
						$skips = $rowdata['skips'];
						$timeTakenBelowAverage = $rowdata['timetakenbelowaverage'];
						$timeTakenPerLetterBelowAverage = $rowdata['timetakenperletterbelowaverage'];
						$skipsBelowAverage = $rowdata['skipsbelowaverage'];
						$output["data"][] = array(	"DT_RowAttr" => array(	"logoID" => $rowdata['id'], 
																			"descriptionWikiTitle" => preventxss($rowdata['descriptionwikititle']),
																			"imagelocation" => preventxss($rowdata['location'])
																			),
													"id" => $rowdata['id'],
													"title" => preventxss($rowdata['title']),
													"timeTaken" => $timeTaken, //Seconds
													"timeTakenBelowAverage" => $timeTakenBelowAverage,
													"timeTakenPerLetter" => $timeTakenPerLetter,
													"timeTakenPerLetterBelowAverage" => $timeTakenPerLetterBelowAverage,
													"skips" => $skips,
													"skipsBelowAverage" => $skipsBelowAverage
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
			$invalidinput = true;
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
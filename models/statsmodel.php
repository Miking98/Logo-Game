<?php
	require_once("model.php");

	class statsModel extends Model {

		public function __construct(mysqli $mysqli) {
			$this->mysqli = $mysqli;
		}

		public function getUserStats($userID) {
			$output = array();
			if ($stmt = $this->mysqli->prepare("SELECT logossolved, letterssolved, totaltimetaken, totalskips, (SELECT COUNT(DISTINCT b.score) FROM statsusers b WHERE b.score >= statsusers.score) AS rank FROM statsusers WHERE userid=?")) {
				$stmt->bind_param('i', $userID);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($output['logosSolved'], $output['lettersSolved'], $output['totalTimeTaken'], $output['totalSkips'], $output['rank']);
				$stmt->fetch();
				return $output;
			}
			else {
				return false;
			}
		}

		public function getAllUsersAverageStats() {
			$output = array();
			if ($stmt = $this->mysqli->prepare("SELECT logossolved, timetaken, timetakenperletter, skips FROM statsallusersaverages")) {
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($output['logosSolved'], $output['timeTaken'], $output['timeTakenPerLetter'], $output['skips']);
				$stmt->fetch();
				return $output;
			}
			else {
				return false;
			}
		}

		public function generateUserRankings() {
			if ($stmt = $this->mysqli->query("UPDATE statsallusersaverages SET logossolved=(SELECT AVG(logossolved) FROM statsusers), timetaken=(SELECT AVG(totaltimetaken/logossolved) FROM statsusers), timetakenperletter=(SELECT AVG(totaltimetaken/letterssolved) FROM statsusers), skips=(SELECT AVG(totalskips/logossolved) FROM statsusers)")) {
				return true;
			}
			return false;
		}

		public function generateLogoRankings() {
			if ($stmt = $this->mysqli->query("UPDATE statsalllogosaverages SET userssolved=(SELECT AVG(userssolved) FROM statslogos), timetaken=(SELECT AVG(totaltimetaken/userssolved) FROM statslogos), timetakenperletter=(SELECT AVG(statslogos.totaltimetaken/statslogos.letters) FROM statslogos), skips=(SELECT AVG(totalskips/userssolved) FROM statslogos)")) {
				return true;
			}
			return false;
		}
	}

?>
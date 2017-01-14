<?php

	class statsView {
		private $usersModel;
		private $statsModel;
		private $additionalScripts;

		//Constructor
		public function __construct(usersModel $usersModel, statsModel $statsModel) {
			$this->usersModel = $usersModel;
			$this->statsModel = $statsModel;
		}
    	
		public function render_usersRankings() {
			$genRankingsSuccess = $this->statsModel->generateUserRankings();
			$this->additionalScripts = 	[	'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
											'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',
											'/scripts/usersrankings.js'
										];
			//Current users's stats
			if ($this->usersModel->checkLogin()) {
				$userStats = $this->statsModel->getUserStats($this->usersModel->getUserID());
				$totalTimeTaken = (int) $userStats['totalTimeTaken'];
				$totalSkips = (int) $userStats['totalSkips'];
				$logosSolved = (int) $userStats['logosSolved'];
				$lettersSolved = (int) $userStats['lettersSolved'];
				$ATT = $logosSolved>0 ? $totalTimeTaken/$logosSolved : 0;
				$ATTPL = $lettersSolved>0 ? $totalTimeTaken/$lettersSolved : 0;
				$AS = $logosSolved>0 ? $totalSkips/$logosSolved : 0;
				$userRank = (int) $userStats['rank'];
				$userName = preventxss($this->usersModel->getUserName());
				$userLogosSolved = (int) $logosSolved;
				$userATT = number_format($ATT/1000, 3);
				$userATTPL = number_format($ATTPL/1000, 3);
				$userAS = number_format($AS/1000, 3);
				$allUsersAverageStats = $this->statsModel->getAllUsersAverageStats();
				$userATTBAU = number_format(($allUsersAverageStats['timeTaken']-$ATT)/1000, 3);
				$userATTPLBAU = number_format(($allUsersAverageStats['timeTakenPerLetter']-$ATTPL)/1000, 3);
				$userASBAU = number_format(($allUsersAverageStats['skips']-$AS)/1000, 3);
			}
			else {
				$userStats = NULL;
			}
			include "templates/top.html";
			include "templates/usersrankings.html";
			include "templates/bottom.html";
		}
		public function render_logosRankings() {
			$genRankingsSuccess = $this->statsModel->generateLogoRankings();
			$this->additionalScripts = 	[	'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
											'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',
											'/scripts/logosrankings.js'
										];
			include "templates/top.html";
			include "templates/logosrankings.html";
			include "templates/bottom.html";
		}
	}

?>
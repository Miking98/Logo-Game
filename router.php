<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	ini_set("auto_detect_line_endings", "1"); //For reading CSV files from Macs

	require_once("secure/db_connect.php"); 
	require_once("secure/functions.php");
	sec_session_start();

	$URI = substr($_SERVER['REQUEST_URI'], 1); //e.g. /logos/view -> logos/view
	$PAGECOMPONENTS = preg_split('/\//', $URI); //e.g. index, stats/alltime, createaccount, createaccount/add, about, contact, login, etc.
	$MAINPAGE = $PAGECOMPONENTS[0]; //e.g. createaccount/add => createaccount
	$SUBPAGE = count($PAGECOMPONENTS)>=2 ? $PAGECOMPONENTS[1] : ''; //e.g. createaccount/add => add

	//Used for authentication, logging in, logging out
	require_once("models/usersmodel.php");
	$usersModel = new usersModel($mysqli);
	//Autologin -- If fails, then it will generate a Guest Key
	$usersModel->autologin();

	//Error checking
	$error404 = false;

	$loggedIn = (boolean) $usersModel->checkLogin();
	$adminStatus = (boolean) $usersModel->checkAdminStatus();


	if ($MAINPAGE==""||$MAINPAGE=="data") {
		header("Location: http://thelogogame.net/index.php", true, 301);
	}
	else if ($MAINPAGE=="index.php") {
		require_once('views/indexview.php');
		$view = new indexView($usersModel);
		$view->render();
	}
	else if ($MAINPAGE=="about") {
		require_once('views/aboutview.php');
		$view = new aboutView($usersModel);
		$view->render();
	}
	else if ($MAINPAGE=="contact") {
		require_once('controllers/contactcontroller.php');
		require_once('views/contactview.php');
		require_once("models/contactmodel.php");
		$contactModel = new contactModel($mysqli);
		$contactView = new contactView($usersModel);
		$contactController = new contactController($usersModel, $contactModel, $contactView);
		if (isset($_POST['email'])) {
			$contactController->submit(trim($_POST['email']), $_POST['message']);
		}
		else {
			$contactView->render_view();
		}
	}
	else if ($MAINPAGE=="login") {
		require_once("controllers/userscontroller.php");
		require_once("views/usersview.php");
		$usersView = new usersView($usersModel);
		$usersController = new usersController($usersModel, $usersView);
		if (isset($_POST['emailorusername'])) {
			$usersController->login($_POST['emailorusername'], $_POST['password'], $_POST['remember']);
		}
		else {
			$usersView->render_login();
		}
	}
	else if ($MAINPAGE=="logout") {
		require_once("controllers/userscontroller.php");
		require_once("views/usersview.php");
		$usersView = new usersView($usersModel);
		$usersController = new usersController($usersModel, $usersView);
		$usersController->logout();
	}
	else if ($MAINPAGE=="users") {
		require_once("controllers/userscontroller.php");
		require_once("views/usersview.php");
		$usersView = new usersView($usersModel);
		$usersController = new usersController($usersModel, $usersView);
		if ($SUBPAGE=="add") {
			if (!$loggedIn) {
				if (isset($_POST['username'])) {
					$usersController->create($_POST['username'], trim($_POST['email']), $_POST['password']);
				}
				else {
					$usersView->render_createAccount();
				}
			}
			else {
				$error404 = true;
			}
		}
		else if ($SUBPAGE=="edit") {
			if ($loggedIn) {
				if (isset($_POST['username'])) {
					$usersController->edit($usersModel->getUserID(), $_POST['username'], $_POST['email'], $_POST['password']);
				}
				else {
					$usersView->render_editAccount();
				}
			}
			else {
				$error404 = true;
			}
		}
		else {
			$error404 = true;
		}
	}
	else if ($MAINPAGE=="stats") {
		require_once("controllers/statscontroller.php");
		require_once("views/statsview.php");
		require_once("models/statsmodel.php");
		$statsModel = new statsModel($mysqli);
		$statsView = new statsView($usersModel, $statsModel);
		$statsController = new statsController($usersModel, $statsModel, $statsView);

		if ($SUBPAGE=="usersRankings") {
			$statsView->render_usersRankings();
		}
		else if ($SUBPAGE=="logosRankings") {
			$statsView->render_logosRankings();
		}
		else {
			$error404 = true;
		}
	}
	else if ($MAINPAGE=="logos") {
		require_once("controllers/logoscontroller.php");
		require_once("views/logosview.php");
		require_once("models/logosmodel.php");
		$logosModel = new logosModel($mysqli, $usersModel);
		$logosView = new logosView($usersModel, $logosModel);
		$logosController = new logosController($usersModel, $logosModel, $logosView);

		if ($SUBPAGE=="add") {
			if ($adminStatus) {
				if (isset($_POST['addType'])) {
					if ($_POST['addType']=="single") {
						$logosController->add($_POST['title'], $_POST['description'], $_FILES['image']);
					}
					else if ($_POST['addType']=="batch_csv") {
						$logosController->add_batch_csv($_FILES['csv'], $_FILES['images']);
					}
					else {
						$error404 = true;
					}
				}
				else {
					$logosView->render_add();
				}
			}
			else {
				$error404 = true;
			}
		}
		else if ($SUBPAGE=="edit") {
			if ($adminStatus) {
				if (isset($_POST['title'])) {
					$logosController->edit($_POST['title'], $_POST['description'], $_FILES['image']);
				}
				else {
					$logosView->render_edit();
				}
			}
			else {
				$error404 = true;
			}
		}
		else if ($SUBPAGE=="view") {
			$logosView->render_view();
		}
		else {
			$error404 = true;
		}
	}
	else {
		$error404 = true;
	}

	if ($error404) {
		require_once("views/404view.php");
		$view = new _404View($usersModel);
		$view->render();
	}

?>
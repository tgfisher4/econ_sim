<?php
/* session.php

- Handles ajax requests related to a particular game session, including loading admin results, toggleing the game session on/off,
entering students to game/ exiting, and handling student submissions.

Last Update:
Joshua Johnson - 8/1/18
Change query in remove_student to look for groupId rather than student AND sessionId

Graham Fisher - 1/10/21
Cut out dead code, simplify interface
*/

ini_set('display_errors', 1); error_reporting(-1);
require_once "../../tsugi_config.php";
require_once('../../dao/QW_DAO.php');

use \Tsugi\Core\LTIX;
use \QW\DAO\QW_DAO;

$LAUNCH = LTIX::session_start();

$QW_DAO = new QW_DAO($PDOX, $CFG->dbprefix, "econ_sim_");

//if (isset($_POST["checkExistance"])) { // Called when student tries to enter game
// why don't we have a uniform interface here and just have all the potential actions check the action field
if ($_POST['action'] == 'checkExistence' ) {
	$data = $QW_DAO->gameIsLive($_POST["gameId"]);
	if( $data['live'] ){
		if ($data['market_struct'] == 'perfect')
			header("Location: ".addSession("../perfect_game.php")."&game=".$_POST['gameId']);
		else if ($data['market_struct'] == 'monopolistic')
			header("Location: ".addSession("../monopolistic_game.php")."&game=".$_POST['gameId']);
		else
			header("Location: ".addSession("../game_main.php")."&game=".$_POST['gameId']);
		/*
		if( $QW_DAO->playerCompletedGame($USER->id, $_POST['gameId']) )
			header("Location: ".addSession("../student.php")."&game=err3"); // player has already completed game for this session
		else {
			if ($data['market_struct'] == 'perfect')
				header("Location: ".addSession("../perfect_game.php")."&game=".$_POST['gameId']);
			else if ($data['market_struct'] == 'monopolistic')
				header("Location: ".addSession("../monopolistic_game.php")."&game=".$_POST['gameId']);
			else
				header("Location: ".addSession("../game_main.php")."&game=".$_POST['gameId']);
		}
		*/
	}
	else
		header("Location: ".addSession("../student.php")."&session=err"); // session doesn't exist (is not toggled on by instuctor)
}

// called when admin starts/stops a session
// set the "live" column in Game table
else if ($_POST['action'] == 'toggle') {
	echo $QW_DAO->toggleGameLive($_POST["gameId"], $_POST["initPriceHistory"]);
}

// save game info to gameSessionData
else if ($_POST['action'] == 'recordSubmission') {

	if($_POST['marketStructureName'] == "oligopoly")	echo $QW_DAO->submitMultiplayerQuantity( $_POST['sessionId'],
																								 $_POST['playerId'],
																								 $_POST['quantity'],
																							 	 /*$_POST['isFinalSubmission']*/);

	else 												$QW_DAO->submitSingleplayerQuantity( $_POST['sessionId'],
																						 $_POST['playerId'],
																						 $_POST['quantity'],
																					 	 /*$_POST['isFinalSubmission']*/);
}

else if( $_POST['action'] == 'joinGame' ){
	if( $_POST['marketStructureName'] == 'oligopoly' ){
		echo $QW_DAO->joinMultiplayerGame($_POST['gameId'], $_POST['playerId']);
	}
	else {
		echo $QW_DAO->joinSingleplayerGame($_POST['gameId'], $_POST['playerId']);
	}
}

// instructor results page uses this function to grab the results for a specific game and display it
else if ($_POST['action'] == 'retrieveGameResults') {
	echo $QW_DAO->retrieveGameResults($_POST["gameId"]);//,$row['groupId'],$row['player']);
}

// don't think this is ever used
else if ($_POST['action'] == 'removeStudent') {
	$QW_DAO->removeFromSession($_POST['sessionId']);
}

<?php
/* add_game.php

- handles saving game/deleting from mysql database.
- When saving, checks if game exists alreay to decide wheter to save as new,
or update existing.\

Last Update:
*/

ini_set('display_errors', 1); error_reporting(-1);
require_once "../../tsugi_config.php";
require_once('../../dao/QW_DAO.php');

use \Tsugi\Core\LTIX;
use \QW\DAO\QW_DAO;

$LAUNCH = LTIX::session_start();

$QW_DAO = new QW_DAO($PDOX, $CFG->dbprefix, "econ_sim_");

// saves equilibrium to game in table
// guessing this is useless
if( $_POST['action'] == 'saveEquilibrium' ){
	$QW_DAO->saveEquilibrium($_POST['equilibrium'], $_POST['gameId']);
}
// Create/save game game
/*	else if (isset($_POST['mode']) && isset($_POST['difficulty']) && isset($_POST['market_struct'])
		&& isset($_POST['macroEconomy']) && isset($_POST['limit']) && isset($_POST['numRounds']) && isset($_POST['course_id'])
		&& isset($_POST['gameName'])) { */
else if( $_POST['action'] == 'updateGame' ){

	/*
	// get max value for quantity, in order to keep price/demand positive
	switch ($_POST['market_struct']) {
		case 'monopoly':
			$max=($_POST['demand_intercept']/$_POST['demand_slope'])-1;
			break;
		case 'oligopoly':
			$max=(($_POST['demand_intercept']/$_POST['demand_slope'])/2)-1;
			break;
		case 'monopolistic':
		case 'perfect':
			$max=500;
			break;
	}
	*/

	// check if game already exists, if so, update rather than create new
	if( strlen($_POST['gameId']) ){
		$QW_DAO->updateGame($_POST['gameId'],
							$_POST['gameName'],
							$_POST['difficultyName'],
							//$_POST['modeID'],
							$_POST['marketStructureName'],
							$_POST['macroEconomyName'],
							$_POST['isReplayable'],
							$_POST['timeLimit'],
							$_POST['numRounds'],
							$_POST['demandIntercept'],
							$_POST['demandSlope'],
							$_POST['fixedCost'],
							$_POST['unitCost']/*,
							$max*/);
		// $sql = "UPDATE Games SET name='".mysqli_real_escape_string($mysqli, $_POST['gameName'])."', difficulty='".$_POST['difficulty']."', mode='".$_POST['mode']."', market_struct='".$_POST['market_struct']."', macro_econ='".$_POST['macroEconomy']."', time_limit='".$_POST['limit']."', num_rounds='".$_POST['numRounds']."', demand_intercept='".$_POST['demand_intercept']."', demand_slope='".$_POST['demand_slope']."', fixed_cost='".$_POST['fixed_cost']."', unit_cost='".$_POST['unit_cost']."', max_quantity='".$max."' WHERE course_id=".$_POST['course_id'];
		// if ($mysqli->query($sql) === TRUE)
		// 	header("Location: ../../src/admin_page.php?game=".$_POST['gameId']);
		// else
		//     echo "Error: " . $sql . "<br>" . $mysqli->error;
	}
	else {
		//$QW_DAO->addGame($_POST['gameName'],$_POST['difficulty'],$_POST['mode'],$_POST['market_struct'],$_POST['macroEconomy'],$_POST['limit'],$_POST['numRounds'],$_POST['demand_intercept'],$max);
		$QW_DAO->addGame($_POST['gameName'],
						 $_POST['gameTypeName'],
						 $_POST['courseId'],
						 $_POST['difficultyName'],
						 //$_POST['mode'],
						 $_POST['marketStructureName'],
						 $_POST['macroEconomyName'],
						 $_POST['isReplayable'],
						 $_POST['timeLimit'],
						 $_POST['numRounds'],
						 $_POST['demandIntercept'],
					 	 $_POST['demandSlope'],
					 	 $_POST['fixedCost'],
					 	 $_POST['unitCost']/*,
					 	 $max*/);

		echo "Added game";
		// $sql = "INSERT INTO Games (name, type, course_id, difficulty, mode, market_struct, macro_econ, time_limit, num_rounds, demand_intercept, demand_slope, fixed_cost, unit_cost, max_quantity)
		// VALUES ('".mysqli_real_escape_string($mysqli, $_POST['gameName'])."', '".$_POST['type']."', '".$_POST['course_id']."', '".$_POST['difficulty']."', '".$_POST['mode']."', '".$_POST['market_struct']."', '".$_POST['macroEconomy']."', '".$_POST['limit']."', '".$_POST['numRounds']."', '".$_POST['demand_intercept']."', '".$_POST['demand_slope']."', '".$_POST['fixed_cost']."', '".$_POST['unit_cost']."', '".$max."')";
		// if ($mysqli->query($sql) === TRUE)
		// 	header("Location: ../../src/admin_page.php?course=".$_POST['course_id']);
		// else
		//     echo "Error: " . $sql . "<br>" . $mysqli->error;

	}
	header("Location: " . addSession("../admin_page.php?course=".$_POST['courseId']));
}

// Delete game
else if ($_POST['action'] == 'deleteGame'){// && isset($_POST['course_id'])) {
	$QW_DAO->deleteGame($_POST['gameId']);
	//header("Location: " . addSession("../admin_page.php?course=".$_POST['course_id']));

	// $delete_sql = "DELETE FROM Games WHERE id='".$_POST['deleteId']."'";

	// if ($mysqli->query($delete_sql) === TRUE)
	// 	header("Location: ../../econ_sim/src/admin_page.php?course=".$_POST['deletedGameCourse']);
	// else
	//     echo "Error: " . $delete_sql . "<br>" . $mysqli->error;
	header("Location: " . addSession("../admin_page.php?course=".$_POST['courseId']));
}
else if ($_POST['action']=='getInitPriceHistory') {
	echo $QW_DAO->getInitPriceHist($_POST['gameId']);
	// $result = $mysqli->query('SELECT price_hist FROM Games WHERE id="'.$_POST["id"].'"');
	// echo $result->fetch_assoc()['price_hist'];
}

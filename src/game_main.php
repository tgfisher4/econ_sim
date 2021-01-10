<?php
/* game_main.php

- Game play for monopoly and oligopoly

On monopoly mode, a user enters directly into game and can start playing. There is one descision to be made, that being output quanity. Once submission is made, an overview of the past year's data is shown in a table along with a chart tracking the quanitity history. The user can then change views using the slide over menue to view more detailed info of different types. The slide over menue can also resummon the instructions modal.

On oligopoly mode, a waiting overlay is shown on top of the game screen preventing the user from starting until being matched with another player. When student submits, screen will wait to allow another submission unitl opponent has submitted as well. If one user quits, the other user is booted out to student.php and a message is displayed.

Last Update: Updated socket.io to Tsugi Websockets
*/

//include 'utils/sql_settup.php';
require_once "../tsugi_config.php";
require_once "../dao/QW_DAO.php";

use \Tsugi\Core\LTIX;
use Tsugi\Core\WebSocket;
use \QW\DAO\QW_DAO;

$LAUNCH = LTIX::session_start();

$QW_DAO = new QW_DAO($PDOX, $CFG->dbprefix, "econ_sim_");

// Render view
$OUTPUT->header();

// get the current games set up info
$gameInfo = $QW_DAO->getGameInfo($_GET['game']);
$startGame = true;

// if multi mode (oligopoly) do not immediately start game - must wait to be matched with another player
if ($gameInfo['market_structure_name'] == 'oligopoly') $startGame = false;

$isMultiplayer = $gameInfo['market_structure_name'] == 'oligopoly';
$timestamp = time();

?>

<!doctype html>
<html class="no-js" lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Econ Sims</title>
    <link rel="stylesheet" href="../css/foundation.css">
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
   	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" />
   	<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/themes/default.min.css"/>
    <!-- absolute dependencies as seen in perfect_game -->
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
    <script src="../node_modules/chartjs-plugin-annotation/chartjs-plugin-annotation.js"></script>
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.6.1/js/foundation.min.js" integrity="sha256-tdB5sxJ03S1jbwztV7NCvgqvMlVEvtcoJlgf62X49iM=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/what-input/5.2.6/what-input.min.js" integrity="sha256-yJJHNtgDvBsIwTM+t18nNnp9rEXdyZ1knji5sqm4mNw=" crossorigin="anonymous"></script>
	<script src="../js/utils/gen_utils.js?t=<?=$timestamp?>"></script>
	<script src="../js/utils/econ_utils.js?t=<?=$timestamp?>"></script>
    <!--This func wouldn't work in safari unless it was up here -->
    <!--Working fine in Safari for me, but I'll keep it here, commented, just in case -->
    <!--script type="text/javascript">
        //
        const gameOptionsUnderscores = { <?=
        			join(",\n\t\t\t\t\t\t\t  ", array_map(function ($key, $value) { return $key . ":" . '"'.$value.'"'; }, array_keys($gameInfo), array_values($gameInfo)));
        			?>};
        const gameOptions = objMapKeys(snakeToCamel, gameOptionsUnderscores);
        const maximumQuantity = calculateMaxQuantity(gameOptions);
        let year = 1;

    	// Func for off canvas menu to change screen content
    	function change_content(to_section) {
    		if (to_section == 'instructions') {
    			$('#beginModal').foundation('open');
    			return;
    		}

    		const headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data"};
    		const elements = document.getElementsByClassName("display_sections");

    		if ( year > 1 ) { // make sure a submit has occured before being able to switch to info sections
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

	    		for (var i = elements.length - 1; i >= 0; i--) {
	    			elements[i].style.display = "none";
	    		}

	    		document.getElementById(to_section).style.display = "";
	    		$('#dynamicHeader').text(headers[to_section]);

	    		drawGraph(to_section);
	    	}
	    	else { // if no submit yet, show message
		  		alertify.set('notifier','delay', 3);
				alertify.set('notifier','position', 'top-right');
				alertify.warning(`<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle"></i><br>Please enter valid quantity!<br>(1- ${maximumQuantity} units)</p>`);
	    	}
    	}
    </script-->
  </head>
  <body style="background-color: #d3f6ff;">

	<div class="off-canvas position-right" id="offCanvas" data-off-canvas data-transition="overlap" style="background-color: #121212">

		<!-- Menu -->
		<ul class="vertical menu darken" style="color: white">
		  <li style="color: #8a8a8a; background: #2c2c2c; font-size: 0.9em; padding: 10px; margin-bottom: 10px">Menu</li>
		  <li><a onclick="change_content('dashboard_section')">Dashboard</a></li>
		  <li><a onclick="change_content('income_section')">Income Statement</a></li>
		  <li><a onclick="change_content('cost_section')">Cost Data</a></li>
		  <li><a onclick="change_content('instructions')">Instructions</a></li>
		</ul>

		<ul class="vertical menu" style="position: absolute; bottom: 0; width: 100%; margin-bottom: 25px">
			<li>
				<button onclick="leaveGame()" class="alert button" style="width: 125px; margin-left: auto; margin-right: auto;"><strong>Exit Game</strong></i></button>
			</li>
		</ul>
	</div>

	<!-- Multiplayer wait screen -->
	<div id="multWaitScreen" style="display: <?= !$startGame ? '' : 'none'?>; z-index: 100; height: 100%; position: fixed; top: 0; left: 0; width: 100%; ">
		<div style="background-color: white; height: 400px; width: 600px; margin: 150px auto; border-radius: 5px;">
			<button class="button" style="float: left; background-color: #cc4b37; margin: 10px; border-radius: 2px;" onclick="leaveGame();">
				<i class="fas fa-times"></i> Cancel
			</button>
			<i class="fas fa-spinner fa-3x fa-pulse" style="display: block; margin: auto; width: 49px; height: 49px; position: relative; top: 41%"></i>
			<h3 style="margin: auto; width: 276px; height: 50px; position: relative; top: 45%">Finding Opponent...</h3>
		</div>
	</div>

	<div class="off-canvas-content" data-off-canvas-content style="filter: <?= !$startGame ? 'blur(10px) brightness(0.8);' : 'none'?>">
	<!-- Your page content lives here -->
		<!-- title bar -->
		<div class="title-bar">
		  <div class="title-bar-left">
		  	<div class="media-object" style="float: left;">
			    <div class="thumbnail" style="margin: 0; border: none; background: none;">
			      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
			    </div>
			</div>
		    <span class="title-bar-title">
		    	<h3 style="margin: 30px 0 0 30px; font-weight: 500"><?= $gameInfo['name'] ?></h3>
		    	<h6 id="oppNameDisplay" style="margin-left: 30px;">
		    		Opponent: <span class="oppName"></span>
		    	</h6>
		    </span>
		  </div>
		  <div class="title-bar-right">
		  	<div style="margin-right: 50px;">
			  	<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
		  		<p style="padding-top: 10px; padding-right: 50px">Logged in as: <?= $USER->displayname ?></p>
		    </div>
		    <button class="menu-icon" type="button" data-open="offCanvas"></button>
		  </div>
		</div>
		<!-- end title bar -->

		<!-- Toolbar -->
		<div id="topToolbar">
			<h3 id="dynamicHeader" style="padding-top: 25px; padding-left: 25px; margin-bottom: 0; font-weight: 350; font-weight: 600;">Dashboard</h3>
			<div style="width: 100%; height: 60px; position: absolute; bottom: 0">
				<div class="grid-x" style="margin-left: 25px">
					<div class="cell large-1 "><p id="year"><b>Year: </b><span class="currentYearSpan">1</span></p></div>
					<div class="cell large-4">
						<p style="float: left; padding-right: 10px"><b>Timer: </b></p>
						<div id="progressContainer" class="success progress" aria-valuemax="100" style="margin-top: 7px;">
						  <span id="progressBar" class="progress-meter" style="width: 100%;">
						  	<p id="timer" class="progress-meter-text" data-length="<?= $gameInfo['time_limit'] ?>"><?= $gameInfo['time_limit'] ?>:00</p>
						  </span>
						</div>
					</div>
					<div class="cell large-6">
						<p style="float: left; padding-right: 20px"><b>Quantity: </b></p>
						<input type="number" id="quantityInput" style="width: 125px; float: left;" min="1" max="500" placeholder="1 - X Units">
						<button class="button" type="button" id="submitQuantityButton" onclick="submitQuantityButtonCallback($('#quantityInput').val())" style="margin-left: 20px; font-weight: 500; float: left;">Submit</i></button>
						<span id="waitOppSub" style="display: inherit; text-decoration: none; border: none; outline: none;" data-tooltip tabindex="1" title="Waiting for opponent to submit quantity">
							<i class="fas fa-spinner fa-pulse fa-2x"></i>
						</span>
					</div>
				</div>
			</div>
		</div>
		<!--  End toolbar -->

		<input type="hidden" id="game_id" value="<?=$_GET['game']?>">
		<input type="hidden" id="session_id" value="<?=$_GET['session_id']?>">
		<input type="hidden" id="usrname" value="<?=$USER->email?>">
		<input type="hidden" id="opponent" value="">

		<div id="mainContent">

			<!-- before first submission prompt -->
			<div id="preStartPrompt" style="width: 500px; margin: 280px auto 30px auto;">
				<h3 style="text-align: center; color: #bdbebf">
					<i class="far fa-play-circle fa-5x"></i><br>
					<strong style="font-weight: 500;">Enter Quantity to Begin!</strong>
				</h3>
			</div>

			<!-- Dashboard -->
			<div class="display_sections" id="dashboard_section">
				<!--div class="section_content" id="summarySection" style="display: none;"-->
				<div class="section_content" id="summarySection">
					<div class="section_cell" style="float: left;">
						<h4 id="summaryHeader" style="text-align: center; font-weight: 450">Summary for Year <span class="previousYearSpan">1</span></h4>
						<hr style="margin-bottom: 30px">
						<table id="summaryTable" class="paleBlueRows">
							<tbody>
								<tr>
									<td>Market Price</td>
									<td><span id="marketPrice"></span></td>
								</tr>
								<tr>
									<td>Production Output</td>
									<td><span id="quantityDisplay"></span></td>
								</tr>
								<tr>
									<td>Average Cost</td>
									<td><span id="avgCost"></span></td>
								</tr>
								<tr>
									<td>Total Cost</td>
									<td><span id="totalCost"></span></p></td>
								</tr>
								<tr>
									<td>Revenue</td>
									<td><span id="revenue"></span></td>
								</tr>
								<tr>
									<td>Profit</td>
									<td><span id="profit"></span></p></td>
								</tr>
								<tr>
									<td><b>Cumulative Earnings</b></td>
									<td><b><span id="cumulativeProfit"></span></b></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- --------- -->

			<!-- Income Statement -->
			<div class="display_sections" id="income_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span class="previousYearSpan">1</span> Overview</h4>
							<hr style="margin-bottom: 20px">
							<table class="paleBlueRows">
								<tbody>
									<?= $isMultiplayer ? '<tr><td> </td><td><b>You</b></td><td><b><span class="oppName"></span></b> (opponent)</td></tr>':''?>
									<tr>
										<td>Price</td>
										<td <?= $isMultiplayer ? 'colspan="2"' : ''?>id="liPrice"></td>
									</tr>
									<tr>
										<td>Revenue</td>
										<td id="liRevenue"></td>
										<?= $isMultiplayer ? '<td id="liRevenueOpp"></td>' : ''?>
									</tr>
									<tr>
										<td>Net Profit</td>
										<td id="liNet"></td>
										<?= $isMultiplayer ? '<td id="liProfitOpp"></td>' : ''?>
									</tr>
									<tr>
										<td>Return on Sales</td>
										<td id="liReturn"></td>
										<?= $isMultiplayer ? '<td id="liReturnOpp"></td>' : ''?>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="margin-bottom: 50px; margin-top: -20px; text-align: center;">
							<i class="fas fa-angle-down fa-4x animated bounce" id="bouncingArrow"></i>
						</div>
					</div>
					<div id="animate0" class="section_cell" style="width: 500px; margin: 0 auto 50px auto;">
						<h3 style="text-align: center;"><strong>Historical Info</strong></h3>
					</div>
					<div id="animate1" class="section_cell cell_graph" style="float: left;">
						<h4 style="text-align: center; font-weight: 450">Annual Income</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="incomeChart" ></canvas>
						</div>
					</div>
					<div id="animate2" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Cumulative Earnings</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="cumulativeChart"></canvas>
						</div>
					</div>
					<div id="animate3" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Price</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="priceChart"></canvas>
						</div>
					</div>
					<div id="animate4" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart2"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- ---------------- -->

			<!-- Cost Data -->
			<div class="display_sections" id="cost_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span class="previousYearSpan">1</span> Overview</h4>
							<hr style="margin-bottom: 20px">
							<table class="paleBlueRows">
								<tbody>
									<tr>
										<td>Shipments</td>
										<td id="liSales"></td>
									</tr>
									<tr>
										<td>Price</td>
										<td id="liPrice2"></td>
									</tr>
									<tr>
										<td>Average Cost</td>
										<td id="liAvgCost"></td>
									</tr>
									<tr>
										<td>Production Cost</td>
										<td id="liTotalCost"></td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="margin-bottom: 50px; margin-top: -20px; text-align: center;">
							<i class="fas fa-angle-down fa-4x animated bounce" id="bouncingArrow"></i>
						</div>
					</div>
					<div id="animate0b" class="section_cell" style="width: 500px; margin: 0 auto 50px auto;">
						<h3 style="text-align: center;"><strong>Historical Info</strong></h3>
					</div>
					<div id="animate1b" class="section_cell cell_graph" style="float: left;">
						<h4 style="text-align: center; font-weight: 450">Production Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="costChart"></canvas>
						</div>
					</div>
					<div id="animate2b" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Marginal Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="marginalChart"></canvas>
						</div>
					</div>
					<div id="animate3b" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Average Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="avgTotalChart"></canvas>
						</div>
					</div>
					<div id="animate4b" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart3"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- --------- -->
		</div>
	</div>

	<!-- MODALS -->
	<!-- begining of game instructions -->
	<div class="reveal" id="beginModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: left;"><strong>Instructions</strong></h2>
		<?php if ($gameInfo['market_structure_name'] == 'oligopoly') { ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a oligopolistic market environment. Your goal is to determine output levels in this strategically interactive environment in order to maximize profit.</p>
			<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe previous prices and choose a quantity to sell in the next period. Since you are one of two firms selling in this market, your choice, along with your competitor’s choice will determine your profits each round.</p>
			<p>At the end of the simulation, cumulative profits will be measured and graded against a hypothetical firm acting optimally.</p>
		<?php } else if ($gameInfo['market_structure_name'] == 'monopoly'){ ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a monopolistic market environment. Your goal is to determine output levels in order to profit maximize.</p>
			<p>Each period you will observe previous prices and choose a quantity to sell in the next period.  Since you are the only firm selling in this market, there is no industry market research to consult.</p>
			<p>At the end of the simulation, cumulative profits will be measured and grading against a hypothetical firm acting optimally.</p>
		<?php }
            else {?> <p>Something went wrong! Market structure <?= $gameInfo['market_structure_name'] ?></p> <?php } ?>
		<button class="close-button" data-close aria-label="Close reveal" onclick="closeStartScreen()" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
    <div class="reveal" id="instructionsModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: left;"><strong>Instructions</strong></h2>
		<?php if ($gameInfo['market_structure_name'] == 'oligopoly') { ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a oligopolistic market environment. Your goal is to determine output levels in this strategically interactive environment in order to maximize profit.</p>
			<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe previous prices and choose a quantity to sell in the next period. Since you are one of two firms selling in this market, your choice, along with your competitor’s choice will determine your profits each round.</p>
			<p>At the end of the simulation, cumulative profits will be measured and graded against a hypothetical firm acting optimally.</p>
		<?php } else if ($gameInfo['market_structure_name'] == 'monopoly'){ ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a monopolistic market environment. Your goal is to determine output levels in order to profit maximize.</p>
			<p>Each period you will observe previous prices and choose a quantity to sell in the next period.  Since you are the only firm selling in this market, there is no industry market research to consult.</p>
			<p>At the end of the simulation, cumulative profits will be measured and grading against a hypothetical firm acting optimally.</p>
		<?php }
            else {?> <p>Something went wrong! Market structure <?= $gameInfo['market_structure_name'] ?></p> <?php } ?>
		<!--button class="close-button" data-close aria-label="Close reveal" onclick="closeModal('instructionsModal')" type="button"-->
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<!-- end game -->
	<div class="reveal" id="endModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: center;"><strong>Game Over!</strong></h2>
		<p style="text-align: center;">(Dismiss to view final results)</p>
		<!-- <button class="close-button" data-close aria-label="Close reveal" type="button" onclick="closeModal('endModal')"> -->
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
    <!-- non-replayable -->
    <div class="reveal" id="alreadyPlayedModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: center;"><strong>Sorry, you've already played this game</strong></h2>
		<p style="text-align: center;">This game may not be replayed and you have completed it already. Dismiss to view your game results.</p>
		<!-- <button class="close-button" data-close aria-label="Close reveal" type="button" onclick="closeModal('endModal')"> -->
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<!-- modal end -->

	<!-- Hidden inputs containing values from game setup (used by python script for calculations) -->
	<input id="dIntr" type="hidden" value="<?=$gameInfo['demand_intercept']?>">
	<input id="dSlope" type="hidden" value="<?=$gameInfo['demand_slope']?>">
	<input id="cCost" type="hidden" value="<?=$gameInfo['unit_cost']?>">
	<input id="fCost" type="hidden" value="<?=$gameInfo['fixed_cost']?>">
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">

	<!-- Bottom bar -->
	<footer class="footer" style="filter: <?= !$startGame ? 'blur(10px) brightness(0.7);' : 'none'?>"></footer>

	<?php
		$OUTPUT->footerStart();
	?>

    <!--script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script-->

    <!--script src="../js/app.js?t=<?=$timestamp?>"></script-->
    <script type="text/javascript">

        /* =============== */
        /* === GLOBALS === */
        /* =============== */

        const gameOptionsUnderscores = { <?=
                			join(",\n\t\t\t\t\t\t\t  ", array_map(function ($key, $value) { return $key . ":" . '"'.$value.'"'; }, array_keys($gameInfo), array_values($gameInfo)));
                			?>};
        const gameOptions = objMapValues( v => parseInt(v) || v, objMapKeys(snakeToCamel, gameOptionsUnderscores));
        const maximumQuantity = calculateMaxQuantity(gameOptions);
        let year = 1;

        const socketEventIdToEventName =    {   0 : 'opponent exit',
                                                1 : 'opponent ready',
                                                2 : 'opponent submission',
                                                3 : 'student submission'};

    	let   isGameComplete = false;
        const isMultiplayer = gameOptions['marketStructureName'] == "oligopoly";

        const numInitSummaryTableRows = $('#summaryTable tr').length;

        const instructorSocket  = tsugiNotifySocket(gameOptions['gameId']);
        let oppSocket; // cannot open socket without session id, which is not known until we have joined a game

        // for debugging/socket understanding
        let   oppSocketWasOpened = false;
        let   instructorSocketWasOpened = false;

        if( instructorSocket ){
            instructorSocket.onopen = function(evt){ console.log("instructor socket opened"); instructorSocketWasOpened = true; }
            instructorSocket.onclose = function(evt){ console.log("instructor socket " + (instructorSocketWasOpened ? "closed" : "failed to open"))}
        }
        else {
            console.log("no socket configured (instructor)");
        }

        const myUserId = <?= $USER->id ?>;
        const myUsername = "<?= $USER->displayname ?>";
        let oppUserId;
        let oppName;

        const myData = {};
        const opponentData = {};
        // price is a session, rather than player, level property
        //  - not stored as data of one or both players
        const priceHistory = [];

        let timerSecondsRemaining = gameOptions['timeLimit'] * 60;
        const timeLimitInSeconds = timerSecondsRemaining; // for resetting timer and calculatin timer bar percentage width
        let timerIntervalId = null;

        /* ======================== */
        /* === SIMULATION SETUP === */
        /* ======================== */

        // having weird foundation bugs: can't close instruction or end game modals via the 'x'
        // previously in app.js, moved here to try to get around weird Foundation bugs
        Foundation.addToJquery($);
        jQuery(document).foundation();
        $('#beginModal').foundation();
        $('#instructionsModal').foundation();
        $('#endModal').foundation();

        /* hide some DOM elements, update others */
        $('#waitOppSub').hide();
        $('#summarySection').hide();
        $('#oppNameDisplay').hide();
        $("#quantityInput").attr('placeholder', `1 - ${maximumQuantity} Units`);

    	window.onbeforeunload = leaveGame;
        /* function () {
    	    leaveGame();
    	}; */

        /* join game */
        $.ajax({
            url: '<?= addSession("utils/session.php") ?>',
            method: 'POST',
            data: {
                action: 'joinGame',
                gameId: gameOptions['gameId'],
                playerId: myUserId,
                marketStructureName: gameOptions['marketStructureName']
            },
            success: function(responseJson){
                console.log(responseJson);
                responseObj = JSON.parse(responseJson);
                mySessionId = responseObj['sessionId'];

                if( isMultiplayer ){
                    oppSocket = tsugiNotifySocket(mySessionId);
                    if( oppSocket ){
                        // debugging info
                        oppSocket.onopen  = function(evt){ console.log("opponent socket opened"); oppSocketWasOpened = true; }
                        oppSocket.onclose = function(evt){ console.log("opponent socket " + (oppSocketWasOpened ? "closed" : "failed to open"))}
                    }
                    else {
                        console.log("no socket configured (opp)");
                        if( isMultiplayer ){
                            // alert player that we need access to Tsugi's socket functionality to play multiplayer: probably tell them to contact Tsugi admin
                        }
                    }
                    oppSocket.onmessage = oppSocketMessageCallback;

                    if( responseObj['result'] == 'ready' ){
                        startGameScreen();
                        console.log("attempting to send socket message 'ready'");
                        saveOppName(responseObj['yourOppName']);
                        socketSendSafe(oppSocket, JSON.stringify({'eventId':1, 'yourOppName': myUsername}));
                    }
                }
                else {
                    switch(responseObj['result']){
                        case "new":
                            startGameScreen();
                            break;
                        case "rejoined":
                        case "completed":
                        // both rejoined and completed feed here

                            // back-process data
                            let myYearStats = null;
                            for( const q of (responseObj['quantityHistory'].slice(0, gameOptions['numRounds'])
                                             ? responseObj['quantityHistory'].split(',')
                                             : []) ){
                                myYearStats = calculateYearStats(q);
                                updatePlayerDataAnnual(myYearStats);
                            }
                            year = priceHistory.length + 1;
                            updateYearDisplays(year - 1);
                            if( myYearStats ){ // checks if quantity was indeed previous submitted ( could rejoin after leaving with 0 submissions)
                                updateInfoScreensMyData(formatYearStats(myYearStats));
                                $("#preStartPrompt").hide();
                                $("#summarySection").show();
                            }

                            if( responseObj['result'] == "completed" )  openModal('alreadyPlayedModal');
                            else                                        startGameScreen();
                            break;
                        default:
                            // error
                            console.error(`reached default for singleplayer join game response result string - result: ${result}`);
                            break;
                    }
                }
            }
        })

        /* === FUNCTIONS === */

        /*
        // previous idea: register socket callbacks - that way, you can attach various callbacks at different points
        function registerOppSocketCallback(callback, args=null){
            oppSocket.onmessage = function(evt){
                console.log("received message with event: ");
                console.log(event);
                if (evt.data = "exit") {
                    const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
                    window.location = urlPrefix+'src/student.php?session=err2';
                }
                else {
                    // abusing the fact that javascript functions are stupid and do not reject calls with don't match their signature
                    //  - doesn't matter if you pass a function too many args
                    callback.apply([evt.data].concat(args || []));
                }
            }

            switch(socketEventIdToEventName[])
        }
        */

        /* --- foundation hack since modals were not opening and closing as expected --- */
        function openModal(modalId){
            (new Foundation.Reveal($(`#${modalId}`))).open();
    		//$(`#${modalId}`).foundation('open');
        }

        function closeModal(modalId){
            //(new Foundation.Reveal($(`#${modalId}`))).close();
            $(`#${modalId}`).foundation('close');
        }
        /* ----------------------------------------------------------------------------- */

        function startGameScreen(){
            openModal('beginModal');
            dismissWaitScreen();
            if( isMultiplayer )     $('oppNameDisplay').show();
        }

        function closeStartScreen(){
            closeModal('beginModal');
            startTimer();
        }

    	// hide the multiplayer "waiting for opponent" overlay, allowing gameplay to start
    	function dismissWaitScreen() {
    		$('#multWaitScreen').css('display','none');
    		$('.off-canvas-content').css('filter','none');
    		$('.footer').css('filter','none');
    	}

    	function leaveGame() {
    		const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
      		// if its a multi game, notify opponent
            if( isMultiplayer )
                socketSendSafe(oppSocket, JSON.stringify({'eventId':0}));

      		// exit to student.php
      		if (!isGameComplete)
      			window.location = urlPrefix + '<?= addSession("src/student.php?session=left") ?>';
      		else
      			window.location = urlPrefix + '<?= addSession("src/student.php?session=comp") ?>';
    	}

        function saveOppName(oppNameLocal){
            oppName = oppNameLocal;
            $(".oppName").text(oppNameLocal);
            $('#oppNameDisplay').show();
        }

        function oppSocketMessageCallback(evt){
            console.log(evt);
            const evtObj = JSON.parse(evt.data);
            // used string comparisons here to make this code more readable
            //  - however, integer comparisons would be more time and space efficient
            //  - if we optimizations become necessary later, this is an easy one
            switch(socketEventIdToEventName[evtObj['eventId']]){
                case 'opponent exit':
                    const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
                    window.location = addSession(urlPrefix+'src/student.php?session=err2');
                    break;
                case 'opponent ready':
                    saveOppName(evtObj['yourOppName']);
                    startGameScreen();
                    break;
                case 'opponent submission':
                    processRoundData(evtObj['yourQuantity'], evtObj['yourOppQuantity']);
                    prepareForNextSubmission(year);
                    break;
                default:
                    console.error(`Reached default in oppSocketMessageCallback - event: ${evt}`)
                    break;
            }
        }

        function socketSendSafe(socket, message){
            console.log(`attempting to send message ${message}`);
            if( !socket )                               console.error(`[FATAL ERROR] Sockets are unavailable. Likely causes:\n\t- websockets not enabled on this Tsugi server\n\t- websocket server not started\nSocket ${socket.url.match(/room=(\d+)/)[1]} could not be opened`)
            else if( socket.readyState == socket.OPEN ) socket.send(message);
            else                                        socket.onopen = function(evt){ console.log(`socket ${socket.url.match(/room=(\d+)/)[1]} opened`); socket.send(message); }
        }

        function pingInstructor(){
            socketSendSafe(instructorSocket, JSON.stringify({'eventId' : 3}));
        }

    	function tickTimer() {
            --timerSecondsRemaining;

    	    if (timerSecondsRemaining == 0) { // if time runs out notify user. submit quanitity. restart timer
                const quantitySubmitted = $("#quantityInput").val() || "1";

    	        $('#quantityInput').val(quantitySubmitted);

        	    alertify.set('notifier','delay', 3);
        		alertify.set('notifier','position', 'top-right');
        		alertify.error(`<i class="fas fa-exclamation-circle"></i><br><strong>Year: ${year} - Time\'s Up!</strong><br>${quantitySubmitted} was submitted.`);

        	  	submitResponse(parseInt(quantitySubmitted));
            }

        	const minute = Math.floor((timerSecondsRemaining)/60);
        	const seconds = timerSecondsRemaining - (minute*60);

        	// show progress bar visualizing time left
        	const percent = ((timerSecondsRemaining/timeLimitInSeconds)*100).toPrecision(3);
        	//if      (percent <= 25)    $('#progressBar').attr('class', 'progress-danger progress');
        	if (percent <= 25)         $('#progressContainer').attr('class', 'progress-danger progress'); //$('#progressContainer').attr('class', 'progress');
        	else if (percent <= 50)    $('#progressContainer').attr('class', 'warning progress');
        	$('#progressBar').css("width", percent+"%");

        	$("#timer").text( `${minute}:${(seconds < 10 ? `0${seconds}` : seconds)}` );
    	}

    	// Submit button click callback
        function submitQuantityButtonCallback(quantity) {
            /* get ball rolling upon quantity submission: validates input, then passes to submitResponse */
            const quantityInt = parseInt(quantity);
            //const quantityInt = quantity;
      		// check validity
      		if( quantityInt >= 1 && quantityInt <= maximumQuantity ){
    	  		submitResponse(quantityInt);
    	  	}
            // reject invalid submissions
            else { // If user hasn't entered quantity or entered invalid quantity, button will shake and show message
    	  		alertify.set('notifier','delay', 3);
    			alertify.set('notifier','position', 'top-right');
    			alertify.error(`<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle" style=""></i><br>Please enter valid quantity!<br>(1-${maximumQuantity} units)</p>`);
    	  		$('#submitQuantityButton').addClass('animated shake').one('webkitAnimationEnd mozAnimationEnd', function() {
        			$(this).removeClass('animated shake');
        		});
    	    }
        }

    	function submitResponse(quantity) {
            /* high level response to a submittion:
                - disable and later re-enable submit button
                - hide/show DOM elements as needed
                - stop and later reset and start timer
                - call data processing functions    */

            // disable submit button until finished processing
    	  	$('#submitQuantityButton').prop('disabled', true); // disable submit button so it isn't pressed twice for same year
            stopTimer();

            // use promise-like feature of jQuery's ajax (deferred value)
            recordSubmissionInDb(quantity)
                .done(response => {
                    pingInstructor();
                    console.log(response);
                    if( !isMultiplayer )    return; // nothing to do here
                    if( response ){
                        oppQuantity = parseInt(response);
                        socketSendSafe(oppSocket, JSON.stringify({'eventId' : 2, 'yourQuantity': oppQuantity, 'yourOppQuantity' : quantity}));
                        processRoundData(quantity, oppQuantity);
                        prepareForNextSubmission(year);
                    }
                    else{
                        // block until other player submits
                        $('#waitOppSub').show();
                    }
                })
                .fail(e => console.log(e));

            // process results (add to data objects, update summary screens)
    	  	// in singleplayer market, can go ahead don't need to wait for DB response
            //  - can go ahead and start processing data
            //  - may be executed while waiting for DB call promise
    	    if( !isMultiplayer ){
    	    	// call function to get results from the year based on submission and update UI
                processRoundData(quantity, null);
                prepareForNextSubmission(year);
    		}
    	}

        function recordSubmissionInDb(quantity, isFinalSubmission){
            console.log('recording quantity');
            const postData = {  action      : 'recordSubmission',
                                sessionId   : mySessionId,
                                playerId    : myUserId,
                                quantity    : quantity,
                                marketStructureName   : gameOptions['marketStructureName'],
                                isFinalSubmission : isFinalSubmission ? 1 : 0  };
            console.log(postData);
            return $.ajax({
                url: '<?= addSession("utils/session.php") ?>',
                method: 'POST',
                data: postData
            });
        }

        function processRoundData(myQuantity, oppQuantity=null){
            // administrative function which coordinates all the data processing

            // for singleplayer game, expect oppQuantity to be null
            // which pattern is better: check whether oppQuantity is null here or call functions that may receive and return null?
            const myYearStats  = calculateYearStats(myQuantity,  oppQuantity ? [oppQuantity] : []);
            const oppYearStats = calculateYearStats(oppQuantity, [myQuantity]); // may be null

            updatePlayerDataAnnual(myYearStats, false);
            updatePlayerDataAnnual(oppYearStats, true);

            // maybe add option to updateInfoScreensMyData to not draw graphs at the end (currently graphs re-drawn at end of both functions following)
            updateInfoScreensMyData(formatYearStats(myYearStats));
            updateInfoScreensOppData(formatYearStats(oppYearStats));
        }

        // here, we use a data storage paradigm that allows myData and opponentData to be manipulated in the same way
        //  - each are global maps of stat names to values or arrays
        function calculateYearStats(myQuantity, oppQuantities=null){
            if( myQuantity === null ) return null;
            // prevent python problem of binding mutable default parameters
            marketPrice = calculatePrice((oppQuantities || []).concat(myQuantity), gameOptions['demandIntercept'], gameOptions['demandSlope']);
            totalCost   = calculateTotalCost(myQuantity, gameOptions['fixedCost'], gameOptions['unitCost']);

            // derive basic results
            revenue  = marketPrice * myQuantity;
            profit   = revenue - totalCost;
            avgCost  = totalCost / myQuantity;
            returnOnInvestment = profit / totalCost * 100;

            myYearStats = {   'quantity'     : myQuantity,
                              'marketPrice'  : marketPrice,
                              'totalCost'    : totalCost,
                              'revenue'      : revenue,
                              'profit'       : profit,
                              'avgCost'      : avgCost,
                              'returnOnInvestment' : returnOnInvestment     };

            return myYearStats;
        }

        function updatePlayerDataAnnual(yearStats, isOpp=false){
            if( yearStats === null) return;
            toUpdate = isOpp ? opponentData : myData;

            ['profit', 'revenue', 'totalCost', 'avgCost', 'quantity'].forEach(stat =>
                toUpdate[stat+'History'] = safeObjAcc(toUpdate, stat+'History', []).concat(yearStats[stat])
            );

            // price history doesn't belong to one player, but we have to update it at some points
            //  - here, I attempt to safeguard against pushing price data twice in a year
            if( priceHistory.length < toUpdate['quantityHistory'].length )
                priceHistory.push(yearStats['marketPrice']);
        }

        function formatYearStats(yearStats){
            if( yearStats === null) return null;
            console.log(`formatYear: ${year}`);
            console.log(yearStats);
            formattedSummaryStats = {   'marketPrice'       : formatDollarAmount(yearStats['marketPrice']),
                                        'quantity'          : yearStats['quantity'].toString(),
                                        'revenue'           : formatDollarAmount(yearStats['revenue']),
                                        'profit'            : formatDollarAmount(yearStats['profit']),
                                        'cumulativeProfit'  : formatDollarAmount(sumArr(myData['profitHistory'])),
                                        'totalCost'         : formatDollarAmount(yearStats['totalCost']),
                                        'avgCost'           : formatDollarAmount(yearStats['avgCost']),
                                        'returnOnInvestment': yearStats['returnOnInvestment'].toPrecision(4)+"%"         };
            return formattedSummaryStats;
        }

        function updateInfoScreensMyData(formattedSummaryResults){
            // set summary screen info
            $("#marketPrice").text(formattedSummaryResults['marketPrice']);
            $("#quantityDisplay").text(formattedSummaryResults['quantity'] + " Units");
            $("#revenue").text(formattedSummaryResults['revenue']);
            $("#avgCost").text(formattedSummaryResults['avgCost']);
            $("#totalCost").text(formattedSummaryResults['totalCost']);
            $("#profit").text(formattedSummaryResults['profit']);
            $("#cumulativeProfit").text(formattedSummaryResults['cumulativeProfit']);

            // set income screen info
            $('#liRevenue').text(formattedSummaryResults['revenue']);
            $('#liNet').text(formattedSummaryResults['profit']);
            $('#liPrice').text(formattedSummaryResults['marketPrice']);
            $('#liReturn').text(formattedSummaryResults['returnOnInvestment']);

            // set cost screen info
            $('#liSales').text(formattedSummaryResults['quantity']+" Units");
            $('#liPrice2').text(formattedSummaryResults['marketPrice']);
            $('#liAvgCost').text(formattedSummaryResults['avgCost'] + " / Unit");
            $('#liTotalCost').text(formattedSummaryResults['totalCost']);

            // redraw graphs
            drawGraphs();
        }

        function updateInfoScreensOppData(oppFormattedSummaryResults){
            if( oppFormattedSummaryResults === null) return;

            // add a new row to display opponent quantity if we have not already
            if( $('#summaryTable tr').length == numInitSummaryTableRows /* year == 1 */){
                const oppRow = document.getElementById('summaryTable').insertRow(2);
                oppRow.insertCell(0).innerHTML = "Opponent Output";
                oppRow.insertCell(1).id = "oppQuantityDisplay";
            }
            $('#oppQuantityDisplay').text(oppFormattedSummaryResults['quantity']+" Units");

            // income screen
            $('#liRevenueOpp').text(oppFormattedSummaryResults['revenue']);
            $('#liProfitOpp').text(oppFormattedSummaryResults['profit']);
            $('#liReturnOpp').text(oppFormattedSummaryResults['returnOnInvestment']);

            drawGraphs(['dashboard_section', 'income_section']);
        }

        function updateYearDisplays(submissionYear){
            // Enable/update year displays
            $('.previousYearSpan').text(submissionYear);
            if( submissionYear != gameOptions['numRounds'] ){
                $('.currentYearSpan').text(submissionYear + 1);
            }
        }

        function prepareForNextSubmission(){
            isGameComplete = year == gameOptions['numRounds'];
            // on first submission, reveal summary section and hide pre-start prompt
            if( year == 1 ){
                $('#summarySection').show();
                $('#preStartPrompt').hide();
            }
            updateYearDisplays(year);
            $("#waitOppSub").hide();

            // if game is over, display ending modal and stop timer
            if( isGameComplete ) { // check if the game is over
                openModal('endModal');
                $(".progress-danger").addClass(".progress-danger").removeClass(".progress-off");
    	  	}
            else {
                year += 1;
                startTimer();
                $('#submitQuantityButton').prop('disabled', false); // re-enable the submit button
            }
        }

        function startTimer(){
            timerSecondsRemaining = 60 * gameOptions['timeLimit'];
        	$('#progressBar').css("width", "100%");
            $('#timer').text(`${gameOptions['timeLimit']}:00`);
    	  	$('#progressContainer').attr('class', 'success progress');
            timerIntervalId = setInterval(tickTimer, 1000);
        }

        function stopTimer(){ clearInterval(timerIntervalId); }

    	// callback to change screen content, triggered by off canvas menu
    	function change_content(to_section) {
    		if (to_section == 'instructions') {
                openModal('instructionsModal');
    			return;
    		}

    		const headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data"};

    		if ( year > 1 ) { // make sure a submit has occured before being able to switch to info sections
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

                $(".display_sections").hide();
                $(`#${to_section}`).show();
	    		$('#dynamicHeader').text(headers[to_section]);

	    		drawGraph(to_section);
	    	}
	    	else { // if no submit yet, show error message
		  		alertify.set('notifier','delay', 3);
				alertify.set('notifier','position', 'top-right');
				alertify.warning(`<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle"></i><br>Please enter valid quantity!<br>(1- ${maximumQuantity} units)</p>`);
	    	}
    	}


        function drawGraphs(graphNames=['dashboard_section', 'income_section', 'cost_section']){
            graphNames.forEach(g => drawGraph(g));
        }

        /* ============================ */
    	/* === Scrolling animations === */
        /* ============================ */

    	var animated = [false,false,false];
    	var animatedB = [false,false,false];
    	$(window).scroll(function() {
    	 	if(window.pageYOffset>55){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[0]) {
		    		$('#animate0').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animated[0]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[0]) {
		    		$('#animate0b').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animatedB[0]=true;
		    	}
		    }
		    if(window.pageYOffset>205){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[1]) {
		    		$('#animate1').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animated[1]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[1]) {
		    		$('#animate1b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2b').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedB[1]=true;
		    	}
		    }
		    if(window.pageYOffset>670){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[2]) {
		    		$('#animate3').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate4').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animated[2]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[2]) {
		    		$('#animate3b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		$('#animate4b').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedB[2]=true;
		    	}
		    }
		});

        /* ====================================== */
        /* === CHARTS SETUP AND CONFIGURATION === */
        /* ====================================== */

    	var graphLabels = ["Yr. 1", "Yr. 2", "Yr. 3", "Yr. 4", "Yr. 5", "Yr. 6", "Yr. 7", "Yr. 8", "Yr. 9", "Yr. 10", "Yr. 11", "Yr. 12", "Yr. 13",  "Yr. 14",  "Yr. 15",  "Yr. 16", "Yr. 17",  "Yr. 18",  "Yr. 19",  "Yr. 20",  "Yr. 21",  "Yr. 22",  "Yr. 23",  "Yr. 24",  "Yr. 25"];
    	graphLabels = graphLabels.slice(0, $('#numRounds').val());

    	function drawGraph(to_section) {
    		if (to_section == "income_section") {
    			var usrIncomeData = [{
			            label: 'Your Revenue ($)',
			            data: myData['revenueHistory'],
			            backgroundColor: 'rgba(0, 0, 230, 0.2)',
			            borderColor: 'rgba(0, 0, 230, 1)',
			            borderWidth: 1
			        },
			        {
			        	label: 'Your Profit ($)',
			            data: myData['profitHistory'],
			            backgroundColor: 'rgba(0, 153, 255, 0.2)',
			            borderColor: 'rgba(0, 153, 255, 1)',
			            borderWidth: 1
			        }];
			    var oppIncomeData = [{
			            label: oppName + '\'s Revenue ($)',
			            data: opponentData['revenueHistory'],
			            backgroundColor: 'rgba(179, 0, 0, 0.2)',
			            borderColor: 'rgba(179, 0, 0, 1)',
			            borderWidth: 1
			        },
			        {
			        	label: oppName+'\'s Profit ($)',
			            data: opponentData['profitHistory'],
			            backgroundColor: 'rgba(255, 128, 128, 0.2)',
			            borderColor: 'rgba(255, 128, 128, 1)',
			            borderWidth: 1
			        }];
			    var usrCumulativeData = [{
			            label: 'Your Revenue ($)',
			            data: cumulativeSumsArr(myData['revenueHistory'] || []),
			            backgroundColor: [
			                'rgba(0, 0, 230, 0.2)'
			            ],
			            borderColor: [
			                'rgba(0, 0, 230,1)'
			            ],
			            borderWidth: 1
			        },
			        {
			            label: 'Your Profit ($)',
			            data: cumulativeSumsArr(myData['profitHistory'] || []),
			            backgroundColor: [
			                'rgba(0, 153, 255, 0.2)'
			            ],
			            borderColor: [
			                'rgba(0, 153, 255,1)'
			            ],
			            borderWidth: 1
			        }];
			    var oppCumulativeData = [{
			            label: oppName+'\'s Revenue ($)',
			            data: cumulativeSumsArr(opponentData['revenueHistory'] || []),
			            backgroundColor: [
			                'rgba(179, 0, 0, 0.2)'
			            ],
			            borderColor: [
			                'rgba(179, 0, 0, 1)'
			            ],
			            borderWidth: 1
			        },
			        {
			            label: oppName+'\'s Profit ($)',
			            data: cumulativeSumsArr(opponentData['profitHistory'] || []),
			            backgroundColor: [
			                'rgba(255, 128, 128, 0.2)'
			            ],
			            borderColor: [
			                'rgba(255, 128, 128, 1)'
			            ],
			            borderWidth: 1
			        }];
			    var usrQuantityData = {
					label: 'Your Sales (Units)',
					data: myData['quantityHistory'] || [],
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				};
				var oppQuantityData = {
					label: oppName+"'s Sales (Units)",
					data: opponentData['quantityHistory'] || [],
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				};
			    if (!isMultiplayer ) {
					var displayIncomeData = {
						labels: graphLabels,
						datasets: usrIncomeData
					};
					var displayCumulativeData = {
						labels: graphLabels,
						datasets: usrCumulativeData
					};
					var displayQuantityData = {
						labels: graphLabels,
						datasets: [usrQuantityData]
					};
				}
				else {
					var displayIncomeData = {
						labels: graphLabels,
						datasets: usrIncomeData.concat(oppIncomeData)
					};
					var displayCumulativeData = {
						labels: graphLabels,
						datasets: usrCumulativeData.concat(oppCumulativeData)
					};
					var displayQuantityData = {
						labels: graphLabels,
						datasets: [usrQuantityData, oppQuantityData]
					};
				}
				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				new Chart(document.getElementById("incomeChart"), {
				    type: 'line',
				    data: displayIncomeData,
				    options: chartOptions
				});
				new Chart(document.getElementById("cumulativeChart"), {
				    type: 'line',
				    data: displayCumulativeData,
				    options: chartOptions
				});
				new Chart(document.getElementById("priceChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Price ($)',
				            data: priceHistory,
				            backgroundColor: [
				                'rgba(0, 99, 0, 0.2)'
				            ],
				            borderColor: [
				                'rgba(0,99,0,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("quantityChart2"), {
				    type: 'line',
				    data: displayQuantityData,
				    options: chartOptions
				});
			}
			else if (to_section == "cost_section") {
				var usrData = {
					label: 'Yours Sales (Units)',
					data: myData['quantityHistory'],
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				}
				var oppData = {
					label: oppName+"'s Sales (Units)",
					data: opponentData['quantityHistory'],
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				}
				if( !isMultiplayer  )
					var displayData = {
						labels: graphLabels,
						datasets: [usrData]
					}
				else
					var displayData = {
						labels: graphLabels,
						datasets: [usrData, oppData]
					}
				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				new Chart(document.getElementById("costChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Total Cost ($)',
				            data: myData['totalCostHistory'],
				            backgroundColor: [
				                'rgba(255, 99, 132, 0.2)'
				            ],
				            borderColor: [
				                'rgba(255,99,132,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("marginalChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Average Cost ($/Unit)',
				            data: myData['avgCostHistory'],
				            backgroundColor: [
				                'rgba(188, 0, 255, 0.2)'
				            ],
				            borderColor: [
				                'rgba(188,0,255,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("avgTotalChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [
				        {
				        	label: 'Average Total Cost ($/unit)',
				            data: myData['avgCostHistory'],
				            backgroundColor: [
				                'rgba(0, 99, 132, 0.2)',
				            ],
				            borderColor: [
				                'rgba(0,99,132,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("quantityChart3"), {
				    type: 'line',
				    data: displayData,
				    options: chartOptions
				});
			}
			else if (to_section == "dashboard_section") {
				var usrData = {
					label: 'Your Sales (Units)',
					data: myData['quantityHistory'],
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				};
				var oppData = {
					label: oppName+"'s Sales (Units)",
					data: opponentData['quantityHistory'],
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				};
				if( !isMultiplayer )
					var displayData = {
						labels: graphLabels,
						datasets: [usrData]
					}
				else
					var displayData = {
						labels: graphLabels,
						datasets: [usrData, oppData]
					}

				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				quantityChart = new Chart(document.getElementById("quantityChart"), {
				    type: 'line',
				    data: displayData,
				    options: chartOptions
				});
			}
		}
    </script>
  </body>

  <style type="text/css">
  	html, body {
  		height: 100%
  	}
  	body {
  		display: flex;
  		flex-direction: column;
  		overflow-x: hidden;
  	}

  	#mainContent {
  		flex: 1 0 auto;
  		min-height: 300px;
  	}
  	.footer {
		background-color: #0a4c6d;
		height: 75px;
		flex-shrink: 0;
		margin-top: 100px;
		bottom: 0
	}
	#summarySection p {
		margin-bottom: 0.5rem;
	}
	.display_sections {
		width: 1150px;
		margin: auto;
	}
	.section_content {
		margin-top: 220px;
	}
	.section_cell {
		background-color: #fcfcfc;
		padding: 25px;
		filter: drop-shadow(3px 3px 5px black);
		border-radius: 5px;
	}
	.cell_graph {
		width: 550px;
		height: 460px;
	}
	.graph {
		width: 520px;
		height: 360px;
	}
	ul a {
		color: white;
		color: #e6e6e6;
		font-weight: 480;
		font-size: 0.9em;
		line-height: 45px;
	}
	ul a:hover, a:focus {
		color: inherit;
	}
	ul li {
		padding-top: 5px;
		padding-bottom: 5px;
	}
	.darken li:hover {
		background: black;
	}
	#progressContainer {
		float: left;
        float: top;
	    width: 80%;
	    margin: 20px 10px 0 0;
	}
	.two-columns {
		columns: 2;
		list-style-type: none;
		width: 500px;
		margin: 0 auto 25px auto;
	}
	.two-columns li {
		line-height: 40px
	}
	.wow {
		visibility: hidden;
	}
	#bouncingArrow {
		-webkit-animation-iteration-count: infinite;
		-moz-animation-iteration-count: infinite;
		-webkit-animation-duration: 3s;
		-moz-animation-duration: 3s;
		color: #a8a8a4;
	}
	#topToolbar {
		width: 100%;
		height: 150px;
		background-color: #fcfcfc;
		filter: drop-shadow(0px 3px 5px black);
		position: fixed;
		z-index: 2;
		margin-top: 0px;
	}
	.title-bar {
		background-color: #0a4c6d;
		position: sticky;
		position: -webkit-sticky;
		z-index: 2;
		top: 0;
	}
	.data_grid > .grid-x{
		margin-bottom: 15px;
	}
	.reveal {
		outline: none;
		box-shadow: none;
	}
	table.paleBlueRows {
	  font-family: "Times New Roman", Times, serif;
	  border: 1px solid #FFFFFF;
	  width: 100%;
	  height: 200px;
	  text-align: center;
	  border-collapse: collapse;
	}
	table.paleBlueRows td {
	  border: 1px solid #FFFFFF;
	  padding: 3px 2px;
	  width: 250px;
	}
	table.paleBlueRows tbody td {
	  font-size: 16px;
	}
	table.paleBlueRows tr:nth-child(even) {
	  background: #D0E4F5;
	}

    .progress-danger > #progressBar{
        background-color: #fc0303;
    }

    .progress-off > #progressBar{
        background-color: gray;
    }
  </style>
</html>

<?php
$OUTPUT->footerEnd();

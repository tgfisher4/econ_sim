<?php
/* results.php

- uses socket.io to live update the results of a game across three categories: output, price, revenue, profit
- presents as chart of averages, and table with individual values
- up to two table rows can be selected to display in chart form

Initially shows "annual averages," which is table showing average quantities submitted. There are buttons to change which value is tracked on chart. Tabs at to can switch to individual view which shows a table of all users in the sessino and their values over the years. The instructor can select 1 to 2 students to show graphicaly on a slide up modal.

*/

ini_set('display_errors', 1); error_reporting(-1);
//include 'utils/sql_settup.php';
require_once "../tsugi_config.php";
require_once "../dao/QW_DAO.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\WebSocket;
use \QW\DAO\QW_DAO;

$LAUNCH = LTIX::session_start();

$QW_DAO = new QW_DAO($PDOX, $CFG->dbprefix, "econ_sim_");
// Render view
$OUTPUT->header();

if (!$USER->instructor)
	header("Location: ..");

$selectedGame = $_GET['game'];

$gameInfo = $QW_DAO->getGameInfo($selectedGame);

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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/zf/dt-1.10.18/b-1.5.2/sl-1.2.6/datatables.min.css"/>
  </head>
  <body style="background-color: #d3f6ff;">

  <input type="hidden" id="game_id" value="<?=$gameInfo['game_id']?>">
  <input type="hidden" id="eq" value="<?=$gameInfo['equilibrium']?>">

			<!-- TITLE BAR -->
  	<div class="title-bar" style="background-color: #0a4c6d">
	  <div class="title-bar-left">
	  	<div class="media-object" style="float: left;">
		    <div class="thumbnail" style="margin: 0; border: none; background: none;">
		      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
		    </div>
		</div>
	    <span class="title-bar-title">
	    	<h3 style="margin: 30px; font-weight: 500;">
	    		<?= $gameInfo['name'] ?> Results
	    	</h3>
	    </span>
	  </div>
	  <div class="title-bar-right">
	  	<?php if (isset($_GET['usr'])) { ?>
	  		<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
	  		<p style="margin-top: 10px; padding-right: 50px">Logged in as: <?= $_SESSION['username'] ?></p>
	  		<button onclick="logout_usr()" class="alert button" style="margin-right: 60px;">
	  			<strong>Logout</strong> <i class="fas fa-sign-out-alt"></i>
	  		</button>
	  	<?php } ?>
	  </div>
	</div>
	<!-- end title bar -->
	<div style="background-color: #fcfcfc; width: 100%; height: 40px; margin-bottom: 50px">
		<button id="backButton" class="secondary button" style="float: left; margin-right: 20px" onclick="redirectAdmin(<?=$selectedGame?>)">
			<i class="fas fa-angle-left"></i> Back
		</button>
		<div class="navButtons">
			<div id="avgButton" class="selected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('avg')">
				Annual Averages
			</div>
			<div id="indivButton" class="nonselected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('indiv')">
				Individual Submissions
			</div>
		</div>
	</div>

	<!-- MAIN CONTENT -->
	<div class="mainContent">
		<div>
			<h4>Annual Averages</h4>
			<hr>
			<div id="valueDisplaySelector" class="grid-x">
				<div class="cell small-3"><button onclick="changeDisplayValue(this)"class="selectedValue">Quantity</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Price</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Revenue</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Profit</button></div>
			</div>
		</div>
		<div id="avgSection" style="width: 1200px; background-color: #fcfcfc">
			<canvas id="chart" style="padding: 10px"></canvas>
		</div>
		<div id="indivSection" style="display: none;">
			<div id="equilibriumDisplayContainer" class="grid-x">
				<div class="cell small-3" id="statEquilibriumLabel">
					Quantity Equilibrium (optimum)
				</div>
				<div class="cell small-3" id="statEquilibriumDisplay">
				</div>
			</div>
			<div style="width: 98%; margin: auto; padding-bottom: 5px">
				<table id="table_id" class="display" width="100%"></table>
			</div>
		</div>
	</div>

	<!-- Modal - displays data for selected students on "individual submissions" section -->
	<div class="reveal" id="chartModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h4><strong>Compare Student(s)</strong></h4>
		<canvas id="revealChart"></canvas>
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>


	<!-- hidden inputs for javasctipt -->
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">
	<input id="gameId" type="hidden" value="<?=$gameInfo['game_id']?>">

	<!-- Bottom bar -->
	<footer class="footer"></footer>

	<?php
		$OUTPUT->footerStart();
	?>


	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/what-input/5.2.6/what-input.min.js" integrity="sha256-yJJHNtgDvBsIwTM+t18nNnp9rEXdyZ1knji5sqm4mNw=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.6.1/js/foundation.min.js" integrity="sha256-tdB5sxJ03S1jbwztV7NCvgqvMlVEvtcoJlgf62X49iM=" crossorigin="anonymous"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/zf/dt-1.10.18/b-1.5.2/sl-1.2.6/datatables.min.js"></script>
	<script src="../js/utils/gen_utils.js?t=<?=  $timestamp ?>"></script>
	<script src="../js/utils/econ_utils.js?t=<?= $timestamp ?>"></script>
	<script type="text/javascript">
		/*<?= var_dump($gameInfo) ?>*/

		const gameOptionsUnderscores = { <?=
                			join(",\n\t\t\t\t\t\t\t  ", array_map(function ($key, $value) { return $key . ":" . '"'.$value.'"'; }, array_keys($gameInfo), array_values($gameInfo)));
                			?>};
        const gameOptions = objMapValues( v => parseInt(v) || v, objMapKeys(snakeToCamel, gameOptionsUnderscores));


		const isMultiplayer = gameOptions['marketStructureName'] == 'oligopoly';


        const socketEventIdToEventName =    {   0 : 'opponent exit',
                                                1 : 'opponent ready',
                                                2 : 'opponent submission',
                                                3 : 'student submission'};

		console.log(gameOptions);

		studentWebSocket = tsugiNotifySocket(gameOptions['gameId']);
		studentWebSocket.onmessage = studentSocketCallback;
		studentWebSocket.onopen = evt => console.log("student socket opened");

		function studentSocketCallback(evt){
			const evtObj = JSON.parse(evt.data);
			switch(socketEventIdToEventName[evtObj['eventId']]){
				case 'student submission':
					updateResults($(".selectedValue").text());
					break;
				default:
					console.error('default reached in studentSockeCallback. Event: ' + evt);
					break;
			}
		}

		// initialize variables needed for chart and table
		var tableData = [], indivData = [], indivData2 = [];
		var chart = null, revealChart = null;
		var chartData = [], averages = [];

		// precalculate equilibrium data because it won't change later
		const statToEquilibrium = ["quantity", "price", "revenue", "profit"].reduce( (curr, nxt) =>
								objUpdate(curr, nxt,
										  calculateNOpolyEquilibriumOf( nxt,
																		gameOptions['demandIntercept'],
																		gameOptions['demandSlope'],
																		gameOptions['unitCost'],
																		gameOptions['fixedCost'],
																		isMultiplayer ? 2 : 1)),
								{} );

		const statToEquilibriumData = ["quantity", "price", "revenue", "profit"].reduce( (curr, nxt) =>
																						 objUpdate(curr, nxt,
																				  	 			   new Array(gameOptions['numRounds']).fill(statToEquilibrium[nxt])),
																						 {} );

		// might be nice to have some closures for calculating price from game options
		/*
		// saving progress for shading each stat screen based on its own scale. stopped because hard to say what is best revenue: that which maximizes revenue or that which optimizes profit?
		const maxQuantity = calculateMaxQuantity(gameOptions);

		const worstRevenue = min([1, maxQuantity].map( q => q * calculatePriceSimple(q, gameOptions['demandIntercept'], gameOptions['demandSlope']) ));

		const worstProfit = min([1, maxQuantity].map( q =>
				q * calculatePriceSimple(q, gameOptions['demandIntercept'], gameOptions['demandSlope'])
				- (gameOptions['fixedCost'] + q * gameOptions['unitCost']) ));

		const maxPrice = calculatePriceSimple(1, gameOptions['demandIntercept'], gameOptions['demandSlope']);

		const minPrice = calculatePriceSimple(maxQuantity, gameOptions['demandIntercept'], gameOptions['demandSlope']);



		const statToInterpolateFunction = {
				"quantity"	: q => q / statToEquilibrium['quantity'],
				"price"		: p => getInterpolationDecimal(minPrice, maxPrice, p),
				"revenue"	: r => getInterpolationDecimal(worstRevenue, ),
				"profit"	: p => getInterpolationDecimal(worstProfit, )
		};
		*/

		const totalCostHistoryFunction = totalCostHistoryFunctionForGame(gameOptions['fixedCost'], gameOptions['unitCost']);


		// graph labels
		const yearStrings = range(1, gameOptions['numRounds'] + 1).map( yr => `Yr. ${yr}`);

		// columns headers for table
		const tableColumnHeaders = ["Student"]
							.concat(isMultiplayer ? "Session": [])
							.concat(yearStrings)
							.map(header => ({title: header}));

		//$('#numRounds').val(<?= $gameInfo['num_rounds']?>);
		console.log($('#numRounds').val());

		//const valTypes = {"Quantity":"quantity_history", "Price":"price_history", "Revenue":"revenue_history", "Profit":"profit_history"};
		//let selectedValType = "quantity_history";
		//let selectedStatistic = "Quantity"// initially display quantity

		// make explicit call to get data one time on load, them listen for dynamic updates thereafter
		// (populates the graph and chart on intial page load as well as refreshes)
		//updateResults(true);
		updateResults("quantity");

		/*
		\\\\when a student submits quantity from gaim_main.php////
		- tableData contains the data for table under "Individual Submissions" tab
		- tableData is an array of arrays - Nested arrays: first element is username, subsequent elements are data
		- chartData contains raw quantities from users
		- chart is indended to display averages of all students for selected value
		- averages contains this compiled data to display on chart
		*/

		function updateResults(selectedStatistic) {
			if ($.fn.dataTable.isDataTable( '#table_id' ) ) { // if table has already been created, clear it and empty the data array
        		$('#table_id').DataTable().destroy();
        		$('#table_id').empty();
        		tableData = [];
        	}

        	// clear arrays
        	averages = []; chartData = [];
			console.log(gameOptions);
			console.log(gameOptions['gameId']);

        	// ajax to get data from sql for chart and table displays
        	$.ajax({
		  		url: "<?= addSession("utils/session.php") ?>",
		  		method: 'POST',
	  			//data: { action: 'retrieve_game_results', gameId: <?=$gameInfo['game_id']?>, valueType: selectedValType },
	  			//data: { action: 'retrieve_game_results', gameId: <?=$gameInfo['game_id']?>},
	  			data: { action: 'retrieveGameResults', gameId: gameOptions['gameId'] },
	  			success: function(response) {
					console.log(response);
					//console.log(JSON.stringify(JSON.parse(response), null, 2));
	  				var json = JSON.parse(response);
					const playerDataArr = JSON.parse(response);

					// data in format:

					// note on JS funky syntax here
					//	- to return an object literal from an array function without an code block body, wrap the literal in parens
					//	- to use a dynamic key in an object literal, place it in brackets
					//const session_quantity_histories = json['player_data'].reduce(
					playerDataArr.forEach( d => console.log(d) );
					/*
					const session_quantity_histories = playerDataArr.reduce(
														 (curr, nxt) => ({ ...curr,
															 [nxt['sessionId']]: zipMin(nxt['quantityHistory'],
																						safeObjAcc(curr, nxt['sessionId'], Array(gameOptions['numRounds']).fill(0))
																				).map(sumArr)}),
														 {}
													 );
													 */
					const sessionQuantityHistories = playerDataArr.reduce( (curr, nxt) => objUpdate(curr,
																									 nxt['sessionId'], arrAdd(safeObjAcc(curr, nxt['sessionId'], []),
																														 	  nxt['quantityHistory'])),
																			{} );
															/*({...curr,
															  [nxt['sessionId']]: arrAdd(safeObjAcc(curr, nxt['sessionId'], []), nxt['quantityHistory']]) }))
															  */

					console.log(sessionQuantityHistories);
					console.log(Object.values(sessionQuantityHistories).some( a => a.some( e => e.includes(undefined) || e.includes(null))));
					//console.log(JSON.stringify(sessionQuantityHistories));
					const sessionPriceHistories	= objMapValues( sessionHistories => calculatePriceHistory(sessionHistories,
																											  gameOptions['demandIntercept'],
																											  gameOptions['demandSlope']),
																sessionQuantityHistories);
					console.log(sessionPriceHistories);
					//console.log(JSON.stringify(sessionPriceHistories));
					/*
					const session_selected_stat_histories = objMapValues(session_quantity_histories,
																		 q_h => calculateStatisticHistory(selectedStatistic,
																	  								   	  session_price_histories[i],
																	  							 	   	  totalCostHistoryFunctionForGame(json['game_data']['fixed_cost'],
																  									      	 							  json['game_data']['unit_cost']),
																	  							 	   	  q_h));
					*/


					//const player_selected_stat_histories = json['player_data'].map( data => ({'player': data['player'], 'sessionId': data['sessionId'],
					const playerSelectedStatHistories = playerDataArr.map( data => ({ 'playerName': data['playerName'], 'sessionId': data['sessionId'],
																					  'statHistory': calculateStatisticHistory(selectedStatistic,
																						 										   sessionPriceHistories[data['sessionId']],
																								  							 	   totalCostHistoryFunction,
																																   data['quantityHistory'])}));

					console.log(playerSelectedStatHistories);
					//console.log(JSON.stringify(playerSelectedStatHistories));
					console.log(selectedStatistic);
					const tableData = playerSelectedStatHistories.map( data => [	data['playerName'], //data['player'].substr(0, json['player'].indexOf('@')),
																					// not sure why the sessionId is needed here
																					/*('<?= $gameInfo["market_structure_name"]?>' == 'oligopoly' ? data['sessionId'] : []), */
																					...(gameOptions['marketStructureName'] == 'oligopoly' ? data['sessionId'] : []),
																   					...data['statHistory'] ]);

					const chartData = zipMax2DArr(playerSelectedStatHistories.map( data => data['statHistory']) ).map(avgArr);

					tableCallback(tableData, selectedStatistic);

					graphCallback(chartData, selectedStatistic);

					// seems like these could be easily condensed into single function call
					/*
					if( !chart )	graphCallback(chartData, 'Quantity');
					// seems like this doesn't update equilibrium data
					else {			chart.data.datasets[0].data = chartData; chart.update();	}
					*/
	  			}
	  		});
        }


		function changeContent(section) {
			// set header
			const header = $('.mainContent').find('h4');
			if (header.text() == 'Annual Averages') header.text('Individual Submissions');
			else header.text('Annual Averages');

			// display selected content
			$('#avgSection').css('display','none');
			$('#indivSection').css('display','none');
			$('#'+section+'Section').css('display','');

			// highlight selected button
			$('#avgButton').css('background-color','#767676');
			$('#indivButton').css('background-color','#767676');
			$('#'+section+'Button').css('background-color','#1779ba');
		}

		/*
		var columns = [{ title: "Yr. 1" },{ title: "Yr. 2" },{ title: "Yr. 3" },{ title: "Yr. 4" },{ title: "Yr. 5" },{ title: "Yr. 6" },{ title: "Yr. 7" }, { title: "Yr. 8" },{ title: "Yr. 9" },{ title: "Yr. 10" }, { title: "Yr. 11" }, { title: "Yr. 12" }, { title: "Yr. 13" },  { title: "Yr. 14" },  { title: "Yr. 15" }, { title: "Yr. 16" }, { title: "Yr. 17" }, { title: "Yr. 18" }, { title: "Yr. 19" }, { title: "Yr. 20" }, { title: "Yr. 21" }, { title: "Yr. 22" }, { title: "Yr. 23" }, { title: "Yr. 24" }, { title: "Yr. 25" }];
		columns = columns.splice(0, gameOptions['numRounds']);
		*/

		/* if ('<?=$gameInfo["market_struct"]?>'=='oligopoly') *//*
		if( gameOptions['marketStructureName'] == 'oligopoly' )
			columns = [{ title: "Student" }, { title: "Group" }].concat(columns)
		else
			columns = [{ title: "Student" }].concat(columns)
		*/

		function tableCallback(data, selectedStatistic) {
			console.log(data);
			console.log(JSON.stringify(data));
			$.fn.dataTable.ext.errMode = 'none'; // supress error from not all columns being
		    var table = $('#table_id').DataTable( {
		        data: data,
		        columns: tableColumnHeaders,
		        destroy: true,
		        dom: 'Bfrtip',
		        select: {
		        	style: "multi"
		        },
		        buttons: [
		        	{
		        		text: "Show Graph",
		        		action: function() { // Displays modal with the data from the selected row(s)
		        			var rowData = table.rows({selected: true }).data().toArray();
		        			revealChartCallback(rowData, rowData.length, selectedStatistic);
		        			$('#chartModal').foundation('open');
		        		}
		        	}
		        ]
		    });

		    table.buttons().disable();

		    // limit the number of selected rows to a max of 2
			table.on( 'select', function ( e, dt, type, ix ) {
			   var selected = dt.rows({selected: true});
			   table.buttons().enable();
			   if ( selected.count() > 2 ) {
			      dt.rows(ix).deselect();
			   }
			} );
			// if no buttons selected, show graph button should be disabled
			table.on( 'deselect', function ( e, dt, type, ix ) {
				var selected = dt.rows({selected: true});
			    if ( selected.count() == 0 ) {
			       table.buttons().disable();
			    }
			} );

			$("#statEquilibriumLabel").text(selectedStatistic[0].toUpperCase() + selectedStatistic.slice(1) + " " + $("#statEquilibriumLabel").text().split(" ").slice(1).join(" "));
			$("#statEquilibriumDisplay").text(statToEquilibrium[selectedStatistic]);

			$('td').filter( (i, ele) => parseInt($(ele).text()) ).css('background-color', function() {return `rgb(${interpolateColors(greenish, reddish, Math.abs(1 - (parseInt($(this).text())/statToEquilibrium[selectedStatistic]))).join(",")})`});
		}

		function graphCallback(chartData, selectedStatistic) {
			console.log($('#numRounds').val());
			// valType used to check if selected value is quantity. if so show equilibrium on chart. hide otherwise..

			if( chart ) {
				// if chart exists, update
				chart.data.datasets[0].data = chartData;
				chart.data.datasets[1].data = statToEquilibriumData[selectedStatistic];
				chart.update();
			}
			else {
				// if chart does not exist, create
				var fullDataObj = {
				        labels: yearStrings,
				        datasets: [{
				            label: 'Average Value',
				            data: chartData,
				            fill: false,
				            borderColor: 'rgba(255,99,132,1)',
				            pointBackgroundColor: 'rgba(255,99,132,1)',
				            borderWidth: 3,
				            pointRadius: 5
				        },
				        {
				            label: 'Equilibrium',
				            data: statToEquilibriumData[selectedStatistic],
				            fill: false,
				            pointRadius: 0,
				            borderColor: 'rgba(0,0,255,1)',
				            borderWidth: 3
				        }]
				    };

				chart = new Chart($('#chart'), {
				    type: 'line',
				    data: fullDataObj,
				    options: {
				        scales: {
				            yAxes: [{
				                ticks: {
				                    beginAtZero:true
				                }
				            }]
				        },
				        animation: false
				    }
				});
			}
		}

		function revealChartCallback(data, count, selectedStatistic) {
			console.log("reveal chart data:");
			console.log(JSON.stringify(data));
			console.log(selectedStatistic)
			const name1 = data[0][0];
			const data1 = data[0].slice(1);

			// create data object based on number of selected students (1 or 2)
			if (count == 1)
				var dataObj = {
				        labels: yearStrings,
				        datasets: [{
				            label: name1,
				            data: data1,
				            fill: false,
				            borderColor: 'rgba(255,99,132,1)',
				            pointBackgroundColor: 'rgba(255,99,132,1)',
				            borderWidth: 3,
				            pointRadius: 4
				        },
				        {
				            label: 'Equilibrium',
				            data: statToEquilibriumData[selectedStatistic],
				            fill: false,
				            pointRadius: 0,
				            borderColor: 'rgba(0,0,255,1)',
				            borderWidth: 3
				        }]
				    };
			else {
				const name2 = data[1][0];
				const data2 = data[1].slice(1);

				var dataObj = {
			        labels: yearStrings,
			        datasets: [{
			            label: name1,
			            data: data1,
			            fill: false,
			            borderColor: 'rgba(255,99,132,1)',
			            pointBackgroundColor: 'rgba(255,99,132,1)',
			            borderWidth: 3,
			            pointRadius: 5
			        },
			        {
			            label: name2,
			            data: data2,
			            fill: false,
			            borderColor: 'rgba(232, 228, 0,1)',
			            pointBackgroundColor: 'rgba(232, 228, 0,1)',
			            borderWidth: 3,
			            pointRadius: 5
			        },
			        {
			            label: 'Equilibrium',
			            data: statToEquilibriumData[selectedStatistic],
			            fill: false,
			            pointRadius: 0,
			            borderColor: 'rgba(0,0,255,1)',
			            borderWidth: 3
			        }]
			    };
			}
			revealChart = new Chart($('#revealChart'), {
			    type: 'line',
			    data: dataObj,
			    options: {
			        scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        },
			        animation: false
			    }
			});
		}

		// on backbutton press
		function redirectAdmin(game) {
			urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
			window.location = "<?= addSession('admin_page.php') ?>" + '&game='+game;
		}

		// handler for value selector buttons on individual submissions section
		$("#valueDisplaySelector").find("button").filter((i, e) => $(e).text() == "Quantity").addClass('selectedValue'); // initial highlighted value is quanitity

		function changeDisplayValue(element) {
			// change colors
			//$("#valueDisplaySelector").find("button").removeClass('selectedValue');
			$(".selectedValue").removeClass("selectedValue");
			$(element).addClass('selectedValue');
			//selectedStatistic = $(element).text();

			updateResults($(element).text().toLowerCase());
			return;

			// get appropriate values and populate chart/table
			// ajax to get data from sql for chart and table displays

			if ($.fn.dataTable.isDataTable( '#table_id' ) ) { // if table has already been created, clear it and empty the data array
        		$('#table_id').DataTable().destroy();
        		$('#table_id').empty();
        		tableData = [];
        	}

        	$.ajax({
		  		url: "<?= addSession("utils/session.php") ?>",
		  		method: 'POST',
	  			data: { action: 'retrieveGameResults', gameId: <?=$gameInfo['game_id']?>, valueType: selectedValType },
	  			success: function(response) {
					console.log("response:" + response);
					console.log(JSON.stringify(JSON.parse(response), null, 2));
	  				var json = JSON.parse(response);

	  				// clear arrays
	  				chartData=[];tableData=[];indivData=[];averages=[];

	  				for (var i=0; i < Object.keys(json).length; i++) {
	  					// data for chart
	  					indivData = json[i]['data'];
	  					chartData.push(indivData);

	  					// data for table (add username to front of individual data arrays)
	        			indivData = [json[i]['username'].substr(0, json[i]['username'].indexOf('@'))]
	        			indivData = indivData.concat(json[i]['data']);
	        			tableData.push(indivData);

	        			indivData = [];

	  				}

	  				// create table
		        	tableCallback(tableData);
					console.log("table created");


					for(let year = 0; year < 20; year++){
						// did not use reduce instead of for loop to facilitate early exit

						res =	chartData.reduce(
									(running, curr) => (
										[	running[0] + (curr.length > year ? curr[year] : 0),
											running[1] + (curr.length > year)]
									), [0,0]
								)

						console.log("Year" + (year + 1));
						console.log("sum: " + res[0] + " from " + res[1] + " - avg: " + res[0]/res[1]);
						if (res[1] == 0) break;
						gAvgs.push(res[0]/res[1]);
						console.log("avgs after append:");
						console.log(averages);

					}
					console.log("final avgs:");
					console.log(averages);
					console.log("\n====================\n");

		        	// update chart
	            	chart.data.datasets[0].data = averages;
	            	if ($(element).text() != "Quantity")
	            		chart.data.datasets[1].data = [];
	            	else
	            		chart.data.datasets[1].data = new Array(20).fill($('#eq').val());
	            	chart.update();

	  			}
	  		});
		}
	</script>

	<style type="text/css">
		html, body {
	  		height: 100%
	  	}
	  	.mainContent { min-height: 700px; }
	  	.footer {
			background-color: #0a4c6d;
			height: 50px;
			width: 100%;
			margin-top: 50px;
		}
		.navButtons > div {
			float: left;
			cursor: pointer;
			height: 40px;
			color: white;
			padding: 5px 15px 0 15px;
			vertical-align: middle;
		}
		.selected {
			background-color: #1779ba;
		}
		.nonselected {
			background-color: #767676;
		}
		.mainContent {
			filter: drop-shadow(3px 3px 5px black);
			border-radius: 5px;
			width: 1200px;
			background-color: #fcfcfc;
			margin: 0 auto 0 auto;
		}
		.mainContent > div > h4 {
			text-align: center;
			font-weight: 450;
			padding-top: 10px;
		}
		hr {
			margin-bottom: 0.8rem;
			width: 80%;
		}
		#valueDisplaySelector {
			width: 50%;
			margin: auto;
		}
		#valueDisplaySelector > .cell{
			display: flex;
  			justify-content: center;
		}
		#valueDisplaySelector > .cell > button {
			width: 80%;
			height: 50px;
			margin: auto;
			background: linear-gradient(141deg, #0fb88a 20%, #0fb8ad 80%);
			border-radius: 24px;
			color: white;
		}
		#valueDisplaySelector > .cell > button:hover {
			cursor: pointer;
		}

		#equilibriumDisplayContainer {
			width: 33%;
			height: 50px;
			margin: 25px auto;
			border: medium solid #013673;
			border-radius: 10px;
		 	/*background-color: #22c5c9;*.
			/*background: linear-gradient(141deg, #8fcfa8 20%, #8fcfbe 80%); */
			/*background: linear-gradient(141deg, #22c5c9 20%, #229cc9 80%);*/
			background: linear-gradient(141deg, #22a2c9 20%, #2286c9 80%);
		}
		#statEquilibriumLabel {
			border-right: thin solid #013673;
			width: 75%;
			text-align: center;
			color: white;
			padding-top: 9px;
		}
		#statEquilibriumDisplay {
			border-left: thin solid #013673;
			width: 25%;
			text-align: center;
			color: white;
			padding-top: 9px;
		}
		.selectedValue {
			background: green !important;
			transform: scale(1.25);
		}
		.reveal {
			outline: none;
			box-shadow: none;
		}
		#table_id {
			border-spacing: 5px;
		}
		#table_id > td {

		}
	</style>
  </body>

 <?php
$OUTPUT->footerEnd();

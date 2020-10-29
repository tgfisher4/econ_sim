<?php
namespace QW\DAO;

class QW_DAO {

    private $PDOX;
    private $p;

    public function __construct($PDOX, $p) {
        $this->PDOX = $PDOX;
        $this->p = $p;
    }

    function addCourse($admin, $courseName, $section, $icon) {
        $query = "INSERT INTO {$this->p}courses (name, section, owner, avatar)
                  VALUES (:name, :section, :owner, :avatar)";
        $arr = array(':name' => $courseName,
                     ':section' => $section,
                     ':owner' => $admin,
                     ':avatar' => $icon);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteCourse($course_id) {
        $query = "DELETE FROM {$this->p}courses WHERE course_id = :course_id";
        $arr = array(':course_id' => $course_id);
        $this->PDOX->queryDie($query, $arr);

        /* should cascade given games fk constraint */
        //$query = "DELETE FROM {$this->p}games WHERE course_id = :course_id";
        //$this->PDOX->queryDie($query, $arr);
    }

    function saveEquilibrium($game_id, $eq) {
        $query = "UPDATE {$this->p}games SET equilibrium = :eq WHERE game_id = :game_id";
        $arr = array(':game_id' => $game_id, ':eq' => $eq);
        $this->PDOX->queryDie($query, $arr);
    }
/*
    function addGame($name, $diff, $mode, $market_struct, $macro_econ, $limit, $numRounds, $intercept, $slope, $fixed, $cons, $maxq) {
        $query = "INSERT INTO {$this->p}games (name, difficulty, mode, market_struct, macro_econ, time_limit, num_rounds. demand_intercept, demand_slope, fixed_cost, const_cost, max_quantity) VALUES (:name, :diff, :mode, :market, :macro_econ, :lim, :rounds, :intercept, :slope, :fixed, :const_cost, :max)";
        $arr = array(':name'=>$name, ':diff'=>$diff, ':mode'=>$mode, ':market'=>$market_struct, ':macro_econ'=>$macro_econ, ':lim'=>$limit, ':rounds'=>$numRounds, ':intercept'=>$intercept, ':slope'=>$slope, ':fixed'=>$fixed, ':const_cost'=>$cons, ':max'=>$maxq);
        $this->PDOX->queryDie($query, $arr);
    }
*/
    function addGame($name, $type, $course_id, $diff, $mode, $market_struct, $macro_econ, $limit, $numRounds, $intercept, $slope, $fixed, $cons, $maxq) {
        $query = "INSERT INTO {$this->p}games (name, type, course_id, difficulty, mode, market_struct, macro_econ, time_limit, num_rounds, demand_intercept, demand_slope, fixed_cost, const_cost, max_quantity)
                  VALUES (:name, :type, :course_id, :diff, :mode, :market, :macro_econ, :lim, :rounds, :intercept, :slope, :fixed, :const_cost, :max_quantity)";
        $arr = array(':name'        => $name,
                     ':type'        => $type,
                     ':course_id'   => $course_id,
                     ':diff'        => $diff,
                     ':mode'        => $mode,
                     ':market'      => $market_struct,
                     ':macro_econ'  => $macro_econ,
                     ':lim'         => $limit,
                     ':rounds'      => $numRounds,
                     ':intercept'   => $intercept,
                     ':slope'       => $slope,
                     ':fixed'       => $fixed,
                     ':const_cost'  => $cons,
                     ':max_quantity'=> $maxq);
        $this->PDOX->queryDie($query, $arr);
    }

    function updateGame($game_id, $name, $diff, $mode, $market_struct, $macro_econ, $limit, $numRounds, $intercept, $slope, $fixed, $cons, $maxq) {
        $query = "UPDATE {$this->p}games
                 SET name               =  :name,
                     difficulty         =  :diff,
                     mode               =  :mode,
                     market_struct      =  :market,
                     macro_econ         =  :macro_econ,
                     time_limit         =  :lim,
                     num_rounds         =  :rounds,
                     demand_intercept   =  :intercept,
                     demand_slope       =  :slope,
                     fixed_cost         =  :fixed,
                     const_cost         =  :const_cost,
                     max_quantity       =  :max
                  WHERE game_id = :game_id";
        $arr = array(':name'            => $name,
                     ':diff'            => $diff,
                     ':mode'            => $mode,
                     ':market'          => $market_struct,
                     ':macro_econ'      => $macro_econ,
                     ':lim'             => $limit,
                     ':rounds'          => $numRounds,
                     ':intercept'       => $intercept,
                     ':slope'           => $slope,
                     ':fixed'           => $fixed,
                     ':const_cost'      => $cons,
                     ':max'             => $maxq,
                     ':game_id'         => $game_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteGame($game_id) {
        $query = "DELETE FROM {$this->p}games WHERE game_id = :game_id";
        $arr = array(':game_id' => $game_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function getPriceHist($game_id) {
        $query = "SELECT price_hist FROM {$this->p}games WHERE game_id = :game_id;";
        $arr = array(':game_id' => $game_id);
        return $this->PDOX->rowDie($query, $arr)['price_hist'];
    }

    function gameExists($game_id) {
        $query = "SELECT live, market_struct FROM {$this->p}games WHERE game_id = :game_id;";
        $arr = array(':game_id' => $game_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    /*  should this take a game or another parameter? So a player can participate in multiple games?
        should a player only be allowed to play a single game in a session?
        I could see how this could potentially be useful if we want to reconnect a student to a game they left, but otherwise not sure the purpose
            - could even only support this option for multiplayer since it might be hard to re-coordinate the two players if both leave
    */
    /*
    function playerCompletedGame($player) {
        $query = "SELECT complete FROM {$this->p}results WHERE player = :player LIMIT 1;";
        $arr = array(':player' => $player);
        return $this->PDOX->rowDie($query, $arr)["complete"];
    }
    */


    /* I suggest the following */

    function playerCompletedGame($player, $game_id){
        $query = "SELECT complete FROM {$this->p}sessions WHERE player = :player AND game_id = :game_id LIMIT 1;";
        $arr = array(':player'  => $player,
                     ':game_id' => $game_id);
        return $this->PDOX->rowDie($query, $arr)["complete"];
    }
    

    //function toggleSession($toggledOn, $game_id, $priceHist) {
    function toggleGameLive($game_id, $priceHist){
        $query = "SELECT live, price_hist FROM {$this->p}games WHERE game_id = :game_id  LIMIT 1;";
        //echo($id);
        $arr = array(':game_id' => $game_id);
        if ($this->PDOX->rowDie($query, $arr)["live"]) {
            $query = "UPDATE {$this->p}games SET live = 0 WHERE game_id = :game_id";
            $this->PDOX->queryDie($query, $arr);
            //$query = "DELETE FROM {$this->p}results WHERE gameId = :id";
            //$this->PDOX->queryDie($query, $arr);
            return false;
        } else {
            //$toggledOn=false;
            $query = "UPDATE {$this->p}games SET live = 1, price_hist = :hist WHERE game_id = :game_id";
            $arr = array(':game_id' => $game_id, ':hist'=> $priceHist);
            $this->PDOX->queryDie($query, $arr);
            return true;
        }
    }

    function updateResults($session_id, $username, $quantity, $revenue, $profit, $return, $price, $unit_cost, $total_cost) {
        $query = "SELECT * FROM {$this->p}results WHERE session_id = :session_id AND player = :player;";
        $arr = array(':group_id' => $group_id, ':player' => $username);
        $data = $this->PDOX->rowDie($query, $arr);

        if ($data) {
            $quantityHist   = $data['quantity_history']     .",".   $quantity;
            $revenueHist    = $data['revenue_history']      .",".   $revenue;
            $profitHist     = $data['profit_history']       .",".   $profit;
            $returnHist     = $data['return_history']       .",".   $return;
            $priceHist      = $data['price_history']        .",".   $price;
            $unitCostHist   = $data['unit_cost_history']    .",".   $unit_cost;
            $totalCostHist  = $data['total_cost_history']   .",".   $total_cost;

            // unit cost not updated?
            $query = "UPDATE {$this->p}results
                      SET
                          -- this doesn't seem like something we need to update
                          /* game_id           =  :game_id */
                          quantity_history      =  :quantity_history,
                          revenue_history       =  :revenue_history,
                          profit_history        =  :profit_history,
                          return_history        =  :return_history,
                          price_history         =  :price_history,
                          unit_cost_history     =  :unit_cost_history,
                          total_cost_history    =  :total_cost_history
                      WHERE session_id = :session_id AND player = :player";

            $arr = array(':quantity_history'        => $quantityHist,
                         ':revenue_history'         => $revenueHist,
                         ':profit_history'          => $profitHist,
                         ':return_history'          => $returnHist,
                         ':price_history'           => $priceHist,
                         ':unit_cost_history'       => $unitCostHist,
                         ':total_cost_history'      => $totalCostHist       );

            $this->PDOX->queryDie($query, $arr);
        } else {
            $query = "INSERT INTO {$this->p}results (session_id, player, quantity_history, revenue_history, profit_history,
                                                     return_history, price_history, unit_cost_history, total_cost_history)
                      VALUES (:player, :quantity, :revenue, :profit, :return, :price, :unit_cost, :total_cost)";
            $arr = array(':player'          => $username,
                         ':quantity_history'        => $quantity,
                         ':revenue_history'         => $revenue,
                         ':profit_history'          => $profit,
                         ':return_history'          => $return,
                         ':price_history'           => $price,
                         ':unit_cost_history'       => $unit_cost,
                         ':total_cost_history'      => $total_cost );
            $this->PDOX->queryDie($query, $arr);
        }
    }

    /* under new scheme, not sure I ever want this function to be called */
    /* don't think I want to delete records */
    function removeFromSession($group_id) {
        /*
        $query = "DELETE FROM {$this->p}results WHERE group_id = :group_id";
        $arr = array(':group_id' => $group_id);
        $this->PDOX->queryDie($query, $arr);
        */
        // due to table constraints, results will automatically drop rows corresponding to the group dropped from live_multiplayer_groups
        $query = "DELETE FROM {$this->p}live_multiplayer_groups WHERE group_id = :group_id";
        $this->PDOX->queryDie($query, $arr);
    }

    /* not sure if the session_id needs to be returned here */
    function retrieveValueFromGameResults($val, $game_id){//, $groupId, $usr) {
        $valid_vals = ["quantity_history", "revenue_history", "profit_history", "return_history", "price_history", "unit_cost_history", "total_cost_history"];
        if (!in_array($val, $valid_vals))       return json_encode([]);
        // marker cannot be used to specify column name
        // https://stackoverflow.com/questions/46259185/how-to-use-prepared-statements-in-php-to-choose-with-select-a-column-of-the-da
        // instead, I will use backticks and inject $val directly into the query
        // this should be ok since I am double checking that $val is one of a few expected values
        $query = "SELECT player, session_id, `${val}` FROM {$this->p}results WHERE game_id = :game_id";
        $arr = array(':game_id' => $game_id);

        $data=[];
        foreach ($this->PDOX->allRowsDie($query, $arr) as $row) {
            $splitData = array_map('intval', explode(',', $row[$val]));
            $splitWithName = array('username'=> $row['player'], 'session_id'=> $row['session_id'], 'data'=> $splitData);
            array_push($data, $splitWithName);
        }
        return json_encode($data);
    }

    // old
    /*
    function joinMultiplayerGame($game_id, $username) {


        $query = "SELECT * FROM results JOIN sessions ON results.session_id = sessions.session_id WHERE session_id = ";

        $query = "SELECT * FROM {$this->p}sessions WHERE game_id = :game_id AND player2 IS NULL AND complete = 0 LIMIT 1";
        $arr = array(':game_id' => $game_id);
        $game = $this->PDOX->rowDie($query, $arr);

        if ($game) {
            $query = "UPDATE {$this->p}sessions SET player2 = :player WHERE game_id = :game_id;";
            $arr = array(':player' => $username, ':game_id' => $game_id);
            $this->PDOX->queryDie($query, $arr);
            return [$game['session_id'], $game['player1']];
        } else {
            //$query = "INSERT INTO {$this->p}live_multiplayer_groups (group_id, game_id, player1) VALUES (:group_id, :game_id, :player1)";
            //$arr = array(':group_id' => $group_id, ':game_id' => $game_id, ':player1' => $username);
            $query = "INSERT INTO {$this->p}sessions (game_id, player1, complete) VALUES (:game_id, :player1, 0)";
            $arr = array(':game_id' => $game_id, ':player1' => $username);
            $this->PDOX->queryDie($query, $arr);
        }

    }
    */

    // new
    function joinMultiplayerGame($game_id, $username) {

        /* TODO: LOCK SESSIONS TABLE */

        /* what's going on here: using information from the results table to tell me which session_id
            - hopefully selecting from the join table will lock both rows of both TABLES

            what I want to happen when 2 people are joining with one empty game
                - first player to execute game search select query receives empty game, joins - atomic, transaction
                - second player to execute game search select query is not only blocked from receiving the result, but is blocked from executing the query until the first transaction finishes re-runs the query
                    - not only is the row returned updated based on previous user joining, but they receive the row they wouldn

            idea: intermediate blocking row
                - first player locks a specific row to indicate intent to run $findOpenGameQuery
                - second player tried to lock this row: is blocked
                    - when this blocking row is unlocked, the second player receives the lock and is THEN allowed to execute the $findOpenGameQuery
                - cannot be in sessions table or else we will think there is an extra empty game
                    - could add WHERE :session_id != 0 if this is my indicator row

            I think this solution makes sense in this case because there is always at most one open game.
            But we cannot lock the open game row because when two people compete, the second gets the full game row and needs to re-execute query

            other option: try to get row. When received row, if it is full

            another situation to avoid:
                - p1 joins, causing new game
                - p2, p3, p4 join in quick succession, all try to lock the same rows from the join.
                - p2 gets lock first, joins game. game now full
                - p3 gets lock second, sees game is full, creates new game
                - p4 gets lock last, sees game is full, creates new game
                - 4 players, but 2 unmatched.

            Also: do not even know if this join is even a worry but it seems it will be extremely hard to test
                - could try manually on command line with 4 terminal windows

            Normal transactions work perfectly: case considered above does not occur
                - p4 gets back the game created by p3: seems to wait to run the query until unblocked, not just witholding result
        */

        //$lockFindGameIntentRowStmt = this->PDOX->prepare("SELECT...");

        // pre-prepare possibly needed SQL statements so that the transaction can take as little time as possible;
        // computes the number of players in each game, returns the game with the least players (1), or, if all are full, the game with the highest session_id
        $findOpenSessionStmt = $this->PDOX->prepare(" SELECT {$this->p}results.session_id, count({$this->p}results.session_id) as players
                                                      FROM {$this->p}results JOIN {$this->p}sessions
                                                      ON {$this->p}results.session_id = {$this->p}sessions.session_id
                                                      WHERE complete = FALSE AND game_id = :game_id
                                                      GROUP BY {$this->p}results.session_id
                                                      ORDER BY players, {$this->p}results.session_id DESC
                                                      LIMIT 1
                                                      FOR UPDATE ");
        $joinSessionStmt     = $this->PDOX->prepare("INSERT INTO {$this->p}results
                                                     (session_id, player) VALUES (:session_id, :player)");
        $createSessionStmt   = $this->PDOX->prepare("INSERT INTO {$this->p}sessions
                                                    (game_id) VALUES (:game_id)
                                                    RETURNING session_id");
        // SELECT LAST_INSERT_ID() gives last ID inserted, crucially, FOR THIS CONNECTION ONLY
        $joinNewSessionStmt  = $this->PDOX->prepare("INSERT INTO {$this->p}results (session_id, player) VALUES ((SELECT LAST_INSERT_ID()), :player)");
        $game_idSQLParam     = array(':game_id' => $game_id);

        //$game = $this->PDOX->rowDie($findOpenGameQuery, $findOpenGameParams);
        /*  Possibilities:
            - no sessions exist for the game: empty returns (FALSE)
            - session returned has count of 1
            - session returned has count of 2
        */
        $player_session_id = -1;
        $this->PDOX->beginTransaction();
        // the following statement blocks until other join games are complete, meaning the findOpenGameQuery will not
        $findOpenSessionStmt->execute($game_idSQLParam);
        $session = $findOpenGameStmt->fetch(PDO::FETCH_ASSOC);
        if ($session and $session['players'] == 1) {
            // game is open
            $joinSessionParams = array(':player' => $username, ':session_id' => $session['session_id']);
            $joinSessionStmt->execute($joinSessionParams);
            //$this->PDOX->queryDie($joinSessionStmt, $joinGameParams);
            //return [$game['session_id'], $game['player1']];
            $player_session_id = session['session_id'];
        } else {
            // create new game
            //$query = "INSERT INTO {$this->p}live_multiplayer_groups (group_id, game_id, player1) VALUES (:group_id, :game_id, :player1)";
            //$arr = array(':group_id' => $group_id, ':game_id' => $game_id, ':player1' => $username);
            // release the selected row: we won't actually use it, but create
            //$new_session_query = "INSERT INTO {$this->p}sessions (game_id, player1, complete) VALUES (:game_id, :player1, 0)";
            //$new_session_params = array(':game_id' => $game_id, ':player1' => $username);
            //$new_session_id = ($game ? $game['session_id'] : 0) + 1;

            // hold row so that we release after we insert, causing other players open session searches to work as expected
            $createSessionStmt->execute($game_idSQLParam);
            $player_session_id = $createSessionStmt->fetchColumn();
            $joinNewSessionParams = array(':player' => $username, ':session_id' => $player_session_id);
            $joinSessionStmt->execute($joinNewSessionParams);

            //$this->PDOX->queryDie($new_session_query, $new_session_params);

            // also need to insert row into results so that other players can see that this session_has an opening
            //$new_result_query = INSERT INTO {$this->p}results (session_id, player) VALUES (:new_session_id, :username)
            //$new_result_params = array(':new_session_id' => $new_session_id,
            //                           ':username'       => $username);

            //$this->PDOX->queryDie($new_result_query, $new_result_params);
        }
        $this->PDOX->commit();
        return $player_session_id;
        //if $session and $session['players' == 1]) return [$session['session_id'], $session[]]

        /* TODO: UNLOCK SESSIONS TABLE */
    }


    // old
    /*
    function multiplayerSubmission($session_id, $username, $quantity) {

        $query = "SELECT * FROM {$this->p}sessions WHERE session_id = :session_id LIMIT 1";
        $arr = array(':session_id' => $session_id);
        $existing_data = $this->PDOX->rowDie($query, $arr);

        if($existing_data['submitted_data'] == NULL){
            $query = "UPDATE {$this->p}live_multiplayer_groups SET submitted_data = :submitted_data WHERE session_id = :session_id;";
            $arr = array(':quantity'   => $quantity,
                         ':session_id' => $session_id);
            $this->PDOX->queryDie($query, $arr);
            return FALSE;
        }

        if ($existing_data['player1'] == NULL) {
            $query = "UPDATE {$this->p}live_multiplayer_groups SET player1 = :username, player1_data = :quantity WHERE group_id = :group_id;";
            $arr = array(':quantity' => $quantity, ':group_id' => $group['group_id']);
            $this->PDOX->queryDie($query, $arr);
            return FALSE;
        }
        else {
            //$query = "UPDATE {$this->p}live_multiplayer_groups SET player1 = :username, player1_data = :quantity WHERE group_id = :group_id;";
            //$arr = array(':quantity' => $quantity, ':group_id' => $group['group_id']);
            $data = $this->PDOX->rowDie($query, $arr);

            // send back array with usernames and their respective submission data
            $submitData = [$data['player1'],$data['player1_data'], $username, $quantity];
            return json_encode($submitData);

        }
    }
    */

    // new
    function submitMuliplayerData($session_id, $quantity_submitted){
        /* Returns either the opponent's move if they have already submitted or FALSE if opponent has not submiited.
           Has the side effect of populating sessions.submitted_data with the player's submission ($submission) if opponent has not yet submitted
           or setting session.submitted_data to NULL if opponent has already submitted.
           Opponent/session identified by session_id ()$session_id).

           uses transactions in case both player submit in quick succession

           Returns: FALSE or integer
           Params: $session_id (integer), $submission (?)
        */

        // prepare SQL statements beforehand so that the relevant rows are locked for as short a time as possible
        $fetchOppDataStmt = $this->PDOX->prepare("SELECT submitted_data
                                                  FROM {$this->p}sessions
                                                  WHERE session_id = :session_id
                                                  FOR UPDATE");
        $submitDataQuery  = $this->PDOX->prepare("UPDATE {$this->p}sessions
                                                 SET submitted_data = :submitted_data
                                                 WHERE session_id = :session_id");
        $clearDataQuery   = $this->PDOX->prepare("UPDATE {$this->p}sessions
                                                 SET submitted_data = NULL
                                                 WHERE session_id = :session_id");

        $session_idSQLParam = array(":session_id"     => $session_id);
        $submitDataParam    = array(":session_id"     => $session_id,
                                    ":submitted_data" => $quantity_submitted);


        //LOCK RESOURCE
        $this->PDOX->beginTransaction();
        $fetchOppDataStmt->execute($session_idSQLParam);
        $opponentData = $fetchOppDataStmt->fetch(PDO::FETCH_ASSOC);

        //$updateDataQuery = FALSE, $updateDataParam = FALSE;

        // right way to check for null value in SQL table?
        if ($opponentData['submitted_data'] === NULL){
            // submit own data
            $submitDataQuery->execute($submitDataParam);
            /*
            $updateDataQuery = "UPDATE {$this->p}sessions
                                SET submitted_data = :submitted_data
                                WHERE session_id = :session_id"
            $updateDataParams = array(':submitted_data' => $quantity_submitted,
                                      ':session_id'     => $session_id);
            */
        }
        else {
            // clear existing data: other player can now submit again
            $clearDataQuery->execute($session_idSQLParam);
            /*
            $updateDataQuery = "UPDATE {$this->p}sessions
                                SET submitted_data = NULL
                                WHERE session_id = :session_id"
            $updateDataParams = array(':session_id' => $session_id);
            */
        }

        // FREE RESOURCE
        $this->PDOX->commit();
        return $opponentData['submitted_data'];
    }

    function getOpponentName($session_id, $player){
        /* Returns the name of the player opposing player '$player' in session with session_id '$session_id'
           Idea is that every player will call this once in a session, store the result, and never need this function again
        */
        // maybe opponent name grabbed by multiplayer submission for a reason.
        // A single DB call might be nice if that's the only place I need it: grab all potentially useful data in a single call, use what's needed
        $getOppNameQuery = "SELECT player
                            FROM results
                            WHERE session_id = :session_id
                            AND player != :player";
        $getOppNameParams = array(':session_id' => $session_id,
                                  ':player'     => $player);
        $opponent_res = $this->$PDOX->rowDie($getOppNameQuery, $getOppNameParams);
        return $opponent_res !== NULL ? $opponent_res['player'] : NULL;
    }

    function getOpponentData($group_id) {
        /* called by player who went first and does not yet have access to opponent's data */
        /* it seem this functionality may be better suited to leave to the JS socket connection */
        /* when first player submits, don't know if someone else in the session: also, what would they do with that info? */
        /* when second player submits, they can send their data to the the first player that they know is in the session and waiting for their submission */
        $query = "SELECT * FROM {$this->p}live_multiplayer_groups WHERE group_id = :group_id LIMIT 1;";
        $arr = array(':group_id' => $group_id);
        $group = $this->PDOX->rowDie($query, $arr);

        $opponentData=[$session['p1'],$session['p1Data']];

        $query = "UPDATE {$this->p}live_multiplayer_groups
                  SET p1        = NULL,
                      p1Data    = NULL
                  WHERE group_id = :group_id";
        $arr = array(':id'=>$group['id']);
        $this->PDOX->rowDie($query, $arr);

        return json_encode($opponentData);
    }

    function getCourses($owner) {
        $query = "SELECT * FROM {$this->p}courses WHERE owner = :owner;";
        $arr = array(':owner' => $owner);
        $result = $this->PDOX->allRowsDie($query, $arr);
        return $result;
    }

    function getCourseNameSection($course_id) {
        $query = "SELECT name, section FROM {$this->p}courses WHERE course_id = :course_id;";
        $arr = array(':course_id' => $course_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getGames($course_id) {
        $query = "SELECT * FROM {$this->p}games WHERE course_id = :course_id;";
        $arr = array(':course_id' => $course_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getGameInfo($game_id) {
        $query = "SELECT * FROM {$this->p}games WHERE game_id = :game_id;";
        $arr = array(':game_id' => $game_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function gameIsLive($game_id) {
        $query = "SELECT live FROM {$this->p}games WHERE game_id = :game_id LIMIT 1;";
        $arr = array(":game_id" => $game_id);
        $result= $this->PDOX->rowDie($query, $arr);
        return $result['live'];
    }

}

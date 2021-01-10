<?php
namespace QW\DAO;

class QW_DAO {

    private $PDOX;
    private $db_p;
    private $app_p;

    public function __construct($PDOX, $db_p, $app_p) {
        $this->PDOX = $PDOX;
        $this->db_p = $db_p;
        $this->app_p = $app_p;


        $this->avatarNameToId           = $this->generateNameToIdMapForTable("avatar");
        $this->gameTypeNameToId         = $this->generateNameToIdMapForTable("game_type");
        $this->difficultyNameToId       = $this->generateNameToIdMapForTable("difficulty");
        $this->marketStructureNameToId  = $this->generateNameToIdMapForTable("market_structure");
        $this->macroEconomyNameToId     = $this->generateNameToIdMapForTable("macro_economy");
    }

    function snakeToCamel($str){
        return array_reduce(explode('_', $str), function ($curr, $nxt){ return [$curr[0] . ($curr[1] ? ucfirst($nxt) : $nxt), TRUE]; }, ["", FALSE]);
    }

    function generateNameToIdMapForTable($singObj){

        $pluralize = function($str) {
            if( strlen($str) > 1 && substr($str, strlen($str) - 1) == "y")  return substr($str, 0, strlen($str) - 1) . "ies";
            else                                                            return $str . "s";
        };

        return array_merge(array_reduce($this->PDOX->allRowsDie("SELECT {$singObj}_name, {$singObj}_id FROM {$this->db_p}{$this->app_p}{$pluralize($singObj)}"),
                                        function ($curr, $nxt) use ($singObj) { return array_merge($curr, array($nxt["{$singObj}_name"] => intval($nxt["{$singObj}_id"]))); },
                                        array()),
                           array("" => ""));
    }

    function addCourse($courseName, $section, $adminId, $avatar) {
        $query = "INSERT INTO {$this->db_p}{$this->app_p}courses (name, section, user_id, avatar_id)
                  VALUES (:name, :section, :user_id, :avatar_id)";
        $arr = array(':name'    => $courseName,
                     ':section' => $section,
                     ':user_id' => $adminId,
                     ':avatar_id'  => $this->avatarNameToId[$avatar]);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteCourse($course_id) {
        $query = "DELETE FROM {$this->db_p}{$this->app_p}courses WHERE course_id = :course_id";
        $arr = array(':course_id' => $course_id);
        $this->PDOX->queryDie($query, $arr);
    }

    function addGame($name, $type, $courseId, $diff, $marketStruct, $macroEcon, $isReplayable, $timeLimit, $numRounds, $demandIntercept, $demandSlope, $fixedCost, $unitCost) {
        $query = "INSERT INTO {$this->db_p}{$this->app_p}games   ( name,  game_type_id,  course_id,  difficulty_id,  is_replayable,  market_structure_id,  macro_economy_id,  time_limit,  num_rounds,  demand_intercept,  demand_slope,  fixed_cost,  unit_cost/*, max_quantity*/)
                  VALUES                                         (:name, :game_type_id, :course_id, :difficulty_id, :is_replayable, :market_structure_id, :macro_economy_id, :time_limit, :num_rounds, :demand_intercept, :demand_slope, :fixed_cost, :unit_cost/*, :max_quantity*/)";
        //var_dump($isReplayable);
        $arr = array(':name'                => $name,
                     ':game_type_id'        => $this->gameTypeNameToId[$type] ?: 1,
                     ':course_id'           => $courseId,
                     ':difficulty_id'       => $this->difficultyNameToId[$diff] ?: 1,
                     ':market_structure_id' => $this->marketStructureNameToId[$marketStruct] ?: 1,
                     ':macro_economy_id'    => $this->macroEconomyNameToId[$macroEcon] ?: 1,
                     ':is_replayable'       => $isReplayable,
                     ':time_limit'          => $timeLimit,
                     ':num_rounds'          => $numRounds,
                     ':demand_intercept'    => $demandIntercept,
                     ':demand_slope'        => $demandSlope,
                     ':fixed_cost'          => $fixedCost,
                     ':unit_cost'           => $unitCost);
        $this->PDOX->queryDie($query, $arr);
    }

    function updateGame($gameId, $name, $difficultyName, $marketStructureName, $macroEconomyName, $isReplayable, $timeLimit, $numRounds, $demandIntercept, $demandSlope, $fixedCost, $unitCost/*, $maxQuantity*/) {
        $query = "UPDATE {$this->db_p}{$this->app_p}games
                  SET name                  =  :name,
                      difficulty_id          =  :difficulty_id,
                      market_structure_id    =  :market_structure_id,
                      macro_economy_id       =  :macro_economy_id,
                      is_replayable          =  :is_replayable,
                      time_limit             =  :time_limit,
                      num_rounds             =  :num_rounds,
                      demand_intercept       =  :demand_intercept,
                      demand_slope           =  :demand_slope,
                      fixed_cost             =  :fixed_cost,
                      unit_cost              =  :unit_cost
                  WHERE game_id = :game_id";
        $arr = array(':name'                => $name,
                     ':difficulty_id'       => $this->difficultyNameToId[$difficultyName],
                     ':market_structure_id' => $this->marketStructureNameToId[$marketStructureName],
                     ':macro_economy_id'    => $this->macroEconomyNameToId[$macroEconomyName],
                     ':is_replayable'       => $isReplayable,
                     ':time_limit'          => $timeLimit,
                     ':num_rounds'          => $numRounds,
                     ':demand_intercept'    => $demandIntercept,
                     ':demand_slope'        => $demandSlope,
                     ':fixed_cost'          => $fixedCost,
                     ':unit_cost'           => $unitCost,
                     ':game_id'             => $gameId);
        $this->PDOX->queryDie($query, $arr);
    }

    function deleteGame($gameId) {
        $query = "DELETE FROM {$this->db_p}{$this->app_p}games WHERE game_id = :game_id";
        $arr = array(':game_id' => $gameId);
        $this->PDOX->queryDie($query, $arr);
    }

    function getInitPriceHistory($gameId) {
        $query = "SELECT init_price_history FROM {$this->db_p}{$this->app_p}games WHERE game_id = :game_id;";
        $arr = array(':game_id' => $gameId);
        return $this->PDOX->rowDie($query, $arr)['init_price_history'];
    }

    function isGameLive($gameId) {
        $query = "SELECT live, market_struct FROM {$this->db_p}{$this->app_p}games WHERE game_id = :game_id;";
        $arr = array(':game_id' => $gameId);
        return $this->PDOX->rowDie($query, $arr);
    }



    /* I suggest the following */
    /*  post Reed meeting: want to disallow playing multiple sessions for monopoly, oligopoly since these modes
        have a single optimal move for all sessions

        maybe, can just use this information in game_main. in other modes, don't even use this to check

    */


    function toggleGameLive($gameId, $initPriceHistory){
        $query = "SELECT live FROM {$this->db_p}{$this->app_p}games WHERE game_id = :game_id  LIMIT 1;";

        $arr = array(':game_id' => $gameId);
        if ($this->PDOX->rowDie($query, $arr)["live"]) {
            $query = "UPDATE {$this->db_p}{$this->app_p}games SET live = 0 WHERE game_id = :game_id";
            $this->PDOX->queryDie($query, $arr);
            return false;
        } else {
            $query = "UPDATE {$this->db_p}{$this->app_p}games SET live = 1, init_price_history = :init_price_history WHERE game_id = :game_id";
            $arr = array(':game_id'            => $gameId,
                         ':init_price_history' => $initPriceHistory);
            $this->PDOX->queryDie($query, $arr);
            return true;
        }
    }

    function submitSingleplayerQuantity($sessionId, $playerId, $quantity){
        // maybe transaction should still be used to prevent instructor from viewing stale data?

        // for singleplayer game, session should be enough, but I'll hold off on the premature optimization
        $updateResultStmt    = $this->PDOX->prepare("UPDATE {$this->db_p}{$this->app_p}results
                                                     SET quantity_history = :quantity_history
                                                     WHERE session_id = :session_id
                                                       AND user_id = :user_id");

        // no one else will be updating this row so it's fine to read it outside of the transaction
        $resultRowQuery      = "SELECT *
                                FROM {$this->db_p}{$this->app_p}results
                                WHERE session_id = :session_id
                                  AND user_id = :user_id";
        $resultRowParams     = array(':session_id' => $sessionId,
                                     ':user_id'  => $playerId);
        $resultRow           = $this->PDOX->rowDie($resultRowQuery, $resultRowParams);

        $updatedQuantityHistory = strlen($resultRow['quantity_history'])
                                    ? $resultRow['quantity_history'] . "," . $quantity
                                    : $quantity;
        $updateResultParams  = array_merge($resultRowParams, array(":quantity_history" => $updatedQuantityHistory));

        // don't want two players both checking the shared resource and possibly updating it to be interleaved
        $this->PDOX->beginTransaction();

        // I should ensure that session and result rows are guaranteed to exist
        //  - joining a game does indeed create session (if needed) and result rows

        // update quantity history
        $updateResultStmt->execute($updateResultParams);

        // use of shared resources finished
        //  - results rows are shared resources because they will be queried to get opponent quantity
        $this->PDOX->commit();

    }

    function retrieveGameResults($gameId){
        $retrieveQuery =   "SELECT {$this->db_p}{$this->app_p}sessions.session_id, displayname, quantity_history
                            FROM   {$this->db_p}{$this->app_p}sessions
                            JOIN   {$this->db_p}{$this->app_p}results
                              ON   {$this->db_p}{$this->app_p}sessions.session_id = {$this->db_p}{$this->app_p}results.session_id
                            JOIN   {$this->db_p}lti_user
                              ON   {$this->db_p}lti_user.user_id = {$this->db_p}{$this->app_p}results.user_id
                            WHERE  {$this->db_p}{$this->app_p}sessions.game_id = :game_id";
        $retrieveParams =   array(":game_id" => $gameId);
        $result = $this->PDOX->allRowsDie($retrieveQuery, $retrieveParams);

        $player_data=array();
        foreach ($result as $row) {
            // easily expandable to add more fields to obj to return
            foreach (array("quantity_history") as $field){
                $splitData      = array_map('intval', explode(',', $row[$field]));
                $splitWithName  = array('playerName'=> $row['displayname'], $this->snakeToCamel($field)[0] => $splitData, 'sessionId' => $row['session_id']);
                array_push($player_data, $splitWithName);
            }
        }
        return json_encode($player_data);

        $toReturn = array('player_data' => $player_data);
        return json_encode($toReturn);
    }

    function retrieveSessionResults($sessionId){
        $retrieveQuery =   "SELECT fixed_cost, unit_cost, price_history, quantity_history
                            FROM {$this->db_p}{$this->app_p}games
                            JOIN {$this->db_p}{$this->app_p}sessions
                              ON {$this->db_p}{$this->app_p}games.game_id = {$this->db_p}{$this->app_p}sessions.game_id
                            JOIN {$this->db_p}{$this->app_p}results
                              ON {$this->db_p}{$this->app_p}sessions.session_id = {$this->db_p}{$this->app_p}results.session_id
                            WHERE session_id = :session_id";
        $retrieveParams =   array(":session_id" => $sessionId);

        $data=[];
        foreach ($this->PDOX->allRowsDie($retrieveQuery, $retrieveParams) as $row) {
            foreach (array("quantity_history") as $field){
                $splitData      = array_map('intval', explode(',', $row[$field]));
                $splitWithName  = array('player'=> $row['player'], $field => $splitData);
                array_push($data, $splitWithName);
            }
        }
        return json_encode($data);
    }

    function joinSingleplayerGame($gameId, $playerId){
        // do I need to lock the sessions table here? I will updating it. Don't want someone else to pull the sessions table, we pull the table, they update it, push, we update it, push, and our push overrides theirs
        //  - seems like this should not be a problem: the "push" is like a push to an array, and not like a push to git
        //  - i.e., seems that if two people insert, one will add one row and one will add another
        //  - in fact, the only reason I'm pulling at all is to check if I should start a new session or continue an old one

        // order by session_id's descending so I can just grab the most recent one (higher id ==> created more recently)
        $findStartedSessionsQuery =    "SELECT num_rounds, quantity_history, is_replayable, {$this->db_p}{$this->app_p}sessions.session_id
                                        FROM {$this->db_p}{$this->app_p}sessions
                                            JOIN {$this->db_p}{$this->app_p}results
                                            ON {$this->db_p}{$this->app_p}sessions.session_id = {$this->db_p}{$this->app_p}results.session_id
                                            JOIN {$this->db_p}{$this->app_p}games
                                            ON {$this->db_p}{$this->app_p}sessions.game_id = {$this->db_p}{$this->app_p}games.game_id
                                        WHERE {$this->db_p}{$this->app_p}games.game_id = :game_id AND {$this->db_p}{$this->app_p}results.user_id = :user_id
                                        ORDER BY session_id DESC
                                        LIMIT 1";
        $mostRecentGameSession = $this->PDOX->rowDie($findStartedSessionsQuery, [ ":game_id" => $gameId,
                                                                                  ":user_id" => $playerId ]);

        if( $mostRecentGameSession ){
            $roundsCompleted = count(explode(',', $mostRecentGameSession['quantity_history']));
            // there is an existing session
            if( !$mostRecentGameSession['is_replayable'] && $roundsCompleted >= $mostRecentGameSession['num_rounds'] )
                // player has completed a session for a non-replayable game
                // (shouldn't ever happen that $roundsCompleted > mostRecentGameSession , but just in case...)
                return json_encode(["result"      => "completed",
                                    "quantityHistory" => $mostRecentGameSession['quantity_history']]);
            else if( $roundsCompleted < $mostRecentGameSession['num_rounds']  )
                // player left game session in progress
                return json_encode(["result"            => "rejoined",
                                    "sessionId"         => $mostRecentGameSession['session_id'],
                                    "quantityHistory"   => $mostRecentGameSession['quantity_history']]);
        }
        // either no session was in progress or all sessions have been completed and replays are allowed: start new session
        $createSessionQuery =  "INSERT INTO {$this->db_p}{$this->app_p}sessions
                                (game_id) VALUES (:game_id)";
        $this->PDOX->queryDie($createSessionQuery, array(':game_id' => $gameId));

        $sessionId = $this->PDOX->lastInsertId();

        $createResultQuery = "INSERT INTO {$this->db_p}{$this->app_p}results  (session_id, user_id) VALUES (:session_id, :user_id)";
        $this->PDOX->queryDie($createResultQuery, array(':user_id' => $playerId, ':session_id' => $sessionId));

        return json_encode(["result"    => "new",
                            "sessionId" => $sessionId]);
    }


    function joinMultiplayerGame($gameId, $playerId) {
        /* what's going on here: using information from the results table to tell me which session_id
            - research indicates that selecting from the join table will lock both rows of both TABLES

            what I want to happen when 2 people are joining with one empty game
                - first player to execute game search select query receives empty game, joins - atomic, transaction
                - second player to execute game search select query is not only blocked from receiving the result, but is blocked from executing the query until the first transaction finishes

            other option: try to get row. When received row, if it is full

            situation to avoid:
                - p1 joins, causing creation of new game
                - p2, p3, p4 join in quick succession, all try to lock the same rows from the join.
                - p2 gets lock first, joins game. game now full
                - p3 gets lock second, sees game is full, creates new game
                - p4 gets lock last, sees game is full, creates new game
                - 4 players, but 2 unmatched.
                - in other words, it would be ideal if the table lock blocked p4's query from execute until p3's transaction completes
                    - (as opposed to the query being run, but the results being blocked until p3's transaction completes)

            Ran this experiment on command line with 4 terminal windows
                - procedure:
                    - p1, p2, p3, p4 start transactions and request data in that order
                    - p1, p2, p3, p4 all act according to their results, then commit, in ascending order of player #
                        - (1 acts, commits, giving 2 access to results; 2 acts, commits, giving 3 access to results; 3 acts, commits, giving 4 access to results; 4 acts, commits)
                        - note: need to execute this process fast, lest the transactions time out: have all queries ready to paste in
                - result: p1 & p2 wound up in one game, p3 & p4 in another
                    - just as I'd hoped, p4's query for the data does not execute until p3 has committed its transaction

            If for some reason this becomes problematic or doesn't work like I think, an alternative idea is the following:
            idea: intermediate blocking rows
                - first player locks a specific row to indicate intent to find an open session of a game
                - second player tried to lock this row: is blocked
                    - when this blocking row is unlocked, the second player receives the lock and is THEN allowed to execute the find open session query
                - might be reasonable to use the rows of the games table for this purpose, but don't want another teacher updating their game to be blocked
        */

        // pre-prepare possibly needed SQL statements so that the transaction can take as little time as possible

        // computes the number of players in each game, returns the game with the least players (1), or, if all are full, the game with the highest session_id
        //  - cannot remember reason for going with highest session id: seems lowest would be better so students who have been waiting the longest get their match first
        $findOpenSessionStmt = $this->PDOX->prepare(" SELECT econ_sim_results.session_id, GROUP_CONCAT(displayname SEPARATOR ',') as current_players, COUNT(econ_sim_results.session_id) as players
                                                      FROM econ_sim_results
                                                      JOIN econ_sim_sessions
                                                        ON econ_sim_results.session_id = econ_sim_sessions.session_id
                                                      JOIN lti_user
                                                        ON econ_sim_results.user_id = lti_user.user_id
                                                      WHERE complete = FALSE AND game_id = :game_id
                                                      GROUP BY econ_sim_results.session_id
                                                      ORDER BY players, econ_sim_results.session_id /*DESC*/
                                                      LIMIT 1
                                                      FOR UPDATE ");

        $joinSessionStmt     = $this->PDOX->prepare("INSERT INTO {$this->db_p}{$this->app_p}results
                                                     (session_id, user_id) VALUES (:session_id, :user_id)");
        $createSessionStmt   = $this->PDOX->prepare("INSERT INTO {$this->db_p}{$this->app_p}sessions
                                                     (game_id) VALUES (:game_id)");
        $gameIdSqlParam     = array(':game_id' => $gameId);
        /*  Possibilities:
            - no sessions exist for the game: empty returns (FALSE)
            - session returned has count of 1
            - session returned has count of 2
        */
        $sessionToJoinId = -1;
        $ready = FALSE;
        $this->PDOX->beginTransaction();
        // the following statement blocks until other join games are complete, meaning the findOpenGameQuery will not execute until we have the lock
        $findOpenSessionStmt->execute($gameIdSqlParam);
        $session = $findOpenSessionStmt->fetch($this->PDOX::FETCH_ASSOC);
        // the following if sets up sessionToJoinId so that a session may be joined (i.e. a results row created, afterward)
        if ($session and $session['players'] == 1) {
            // game is open
            $sessionToJoinId = $session['session_id'];
            $ready = TRUE;
            $opponent = $session['current_players'];
        } else {
            // create new game
            $createSessionStmt->execute($gameIdSqlParam);
            // PDO(X)->lastInsertId gives last id inserted, crucially, FOR THIS CONNECTION ONLY
            $sessionToJoinId = $this->PDOX->lastInsertId();
            $ready = FALSE;
        }
        $joinSessionParams = array(':user_id' => $playerId, ':session_id' => $sessionToJoinId);
        $joinSessionStmt->execute($joinSessionParams);
        $this->PDOX->commit();

        $toReturn = ["sessionId" => $sessionToJoinId,
                     "result" => $ready ? "ready" : "waiting"];
        if( $ready ) $toReturn['yourOppName'] = $opponent;
        return json_encode($toReturn);
    }

function submitMultiplayerQuantity($sessionId, $playerId, $quantity) {

        $retrieveQuantitiesStmt = $this->PDOX->prepare("SELECT num_rounds, quantity_history, user_id
                                                        FROM   {$this->db_p}{$this->app_p}sessions
                                                        JOIN   {$this->db_p}{$this->app_p}results
                                                          ON   {$this->db_p}{$this->app_p}sessions.session_id = {$this->db_p}{$this->app_p}results.session_id
                                                        JOIN   {$this->db_p}{$this->app_p}games
                                                          ON   {$this->db_p}{$this->app_p}sessions.game_id = {$this->db_p}{$this->app_p}games.game_id
                                                        WHERE  {$this->db_p}{$this->app_p}sessions.session_id = :session_id
                                                        FOR UPDATE");
        $retrieveQuantitiesParam= array(":session_id" => $sessionId);

        $recordQuantityStmt     = $this->PDOX->prepare("UPDATE {$this->db_p}{$this->app_p}results
                                                        SET quantity_history = :quantity_history
                                                        WHERE session_id = :session_id
                                                          AND user_id    = :user_id");
        $recordQuantityParams   = array(":session_id" => $sessionId,
                                        ":user_id"    => $playerId);

        $quantityHistories = array();
        $oppId;

        $this->PDOX->beginTransaction();
        $retrieveQuantitiesStmt->execute($retrieveQuantitiesParam);
        foreach( $retrieveQuantitiesStmt->fetchAll($this->PDOX::FETCH_ASSOC) as $row ){
            $quantityHistories[$row['user_id']] = strlen($row['quantity_history']) ? array_map('intval', explode(',', $row['quantity_history'])) : [];
            if($row['user_id'] != $playerId)    $oppId = $row['user_id'];
            $numRounds = $row['num_rounds'];
        }

        // same length ==> submit and wait for opponents submission
        // shorter length ==> submit and return opponent's last submission
        // it should never happen that your history is longer than opponents when submitted data: that would mean you are two rounds ahead (one ahead already, and about to submit again)
        // thus, we can reduce to two cases: equality and inequality

        $oppAlreadySubmitted = count($quantityHistories[$playerId]) != count($quantityHistories[$oppId]);

        array_push($quantityHistories[$playerId], $quantity);
        $recordQuantityParams[':quantity_history'] = implode(',', $quantityHistories[$playerId]);
        $recordQuantityStmt->execute($recordQuantityParams);

        $this->PDOX->commit();

        return $oppAlreadySubmitted ? $quantityHistories[$oppId][count($quantityHistories[$oppId]) - 1] : NULL;
    }

    function getCourses($adminId) {
        $query =   "SELECT *
                    FROM {$this->db_p}{$this->app_p}courses
                        JOIN {$this->db_p}{$this->app_p}avatars
                        ON {$this->db_p}{$this->app_p}courses.avatar_id = {$this->db_p}{$this->app_p}avatars.avatar_id
                    WHERE user_id = :user_id";
        $arr = array(':user_id' => $adminId);
        $result = $this->PDOX->allRowsDie($query, $arr);
        return $result;
    }

    function getCourseNameSection($courseId) {
        $query = "SELECT name, section FROM {$this->db_p}{$this->app_p}courses WHERE course_id = :course_id;";
        $arr = array(':course_id' => $courseId);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getGames($courseId) {
        $query = "SELECT *
                  FROM {$this->db_p}{$this->app_p}games
                    JOIN {$this->db_p}{$this->app_p}game_types
                      ON {$this->db_p}{$this->app_p}games.game_type_id = {$this->db_p}{$this->app_p}game_types.game_type_id
                    JOIN {$this->db_p}{$this->app_p}difficulties
                      ON {$this->db_p}{$this->app_p}games.difficulty_id = {$this->db_p}{$this->app_p}difficulties.difficulty_id
                    JOIN {$this->db_p}{$this->app_p}market_structures
                      ON {$this->db_p}{$this->app_p}games.market_structure_id = {$this->db_p}{$this->app_p}market_structures.market_structure_id
                    JOIN {$this->db_p}{$this->app_p}macro_economies
                      ON {$this->db_p}{$this->app_p}games.macro_economy_id = {$this->db_p}{$this->app_p}macro_economies.macro_economy_id
                  WHERE course_id = :course_id";
        $arr = array(':course_id' => $courseId);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getGameInfo($gameId) {
        $query = "SELECT *
                  FROM {$this->db_p}{$this->app_p}games
                    JOIN {$this->db_p}{$this->app_p}game_types
                      ON {$this->db_p}{$this->app_p}games.game_type_id = {$this->db_p}{$this->app_p}game_types.game_type_id
                    JOIN {$this->db_p}{$this->app_p}difficulties
                      ON {$this->db_p}{$this->app_p}games.difficulty_id = {$this->db_p}{$this->app_p}difficulties.difficulty_id
                    JOIN {$this->db_p}{$this->app_p}market_structures
                      ON {$this->db_p}{$this->app_p}games.market_structure_id = {$this->db_p}{$this->app_p}market_structures.market_structure_id
                    JOIN {$this->db_p}{$this->app_p}macro_economies
                      ON {$this->db_p}{$this->app_p}games.macro_economy_id = {$this->db_p}{$this->app_p}macro_economies.macro_economy_id
                  WHERE game_id = :game_id";
        $arr = array(':game_id' => $gameId);
        return $this->PDOX->rowDie($query, $arr);
    }

    function gameIsLive($gameId) {
        $query = "SELECT live FROM {$this->db_p}{$this->app_p}games WHERE game_id = :game_id LIMIT 1;";
        $arr = array(":game_id" => $gameId);
        $result= $this->PDOX->rowDie($query, $arr);
        return $result['live'];
    }


    /* === methods that were used at one point but are now never actually used === */

    // NOTE: proof of concept - never actually used in game_main
    function getOpponentData($group_id) {
        /* called by player who went first and does not yet have access to opponent's data */
        /* it seem this functionality may be better suited to leave to the JS socket connection */
        /* when first player submits, don't know if someone else in the session: also, what would they do with that info? */
        /* when second player submits, they can send their data to the the first player that they know is in the session and waiting for their submission */
        $query = "SELECT * FROM {$this->db_p}{$this->app_p}live_multiplayer_groups WHERE group_id = :group_id LIMIT 1;";
        $arr = array(':group_id' => $group_id);
        $group = $this->PDOX->rowDie($query, $arr);

        $opponentData=[$session['p1'],$session['p1Data']];

        $query = "UPDATE {$this->db_p}{$this->app_p}live_multiplayer_groups
                  SET p1        = NULL,
                      p1Data    = NULL
                  WHERE group_id = :group_id";
        $arr = array(':id'=>$group['id']);
        $this->PDOX->rowDie($query, $arr);

        return json_encode($opponentData);
    }


    /*  should this take a game or another parameter? So a player can participate in multiple games?
        should a player only be allowed to play a single game in a session?
        I could see how this could potentially be useful if we want to reconnect a student to a game they left, but otherwise not sure the purpose
            - could even only support this option for multiplayer since it might be hard to re-coordinate the two players if both leave
    */
    // NOTE: proof of concept - never actually used in game_main
    function hasPlayerCompletedGame($playerId, $gameId){
        $query =    "SELECT complete
                     FROM {$this->db_p}{$this->app_p}sessions JOIN {$this->db_p}{$this->app_p}results
                     ON {$this->db_p}{$this->app_p}sessions.session_id = {$this->db_p}{$this->app_p}results.session_id
                     WHERE user_id = :user_id AND game_id = :game_id";
        $params = array(':user_id' => $playerId,
                        ':game_id' => $gameId);
        return $this->PDOX->rowDie($query, $params)["complete"];
    }

    // NOTE: proof of concept - never actually used in game_main
    function getOpponentName($sessionId, $playerId){
        /* Returns the name of the player opposing player '$player' in session with session_id '$session_id'
           Idea is that every player will call this once in a session, store the result, and never need this function again
        */
        // maybe opponent name grabbed by multiplayer submission for a reason.
        // A single DB call might be nice if that's the only place I need it: grab all potentially useful data in a single call, use what's needed
        $getOppNameQuery = "SELECT username
                            FROM   {$this->db_p}{$this->app_p}results JOIN {$this->db_p}lti_user
                              ON   {$this->db_p}{$this->app_p}results.user_id = {$this->db_p}lti_user.user_id
                            WHERE  session_id = :session_id
                              AND  {$this->db_p}lti_user.user_id != :user_id";
        $getOppNameParams = array(':session_id' => $sessionId,
                                  ':user_id'    => $playerId);
        $opponentRes = $this->$PDOX->rowDie($getOppNameQuery, $getOppNameParams);
        return $opponentRes !== NULL ? $opponentRes['username'] : NULL;
    }

}

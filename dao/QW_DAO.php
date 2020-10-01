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

    function playerCompletedGame($player) {
        $query = "SELECT complete FROM {$this->p}results WHERE player = :player LIMIT 1;";
        $arr = array(':player' => $player);
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

    function updateResults($complete, $group_id, $game_id, $username, $opponent, $quantity, $revenue, $profit, $return, $price, $unit_cost, $total_cost) {
        $query = "SELECT * FROM {$this->p}results WHERE group_id = :group_id AND player = :player;";
        $arr = array(':group_id' => $group_id, ':player' => $username);
        $data = $this->PDOX->rowDie($query, $arr);

        if ($data) {
            $quantityHist   = $data['player_quantity']  .",".   $quantity;
            $revenueHist    = $data['player_revenue']   .",".   $revenue;
            $profitHist     = $data['player_profit']    .",".   $profit;
            $returnHist     = $data['player_return']    .",".   $return;
            $priceHist      = $data['price']            .",".   $price;
            $unitCostHist   = $data['unit_cost']        .",".   $unit_cost;
            $totalCostHist  = $data['total_cost']       .",".   $total_cost;

            // unit cost not updated?
            $query = "UPDATE {$this->p}results
                      SET
                          complete          =  :complete,
                          -- this doesn't seem like something we need to update
                          /* game_id           =  :game_id */
                          player_quantity   =  :quantity,
                          player_revenue    =  :revenue,
                          player_profit     =  :profit,
                          player_return     =  :return,
                          price             =  :price,
                          unit_cost         =  :unit_cost,
                          total_cost        =  :total_cost
                      WHERE group_id = :group_id AND player = :player";
            $arr = array(':complete'        => $complete,
                         // doesn't seem like something we need to update
                         // ':game_id'         => $game_id,
                         ':quantity'        => $quantity,
                         ':revenue'         => $revenue,
                         ':profit'          => $profit,
                         ':return'          => $return,
                         ':price'           => $price,
                         ':unit_cost'       => $unitCostHist,
                         ':total_cost'      => $total_cost,
                         ':group_id'        => $group_id,
                         ':player'          => $username    );

            $arr = array(':complete'        => $complete,
                         // doesn't seem like something we need to update
                         // ':game_id'         => $game_id,
                         ':quantity'        => $quantityHist,
                         ':revenue'         => $revenueHist,
                         ':profit'          => $profitHist,
                         ':return'          => $returnHist,
                         ':price'           => $priceHist,
                         ':unit_cost'       => $unitCostHist,
                         ':total_cost'      => $totalCostHist,
                         ':group_id'        => $group_id,
                         ':player'          => $username    );

            $this->PDOX->queryDie($query, $arr);
        } else {
            $query = "INSERT INTO {$this->p}results (group_id, game_id, player, opponent, player_quantity, player_revenue, player_profit, player_return, price, unit_cost, total_cost)
                      VALUES (:group_id, :game_id, :player, :opponent, :quantity, :revenue, :profit, :return, :price, :unit_cost, :total_cost)";
            $arr = array(':group_id'        => $group_id,
                         ':game_id'         => $game_id,
                         ':player'          => $username,
                         ':opponent'        => $opponent,
                         ':quantity'        => $quantity,
                         ':revenue'         => $revenue,
                         ':profit'          => $profit,
                         ':return'          => $return,
                         ':price'           => $price,
                         ':unit_cost'       => $unit_cost,
                         ':total_cost'      => $total_cost );
            $this->PDOX->queryDie($query, $arr);
        }
    }

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

    function retrieveValueFromGameResults($val, $game_id){//, $groupId, $usr) {
        $valid_vals = ["player_quantity", "price", "player_revenue", "player_profit"];
        if (!in_array($val, $valid_vals))       return json_encode([]);
        // marker cannot be used to specify column name
        // https://stackoverflow.com/questions/46259185/how-to-use-prepared-statements-in-php-to-choose-with-select-a-column-of-the-da
        // instead, I will use backticks and inject $val directly into the query
        // this should be ok since I am double checking that $val is one of a few expected values
        $query = "SELECT player, group_id, `${val}` FROM {$this->p}results WHERE game_id = :game_id";
        $arr = array(':game_id' => $game_id);

        $data=[];
        foreach ($this->PDOX->allRowsDie($query, $arr) as $row) {
            $splitData = array_map('intval', explode(',', $row[$val]));
            $splitWithName = array('username'=> $row['player'], 'group'=> $row['group_id'], 'data'=> $splitData);
            array_push($data, $splitWithName);
        }
        return json_encode($data);
    }

    //
    function joinMultiplayerGame($game_id, $username, $group_id) {
        $query = "SELECT * FROM {$this->p}live_multiplayer_groups WHERE game_id = :game_id AND player2 IS NULL LIMIT 1;";
        $arr = array(':game_id' => $game_id);
        $game = $this->PDOX->rowDie($query, $arr);

        if ($game) {
            $query = "UPDATE {$this->p}live_multiplayer_groups SET game_id = NULL, player2 = :player WHERE game_id = :game_id;";
            $arr = array(':player' => $username, ':game_id' => $game['id']);
            $this->PDOX->rowDie($query, $arr);
            return [$game['group_id'], $game['player1']];
        } else {
            //$query = "INSERT INTO {$this->p}live_multiplayer_groups (group_id, game_id, player1) VALUES (:group_id, :game_id, :player1)";
            //$arr = array(':group_id' => $group_id, ':game_id' => $game_id, ':player1' => $username);
            $query = "INSERT INTO {$this->p}live_multiplayer_groups (game_id, player1) VALUES (:game_id, :player1)";
            $arr = array(':game_id' => $game_id, ':player1' => $username);
            $this->PDOX->queryDie($query, $arr);
        }
    }

    function multiplayerSubmission($group_id, $username, $quantity) {
        $query = "SELECT * FROM {$this->p}live_multiplayer_groups WHERE group_id = :group_id LIMIT 1;";
        $arr = array(':groupId' => $group_id);
        $existing_data = $this->PDOX->rowDie($query, $arr);

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

    function getOpponentData($group_id) {
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

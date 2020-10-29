<?php

$app_p = "econ_sim_";

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}{$app_p}course",
    "drop table if exists {$CFG->dbprefix}{$app_p}games",
    "drop table if exists {$CFG->dbprefix}{$app_p}results",
    "drop table if exists {$CFG->dbprefix}{$app_p}sessions"
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}{$app_p}courses",

   "create table {$CFG->dbprefix}{$app_p}courses (
    course_id           INT(6)          UNSIGNED AUTO_INCREMENT,
    name                VARCHAR(30)     NOT NULL,
    section             VARCHAR(30)     NOT NULL,
    owner               VARCHAR(30)     NOT NULL,
    avatar              VARCHAR(30)     DEFAULT 'fa-chart-bar',
    reg_date            TIMESTAMP,

    PRIMARY KEY(course_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


    array( "{$CFG->dbprefix}{$app_p}games",

   "create table {$CFG->dbprefix}{$app_p}games (
    game_id             INT(6)          UNSIGNED AUTO_INCREMENT,
    name                VARCHAR(30)     NOT NULL,
    live                BOOLEAN         DEFAULT FALSE,
    type                VARCHAR(30)     NOT NULL,
    course_id           INT(6)          NOT NULL,
    difficulty          VARCHAR(30)     NOT NULL,
    mode                VARCHAR(30)     NOT NULL,
    market_struct       VARCHAR(30)     NOT NULL,
    macro_econ          VARCHAR(30)     NOT NULL,
    time_limit          INT(6)          NOT NULL,
    num_rounds          INT(6)          NOT NULL,
    demand_intercept    INT(6)          NOT NULL,
    demand_slope        INT(6)          NOT NULL,
    fixed_cost          INT(6)          NOT NULL,
    const_cost          INT(6)          NOT NULL,
    max_quantity        INT(6)          NOT NULL,
    /* equilibrium         INT(6)          DEFAULT NULL, */
    init_price_hist     VARCHAR(300)    DEFAULT NULL,
    reg_date            TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_1`
        FOREIGN KEY (`course_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}courses` (`course_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY(game_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

    array( "{$CFG->dbprefix}{$app_p}results",

    "create table {$CFG->dbprefix}{$app_p}results(
    /* result_id           INT(6)          UNSIGNED AUTO_INCREMENT, */
    session_id          INT(6)          NOT NULL,
    player              VARCHAR(30)     NOT NULL,
    quantity_history    VARCHAR(300)    NOT NULL DEFAULT '',
    revenue_history     VARCHAR(300)    NOT NULL DEFAULT '',
    profit_history      VARCHAR(300)    NOT NULL DEFAULT '',
    return_history      VARCHAR(300)    NOT NULL DEFAULT '',
    price_history       VARCHAR(300)    NOT NULL DEFAULT '', /* if the following are session wide, move them to sessions */
    unit_cost_history   VARCHAR(300)    NOT NULL DEFAULT '',
    total_cost_history  VARCHAR(300)    NOT NULL DEFAULT '',

    /*  having trouble adding this foreign key constraint, not sure why
        probably because I'm trying to create this table first: try order swap
    */
    /*
    CONSTRAINT `{$CFG->dbprefix}{$app_p}results_ibfk_1`
        FOREIGN KEY (`session_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}sessions` (`session_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    */

    CONSTRAINT results_uq_1 UNIQUE(session_id, player)

    /* PRIMARY KEY(result_id) */

    ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


    array("{$CFG->dbprefix}{$app_p}sessions",

    "create table {$CFG->dbprefix}{$app_p}sessions (
    session_id          INT(6)          UNSIGNED AUTO_INCREMENT,
    game_id             INT(6)          NOT NULL,
    /* player1             VARCHAR(30)     NOT NULL,
       player2             VARCHAR(30)     DEFAULT NULL, */
    complete            BOOLEAN         DEFAULT FALSE,
    submitted_data      INT(20)         DEFAULT NULL,

    /* having trouble adding this foreign key constraint */

    CONSTRAINT `{$CFG->dbprefix}{$app_p}sessions_ibfk_1`
        FOREIGN KEY (`game_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}games` (`game_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,


    PRIMARY KEY (session_id)

    ) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

/*
this is how you add post-create functionality
$DATABASE_POST_CREATE = function($table){
    global $CFG, $PDOX;

    if($table == "{$CFG->dbprefix}{$app_p}sessions"){

    }
}
*/

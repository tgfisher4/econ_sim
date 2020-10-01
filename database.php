<?php

$app_p = "econ_sim_";

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}{$app_p}course",
    "drop table if exists {$CFG->dbprefix}{$app_p}games",
    "drop table if exists {$CFG->dbprefix}{$app_p}results",
    "drop table if exists {$CFG->dbprefix}{$app_p}live_multiplayer_groups"
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
    course_id           VARCHAR(30)     NOT NULL,
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
    equilibrium         INT(6)          DEFAULT NULL,
    price_hist          VARCHAR(300)    DEFAULT NULL,
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
    complete            BOOLEAN         DEFAULT FALSE,
    group_id            VARCHAR(10)     NOT NULL,
    game_id             INT(6)          UNSIGNED NOT NULL,
    player              VARCHAR(30)     NOT NULL,
    opponent            VARCHAR(30)     DEFAULT NULL,
    player_quantity     VARCHAR(300)    NOT NULL,
    player_revenue      VARCHAR(300)    NOT NULL,
    player_profit       VARCHAR(300)    NOT NULL,
    player_return       VARCHAR(300)    NOT NULL,
    price               VARCHAR(300)    NOT NULL,
    unit_cost           VARCHAR(300)    NOT NULL,
    total_cost          VARCHAR(300)    NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}results_ibfk_1`
        FOREIGN KEY (`game_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}games` (`game_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT results_uq_1 UNIQUE(group_id, player)

    /* PRIMARY KEY(result_id) */

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

    array( "{$CFG->dbprefix}{$app_p}live_multiplayer_groups",

   "create table {$CFG->dbprefix}{$app_p}live_multiplayer_groups (
    group_id            INT(6)          UNSIGNED AUTO_INCREMENT,
    game_id             INT(6)          NOT NULL,
    player1             VARCHAR(30)     DEFAULT NULL,
    player1_data        INT(20)         DEFAULT NULL,
    player2             VARCHAR(30)     DEFAULT NULL,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}live_multiplayer_groups_ibfk_1`
        FOREIGN KEY (`game_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}games` (`game_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (group_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

    array(),

    "create table {$CFG->dbprefix}{$app_p}groups (
    group_id            INT(6)          UNSIGNED AUTO_INCREMENT,
    player1             VARCHAR(30)     DEFAULT NULL,
    player2             VARCHAR(30)     DEFAULT NULL,
    player_submission   INT(20)         DEFAULT NULL,
    player_1_submitted  BOOLEAN         DEFAULT FALSE,

    game_id
    )"
);

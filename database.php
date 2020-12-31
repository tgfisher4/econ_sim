<?php
/*
    The data model on the backend of the simulation.

    Glossary of terms:
        course  - A container for multiple games. Allows instructor to manage games according to their real-world courses.
        game    - A template for spawning sessions. Allows instructor to configure simulation parameters for all students.
            live               - Determines whether students are allowed to join sessions of this game.
            is_replayable      - Some market structures do not change between sessions, so the instructor may decide not to allow students to replay.
            game_type          - Subject area of game, i.e., economics, accounting, marketing. Only economics is currently supported.
            difficulty         - Difficulty level of a given game. Generally, higher difficulty corresponds to more user choices
            market_structure   - Which type of market structure is being simulated. Oligpoly is the only current multiplayer mode (and is 2-player).
            macro_economy      - Larger economy of market. A growth economy will include marcoeconomic shocks for the relevant market structures.
            time_limit         - Number of minutes students have to make their decisions each round of a session of this game.
            init_price_history - Used by perfect competition market structure to give students previous data to make their inital decisions.
        session - An instance of a game. Includes one or more players.
        result  - A record of a particular player's moves in a given session.
*/
$app_p = "econ_sim_";

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists   {$CFG->dbprefix}{$app_p}results,
                            {$CFG->dbprefix}{$app_p}sessions,
                            {$CFG->dbprefix}{$app_p}games,
                            {$CFG->dbprefix}{$app_p}courses,
                            {$CFG->dbprefix}{$app_p}avatars,
                            {$CFG->dbprefix}{$app_p}game_types,
                            {$CFG->dbprefix}{$app_p}difficulties,
                            {$CFG->dbprefix}{$app_p}market_structures,
                            {$CFG->dbprefix}{$app_p}macro_economies"
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}{$app_p}avatars",

    "create table {$CFG->dbprefix}{$app_p}avatars (
        avatar_id       INT(4)          UNSIGNED NOT NULL,
        avatar_name     VARCHAR(30)     NOT NULL,

        PRIMARY KEY(avatar_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8"),


    array( "{$CFG->dbprefix}{$app_p}game_types",

    "create table {$CFG->dbprefix}{$app_p}game_types (
        game_type_id       INT(2)           UNSIGNED NOT NULL,
        game_type_name     VARCHAR(30)      NOT NULL,

        PRIMARY KEY(game_type_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8"),


    array( "{$CFG->dbprefix}{$app_p}difficulties",

    "create table {$CFG->dbprefix}{$app_p}difficulties (
        difficulty_id       INT(2)          UNSIGNED NOT NULL,
        difficulty_name     VARCHAR(30)     NOT NULL,

        PRIMARY KEY(difficulty_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8"),


    array( "{$CFG->dbprefix}{$app_p}market_structures",

    "create table {$CFG->dbprefix}{$app_p}market_structures (
        market_structure_id       INT(3)          UNSIGNED NOT NULL,
        market_structure_name     VARCHAR(30)     NOT NULL,

        PRIMARY KEY(market_structure_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8"),


    array( "{$CFG->dbprefix}{$app_p}macro_economies",

    "create table {$CFG->dbprefix}{$app_p}macro_economies (
        macro_economy_id       INT(2)          UNSIGNED NOT NULL,
        macro_economy_name     VARCHAR(30)     NOT NULL,

        PRIMARY KEY(macro_economy_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8"),


    array( "{$CFG->dbprefix}{$app_p}courses",

   "create table {$CFG->dbprefix}{$app_p}courses (
    course_id           INT(6)          UNSIGNED AUTO_INCREMENT,
    name                VARCHAR(30)     NOT NULL,
    /* maybe constrain this to be an integer? */
    section             VARCHAR(30)     NOT NULL,
    /* user_id of the instructor who owns the course */
    user_id             INT(11)         NOT NULL,
    avatar_id           INT(4)          UNSIGNED NOT NULL,
    reg_date            TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}courses_ibfk_1`
        FOREIGN KEY (`avatar_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}avatars` (`avatar_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}courses_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY(course_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


    array( "{$CFG->dbprefix}{$app_p}games",

   "create table {$CFG->dbprefix}{$app_p}games (
    game_id             INT(6)          UNSIGNED AUTO_INCREMENT,
    name                VARCHAR(30)     NOT NULL,
    live                BOOLEAN         DEFAULT FALSE,
    is_replayable       BOOLEAN         DEFAULT TRUE,
    game_type_id        INT(2)          UNSIGNED NOT NULL,
    course_id           INT(6)          UNSIGNED NOT NULL,
    difficulty_id       INT(2)          UNSIGNED NOT NULL,
    market_structure_id INT(3)          UNSIGNED NOT NULL,
    macro_economy_id    INT(2)          UNSIGNED NOT NULL,
    time_limit          INT(6)          UNSIGNED NOT NULL,
    num_rounds          INT(6)          UNSIGNED NOT NULL,
    /* might any of these need to be floats? */
    demand_intercept    INT(6)          NOT NULL,
    demand_slope        INT(6)          NOT NULL,
    fixed_cost          INT(6)          NOT NULL,
    unit_cost           INT(6)          NOT NULL,
    /*  init_price_history is used for perfect competition (maybe also monopolistic competition) */
    init_price_history     VARCHAR(300)    DEFAULT NULL,
    reg_date            TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_1`
        FOREIGN KEY (`course_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}courses` (`course_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_2`
        FOREIGN KEY (`game_type_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}game_types` (`game_type_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_3`
        FOREIGN KEY (`difficulty_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}difficulties` (`difficulty_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_4`
        FOREIGN KEY (`market_structure_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}market_structures` (`market_structure_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}games_ibfk_5`
        FOREIGN KEY (`macro_economy_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}macro_economies` (`macro_economy_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY(game_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

    array("{$CFG->dbprefix}{$app_p}sessions",

    "create table {$CFG->dbprefix}{$app_p}sessions (
    session_id          INT(6)          UNSIGNED AUTO_INCREMENT,
    game_id             INT(6)          UNSIGNED NOT NULL,
    /* might need price history for perfect and/or monopolistic competition */
    /*    price_history       VARCHAR(300)    NOT NULL DEFAULT '' */

    CONSTRAINT `{$CFG->dbprefix}{$app_p}sessions_ibfk_1`
        FOREIGN KEY (`game_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}games` (`game_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (session_id)

    ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}{$app_p}results",
    // econ_sim_results is a join table between econ_sim_sessions and lti_user
    "create table {$CFG->dbprefix}{$app_p}results(
    session_id          INT(6)          UNSIGNED NOT NULL,
    /* lti_user.user_id is not unsigned */
    user_id             INT(11)         NOT NULL,
    quantity_history    VARCHAR(300)    NOT NULL DEFAULT '',

    CONSTRAINT `{$CFG->dbprefix}{$app_p}results_ibfk_1`
        FOREIGN KEY (`session_id`)
        REFERENCES `{$CFG->dbprefix}{$app_p}sessions` (`session_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}{$app_p}results_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT results_uq_1 UNIQUE(session_id, user_id)

    ) ENGINE = InnoDB DEFAULT CHARSET=utf8")


);

// this is how you add post-create functionality
$DATABASE_POST_CREATE = function($table){
    global $CFG, $PDOX, $app_p;
    if($table == "{$CFG->dbprefix}{$app_p}avatars"){
        $populate_avatars_query = "INSERT INTO {$CFG->dbprefix}{$app_p}avatars (avatar_id, avatar_name) VALUES
                                                                               (1,  'fa-chart-bar'),
                                                                               (2,  'fa-balance-scale'),
                                                                               (3,  'fa-book'),
                                                                               (4,  'fa-briefcase'),
                                                                               (5,  'fa-certificate'),
                                                                               (6,  'fa-clipboard'),
                                                                               (7,  'fa-comments'),
                                                                               (8,  'fa-hand-holding-usd'),
                                                                               (9,  'fa-chalkboard-teacher'),
                                                                               (10, 'fa-lightbulb'),
                                                                               (11, 'fa-dollar-sign'),
                                                                               (12, 'fa-file-alt'),
                                                                               (13, 'fa-user-graduate'),
                                                                               (14, 'fa-university')";
        echo("Inserting avatars: <br/>\n");
        error_log("Inserting avatars: ".$populate_avatars_query);
        $PDOX->queryDie($populate_avatars_query);
    }

    if($table == "{$CFG->dbprefix}{$app_p}game_types"){
        $populate_game_types_query = "INSERT INTO {$CFG->dbprefix}{$app_p}game_types (game_type_id, game_type_name) VALUES
                                                                                     (1,  'economics'),
                                                                                     (2,  'marketing'),
                                                                                     (3,  'accounting')";
        echo("Inserting game_types: <br/>\n");
        error_log("Inserting game_types: ".$populate_game_types_query);
        $PDOX->queryDie($populate_game_types_query);
    }

    if($table == "{$CFG->dbprefix}{$app_p}difficulties"){
        $populate_difficulties_query = "INSERT INTO {$CFG->dbprefix}{$app_p}difficulties (difficulty_id, difficulty_name) VALUES
                                                                                         (1,  'principles'),
                                                                                         (2,  'intermediate'),
                                                                                         (3,  'advanced')";
        echo("Inserting difficulties: <br/>\n");
        error_log("Inserting difficulties: ".$populate_difficulties_query);
        $PDOX->queryDie($populate_difficulties_query);
    }

    if($table == "{$CFG->dbprefix}{$app_p}market_structures"){
        $populate_market_structures_query = "INSERT INTO {$CFG->dbprefix}{$app_p}market_structures (market_structure_id, market_structure_name) VALUES
                                                                                                   (1,  'monopoly'),
                                                                                                   (2,  'oligopoly'),
                                                                                                   (3,  'monopolistic competition'),
                                                                                                   (4,  'perfect competition')";
        echo("Inserting market_structures: <br/>\n");
        error_log("Inserting market_structures: ".$populate_market_structures_query);
        $PDOX->queryDie($populate_market_structures_query);
    }

    if($table == "{$CFG->dbprefix}{$app_p}macro_economies"){
        $populate_macro_economies_query = "INSERT INTO {$CFG->dbprefix}{$app_p}macro_economies (macro_economy_id, macro_economy_name) VALUES
                                                                                               (1,  'stable'),
                                                                                               (2,  'growth')";
        echo("Inserting macro_economies: <br/>\n");
        error_log("Inserting macro_economies: ".$populate_macro_economies_query);
        $PDOX->queryDie($populate_macro_economies_query);
    }
};

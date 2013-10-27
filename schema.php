<?PHP
    CREATE TABLE Player (
        id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL,
        game_started TIMESTAMP NOT NULL,
        last_turn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        games_won SMALLINT(5) NOT NULL,
        games_lost SMALLINT(5) NOT NULL,
        INDEX name,
    )

    CREATE TABLE Game (
        id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        player1 MEDIUMINT UNSIGNED NOT NULL,
        player2 MEDIUMINT UNSIGNED NOT NULL,
        turns SMALLINT(3) NOT NULL,
        p1history VARCHAR(8000) NOT NULL,
        p2history VARCHAR(8000) NOT NULL,
        p2word VARCHAR(5) NOT NULL,
        p2word VARCHAR(5) NOT NULL,
        game_started TIMESTAMP NOT NULL,
        last_turn TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        winner SMALLINT(5) NOT NULL,
        games_lost SMALLINT(5) NOT NULL,
    )


<?php
/**
 * Author: Jaideep Prasad
 */

/**
 * Deletes any game database records with this user's id
 * @param $pdo PDO The PDO object
 * @param $userid int The user id
 */
function deleteUserGames($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
DELETE FROM chess_game WHERE player1 = $useridQ or player2 = $useridQ
SQL;

    $pdo->query($sql);
}

<?php
/**
 * Author: Jaideep Prasad
 */

require_once "db.inc.php";

session_start();
echo '<?xml version="1.0" encoding="UTF-8" ?>';

// Ensure user is logged in
if (!isset($_SESSION['login'])) {
    echo '<chess status="logged out" msg="user is not logged in" />';
    exit;
}

// Ensure the xml post item exists
if(!isset($_POST['xml'])) {
    echo '<chess status="no" msg="missing XML" />';
    exit;
}

processXml(stripslashes($_POST['xml']));

/**
 * Process the XML query
 * @param $xmltext string the provided XML
 */
function processXml($xmltext)
{
    // Load the XML
    $xml = new XMLReader();
    if (!$xml->XML($xmltext)) {
        echo '<chess status="no" msg="invalid XML" />';
        exit;
    }

    // Connect to the database
    $pdo = pdo_connect();

    // Read to the start tag
    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == "chess") {
            // We have the chess tag
            $magic = $xml->getAttribute("magic");
            if ($magic != "Ax34Y?lz8Pyq3r9!") {
                echo '<chess status="no" msg="magic" />';
                exit();
            }
            else {
                break;
            }
        }
    }

    $userid = $_SESSION['userid'];
    if (alreadyHosting($pdo, $userid)) {
        isMatched($pdo, $userid);
    }
    else {
        if (joinGame($pdo, $userid)) {
            exit;
        }
        else {
            establishGame($pdo, $userid);
        }
    }
    exit;
}

/**
 * Determines if the current player is already hosting a game
 * @param $pdo PDO The PDO object
 * @param $userid int The user id
 * @return true if hosting
 */
function alreadyHosting($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
SELECT id FROM chess_game WHERE player1 = $useridQ
SQL;
    $rows = $pdo->query($sql);
    if ($rows->rowCount() > 0) {
        return true;
    }
    return false;
}

/**
 * Determines if two players have been matched
 * @param $pdo PDO The PDO object
 * @param $userid int The user id
 */
function isMatched($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
SELECT id, player1, player2 
FROM chess_game 
WHERE (player1 = $useridQ or player2 = $useridQ) and player1 <> player2
SQL;

    $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!is_null($row['player1']) && !is_null($row['player2'])) {
        $names = getPlayerNamesInGame($pdo, $row['id']);
        $playerOne = $names[0];
        $playerTwo = $names[1];
        echo "<chess status=\"yes\" msg=\"matched\" playerOne=\"$playerOne\" playerTwo=\"$playerTwo\" />";
        exit;
    }
    else {
        echo '<chess status="no" msg="still searching" />';
        exit;
    }
}

/**
 * @param $pdo PDO The PDO object
 * @param $userid int Current user's id
 */
function establishGame($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
DELETE FROM chess_game WHERE player1=$useridQ;
INSERT INTO chess_game(player1, player2, state, updater) VALUES($useridQ, NULL, NULL, NULL);
SQL;
    $pdo->query($sql);
    echo '<chess status="no" msg="established game, still searching" />';
    exit;
}

/**
 * Second player joins a game
 * @param $pdo PDO The pdo object
 * @param $userid int The user id
 * @return true if successfully joined a game
 */
function joinGame($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
SELECT id 
FROM chess_game 
WHERE player1 <> $useridQ and player2 IS NULL
SQL;

    $rows = $pdo->query($sql);
    if ($rows->rowCount() > 0) {
        $gameid = $rows->fetch(PDO::FETCH_ASSOC)['id'];
        $sql = <<<SQL
UPDATE chess_game
SET player2 = $useridQ
WHERE id = $gameid
SQL;

        $pdo->query($sql);
        $names = getPlayerNamesInGame($pdo, $gameid);
        $playerOne = $names[0];
        $playerTwo = $names[1];
        echo "<chess status=\"yes\" msg=\"matched\" playerOne=\"$playerOne\" playerTwo=\"$playerTwo\" />";
        return true;
    }
    return false;
}

/**
 * Get the player names in a given game
 * @param $pdo PDO The PDO object
 * @param $gameid int The game id
 * @return array Player names
 */
function getPlayerNamesInGame($pdo, $gameid) {
    $gameidQ = $pdo->quote($gameid);
    $sql = <<<SQL
SELECT DISTINCT name
FROM chess_player, chess_game
WHERE chess_game.id = $gameidQ and (player1 = chess_player.id or player2 = chess_player.id) and player1 <> player2
SQL;

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $names = array();
    foreach ($rows as $row) {
        $names[] = $row['name'];
    }
    return $names;
}


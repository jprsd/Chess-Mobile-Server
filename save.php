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

    // Only the XML data we care about
    $data = "";

    // Read to the start tag
    while ($xml->read()) {
        if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == "chess") {
            // We have the chess tag
            $magic = $xml->getAttribute("magic");
            if ($magic != "Ax34Y?lz8Pyq3r9!") {
                echo '<chess status="no" msg="magic" />';
                exit();
            }
        }
        if ($xml->nodeType == XMLReader::ELEMENT && ($xml->name == "piece" || $xml->name == "resign")) {
            $data .= $xml->readOuterXml();
        }
    }

    saveGameState($pdo, $_SESSION['userid'], $data);
}

/**
 * Saves the game state
 * @param $pdo PDO The PDO object
 * @param $userid int The user id
 */
function saveGameState($pdo, $userid, $data) {
    $useridQ = $pdo->quote($userid);
    $dataQ = $pdo->quote($data);
    $sql = <<<SQL
UPDATE chess_game
SET state = $dataQ, updater = $useridQ
WHERE player1 = $useridQ or player2 = $useridQ
SQL;

    $rows = $pdo->query($sql);

    if ($rows->rowCount() > 0) {
        echo '<chess status="yes" msg="success" />';
        exit;
    }
    else {
        echo '<chess status="no" msg="no games found" />';
        exit;
    }
}

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

    getGameState($pdo, $_SESSION['userid']);
}

/**
 * Gets the game state
 * @param $pdo PDO The PDO object
 * @param $userid int The user id
 */
function getGameState($pdo, $userid) {
    $useridQ = $pdo->quote($userid);
    $sql = <<<SQL
SELECT state, updater FROM chess_game WHERE player1 = $useridQ or player2 = $useridQ
SQL;

    $rows = $pdo->query($sql);
    if ($rows->rowCount() == 1) {
        $row = $rows->fetch(PDO::FETCH_ASSOC);
        $data = $row['state'];
        $updater = $row['updater'];
        if (is_null($data) || $data == "") {
            echo '<chess status="no" msg="empty state" />';
            exit;
        }
        if (is_null($updater) || $updater === $useridQ || $updater === $userid) {
            echo '<chess status="no" msg="not updated yet" />';
            exit;
        }
        if (strpos($data, "resign") !== false) {
            echo '<chess status="resign" msg="opponent has resigned or lost connection" />';
            exit;
        }
        echo '<chess status="yes" msg="success">' . $data . '</chess>';
        exit;
    }
    else {
        echo '<chess status="no" msg="no games found" />';
        exit;
    }
}

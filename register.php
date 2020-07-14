<?php
/**
 * Author: Jaideep Prasad
 */

require_once "db.inc.php";
date_default_timezone_set('America/Detroit');
echo '<?xml version="1.0" encoding="UTF-8" ?>';

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
function processXml($xmltext) {
    // Load the XML
    $xml = new XMLReader();
    if(!$xml->XML($xmltext)) {
        echo '<chess status="no" msg="invalid XML" />';
        exit;
    }

    // Connect to the database
    $pdo = pdo_connect();

    // Read to the start tag
    while($xml->read()) {
        if($xml->nodeType == XMLReader::ELEMENT && $xml->name == "chess") {
            // We have the chess tag
            $magic = $xml->getAttribute("magic");
            if($magic != "Ax34Y?lz8Pyq3r9!") {
                echo '<chess status="no" msg="magic" />';
                exit;
            }

            $userName = $xml->getAttribute("user");
            checkUserNameTaken($pdo, $userName);
            $userNameQ = $pdo->quote($userName);

            $password = $xml->getAttribute("pw");
            $passwordQ = $pdo->quote($password);

            $query = <<<QUERY
INSERT INTO chess_player(name, password, joined)
VALUES($userNameQ, $passwordQ, ?)
QUERY;

            $statement = $pdo->prepare($query);
            if(!$statement->execute(array(date("Y-m-d H:i:s")))) {
                echo '<chess status="no" msg="registerfail">' . $query . '</chess>';
                exit;
            }

            echo '<chess status="yes"/>';
            exit;
        }
    }

    echo '<chess save="no" msg="invalid XML" />';
}

/**
 * Checks to see if the given user name is already taken
 * @param $pdo PDO The PDO object
 * @param $name string Player name
 */
function checkUserNameTaken($pdo, $name) {
    // Does the user exist in the database?
    $nameQ = $pdo->quote($name);
    $query = "SELECT name from chess_player where name=$nameQ";

    $rows = $pdo->query($query);
    if ($rows->rowCount() > 0) {
        echo '<chess status="no" msg="username taken" />';
        exit;
    }
}

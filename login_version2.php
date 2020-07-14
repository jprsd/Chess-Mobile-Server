<?php
/**
 * Author: Jaideep Prasad
 */

require_once "db.inc.php";
require_once "clear.inc.php";
echo '<?xml version="1.0" encoding="UTF-8" ?>';

// Ensure the xml post item exists
if (!isset($_POST['xml'])) {
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

            $userName = $xml->getAttribute("user");
            $userNameQ = $pdo->quote($userName);

            $password = $xml->getAttribute("pw");
            $passwordQ = $pdo->quote($password);

            $query = <<<QUERY
SELECT id FROM chess_player WHERE name=$userNameQ and password=$passwordQ
QUERY;

            $rows = $pdo->query($query);
            if ($rows->rowCount() == 1) {
                session_start();
                if (!isset($_SESSION['login'])) {
                    $_SESSION['login'] = true;
                    $id = $rows->fetch(PDO::FETCH_ASSOC)['id'];
                    $_SESSION['userid'] = $id;
                }
                deleteUserGames($pdo, $_SESSION['userid']);
                $sessionId = session_id();
                echo "<chess status=\"yes\" msg=\"$sessionId\"/>";
                exit;
            } else {
                echo '<chess status="no" msg="loginfail">' . $query . '</chess>';
                exit;
            }
        }
    }

    echo '<chess save="no" msg="invalid XML" />';
}


<?php
/**
 * Author: Jaideep Prasad
 */

require_once "db.inc.php";
require_once "clear.inc.php";

session_start();
echo '<?xml version="1.0" encoding="UTF-8" ?>';

// Ensure user is logged in
if (!isset($_SESSION['login'])) {
    echo '<chess status="yes" msg="user is already logged out" />';
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
            } else {
                break;
            }
        }
    }

    deleteUserGames($pdo, $_SESSION['userid']);
    unset($_SESSION['login']);
    unset($_SESSION['userid']);
    echo '<chess status="yes" msg="successfully logged out" />';
    exit;
}


<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Library
require_once API_DIR . "api.php";

$api = new gbAPI(false, $internal);

// Get Entries
$command = "SELECT * FROM entries WHERE status = :status ORDER BY date DESC";
$result = $api->dbCommand($command, true, true, [":status" => 1]);

$entries = [];

if (!$result) {
    $api->answer(200, [
        "entries" => [],
        "empty" => true
    ]);
    return;
}

foreach($result as $row) {
    $entry_name = $row["name"];
    $entry_text = $row["text"];
    $date = new DateTime($row["date"]);
    $entry_date = $date->format("d.m.Y H:i:s");

    $entry = [
        "name" => $entry_name,
        "text" => $entry_text,
        "date" => $entry_date
    ];

    $entries[] = $entry;    
}

$json = json_encode($entries);

$api->answer(200, [
    "entries" => $json,
    "empty" => false
]);
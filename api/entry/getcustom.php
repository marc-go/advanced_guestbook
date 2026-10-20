<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Library
require_once API_DIR . "api.php";

$api = new gbAPI(true, $internal);

$input = $api->getInput();


// Check given Status
if (!isset($input["status"])) {
    $api->answer(400, [
        "error" => "missing_fields",
        "error_text" => "Some fields are missing"
    ], true);
    return;
}

$status = intval($input["status"]);

if ($status !== 0 && $status !== 1 && $status !== 2) {
    $api->answer(400, [
        "error" => "status_invalid",
        "error_text" => "The given status is invalid."
    ], true);
    return;
}

$command = "SELECT * FROM entries WHERE status = :status ORDER BY date DESC";

$result = $api->dbCommand($command, true, true, [
    ":status" => $status
]);

if (!$result) {
    $api->answer(200, [
        "entries" => [],
        "empty" => true,
        "requested_status" => $status
    ]);
    return;
}

$entries = [];

foreach($result as $row) {
    $entry_name = $row["name"];
    $entry_text = $row["text"];
    $date = new DateTime($row["date"]);
    $entry_date = $date->format("d.m.Y H:i:s");

    $entry = [
        "id" => $row["id"],
        "name" => $entry_name,
        "text" => $entry_text,
        "date" => $entry_date
    ];

    $entries[] = $entry;
}

$api->answer(200, [
    "entries" => $entries,
    "empty" => false,
    "requested_status" => $status
]);
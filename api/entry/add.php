<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Library
require_once API_DIR . "api.php";

$api = new gbAPI(false, $internal);

$input = $api->getInput();

if (!isset($input["name"]) || !isset($input["text"])) {
    $api->answer(400, [
        "error" => "missing_fields",
        "error_text" => "Some fields are missing"
    ], true);
    return;
}

$name = htmlspecialchars($input["name"]);
$text = htmlspecialchars($input["text"]);

$date = date("Y-m-d H:i:s");
$approve = $api->getSetting("approve_entries") == "true" ? true : false;

if ($approve) {
    $status = 0;
}else{
    $status = 1;
}


// Insert Into Database
$command = "INSERT INTO entries (name, text, date, status) VALUES (:name, :text, :date, :status)";
$api->dbCommand($command, false, true, [
    ":name" => $name,
    ":text" => $text,
    ":date" => $date,
    ":status" => $status
]);

$api->answer(200);
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

if (!isset($input["id"]) || !isset($input["name"]) || !isset($input["text"])) {
    $api->answer(400, [
        "error" => "missing_fields",
        "error_text" => "Some fields are missing"
    ], true);
}

$id = intval($input["id"]);

// Check if Entry exists
$command = "SELECT * FROM entries WHERE id = :id";

$result = $api->dbCommand($command, true, true, [":id" => $id]);

if (!$result) {
    $api->answer(400, [
        "error" => "item_not_exists",
        "error_text" => "The entry does not exists."
    ], true);
}

// Write edit
$name = htmlspecialchars($input["name"]);
$text = htmlspecialchars($input["text"]);

$command = "UPDATE entries SET name = :name, text = :text WHERE id = :id";

$api->dbCommand($command, false, true, [":name" => $name, ":text" => $text, ":id" => $id]);

// Send success Message
$api->answer(200, [
    "id" => $id
]);
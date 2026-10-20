<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Library
require_once API_DIR . "api.php";

$api = new gbAPI(false, $internal);

// Get App Name
$result = $api->getSetting("application_name");
if ($result !== NULL) {
    $api->answer(200, [
        "application_name" => $result
    ]);
}else{
    $api->answer(500, [
        "error" => "empty_query",
        "error_text" => "Database query returned nothing."
    ]);
}
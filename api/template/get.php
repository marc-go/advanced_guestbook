<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}
// Load Main Library
require_once API_DIR . "api.php";
$api = new gbAPI(false, $internal);

// Get Template
$template = $api->getSetting("template");
if ($template !== NULL) {
    $api->answer(200, [
        "template_name" => $template
    ]);
}
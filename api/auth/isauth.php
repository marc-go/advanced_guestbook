<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Library
require_once API_DIR . "api.php";
$api = new gbAPI(true, $internal);

$auth = $api->checkAuth();

$lastError = $api->returnLastError();

if ($lastError == NULL) {
    if ($auth) {
        $api->answer(200, [
            "login" => true,
            "username" => $api->username
        ]);
    }else{
        $api->answer(401, [
            "login" => false,
            "username" => ""
        ]);
    }
}
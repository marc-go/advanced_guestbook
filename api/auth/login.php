<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load API Library
require_once API_DIR . "api.php";

$api = new gbAPI(false, $internal);

// Check Request Body
$input = $api->getInput();

if (!isset($input["username"]) || !isset($input["password"])) {
    $api->answer(400, [
        "error" => "missing_fields",
        "error_text" => "Some fields are missing"
    ], true);
    return;
}

$username = $input["username"];
$password = $input["password"];

// Prepare SQL Command
$command = "SELECT username, password FROM users WHERE username = :username";
$args = [
    ":username" => $username
];

// Execute SQL Command
$result = $api->dbCommand($command, true, true, $args);

// Check Result
if ($result && password_verify($password, $result[0]["password"])) {
    // Login successful. Generate Auth Token
    $token = $api->generateCookieToken($username);

    // Set cookies
    setcookie("auth_token", $token, time() + 86400, "/");

    $api->answer(200);
}else{
    $api->answer(401, [
        "error" => "unauthorized",
        "error_text" => "A user with this password does not exists."
    ], true);
}
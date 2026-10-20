<?php
// Set internal to false so that the answer goes to the client
if (!isset($internal)) {
    $internal = false;
    define("API_DIR", "../");
}

// Load Main Libary
require_once API_DIR . "api.php";

$api = new gbAPI(false, $internal);

// Check Request body
$input = $api->getInput();

if (!isset($input["username"]) || !isset($input["password"]) || !isset($input["password_repeat"])) {
    $api->answer(400, [
        "error" => "missing_fields",
        "error_text" => "Some fields are missing"
    ]);
    return;
}

// Check if application already configurated
if (is_file(APP_PATH . "database.db")) {
    $api->answer(503, [
        "error" => "already_configurated",
        "error_text" => "The application is already configurated."
    ]);
    return;
}

// Check PDO drivers
if (!extension_loaded("pdo_sqlite") || !extension_loaded("sqlite3")) {
    $api->answer(500, [
        "error" => "missing_drives",
        "error_text" => "Please install the extensions pdo_sqlite and sqlite3."
    ]);
    return;
}

$username = $input["username"];
$password = $input["password"];
$password_repeat = $input["password_repeat"];

// Check Passwords
if ($password !== $password_repeat) {
    $api->answer(400, [
        "error" => "passwords_dont_match",
        "error_text" => "The passwords does not match."
    ]);
    return;
}

// Hash Password
$hash = password_hash($password, PASSWORD_DEFAULT);



// CREATE DATABASE STRUCTURE
$sql_file = file_get_contents(APP_PATH . "db_structure.sql");

$db_path = APP_PATH . "database.db";

$db = new PDO("sqlite:" . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec($sql_file);



// INSERT USER
$command = "INSERT INTO users (username, password, owner) VALUES (:username, :password, :owner)";

$stmt = $db->prepare($command);
$stmt->execute([":username" => $username, ":password" => $hash, ":owner" => 1]);


// INSERT SETTINGS
$command = "INSERT INTO settings (name, value) VALUES (:name, :value)";

$settings = [
    "application_name" => "Guestbook",
    "approve_entries" => "true",
    "allow_new_entries" => "true",
    "template" => "standard"
];

foreach ($settings as $key => $value) {
    $stmt = $db->prepare($command);
    $stmt->execute([":name" => $key, ":value" => $value]);
}

// WRITE .env AND GENERATE SECRETS
$env = "";

$cookie_token = bin2hex(random_bytes(32));
$env .= "COOKIE_TOKEN_SIGNATURE=" . $cookie_token . "\n";
$env .= "COOKIE_TOKEN_LIFETIME=86400\n";

$api_token = bin2hex(random_bytes(32));
$env .= "API_TOKEN_SIGNATURE=" . $api_token . "\n";

$env .= "DB_FILE=database.db";

file_put_contents(APP_PATH . ".env", $env);


// RETURN SUCCESS MESSAGE
$api->answer(200);
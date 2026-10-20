<?php
// Set debug to true
$debug = true;

// Load Main Library
require "../../main.php";

// Init Main Class
$gb = new gb($debug);

// Load and init HTML Loader Library
require FRONTEND_PATH . "html_loader.php";
$htmlloader = new HTMLLoader();

// Check Login
$call = new APICall("auth/isauth.php");
$response = $call->response;

if (!$response["data"]["login"]) {
    $htmlloader->setPage("login");
    $htmlloader->show([], true);
}else{
    // Get pending entries
    $call = new APICall("entry/getcustom.php", [
        "status" => 0
    ]);
    $response = $call->response;

    // Check Response
    if ($response !== NULL && $response["status"] == 200) {
        $html_block = "";

        $htmlloader->setPage("admin_home");

        if (!$response["data"]["empty"]) {
            foreach ($response["data"]["entries"] as $entry) {
                $htmlloader->addCompletion("entries", [
                    "%id%" => $entry["id"],
                    "%name%" => htmlspecialchars($entry["name"]),
                    "%date%" => htmlspecialchars($entry["date"]),
                    "%text%" => htmlspecialchars($entry["text"])
                ]);
            }
        }else{
            $htmlloader->show(["%entries%" => ""], true);
        }
    }else{
        error_log("Error in " . __FILE__ . ": API answerd with status " . $response["status"] . " and the following JSON: " . json_encode($response));
        $htmlloader->showError('API Call for entries failed (API answerd with code <span class="code">' . $response["status"] . "</span>). More details in the server log.");
    }

    $htmlloader->show([], true, ["entries" => "%entries%"]);
}
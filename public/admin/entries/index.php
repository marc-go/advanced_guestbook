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

        $htmlloader->setPage("admin_entries");

        if (!$response["data"]["empty"]) {
            foreach ($response["data"]["entries"] as $entry) {
                $htmlloader->addCompletion("pending_entries", [
                    "%id%" => $entry["id"],
                    "%name%" => htmlspecialchars($entry["name"]),
                    "%date%" => htmlspecialchars($entry["date"]),
                    "%text%" => htmlspecialchars($entry["text"])
                ]);
            }
        }else{
            $pending_entries_empty = true;
        }
    }else{
        error_log("Error in " . __FILE__ . ": API answerd with status " . $response["status"] . " and the following JSON: " . json_encode($response));
        $htmlloader->showError('API Call for entries failed (API answerd with code <span class="code">' . $response["status"] . "</span>). More details in the server log.");
    }


    // Get approved entries
    $call = new APICall("entry/getcustom.php", [
        "status" => 1
    ]);
    $response = $call->response;

    // Check Response
    if ($response !== NULL && $response["status"] == 200) {
        $html_block = "";

        $htmlloader->setPage("admin_entries");

        if (!$response["data"]["empty"]) {
            foreach ($response["data"]["entries"] as $entry) {
                $htmlloader->addCompletion("approved_entries", [
                    "%id%" => $entry["id"],
                    "%name%" => htmlspecialchars($entry["name"]),
                    "%date%" => htmlspecialchars($entry["date"]),
                    "%text%" => htmlspecialchars($entry["text"])
                ]);
            }
        }else{
            $approved_entries_empty = true;
        }
    }else{
        error_log("Error in " . __FILE__ . ": API answerd with status " . $response["status"] . " and the following JSON: " . json_encode($response));
        $htmlloader->showError('API Call for entries failed (API answerd with code <span class="code">' . $response["status"] . "</span>). More details in the server log.");
    }


    // Get blocked entries
    $call = new APICall("entry/getcustom.php", [
        "status" => 2
    ]);
    $response = $call->response;

    // Check Response
    if ($response !== NULL && $response["status"] == 200) {
        $html_block = "";

        $htmlloader->setPage("admin_entries");

        if (!$response["data"]["empty"]) {
            foreach ($response["data"]["entries"] as $entry) {
                $htmlloader->addCompletion("blocked_entries", [
                    "%id%" => $entry["id"],
                    "%name%" => htmlspecialchars($entry["name"]),
                    "%date%" => htmlspecialchars($entry["date"]),
                    "%text%" => htmlspecialchars($entry["text"])
                ]);
            }
        }else{
            $blocked_entries_empty = true;
        }
    }else{
        error_log("Error in " . __FILE__ . ": API answerd with status " . $response["status"] . " and the following JSON: " . json_encode($response));
        $htmlloader->showError('API Call for entries failed (API answerd with code <span class="code">' . $response["status"] . "</span>). More details in the server log.");
    }

    $completions = [];
    $args = [];

    if (isset($blocked_entries_empty)) {
        $args["%blocked_entries%"] = "";
    }else{
        $completions["blocked_entries"] = "%blocked_entries%";
    }

    if (isset($approved_entries_empty)) {
        $args["%approved_entries%"] = "";
    }else{
        $completions["approved_entries"] = "%approved_entries%";
    }

    if (isset($pending_entries_empty)) {
        $args["%pending_entries%"] = "";
    }else{
        $completions["pending_entries"] = "%pending_entries%";
    }

    $htmlloader->show($args, true, $completions);
}
<?php
// Debug setting
$debug = true;

// Load GB Libary
require "main.php";

// Init GB Class
$gb = new gb(true);

// Load HTMLLoader Library
require FRONTEND_PATH . "html_loader.php";
$htmlLoader = new HTMLLoader();

// Build Entry Page
$htmlLoader->setPage("entries_frontend");

// Get entries
$call = new APICall("entry/get.php");
$response = $call->response;

if ($call->getError() !== false) {
    $htmlLoader->showError("Entry API Returned an error: " . $call->getError());
}

if ($response["data"]["empty"]) {
    $htmlLoader->show(["%entries%" => "<p>There aren't any entries yet. Be the first one!</p>"]);
}else{
    $entries = json_decode($response["data"]["entries"], true);

    foreach($entries as $entry) {
        $name = htmlspecialchars($entry["name"]);
        $text = htmlspecialchars($entry["text"]);
        $date = htmlspecialchars($entry["date"]);

        $htmlLoader->addCompletion("entries", [
            "%name%" => $name,
            "%date%" => $date,
            "%text%" => $text
        ]);
    }

    $htmlLoader->show([], true, ["entries" => "%entries%"]);
}
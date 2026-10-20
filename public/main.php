<?php
class gb {
    function __construct($debug) {
        // Debug
        if ($debug) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
            ini_set("display_startup_errors", 1);
        }

        // Define Paths
        define("APP_DOMAIN", $_SERVER["SERVER_NAME"]);
        define("APP_PATH", __DIR__ . "/../");
        define("FRONTEND_PATH", __DIR__ . "/");
        define("API_DIR", __DIR__ . "/../api/");
        define("TEMPLATE_PATH", FRONTEND_PATH . "templates/");
        define("TEMPLATE_CONF", TEMPLATE_PATH . "templates.json");
        
        // Check Database and show setup if database not exists
        if (!file_exists(APP_PATH . "database.db")) {
            $this->initSetup();
        }
    }

    function initSetup() {
        // Load and init the HTML Loader
        require "html_loader.php";
        $html = new HTMLLoader();

        // Set template to Setup an show it
        $html->setTemplate("standard");
        $html->setPage("setup");
        $html->show([], false);
    }
}


// Internal API Call Library
class APICall {
    public $response;
    private $error;

    function __construct($path, $data = []) {
        // Set $internal to true so the api return does not go to the browser
        $internal = true;

        // Check Path
        if (!is_file(API_DIR . $path)) {
            $this->error = "API Path invalid.";
            return false;
        }

        // Write input
        $GLOBALS["API_INPUT"] = $data;

        // Call API
        require API_DIR . $path;
        
        // Get return from the API Library
        $response = $api->getAnswer();

        if ($response["status"] !== 200) {
            $this->error = $response;
        }

        $this->response = $response;
    }

    function getError() {
        if (isset($this->error)) {
            return $this->error;
        }else{
            return false;
        }
    }
}
<?php
$debug = true;

if ($debug) {
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
    error_reporting(E_ALL);
}

/*if (isset($input)) {
    $GLOBALS["API_INPUT"] = $input;
}*/

// API Main Library
class gbAPI {
    public $username;
    public $input;

    private $db;
    private $config;
    private $internal;
    private $response;
    private $lastError;

    private function b64url_encode($data) {
        return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
    }

    private function b64url_decode($data) {
        return base64_decode(strtr($data, "-_", "+/"));
    }

    function __construct($authRequired, $internal) {
        if (!defined("APP_PATH")) {
            define("APP_PATH", __DIR__ . "/../");
        }    
    
        $this->internal = $internal;

        if ($authRequired && !$internal) {
            if (!$this->checkAuth()) {
                $status = 401;
                $data = [
                    "error" => "unauthorized",
                    "error_text" => "Authentication failed."
                ];

                $this->lastError = $data;

                $this->answer($status, $data, true);
            }
        }

        if ($internal && $GLOBALS["API_INPUT"]) {
            $this->input = $GLOBALS["API_INPUT"];
        }
    }

    function getInput() {
        if (isset($this->input)) {
            return $this->input;
        }elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
            try {
                $input = json_decode(file_get_contents("php://input"), true);
            } catch (JsonException $error) {
                $error_payload = [
                    "error" => "invalid_body",
                    "error_text" => "Can not read request body: " . $error
                ];

                $this->lastError = $error_payload;

                $this->answer(400, $error_payload, true);
            }
            return $input;
        }else{
            $error_payload = [
                "error" => "false_request_method",
                "error_text" => "Only POST allowed"
            ];

            $this->lastError = $error_payload;

            $this->answer(400, $error_payload, true);
        }
    }

    function getConfig() {
        if (isset($this->config)) {
            return $config;
        }else{
            return parse_ini_file(APP_PATH . ".env");
        }
    }

    function generateCookieToken($username) {
        $config = $this->getConfig();

        // Generate Token
        $payload = [
            "user" => $username,
            "auth" => true,
            "exp" => time() + $config["COOKIE_TOKEN_LIFETIME"]
        ];
        $string = $this->b64url_encode(json_encode($payload));
        $secret = $config["COOKIE_TOKEN_SIGNATURE"];

        $sig = hash_hmac("sha256", $string, $secret);
        return $string . "." . $sig;
    }

    function generateAPIToken($username) {
        $config = $this->getConfig();

        // Generate Token
        $payload = [
            "user" => $username,
            "auth" => true
        ];
        $string = $this->b64url_encode(json_encode($payload));
        $secret = $config["API_TOKEN_SIGNATURE"];

        $sig = hash_hmac("sha256", $string, $secret);
        return $string . "." . $sig;
    }

    function checkAuth() {
        if (isset($_COOKIE["auth_token"])) {
            // Get Config
            $config = $this->getConfig();
        
            // Check Token
            $given = $_COOKIE["auth_token"];
            if (!is_string($given)) {
                return false;
            }

            $given_string = explode(".", $given);

            if (count($given_string) !== 2) {
                return false;
            }

            $sig_expected = hash_hmac("sha256", $given_string[0], $config["COOKIE_TOKEN_SIGNATURE"]);

            @$result = hash_equals($sig_expected, $given_string[1]);
            if ($result) {
                $body = json_decode($this->b64url_decode($given_string[0]), true);

                if ($body["exp"] > time()) {
                    $this->username = $body["user"];
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }elseif (isset($_SERVER["HTTP_X_API_KEY"])) {
            // Get config
            $config = $this->getConfig();

            // Decode Token
            $given = $_SERVER["HTTP_X_API_KEY"];

            if (!is_string($given)) {
                return false;
            }

            $given_string = explode(".", $given);

            if (count($given_string) !== 2) {
                return false;
            }

            $sig_expected = hash_hmac("sha256", $given_string[0], $config["API_TOKEN_SIGNATURE"]);
            
            @$result = hash_equals($sig_expected, $given_string[1]);
        }

        return false;
    }

    function answer($status, $data = [], $error = false) {
        if ($error) {
            $return = [];

            $return["status"] = $status;

            foreach ($data as $key => $value) {
                $return[$key] = $value;
            }
        }else{
            $return = [
                "status" => $status,
                "data" => $data
            ];
        }
        
        // Check if request is internal or external
        if ($this->internal) {
            $this->response = $return;
            return;
        }else{
            http_response_code($status);
            header("Content-Type: application/json; charset=UTF-8");

            $return = json_encode($return);
            die($return);
        }
    }

    function returnLastError() {
        if (isset($this->lastError)) {
            return $this->lastError;
        }else{
            return NULL;
        }
    }

    function getAnswer() {
        if (!isset($this->response)) {
            $error = [
                "status" => 500,
                "error" => "empty_return",
                "error_text" => "API Returned nothing."
            ];

            $this->lastError = $error;

            return $error;
        }

        return $this->response;
    }

    function getResponse() {
        return $this->response;
    }

    function dbCommand($command, $expectResult, $stmtArgsRequired = false, $stmtArgs = []) {
        if (isset($this->db)) {
            $db = $this->db;
        }else{
            $config = $this->getConfig();
            $db_name = $config["DB_FILE"];
            $db_path = APP_PATH . $db_name;

            if (!is_file($db_path)) {
                $error_payload = [
                    "error" => "database_not_found",
                    "error_text" => "The Database was not found."
                ];

                $this->lastError = $error_payload;

                $this->answer(500, $error_payload, true);
                return;
            }

            if (!is_writeable($db_path)) {
                $error_payload = [
                    "error" => "database_not_writeable",
                    "error_text" => "The Database is not writeable."
                ];

                $this->lastError = $error_payload;

                $this->answer(500, $error_payload, true);
                return;
            }

            $db = new PDO("sqlite:" . $db_path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        try {
            $stmt = $db->prepare($command);
            $stmt->execute($stmtArgs);
        } catch (PDOException $e) {
            $error_payload = [
                "error" => "pdo_error",
                "error_text" => "PDO returned an Error. More details in the server log."
            ];

            error_log("PDO Error: " . $e);
            $this->lastError = $error_payload;
            $this->answer(500, $error_payload, true);
            return;
        }

        if ($expectResult) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result !== NULL) {
                return $result;
            }else{
                $error_payload = [
                    "error" => "empty_result",
                    "error_text" => "Database query returned nothing useable."
                ];

                $this->lastError = $error_payload;
                $this->answer(500, $error_payload, true);
                return;
            }   
        }
    }

    function getSetting($key) {
        $command = "SELECT name, value FROM settings WHERE name = :name";

        $value = $this->dbCommand($command, true, true, [":name" => $key]);
        
        if (isset($value[0]["value"])) {
            return $value[0]["value"];
        }else{
            $this->answer(500, [
                "error" => "query_returned_nothing",
                "error_text" => "The SQL Query Returned nothing usable. Check Database Permissions."
            ], true);
        }
    }
}

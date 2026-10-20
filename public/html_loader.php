<?php
class HTMLLoader {
    private $template;
    private $page;

    private $templates;
    private $template_conf;
    
    private $completions;

    function __construct() {
        // Check if Paths variables exists
        if (!defined("TEMPLATE_PATH") || !defined("TEMPLATE_CONF") || !defined("APP_DOMAIN")) {
            die("TEMPLATE_PATH or TEMPLATE_CONF are not defined at " . __FILE__);
        }

        $this->templates = json_decode(file_get_contents(TEMPLATE_CONF), true);
    }
    
    function setTemplate($template) {
        // Check if template exists
        if (!array_key_exists($template, $this->templates)) {
            die("Template " . $template . " not found.");
        }
        
        if (!is_dir(TEMPLATE_PATH . $this->templates[$template]["dir_name"])) {
            die("Template " . $template . " not found.");
        }

        $this->template = $template;
        $this->template_conf = json_decode(file_get_contents(TEMPLATE_PATH . $this->templates[$template]["dir_name"] . "/template.json"), true);
    }

    function getTemplate() {
        // Call API
        $call = new APICall("template/get.php");
        $response = $call->response;
        
        if ($response["status"] !== 200) {
            $this->setTemplate("standard");
            $this->showError("Template API Returned an error: " . $response["error_text"]);
        }

        $template = $response["data"]["template_name"];

        $this->template_conf = json_decode(file_get_contents(TEMPLATE_PATH . $this->templates[$template]["dir_name"] . "/template.json"), true);
        $this->template = $template;
    }

    function setPage($page) {
        if (!isset($this->template)) {
            $this->getTemplate();
        }
        
        if (!array_key_exists($page, $this->template_conf)) {
            $this->showError('Page <span class="code">' . $page . '</span> for template <span class="code">' . $this->template . '</span> not found.');
        }else{
            $this->page = $page;
        }
    }

    function showError($error) {
        if (!isset($this->template)) {
            $this->getTemplate();
        }

        $template_dir = $this->templates[$this->template]["dir_name"];
        $template_conf = json_decode(file_get_contents(TEMPLATE_PATH . $template_dir . "/template.json"), true);

        $page = $template_conf["error"];
        $html = file_get_contents(TEMPLATE_PATH . $template_dir . "/" . $page["dir_name"] . "/" . $page["html_file"]);
        
        $css = "";

        // Get CSS
        foreach ($page["css_files"] as $file) {
            // Check if File exists
            if (!is_file(TEMPLATE_PATH . $template_dir . "/" . $page["dir_name"] . "/" . $file)) {
                die("CSS File " . $file . " not found.");
            }

            // Add CSS Files to HTML
            $css .= '<link rel="stylesheet" type="text/css" href="/templates/' . $template_dir . "/" . $page["dir_name"] . "/" . $file . '">';
        }

        $html = str_replace("%css%", $css, $html);

        // Write error
        $replace = str_replace("%error%", $error, $html);

        die($replace);
    }

    function show($args = [], $showAppName = true, $completions = []) {
        if (!isset($this->template)) {
            $this->getTemplate();
        }
        
        $template = $this->template;
        $tempalte_conf = $this->template_conf;
        $template_dir = $this->templates[$template]["dir_name"];

        $page = $tempalte_conf[$this->page];

        if (!is_file(TEMPLATE_PATH . $template_dir . "/" . $page["dir_name"] . "/" . $page["html_file"])) {
            $this->showError('HTML File <span class="code">' . $page["html_file"] . '</span> for page <span class="code">' . $page["name"] . '</span> in template <span class="code">' . $template . '</span> does not exists.');
        }

        $html = file_get_contents(TEMPLATE_PATH . $template_dir . "/" . $page["dir_name"] . "/" . $page["html_file"]);

        if ($showAppName) {
            $call = new APICall("settings/appname.php");
            $response = $call->response;
            if (isset($response["error"])) {
                $this->showError("Failed to get Application Name: API Returned an error: " . $response["error_text"]);
            }


            if (isset($response["data"]["application_name"])) {
                $app_name = $response["data"]["application_name"];
            }else{
                $this->showError('Application Name API Returend nothing in <span class="code">html_loader.php</span>');
            }

            $html = str_replace("%appname%", $app_name, $html);
        }

        // Insert Completions
        foreach ($completions as $key => $value) {
            if (isset($this->completions[$key])) {
                $html = str_replace($value, $this->completions[$key], $html);
            }
        }

        // Get CSS
        $css = "";
        
        foreach ($page["css_files"] as $file) {
            // Check if File exists
            if (!is_file(TEMPLATE_PATH . $template_dir . "/" . $page["dir_name"] . "/" . $file)) {
                $this->showError('CSS File <span class="code">' . $file . '</span> for Template <span class="code">' . $this->template . '</span> in <span class="code">' . __FILE__ . "</span> not found.");
            }

            // Add CSS Files to HTML
            $css .= '<link rel="stylessheet" text="text/css" href="' . APP_DOMAIN . "/templates/" . $template_dir . "/" . $page["dir_name"] . "/" . $file . '">';
        }

        $html = str_replace("%css%", $css, $html);

        // Insert Args
        foreach ($args as $key => $value) {
            $html = str_replace($key, $value, $html);
        }

        die($html);
    }

    function addCompletion($name, $content) {
        // Check if file exists
        if (!isset($this->template)) {
            $this->getTemplate();
        }

        if (!isset($this->page)) {
            $this->showError("Called function HTMLLoader::addCompletion() without setting a page");
        }

        $template = $this->template;
        $completions = $this->template_conf[$this->page]["completion_files"];

        if (!array_key_exists($name, $completions)) {
            $this->showError('Completion key <span class="code">' . $name . '</span> does not exists.');
        }

        if (!is_file(TEMPLATE_PATH . $this->templates[$template]["dir_name"] . "/" . $this->template_conf[$this->page]["dir_name"] . "/" . $completions[$name])) {
            $this->showError('Completion file for <span class="code">' . $name . '</span> does not exists.');
        }
        $file = file_get_contents(TEMPLATE_PATH . $this->templates[$template]["dir_name"] . "/" . $this->template_conf[$this->page]["dir_name"] . "/" . $completions[$name]);
        $completion = $file;

        foreach($content as $key => $value) {
            $completion = str_replace($key, $value, $completion);
        }

        if (!isset($this->completions[$name])) {
            $this->completions[$name] = "";
        }
    
        $this->completions[$name] .= $completion;
    }
}
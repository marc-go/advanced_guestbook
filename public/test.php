<?php

/*foreach($_SERVER as $key => $value) {
    echo $key . " === " . $value . "<br>";
}*/

//echo $_SERVER["HTTP_X_API_KEY"];

//$string = '{"user":"marc", "auth":true}';
//$secret = "281361f952279bd9530734c67ed04aed2756f0fe00cb998cd6c28324f491484c";

//echo "Hash: " . hash_hmac("sha256", $string, $secret);

//echo "Decode: " . hash_equals($string, $secret);

//print_r(PDO::getAvailableDrivers());

//print_r(json_decode('{"entrys":[{"name":"marc"}]}', true));

//print_r(explode(".", "jakob.stinkt"));

//var_dump(extension_loaded("pdo_sqlite"));
//var_dump(extension_loaded("sqlite3"));

class test {
    private $test;

    function __construct() {
        var_dump(isset($this->test));
    }
}

new test();
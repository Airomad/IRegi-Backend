<?php

ini_set('display_errors', 1);
ini_set('safe_mode', false);

error_reporting(E_ALL);

header('Content-type: text/html; charset=UTF-8');

if (count($_REQUEST) > 0) {
    require_once 'API.php';

    $APIFunName = array_keys($_REQUEST)[0];
    $APIFunParams = $_REQUEST[$APIFunName];
    $API = new API($APIFunName, $APIFunParams);
    echo $API->callAPIFun();
} else {
    $JSON = new stdClass();
    $JSON->error = 'No API function called';
    echo json_encode($JSON);
}

?>

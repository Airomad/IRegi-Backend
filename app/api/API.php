<?php
require('../../vendor/autoload.php');
require_once ('APIConfig.php');
require_once ('APIUtils.php');

class API {
    private $APIFunName;
    private $APIFunParams;

    /*
    /  $apiFunName - name of API and called method in format APITEST_HELLOWORLD
    /  $apiFunParams - JSON parameters in a string representation
    */
    function __construct($APIFunName, $APIFunParams) {
        $this->APIFunName = explode('_', $APIFunName);
        $this->APIFunParams = isset($APIFunParams) ? stripslashes($APIFunParams) : NULL;
    }


    static function getAPIByName($APIName) {
        require_once "APIBase.php";
        $APIName = "API" . $APIName;
        require_once $APIName . ".php";
        $APIObj = new $APIName();
        return $APIObj;
    }

    function callAPIFun() {
        $result = new stdClass();
        $result->status = API_REQUEST_ERROR;

        $APIName = trim(strtolower($this->APIFunName[0]));
        if (in_array($APIName, API_LIST)) {
            $API = API::getAPIByName($APIName);
            $APIMethod = $this->APIFunName[1];

            if ($API->isValidMethod($APIMethod)) {
                $result->status = API_REQUEST_SUCCESS;
                $APIReflection = new ReflectionClass("API" . $APIName);
                try {
                    $APIReflection->getMethod($APIMethod);
                    if ($API->isRequiresParameters($APIMethod)) {
                        $JSONParams = json_decode($this->APIFunParams);
                        if ($JSONParams) {
                            $API->$APIMethod($JSONParams);
                            $result->response = $API->getResult();
                        } else {
                            $result->error = "Error given params";
                        }
                    } else {
                        $API->$APIMethod();
                        $result->response = $API->getResult();
                    }
                } catch (Exception $e) {
                    $result->error = $e->getMessage();
                }
            } else {
                $result->error = "Wrong API Method `$APIMethod` of Function `$APIName`";
            }
        } else {
            $result->error = "Wrong API Function `$APIName`";
        }

        return json_encode($result);
    }
}

?>

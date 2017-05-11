<?php

use voku\db\DB;
require_once "APIMethod.php";

class APIBase {
    protected $db;
    private $methods;
    private $response;

    function __construct() {
        $this->methods = new stdClass();
        $this->response = new stdClass();
        $this->db = DB::getInstance(DATABASE_URL, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
    }

    /*public function checkTokenToUpdate() {
        if ($this->user !== null) {
            $currentTime = new DateTime();
            $currentTime->setTimezone(new DateTimeZone('UTC'));
            $currentTimestamp = $currentTime->getTimestamp();
            $prevTokenUpdateTime = $this->user["date_token_last_update"];
            $prevTokenUpdateTime = new DateTime($prevTokenUpdateTime); //convert users incoming

            $difference = $currentTime->diff($prevTokenUpdateTime);
            $minutes = ($difference->format('%a')*1440)+($difference->format('%h')*60)+ ($difference->format('%i'));

            if($minutes > TOKEN_UPDATE_EACH_MINUTES) {
                $token = md5(uniqid($this->user["login"], true));
                $login = $this->user["login"];
                $result = $this->db->query("UPDATE `users` SET `token` = '$token', `date_token_last_update` = now() WHERE `login` = '$login'");
            }
        }
    }*/

    protected function registerMethod($APIMethod) {
        $key = strtolower($APIMethod->getName());
        $this->methods->$key = $APIMethod;
    }

    public function isRequiresParameters($APIMethodName) {
        $key = strtolower($APIMethodName);
        return $this->methods->$key->isRequiresParameters();
    }

    public function isValidMethod($methodName) {
        $key = strtolower($methodName);
        return isset($this->methods->$key);
    }

    public function putResponseDataElement($key, $value) {
        if ($key !== NULL && $value !== NULL) {
            $key = strtolower($key);
            if (!property_exists($this->response, "data"))
                $this->response->data = new stdClass();
            $this->response->data->$key = $value;
            return true;
        }
        return false;
    }

    public function putResponseErrorElement($value) {
        if (!property_exists($this->response, "errors"))
            $this->response->errors = [];
        array_push($this->response->errors, $value);
    }

    public function putResponseSuccess() {
        $this->response->method_status = "success";
    }

    public function putResponseError() {
        $this->response->method_status = "error";
    }

    public function putResponseData($data) {
        $this->response = $data;
    }

    public function getResult() {
        return $this->response;
    }
}

?>

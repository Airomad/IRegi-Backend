<?php

class APIusers extends APIBase {

    function __construct() {
        parent::__construct();
        // Register all methods for current API Function
        $this->registerMethod(new APIMethod("get", true));
        $this->registerMethod(new APIMethod("getAll", true));
        $this->registerMethod(new APIMethod("register", true));
        $this->registerMethod(new APIMethod("auth", true));
    }

    function get($data) {
        $isDataValid = APIUtils::checkInputProperties($data, array("token"));
        if ($isDataValid) {
            $token = trim($data->token);
            $result = $this->db->query("SELECT * FROM `users` WHERE `token` = '$token'");
            if ($result->num_rows > 0) {
                $this->user = $result->fetchArray();
                $this->putResponseSuccess();
                $this->putResponseData($this->user);
                return;
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("TOKEN_INVALID_ERROR");
    }

    function getAll($data) {
        $isDataValid = APIUtils::checkInputProperties($data, array("token"));
        if ($isDataValid) {
            $token = trim($data->token);
            $result = $this->db->query("SELECT * FROM `users` WHERE `token` = '$token'");
            if ($result->num_rows > 0) {
                $this->user = $result->fetchArray();
                if ($this->user["access_level"] == 9) {
                    $result = $this->db->query("SELECT `id`,`login` FROM `users`");
                    $users = $result->fetchAll();
                    $this->putResponseSuccess();
                    $this->putResponseDataElement("count", count($users));
                    $this->putResponseDataElement("users", $users);
                    return;
                } else {
                    $this->putResponseError();
                    $this->putResponseErrorElement("ACCESS_DENIED_ERROR");
                    return;
                }
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("TOKEN_INVALID_ERROR");
    }

    function register($data) {
        $isDataValid = APIUtils::checkInputProperties($data, array("login", "password", "email"));
        if ($isDataValid) {
            $login = trim($data->login);
            $options = ['cost' => 12];
            $password = password_hash(trim($data->password), PASSWORD_BCRYPT, $options);
            $email = trim($data->email);
            $token = md5(uniqid($login, true));

            $result = $this->db->query("SELECT * FROM `users` WHERE `login` = '$login'");
            if ($result->num_rows > 0) {
                $this->putResponseError();
                $this->putResponseErrorElement("REGISTRATION_USER_EXISTS_ERROR");
                return;
            } else {
                $result = $this->db->query("INSERT IGNORE INTO `users` (`login`, `password`, `email`, `token`)
                                            VALUES ('$login', '$password', '$email', '$token')");
                $this->putResponseSuccess();
                $this->putResponseDataElement("token", $token);
                return;
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("REGISTRATION_DATA_INVALID_ERROR");
    }

    function auth($data) {
        $isDataValid = APIUtils::checkInputProperties($data, array("login", "password"));
        if ($isDataValid) {
            $login = trim($data->login);
            $password = trim($data->password);
            $result = $this->db->query("SELECT * FROM `users` WHERE `login` = '$login'");
            if ($result->num_rows > 0) {
                $this->user = $result->fetchArray();
                if (password_verify($password, $this->user["password"])) {
                    $this->putResponseSuccess();
                    $this->putResponseDataElement("token", $this->user["token"]);
                    return;
                }
                if (IS_DEBUG) {
                    $this->putResponseError();
                    $this->putResponseErrorElement("AUTH_PASSWORD_INVALID_ERROR");
                    return;
                }
            }
            if (IS_DEBUG) {
                $this->putResponseError();
                $this->putResponseErrorElement("AUTH_LOGIN_INVALID_ERROR");
                return;
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("AUTH_DATA_INVALID_ERROR");
    }
}

?>

<?php

class APIusers extends APIBase {

    function __construct() {
        parent::__construct();
        // Register all methods for current API Function
        $this->registerMethod(new APIMethod("get", true));
        $this->registerMethod(new APIMethod("getAll", true));
        $this->registerMethod(new APIMethod("register", true));
        $this->registerMethod(new APIMethod("auth", true));
        $this->registerMethod(new APIMethod("ping", true));
        $this->registerMethod(new APIMethod("getToken", true));
    }

    /*
    *  Returns all data about user by Token key, if key is correct.
    *  Required input data:
    *       token: string
    *
    *  Return: User data or Error code
    */
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

    /*
    *  Returns data about all Users in the Database if Token key is correct and
    *  access level is 9 (Admin).
    *  Required input data:
    *       token: string
    *
    *  Return: Data about all Users or Error code
    */
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

    /*
    *  Registers a new user in the Database. Creates a Session and Token keys and
    *  return the Session key.
    *  Required input data:
    *       login: string
    *       password: string
    *       email: string
    *
    *  Return: Session key or Error code
    */
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
                $result = $this->db->query("INSERT IGNORE INTO `users` (`login`, `password`, `email`)
                                            VALUES ('$login', '$password', '$email')");
                $result = $this->db->query("SELECT `id` FROM `users` WHERE `login` = '$login'");
                if ($result->num_rows > 0) {
                    $this->user = $result->fetchArray();
                    $user_id = $this->user["id"];
                    $session = uniqid($login, true);
                    $result = $this->db->query("INSERT IGNORE INTO `sessions` (`user_id`, `session`, `last_update`)
                                                VALUES ('$user_id', '$session', now())");
                    $result = $this->db->query("INSERT IGNORE INTO `tokens` (`user_id`, `token`)
                                                VALUES ('$user_id', '$token')");
                    $this->putResponseSuccess();
                    $this->putResponseDataElement("session", $session);
                    return;
                } else {
                    $this->putResponseError();
                    $this->putResponseErrorElement("REGISTRATION_INTERNAL_ERROR");
                    return;
                }
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("REGISTRATION_DATA_INVALID_ERROR");
    }

    /*
    *  Creates and returns a Session key for user if login and password are correct
    *  Required input data:
    *       login: string
    *       password: string
    *
    *  Return: Session key or Error code
    */
    function auth($data) {
        $isDataValid = APIUtils::checkInputProperties($data, array("login", "password"));
        if ($isDataValid) {
            $login = trim($data->login);
            $password = trim($data->password);
            $result = $this->db->query("SELECT * FROM `users` WHERE `login` = '$login'");
            if ($result->num_rows > 0) {
                $this->user = $result->fetchArray();
                if (password_verify($password, $this->user["password"])) {
                    $session = uniqid($login, true);
                    $user_id = $this->user["id"];

                    $result = $this->db->query("INSERT INTO `sessions` (`user_id`, `session`, `last_update`)
                                                VALUES ('$user_id', '$session', now())
                                                ON DUPLICATE KEY UPDATE `session` = '$session', `last_update` = now()");
                    $this->putResponseSuccess();
                    $this->putResponseDataElement("session", $session);
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

    /*
    *  Check Token key or Update Session time.
    *  Uses, for instance to update a user online status
    *  Required input data:
    *       session: string <OR> token: string
    *
    *  Return: Status code
    */
    function ping($data) {
        $isDataValid = APIUtils::checkInputProperties($data, ["session|token"]);
        if ($isDataValid) {
            if (property_exists($data, "session")) {
                $session = trim($data->session);
                $result = $this->db->query("SELECT * FROM `sessions` WHERE `session` = '$session'");
                d($result);
                if ($result->num_rows > 0) {
                    // SESSION IS VALID
                    $user_id = $result->fetchArray()["user_id"];
                    $result = $this->db->query("UPDATE `sessions` SET `last_update` = now() WHERE `session` = '$session'");
                    if ($result) {
                        $this->putResponseSuccess();
                        return;
                    } else {
                        $this->putResponseError();
                        $this->putResponseErrorElement("SESSION_INTERNAL_ERROR");
                        return;
                    }
                } else {
                    $this->putResponseError();
                    $this->putResponseErrorElement("SESSION_INVALID_ERROR");
                    return;
                }
            } else if (property_exists($data, "token")) {
                // TOKEN
                $token = trim($data->token);
                $result = $this->db->query("SELECT * FROM `tokens` WHERE `token` = '$token'");
                d($result);
                if ($result->num_rows > 0) {
                    // token is valid
                    $user_id = $result->fetchArray()["user_id"];
                    $result = $this->db->query("SELECT * FROM `users` WHERE `id` = '$user_id'");
                    if ($result) {
                        $this->putResponseSuccess();
                        return;
                    } else {
                        $this->putResponseError();
                        $this->putResponseErrorElement("TOKEN_NO_USER_FOUND_ERROR");
                        return;
                    }
                } else {
                    $this->putResponseError();
                    $this->putResponseErrorElement("TOKEN_INVALID_ERROR");
                    return;
                }
            }
        }
        $this->putResponseError();
        $this->putResponseErrorElement("INPUT_DATA_INVALID_ERROR");
    }

    /*
    *  Find user by Login and Password and Return / Create a Token key as response
    *  Required input data:
    *       login: string
    *       password: string
    *
    *  Return: Token key or Error code
    */
    public function getToken($data) {
        $isDataValid = APIUtils::checkInputProperties($data, ["login", "password"]);
        if ($isDataValid) {
            $login = trim($data->login);
            $password = trim($data->password);
            $result = $this->db->query("SELECT * FROM `users` WHERE `login` = '$login'");
            if ($result->num_rows > 0) {
                $this->user = $result->fetchArray();
                if (password_verify($password, $this->user["password"])) {
                    $user_id = $this->user["id"];
                    $result = $this->db->query("SELECT * FROM `tokens` WHERE `user_id` = '$user_id'");
                    if ($result->num_rows > 0) {
                        $token = $result->fetchArray()["token"];
                        $this->putResponseSuccess();
                        $this->putResponseDataElement("token", $token);
                        return;
                    } else {
                        $token = md5(uniqid($login, true));
                        $result = $this->db->query("INSERT IGNORE INTO `tokens` (`user_id`, `token`)
                                                    VALUES ('$user_id', '$token')");
                        $this->putResponseSuccess();
                        $this->putResponseDataElement("token", $token);
                        return;
                    }
                }
            }
            $this->putResponseError();
            $this->putResponseErrorElement("USER_AUTH_ERROR");
            return;
        }
        $this->putResponseError();
        $this->putResponseErrorElement("INPUT_DATA_INVALID_ERROR");
    }
}

?>

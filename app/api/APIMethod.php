<?php

class APIMethod {

    private $name;
    private $isRequiresParams;

    public function __construct($name, $isRequiresParams) {
        $this->name = strtolower($name);
        $this->isRequiresParams = $isRequiresParams;
    }

    public function getName() {
        return $this->name;
    }

    public function isRequiresParameters() {
        return $this->isRequiresParams;
    }
}

?>

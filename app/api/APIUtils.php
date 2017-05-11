<?php

class APIUtils {

    public static function checkInputProperties($data, $properties) {
        if ($data !== NULL && $properties !== NULL && count($properties) > 0) {
            foreach ($properties as $property) {
                if (!property_exists($data, $property) || $data->$property === NULL)
                return false;
            }
            return true;
        }
        return false;
    }

}

?>

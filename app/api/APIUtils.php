<?php

class APIUtils {

    public static function checkInputProperties($data, $properties) {
        if ($data !== NULL && $properties !== NULL && count($properties) > 0) {
            foreach ($properties as $property) {
                if (strpos($property, '|') !== false) {
                    $p = explode("|", $property);
                    foreach ($p as $prop) {
                        if (property_exists($data, $prop) && $data->$prop !== NULL)
                            return true;
                    }
                    return false;
                } else
                    if (!property_exists($data, $property) || $data->$property === NULL)
                        return false;
            }
            return true;
        }
        return false;
    }

    /**
    *  Calculates if Time is Over.
    *  $timestamp - a time to check
    *  $max_time  - limit of time (in minutes)
    *
    *  return - true | false
    */
    public static function isTimeOver($timestamp, $max_time = SESSION_MAX_TIME_MINUTES) {
        if (isset($timestamp) && !empty($timestamp)) {
            $currentTime = new DateTime();
            $currentTime->setTimezone(new DateTimeZone('UTC'));
            $timeToCheck = new DateTime($timestamp);
            $diff = $currentTime->diff($timeToCheck);
            $diff_minutes = ($diff->format('%a')*1440)+($diff->format('%h')*60)+ ($diff->format('%i'));

            if($diff_minutes > $max_time)
                return true;
        }
        return false;
    }

}

?>

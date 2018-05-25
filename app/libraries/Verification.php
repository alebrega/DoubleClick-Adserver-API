<?php

class Verification {

    public static function isExist($array, $key, $required = FALSE) {
        if (array_key_exists($key, $array)) {
            return TRUE;
        } else {
            if ($required) {
                return Response::json(array(
                            'error' => true,
                            'message' => 'Falta el objeto obligatorio ' . $key), 403
                );
            } else {
                return FALSE;
            }
        }
    }

}

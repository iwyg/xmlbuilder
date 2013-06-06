<?php

if (!function_exists('snake_case')) {

    function snake_case($string)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $string));
    }
}

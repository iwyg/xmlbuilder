<?php

if (!function_exists('snake_case')) {

    function snake_case($string)
    {
		return ctype_lower($string) ? $string : strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $string));
    }
}

<?php

if (!function_exists('snake_case')) {

    /**
     * snake_case
     *
     * @param mixed $string
     * @access
     * @return mixed
     */
    function snake_case($string)
    {
		return ctype_lower($string) ? $string : strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $string));
    }
}

if (!function_exists('clear_value')) {

    /**
     * array_is_numeric
     *
     * @param array $array
     * @access
     * @return booelan
     */
    function clear_value($value)
    {
        return ((is_string($value) && 0 === strlen(trim($value))) || is_null($value)) ? null : $value;
    }
}

if (!function_exists('array_is_numeric')) {

    /**
     * array_is_numeric
     *
     * @param array $array
     * @access
     * @return booelan
     */
    function array_is_numeric(array $array)
    {
        return ctype_digit(implode('', array_keys($array)));
    }
}

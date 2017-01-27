<?php

/*
 * @copyright (c) Copyright 2007-2016 Virtuvia, LLC. All rights reserved.
 */

/**
 * Simple string inflection utility class.
 */
class Inflector
{
    /**
     * Translates a string with underscores into camel case (e.g. first_name firstName).
     *
     * @param string $str String in underscore format
     *
     * @return string translated into camel case
     */
    public static function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    /**
     * Translates a CamelCase string to an under_scored string.
     *
     * @param string $str String in CamelCase
     *
     * @return string underscored $str
     */
    public static function underscore($str)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $str));
    }
}

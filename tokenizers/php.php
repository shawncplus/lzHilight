<?php

/*
 * @copyright (c) Copyright 2007-2016 Virtuvia, LLC. All rights reserved.
 */

class PhpLexer extends DefaultLexer
{
    private static $func_next = false;
    private static $function_table = [];

    protected static $magic_methods = [
        '__construct', '__destruct', '__call', '__callStatic', '__get',
        '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString',
        '__invoke', '__set_state', '__clone', '__debugInfo',
    ];

    public function tokenize($output, &$starting_state = null)
    {
        $ret_tokens = [];
        foreach (token_get_all($output) as $key => $token) {
            if (is_array($token)) {
                $ret_tokens[] = ['token' => token_name($token[0]), 'string' => $token[1]];
            } elseif (strpos('=!+-/*.', trim($token)) !== false) {
                $ret_tokens[] = ['token' => 'PHP_OPERATOR', 'string' => $token];
            } else {
                $ret_tokens[] = ['token' => 'T_NORMAL', 'string' => $token];
            }
        }

        return $ret_tokens;
    }

    public static function handleVar($string)
    {
        return !preg_match('#(\$)([a-z0-9_]+)#i', $string, $var) ? [] : [
            ['token' => 'T_VAR_TOKEN', 'string' => $var[1]],
            ['token' => 'T_VAR_NAME',  'string' => $var[2]],
        ];
    }

    public static function handleFunc($string)
    {
        self::$func_next = true;

        return [['token' => 'PHP_KEYWORD', 'string' => $string]];
    }

    public static function handleString($string)
    {
        $lstr = trim(strtolower($string));

        if (self::$func_next) {
            self::$function_table[] = trim($string);
            self::$func_next = false;

            return [['token' => 'FUNC', 'string' => $string]];
        } elseif (in_array(trim($string), self::$function_table)) {
            return [['token' => 'FUNC', 'string' => $string]];
        } elseif (function_exists(trim($string)) || in_array(trim($string), self::$magic_methods)) {
            return [['token' => 'PHP_BUILTIN', 'string' => $string]];
        } elseif ($lstr === 'true' || $lstr === 'false') {
            return [['token' => 'PHP_BOOLEAN', 'string' => $string]];
        } elseif (in_array($lstr, ['null', 'bool', 'boolean', 'int', 'integer', 'real', 'double', 'float', 'string', 'object'])) {
            return [['token' => 'PHP_TYPE', 'string' => $string]];
        } else {
            return [['token' => 'PHP_NORMAL', 'string' => $string]];
        }
    }

    public static function handleStringHtml($string)
    {
        $lstr = trim(strtolower($string));

        if (self::$func_next) {
            self::$function_table[] = trim($string);
            $string = '<a id="' . trim($string) . '">' . $string . '</a>';
            self::$func_next = false;

            return [['token' => 'FUNC', 'string' => $string, 'noentities' => 1]];
        } elseif (in_array(trim($string), self::$function_table)) {
            $string = '<a href="#' . trim($string) . '">' . $string . '</a>';

            return [['token' => 'FUNC', 'string' => $string, 'noentities' => 1]];
        } elseif (function_exists(trim($string)) || in_array(trim($string), self::$magic_methods)) {
            $string = '<a href="http://php.net/' . trim($string) . '">' . $string . '</a>';

            return [['token' => 'PHP_BUILTIN', 'string' => $string, 'noentities' => 1]];
        } else {
            return self::handleString($string);
        }
    }

    public static function handleDocBlock($string)
    {
        if (strpos($string, '@') === false) {
            return [['token' => 'PHP_DOCBLOCK', 'string' => $string]];
        }

        $doctoks = [];
        foreach (explode("\n", $string) as $docpart) {
            $doctag = [];
            if (preg_match('#(^\s*\*\s+)(@[\S]+)($|(:?\s*.+))$#i', $docpart, $doctag)) {
                $doctoks[] = ['token' => 'PHP_DOCBLOCK', 'string' => $doctag[1]];
                $doctoks[] = ['token' => 'PHP_DOCTAG',    'string' => $doctag[2]];
                $doctoks[] = ['token' => 'PHP_DOCBLOCK', 'string' => $doctag[3] . "\n"];
            } else {
                $doctoks[] = ['token' => 'PHP_DOCBLOCK', 'string' => $docpart . (strpos($docpart, '*/') === false ? "\n" : '')];
            }
        }

        return $doctoks;
    }
}
/* vim: set syn=php nofen: */

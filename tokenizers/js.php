<?php

/*
 * @copyright (c) Copyright 2007-2016 Virtuvia, LLC. All rights reserved.
 */

/**
 * Simple FSM for tokenizing JS files, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 */
class JsLexer extends ClikeLexer
{
    protected static $operators = 'in,instanceof,let,typeof';
    protected static $labels = 'case,default';
    protected static $global_objects = 'array,boolean,date,infinity,javaarray,javaclass,javaobject,javapackage,math,number,nan,object,packages,regexp,string,undefined,java,netscape,sun,var';
    protected static $exceptions = 'error,evalerror,rangeerror,referenceerror,syntaxerror,typeerror,urierror';
    protected static $mem_ops = 'new,delete,class,extends';

    protected $starting_state = CLIKE_NORMAL;

    protected $tokens = [
        'JS_NRM', 'JS_STR_S', 'JS_STR_D', 'JS_E_Q', 'JS_STRING', 'JS_BRC', 'JS_OP',
        'JS_NMBR', 'JS_COMM_SLSH', 'JS_COMM_STAR', 'JS_ICOMM', 'JS_COMM_END', 'JS_BCOMM',
        'JS_ESC', 'JS_ESC', 'JS_ESC_INV',
    ];

    public static function handleString($string)
    {
        $tstr = strtolower(trim($string));
        $tests = [
            'keywords' => 'JS_KEY',   'conditional' => 'JS_COND',  'repeat' => 'JS_RPT',
            'branch' => 'JS_BRNCH', 'mem_ops' => 'JS_MEMOP', 'statement' => 'JS_STMT',
            'exceptions' => 'JS_EXCEP', 'operators' => 'JS_OP',    'labels' => 'JS_LBL',
            'global_objects' => 'JS_OBJ', 'constants' => 'CONST',
        ];

        $token = 'NORM';
        foreach ($tests as $var => $token_name) {
            if (preg_match('/(^|,)' . $tstr . '($|,)/', self::$$var)) {
                $token = $token_name;
            }
        }

        return [['token' => $token, 'string' => $string]];
    }

    public static function handleOperator($string)
    {
        if ($string === '=>') {
            return [['token' => 'JS_ARROW', 'string' => $string]];
        }

        if ($string === '.') {
            return [['token' => 'JS_BRC', 'string' => $string]];
        }

        return [['token' => 'OP', 'string' => $string]];
    }
}

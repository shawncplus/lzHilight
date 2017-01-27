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
define('CLIKE_NORMAL',     0); define('CLIKE_STRLIT_S',  1); define('CLIKE_STRLIT_D',  2); define('CLIKE_END_QUOTE',  3);
define('CLIKE_STRING',     4); define('CLIKE_BRACE',     5); define('CLIKE_OPERATOR',  6); define('CLIKE_NUMBER',     7);
define('CLIKE_COMM_SLASH', 8); define('CLIKE_COMM_STAR', 9); define('CLIKE_ICOMMENT', 10); define('CLIKE_COMM_END',  11);
define('CLIKE_BCOMMENT',  12); define('CLIKE_ESCAPE_S', 13); define('CLIKE_ESCAPE_D', 14); define('CLIKE_ESC_INV_D', 15);
define('CLIKE_ESC_INV_S', 16);

class ClikeLexer extends DefaultLexer
{
    protected static $keywords = 'void,function,throw,with';
    protected static $constants = 'true,false,null';
    protected static $conditional = 'if,else';
    protected static $repeat = 'do,while,for';
    protected static $branch = 'break,continue,switch,return';
    protected static $statement = 'try,catch,throw,with,finally';
    protected static $operators = '';
    protected static $labels = 'case,default';
    protected static $global_objects = '';
    protected static $exceptions = '';
    protected static $mem_ops = 'new,delete';

    protected $starting_state = CLIKE_NORMAL;

    protected $state_table = [
        CLIKE_NORMAL => [
            '\'' => CLIKE_STRLIT_S, '"' => CLIKE_STRLIT_D, '[a-zA-Z]' => CLIKE_STRING, '[\{\[\(\)\]\}]' => CLIKE_BRACE,
            '[\-\+\^\>\<\*=\:\|\?\!]' => CLIKE_OPERATOR, '[\d\.]' => CLIKE_NUMBER, '\/' => CLIKE_COMM_SLASH,
        ],
        CLIKE_STRLIT_S => ['\\\\' => CLIKE_ESCAPE_S, '\'' => CLIKE_END_QUOTE],
        CLIKE_STRLIT_D => ['\\\\' => CLIKE_ESCAPE_D, '"' => CLIKE_END_QUOTE],
        CLIKE_END_QUOTE => [CLIKE_NORMAL],
        CLIKE_STRING => ['[\{\[\(\)\]\}]' => CLIKE_BRACE, '[\.\-\+\^\>\<\*=\:\|\?\!]' => CLIKE_OPERATOR, '\'' => CLIKE_STRLIT_S, '"' => CLIKE_STRLIT_D,  '\W' => CLIKE_NORMAL],
        CLIKE_BRACE => [CLIKE_NORMAL],
        CLIKE_OPERATOR => [
            '[\{\[\(\)\]\}]' => CLIKE_BRACE, '\'' => CLIKE_STRLIT_S, '"' => CLIKE_STRLIT_D,
            '[\d\.]' => CLIKE_NUMBER, '[^\-\+\>\=\|]' => CLIKE_NORMAL,
        ],
        CLIKE_NUMBER => ['[\{\[\(\)\]\}]' => CLIKE_BRACE, '[\-\+\^\>\<\*=\:\|\?\!]' => CLIKE_OPERATOR, '[^\d\.]' => CLIKE_NORMAL],
        CLIKE_COMM_SLASH => ['\*' => CLIKE_COMM_STAR, '\/' => CLIKE_ICOMMENT, "['\"]" => CLIKE_END_QUOTE, CLIKE_NORMAL],
        CLIKE_ICOMMENT => ["[\r\n]" => CLIKE_NORMAL],
        CLIKE_COMM_STAR => ['\/' => CLIKE_COMM_END,   '.' => CLIKE_BCOMMENT],
        CLIKE_BCOMMENT => ['\*' => CLIKE_COMM_STAR],
        CLIKE_COMM_END => [CLIKE_NORMAL],
        CLIKE_ESCAPE_S => ['\'' => CLIKE_STRLIT_S, CLIKE_ESC_INV_S],
        CLIKE_ESCAPE_D => ['["nrt\\\]' => CLIKE_STRLIT_D, CLIKE_ESC_INV_D],
        CLIKE_ESC_INV_D => [CLIKE_STRLIT_D],
        CLIKE_ESC_INV_S => [CLIKE_STRLIT_S],
    ];

    protected $tokens = [
        'CLIKE_NRM', 'CLIKE_STR_S', 'CLIKE_STR_D', 'CLIKE_E_Q', 'CLIKE_STRING', 'CLIKE_BRC', 'CLIKE_OP',
        'CLIKE_NMBR', 'CLIKE_COMM_SLSH', 'CLIKE_COMM_STAR', 'CLIKE_ICOMM', 'CLIKE_COMM_END', 'CLIKE_BCOMM',
        'CLIKE_ESC', 'CLIKE_ESC', 'CLIKE_ESC_INV',
    ];

    public static function handleString($string)
    {
        $tstr = strtolower(trim($string));
        $tests = [
            'keywords' => 'CLIKE_KEY',   'conditional' => 'CLIKE_COND',  'repeat' => 'CLIKE_RPT',
            'branch' => 'CLIKE_BRNCH', 'mem_ops' => 'CLIKE_MEMOP', 'statement' => 'CLIKE_STMT',
            'exceptions' => 'CLIKE_EXCEP', 'operators' => 'CLIKE_OP',    'labels' => 'CLIKE_LBL',
            'global_objects' => 'CLIKE_OBJ', 'constants' => 'CONST',
        ];

        $token = 'NORM';
        foreach ($tests as $var => $token_name) {
            if (preg_match('/(^|,)' . $tstr . '($|,)/', self::$$var)) {
                $token = $token_name;
            }
        }

        return [['token' => $token, 'string' => $string]];
    }
}

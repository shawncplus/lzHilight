<?php

/*
 * @copyright (c) Copyright 2007-2016 Virtuvia, LLC. All rights reserved.
 */

/**
 * Simple FSM for tokenizing C++ files, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 */
define('CPP_NORMAL',     0); define('CPP_STRLIT_S',  1); define('CPP_STRLIT_D',  2); define('CPP_END_QUOTE',  3);
define('CPP_STRING',     4); define('CPP_BRACE',     5); define('CPP_OPERATOR',  6); define('CPP_NUMBER',     7);
define('CPP_COMM_SLASH', 8); define('CPP_COMM_STAR', 9); define('CPP_ICOMMENT', 10); define('CPP_COMM_END',  11);
define('CPP_BCOMMENT',  12); define('CPP_ESCAPE_S', 13); define('CPP_ESCAPE_D', 14); define('CPP_ESC_INV_D', 15);
define('CPP_ESC_INV_S', 16); define('CPP_PREPROC',  17); define('CPP_PREPROC_CONT', 18); define('CPP_NEWLINE', 19);

class CppLexer extends ClikeLexer
{
    protected static $keywords = 'function,throw,with,using,namespace';
    protected static $global_objects = 'void,integer,int,bool,boolean,long,char,class,enum,struct,inline,static,unsigned';
    protected static $operators = 'public,private,protected,friend,typedef,const';
    protected $starting_state = CPP_NORMAL;

    protected $tokens = [
        'CPP_NRM', 'CPP_STR_S', 'CPP_STR_D', 'CPP_E_Q', 'CPP_STRING', 'CPP_BRC', 'CPP_OP',
        'CPP_NMBR', 'CPP_COMM_SLSH', 'CPP_COMM_STAR', 'CPP_ICOMM', 'CPP_COMM_END', 'CPP_BCOMM',
        'CPP_ESC', 'CPP_ESC', 'CPP_ESC_INV', 'CPP_ESC_INV', 'CPP_PPROC', 'CPP_PPROC', 'CPP_NRM',
    ];

    protected $state_table = [
        CPP_NORMAL => [
            '\'' => CPP_STRLIT_S, '"' => CPP_STRLIT_D, '[a-zA-Z]' => CPP_STRING, '[\{\[\(\)\]\}]' => CPP_BRACE,
            '[\-\+\^\>\<\*=\:\|\?\!%]' => CPP_OPERATOR, '[\d\.]' => CPP_NUMBER, '\/' => CPP_COMM_SLASH,
            '#' => CPP_PREPROC, "[\r\n]" => CPP_NEWLINE,
        ],
        CPP_STRLIT_S => ['\\\\' => CPP_ESCAPE_S, '\'' => CPP_END_QUOTE],
        CPP_STRLIT_D => ['\\\\' => CPP_ESCAPE_D, '"' => CPP_END_QUOTE],
        CPP_END_QUOTE => [CPP_NORMAL],
        CPP_STRING => ['[\{\[\(\)\]\}]' => CPP_BRACE, '[\-\+\^\>\<\*=\:\|\?\!]' => CPP_OPERATOR, '\'' => CPP_STRLIT_S, '"' => CPP_STRLIT_D,  '\W' => CPP_NORMAL],
        CPP_BRACE => [CPP_NORMAL],
        CPP_OPERATOR => ['\'' => CPP_STRLIT_S, '"' => CPP_STRLIT_D, '[\d\.]' => CPP_NUMBER, '[^\=\|:\<\>\-\+]' => CPP_NORMAL],
        CPP_NUMBER => ['[\{\[\(\)\]\}]' => CPP_BRACE, '[\-\+\^\>\<\*=\:\|\?\!]' => CPP_OPERATOR, '[^\d\.]' => CPP_NORMAL],
        CPP_COMM_SLASH => ['\*' => CPP_COMM_STAR, '\/' => CPP_ICOMMENT, "['\"]" => CPP_END_QUOTE, CPP_NORMAL],
        CPP_ICOMMENT => ["[\r\n]" => CPP_NORMAL],
        CPP_COMM_STAR => ['\/' => CPP_COMM_END,   '.' => CPP_BCOMMENT],
        CPP_BCOMMENT => ['\*' => CPP_COMM_STAR],
        CPP_COMM_END => [CPP_NORMAL],
        CPP_ESCAPE_S => ['\'' => CPP_STRLIT_S, CPP_ESC_INV_S],
        CPP_ESCAPE_D => ['["nrt\\\]' => CPP_STRLIT_D, CPP_ESC_INV_D],
        CPP_ESC_INV_D => [CPP_STRLIT_D],
        CPP_ESC_INV_S => [CPP_STRLIT_S],
        CPP_PREPROC => ["[\r\n]" => CPP_NEWLINE, '\\\\' => CPP_PREPROC_CONT],
        CPP_PREPROC_CONT => ["[\r\n]" => CPP_PREPROC, CPP_NORMAL],
        CPP_NEWLINE => ['#' => CPP_PREPROC, CPP_NORMAL],
    ];

    public static function handleString($string)
    {
        $tstr = strtolower(trim($string));
        $tests = [
            'keywords' => 'CPP_KEY',   'conditional' => 'CPP_COND',  'repeat' => 'CPP_RPT',
            'branch' => 'CPP_BRNCH', 'mem_ops' => 'CPP_MEMOP', 'statement' => 'CPP_STMT',
            'exceptions' => 'CPP_EXCEP', 'operators' => 'CPP_OP',    'labels' => 'CPP_LBL',
            'global_objects' => 'CPP_OBJ', 'constants' => 'CONST',
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

<?php
/**
 * Simple FSM for tokenizing JS files, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 */

define('JS_NORMAL',     0); define('JS_STRLIT_S',  1); define('JS_STRLIT_D',  2); define('JS_END_QUOTE',  3);
define('JS_STRING',     4); define('JS_BRACE',     5); define('JS_OPERATOR',  6); define('JS_NUMBER',     7);
define('JS_COMM_SLASH', 8); define('JS_COMM_STAR', 9); define('JS_ICOMMENT', 10); define('JS_COMM_END',  11);
define('JS_BCOMMENT',  12);

class JsLexer extends DefaultLexer
{
	protected static $keywords     = 'void,function,throw,with';
	protected static $constants    = 'true,false,null';
	protected static $conditional  = 'if,else';
	protected static $repeat       = 'do,while,for';
	protected static $branch       = 'break,continue,switch,return';
	protected static $statement    = 'try,catch,throw,with,finally';
	protected static $operators    = 'in,instanceof,let,typeof,yield';
	protected static $labels       = 'case,default';
	protected static $global_objects = 'array,boolean,date,infinity,javaarray,javaclass,javaobject,javapackage,math,number,nan,object,packages,regexp,string,undefined,java,netscape,sun,var';
	protected static $exceptions   = 'error,evalerror,rangeerror,referenceerror,syntaxerror,typeerror,urierror';
	protected static $mem_ops      = 'new,delete';

	protected $starting_state = JS_NORMAL;

	protected $state_table = array(
		JS_NORMAL     => array(
			'\'' => JS_STRLIT_S, '"' => JS_STRLIT_D, '[a-zA-Z]' => JS_STRING, '[\{\[\(\)\]\}]' => JS_BRACE,
			'[\-\+\^\>\<\*=\:\|\?\!]' => JS_OPERATOR, '[\d\.]' => JS_NUMBER, '\/' => JS_COMM_SLASH,
		),
		JS_STRLIT_S   => array('\'' => JS_END_QUOTE),
		JS_STRLIT_D   => array('"' => JS_END_QUOTE),
		JS_END_QUOTE  => array(JS_NORMAL),
		JS_STRING     => array('[\{\[\(\)\]\}]' => JS_BRACE, '[\-\+\^\>\<\*=\:\|\?\!]' => JS_OPERATOR, '\W' => JS_NORMAL),
		JS_BRACE      => array(JS_NORMAL),
		JS_OPERATOR   => array('\'' => JS_STRLIT_S, '"' => JS_STRLIT_D, '[^\+\-\=\|]' => JS_NORMAL),
		JS_NUMBER     => array('[\{\[\(\)\]\}]' => JS_BRACE, '[\-\+\^\>\<\*=\:\|\?\!]' => JS_OPERATOR, '[^\d\.]' => JS_NORMAL),
		JS_COMM_SLASH => array('\*' => JS_COMM_STAR, '\/' => JS_ICOMMENT, "['\"]" => JS_END_QUOTE, JS_NORMAL),
		JS_ICOMMENT   => array("[\r\n]" => JS_NORMAL),
		JS_COMM_STAR  => array('\/' => JS_COMM_END,   '.'  => JS_BCOMMENT),
		JS_BCOMMENT   => array('\*' => JS_COMM_STAR),
		JS_COMM_END   => array(JS_NORMAL),
	);

	protected $tokens = array(
		'JS_NRM', 'JS_STR_S', 'JS_STR_D', 'JS_E_Q', 'JS_STRING', 'JS_BRC', 'JS_OP',
		'JS_NMBR', 'JS_COMM_SLSH', 'JS_COMM_STAR', 'JS_ICOMM', 'JS_COMM_END', 'JS_BCOMM',
	);

	public static function handleString($string)
	{
		$tstr = strtolower(trim($string));
		$tests = array(
			'keywords'   => 'JS_KEY',   'conditional' => 'JS_COND',  'repeat'    => 'JS_RPT',
			'branch'     => 'JS_BRNCH', 'mem_ops'     => 'JS_MEMOP', 'statement' => 'JS_STMT',
			'exceptions' => 'JS_EXCEP', 'operators'   => 'JS_OP',    'labels'    => 'JS_LBL',
			'global_objects' => 'JS_OBJ', 'constants' => 'CONST',
		);

		$token = 'NORM';
		foreach ($tests as $var => $token_name)
		{
			if (preg_match('/(^|,)' . $tstr . '($|,)/', self::$$var))
			{
				$token = $token_name;
			}
		}

		return array(array('token' => $token, 'string' => $string));
	}
}

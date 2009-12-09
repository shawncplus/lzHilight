<?php
/**
 * Simple FSM for tokenizing INI files, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 */

define('INI_WHITESPACE',   0); define('INI_BRACE',        1); define('INI_SECTION',      2); define('INI_COMMENT', 3);
define('INI_KEY',          4); define('INI_STRING_S',     5); define('INI_STRING_D',     6); define('INI_VAL_I',   7);
define('INI_ERROR',        8); define('INI_EQUAL',        9); define('INI_I_WHITESPACE',10); define('INI_VAL_S',  11);
define('INI_END_QUOTE',   12);

class IniLexer extends DefaultLexer
{
	protected $starting_state = INI_WHITESPACE;

	protected $state_table = array(
		INI_WHITESPACE => array(';' => INI_COMMENT, '\w' => INI_KEY, '\[' => INI_BRACE),
		INI_BRACE      => array("[^\n\r]" => INI_SECTION, "[\n\r]" => INI_WHITESPACE),
		INI_SECTION    => array('\]' => INI_BRACE),
		INI_COMMENT    => array("[\n\r]" => INI_WHITESPACE),
		INI_KEY        => array('\=' => INI_EQUAL, "[ \t]" => INI_I_WHITESPACE, '[^\w\-\.]' => INI_ERROR),
		INI_EQUAL      => array('\'' => INI_STRING_S, '"' => INI_STRING_D, '[\.\d]' => INI_VAL_I, "[ \t]" => INI_I_WHITESPACE, "[^ \t]" => INI_VAL_S),
		INI_STRING_S   => array('\'' => INI_END_QUOTE),
		INI_STRING_D   => array('"' => INI_END_QUOTE),
		INI_VAL_I      => array('\s' => INI_WHITESPACE),
		INI_I_WHITESPACE => array(
			'\'' => INI_STRING_S, '"' => INI_STRING_D, '\=' => INI_EQUAL, '\d' => INI_VAL_I, "[^ \t]" => INI_VAL_S
		),
		INI_VAL_S      => array(';' => INI_COMMENT, "[\n\r]" => INI_WHITESPACE),
		INI_END_QUOTE  => array(INI_WHITESPACE),
	);

	protected $tokens = array(
		'INI_WHTSPC', 'INI_BRC', 'INI_SEC', 'INI_COMM', 'INI_KEY', 'INI_STR_S', 'INI_STR_D', 'INI_VAL_I', 'INI_ERR',
		'INI_EQ', 'INI_I_WHTSPC', 'INI_VAL_S', 'INI_E_Q',
	);
}

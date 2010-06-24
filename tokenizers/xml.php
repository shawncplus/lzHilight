<?php
/**
 * Simple FSM for tokenizing XML, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 * @version 0.6
 */

define('XML_WHITESPACE',   0); define('XML_TAG_START',    1); define('XML_TAG_END',     2); define('XML_ATTRIBUTE',    3);
define('XML_COMMENT',      4); define('XML_STRING_S',     5); define('XML_STRING_D',    6); define('XML_COMMENT_BANG', 7);
define('XML_COMM_SBD',     8); define('XML_COMM_SDD',     9); define('XML_ERROR',      10); define('XML_CONTENT',     11);
define('XML_TAG_NAME',    12); define('XML_T_WHITESPACE',13); define('XML_END_SLASH',  14); define('XML_VAL_EQ',      15);
define('XML_COMM_EBD',    16); define('XML_COMM_EDD',    17); define('XML_AMP',        18); define('XML_ENTITY',      19);
define('XML_CD_START',    20); define('XML_CDATA',       21); define('XML_CD_END',     22); define('XML_END_QUOTE',   23);

class XmlLexer extends DefaultLexer
{
	protected $starting_state = XML_CONTENT;

	protected $tokens = array(
		'XML_WHITESPACE', 'XML_TAG_START',    'XML_TAG_END',   'XML_ATTRIBUTE', 'XML_COMMENT',  'XML_STRING_S',
		'XML_STRING_D',   'XML_COMMENT_BANG', 'XML_COMM_SBD',  'XML_COMM_SDD',  'XML_ERROR',    'XML_CONTENT',
		'XML_TAG_NAME',   'XML_T_WHITESPACE', 'XML_END_SLASH', 'XML_VAL_EQ',    'XML_COMM_EBD', 'XML_COMM_EDD',
		'XML_AMP',        'XML_ENTITY', 'XML_CD_START', 'XML_CDATA', 'XML_CD_END', 'XML_END_QUOTE'
	);

	protected $state_table = array(
		XML_CONTENT    => array('\<' => XML_TAG_START, '&' => XML_AMP),
		XML_AMP        => array('\W' => XML_CONTENT, '\w' => XML_ENTITY),
		XML_ENTITY     => array(';' => XML_CONTENT),
		XML_TAG_START  => array('\w' => XML_TAG_NAME, ' ' => XML_CONTENT,    '!' => XML_COMMENT_BANG, '\/' => XML_END_SLASH, '[^\?]' => XML_ERROR),
		XML_COMMENT_BANG => array('-' => XML_COMM_SBD, '\w' => XML_TAG_NAME, '\[' => XML_CD_START,  '.' => XML_CONTENT),
		XML_COMM_SBD   => array('-' => XML_COMM_SDD, XML_CONTENT),
		XML_COMM_SDD   => array('\>' => XML_CONTENT, '-' => XML_ERROR, XML_COMMENT),
		XML_COMMENT    => array('-' => XML_COMM_EBD),
		XML_COMM_EBD   => array('-' => XML_COMM_EDD, XML_COMMENT),
		XML_COMM_EDD   => array('\>' => XML_CONTENT, XML_ERROR),
		XML_TAG_NAME   => array('\s' => XML_T_WHITESPACE, '\/' => XML_END_SLASH, '[\?\>]' => XML_TAG_END, '[^:\-\w]' => XML_ERROR),
		XML_TAG_END    => array(XML_CONTENT),
		XML_T_WHITESPACE => array('\>' => XML_TAG_END, '\/' => XML_END_SLASH, '\w' => XML_ATTRIBUTE, '\'' => XML_STRING_S, '"' => XML_STRING_D),
		XML_ATTRIBUTE  => array('\=' => XML_VAL_EQ, '\s' => XML_T_WHITESPACE, '[\:\-]' => XML_ATTRIBUTE, '\>' => XML_TAG_END, '\/' => XML_END_SLASH, '\W' => XML_ERROR),
		XML_VAL_EQ     => array('\'' => XML_STRING_S, '"' => XML_STRING_D, '.' => XML_ERROR),
		XML_STRING_S   => array('\'' => XML_END_QUOTE),
		XML_STRING_D   => array('"' => XML_END_QUOTE),
		XML_ERROR      => array('\>' => XML_TAG_END),
		XML_END_SLASH  => array('\>' => XML_TAG_END, '\w' => XML_TAG_NAME),
		XML_CD_START   => array('C' => XML_CDATA, XML_CONTENT),
		XML_CDATA      => array('\]' => XML_CD_END),
		XML_CD_END    => array('\]' => XML_CONTENT, XML_CDATA),
		XML_END_QUOTE => array(XML_T_WHITESPACE),
	);

	function tokenize($output, &$starting_state = XML_CONTENT, &$current_tag = '')
	{
		$i = 0;
		$state = is_string($starting_state) ? array_search($starting_state, $this->tokens) : $this->starting_state;
		$ret_tokens = array();
		$cur_state_string = '';

		$inside_tag = false;

		while (isset($output[$i]))
		{
			$char = $output[$i++];
			$new_state = $this->change_state($state, $char);
			if ($new_state !== $state)
			{
				if ($state === XML_TAG_NAME)
				{
					$current_tag = trim($cur_state_string);
				}

				if ($state === XML_END_SLASH)
				{
					$inside_tag = false;
				}
				else if ($state == XML_TAG_NAME) 
				{
					$inside_tag = true;
				}

				if ($state === XML_CONTENT)
				{
					$ret_tokens = array_merge($ret_tokens, XmlLexer::handleContent($current_tag, $cur_state_string));
					$state = $new_state;
					$cur_state_string = $char;
					continue;
				}

				$ret_tokens[] = array('token' => $this->tokens[$state], 'string' => $cur_state_string);
				$state = $new_state;
				$cur_state_string = $char;
				continue;
			}
			$cur_state_string .= $char;
		}

		$starting_state = $this->tokens[$state];
		if ($state === XML_CONTENT)
		{
			return array_merge($ret_tokens, XmlLexer::handleContent($current_tag, $cur_state_string));
		}

		$ret_tokens[] = array('token' => $this->tokens[$state], 'string' => $cur_state_string);
		return $ret_tokens;
	}

	public static function handleString($string)
	{
		if (preg_match('#^[\'"]http://#', $string))
		{
			$string = str_replace(array("'", '"'), array('',''), $string);
			$string = '&quot;<a href="' . $string . '" class="STR">' . htmlentities($string) . '</a>';
		}
		else
		{
			return array(array('token' => 'STR', 'string' => $string));
		}
		return array(array('token' => 'STR', 'string' => $string, 'noentities' => 1));
	}

	public static function handleContent($tag, $content)
	{
		$tokens = NULL;
		switch ($tag)
		{
			case 'tyle': // PHP retardation
			case 'style':
				$lexer = new CssLexer;
				$tokens = $lexer->tokenize($content);
				unset($lexer);
				break;
			case 'cript':
			case 'script':
				$lexer = new JsLexer;
				$tokens = $lexer->tokenize($content);
				unset($lexer);
				break;
			default:
				$tokens = array(array('token' => 'XML_CONTENT', 'string' => $content));
				break;
		}
		return $tokens;
	}

}
/* vim: set syn=php: */

<?php
/**
 * Simple FSM for tokenizing HTML, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 * @version 0.6
 */

define('HTML_WHITESPACE',   0); define('HTML_TAG_START',    1); define('HTML_TAG_END',     2); define('HTML_ATTRIBUTE',    3);
define('HTML_COMMENT',      4); define('HTML_STRING_S',     5); define('HTML_STRING_D',    6); define('HTML_COMMENT_BANG', 7);
define('HTML_COMM_SBD',     8); define('HTML_COMM_SDD',     9); define('HTML_ERROR',      10); define('HTML_CONTENT',     11);
define('HTML_TAG_NAME',    12); define('HTML_T_WHITESPACE',13); define('HTML_END_SLASH',  14); define('HTML_VAL_EQ',      15);
define('HTML_COMM_EBD',    16); define('HTML_COMM_EDD',    17); define('HTML_AMP',        18); define('HTML_ENTITY',      19);
define('HTML_CD_START',    20); define('HTML_CDATA',       21); define('HTML_CD_END',     22); define('HTML_END_QUOTE',   23);

class HtmlLexer extends DefaultLexer
{
	protected $starting_state = HTML_CONTENT;

	protected $tokens = array(
		'HTML_WHITESPACE', 'HTML_TAG_START',    'HTML_TAG_END',   'HTML_ATTRIBUTE', 'HTML_COMMENT',  'HTML_STRING_S',
		'HTML_STRING_D',   'HTML_COMMENT_BANG', 'HTML_COMM_SBD',  'HTML_COMM_SDD',  'HTML_ERROR',    'HTML_CONTENT',
		'HTML_TAG_NAME',   'HTML_T_WHITESPACE', 'HTML_END_SLASH', 'HTML_VAL_EQ',    'HTML_COMM_EBD', 'HTML_COMM_EDD',
		'HTML_AMP',        'HTML_ENTITY', 'HTML_CD_START', 'HTML_CDATA', 'HTML_CD_END', 'HTML_END_QUOTE'
	);

	protected $state_table = array(
		HTML_CONTENT    => array('\<' => HTML_TAG_START, '&' => HTML_AMP),
		HTML_AMP        => array('\W' => HTML_CONTENT, '\w' => HTML_ENTITY),
		HTML_ENTITY     => array(';' => HTML_CONTENT),
		HTML_TAG_START  => array('\w' => HTML_TAG_NAME, ' ' => HTML_CONTENT,    '!' => HTML_COMMENT_BANG, '\/' => HTML_END_SLASH, '[^\?]' => HTML_ERROR),
		HTML_COMMENT_BANG => array('-' => HTML_COMM_SBD, '\w' => HTML_TAG_NAME, '\[' => HTML_CD_START,  '.' => HTML_CONTENT),
		HTML_COMM_SBD   => array('-' => HTML_COMM_SDD, HTML_CONTENT),
		HTML_COMM_SDD   => array('\>' => HTML_CONTENT, '-' => HTML_ERROR, HTML_COMMENT),
		HTML_COMMENT    => array('-' => HTML_COMM_EBD),
		HTML_COMM_EBD   => array('-' => HTML_COMM_EDD, HTML_COMMENT),
		HTML_COMM_EDD   => array('\>' => HTML_CONTENT, HTML_ERROR),
		HTML_TAG_NAME   => array('\s' => HTML_T_WHITESPACE, '\/' => HTML_END_SLASH, '[\?\>]' => HTML_TAG_END, '[^:\-\w]' => HTML_ERROR),
		HTML_TAG_END    => array(HTML_CONTENT),
		HTML_T_WHITESPACE => array('\>' => HTML_TAG_END, '\/' => HTML_END_SLASH, '\w' => HTML_ATTRIBUTE, '\'' => HTML_STRING_S, '"' => HTML_STRING_D),
		HTML_ATTRIBUTE  => array('\=' => HTML_VAL_EQ, '\s' => HTML_T_WHITESPACE, '[\:\-]' => HTML_ATTRIBUTE, '\>' => HTML_TAG_END, '\/' => HTML_END_SLASH, '\W' => HTML_ERROR),
		HTML_VAL_EQ     => array('\'' => HTML_STRING_S, '"' => HTML_STRING_D, '.' => HTML_ERROR),
		HTML_STRING_S   => array('\'' => HTML_END_QUOTE),
		HTML_STRING_D   => array('"' => HTML_END_QUOTE),
		HTML_ERROR      => array('\>' => HTML_TAG_END),
		HTML_END_SLASH  => array('\>' => HTML_TAG_END, '\w' => HTML_TAG_NAME),
		HTML_CD_START   => array('C' => HTML_CDATA, HTML_CONTENT),
		HTML_CDATA      => array('\]' => HTML_CD_END),
		HTML_CD_END    => array('\]' => HTML_CONTENT, HTML_CDATA),
		HTML_END_QUOTE => array(HTML_T_WHITESPACE),
	);

	function tokenize($output, &$starting_state = HTML_CONTENT, &$current_tag = '')
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
				if ($state === HTML_TAG_NAME)
				{
					$current_tag = trim($cur_state_string);
				}

				if ($state === HTML_END_SLASH)
				{
					$inside_tag = false;
				}
				else if ($state == HTML_TAG_NAME) 
				{
					$inside_tag = true;
				}

				if ($state === HTML_CONTENT)
				{
					$ret_tokens = array_merge($ret_tokens, HtmlLexer::handleContent($current_tag, $cur_state_string));
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
		if ($state === HTML_CONTENT)
		{
			return array_merge($ret_tokens, HtmlLexer::handleContent($current_tag, $cur_state_string));
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
				$tokens = array(array('token' => 'HTML_CONTENT', 'string' => $content));
				break;
		}
		return $tokens;
	}

}
/* vim: set syn=php: */

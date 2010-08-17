<?php
/**
 * Simple FSM for tokenizing YAML files, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 */

define('YML_WHITESPACE',   0); define('YML_COMMENT',      1); define('YML_HEADER',       2); define('YML_TERMINATOR', 3);
define('YML_KEY',          4); define('YML_STRING_S',     5); define('YML_STRING_D',     6); define('YML_VAL_I',   7);
define('YML_ERROR',        8); define('YML_VALOP',        9); define('YML_I_WHITESPACE',10); define('YML_VAL_S',  11);
define('YML_END_QUOTE',   12); define('YML_OPERATOR',    13);

class YamlLexer extends DefaultLexer
{
	protected $starting_state = YML_WHITESPACE;

	const HEADER_CHAR = '-';

	protected $state_table = array(
		YML_WHITESPACE => array('#' => YML_COMMENT, '-' => '&branchHeader', '\s' => YML_WHITESPACE, YML_KEY),
		YML_COMMENT    => array("[\n\r]" => YML_WHITESPACE),
		YML_KEY        => array(
			':' => YML_VALOP, "[ \t]" => YML_I_WHITESPACE, '\'' => YML_STRING_S, '"' => YML_STRING_D,
		),
		YML_VALOP      => array(
			"[\|>\+\-]" => YML_OPERATOR, "[\n\r]" => YML_WHITESPACE, '\'' => YML_STRING_S, '"' => YML_STRING_D,
			'[\.\d]' => YML_VAL_I, 	"[ \t]" => YML_I_WHITESPACE, "[^ \t]" => YML_VAL_S
		),
		YML_STRING_S   => array('\'' => YML_END_QUOTE),
		YML_STRING_D   => array('"' => YML_END_QUOTE),
		YML_VAL_I      => array("[ \t]" => YML_I_WHITESPACE, "[\n\r]" => YML_WHITESPACE),
		YML_I_WHITESPACE => array(
			"[\n\r]" => YML_WHITESPACE, '\'' => YML_STRING_S, '"' => YML_STRING_D,
			':' => YML_VALOP, "[\|>\+\-]" => YML_OPERATOR, '\d' => YML_VAL_I, "[^ \t]" => YML_VAL_S
		),
		YML_VAL_S      => array('#' => YML_COMMENT, "[ \t]" => YML_I_WHITESPACE, "[\n\r]" => YML_WHITESPACE),
		YML_END_QUOTE  => array("[ \t]" => YML_I_WHITESPACE, "[\n\r]" => YML_WHITESPACE, YML_KEY),
		YML_OPERATOR   => array("[\|>\+\-]" => YML_OPERATOR, "[\n\r]" => YML_WHITESPACE, YML_KEY),

		# reserved token for splitting state
		YML_HEADER => array("[\n\r]" => YML_WHITESPACE),
	);

	protected $tokens = array(
		'YML_WHTSPC', 'YML_CMT', 'YML_HEAD', 'YML_TERM', 'YML_KEY', 'YML_STR_S', 'YML_STR_D', 'YML_VAL_I', 'YML_ERR',
		'YML_VALOP', 'YML_I_WHTSPC', 'YML_VAL_S', 'YML_E_Q', 'YML_OP'
	);


	public function tokenize($output, &$starting_state = NULL)
	{
		$i = 0;
		$state = is_string($starting_state) ? array_search($starting_state, $this->tokens) : $this->starting_state;
		$ret_tokens = array();
		$cur_state_string = '';
		while (isset($output[$i]))
		{
			$char = $output[$i++];
			$new_state = $this->change_state($state, $char);

			// kludgey branching state handler
			if ($new_state[0] === '&')
			{
				$handler = str_replace('&', '', $new_state);
				$this->$handler($state, $new_state, $i, $char, $ret_tokens, $output);
			}

			if ($new_state != $state)
			{
				$ret_tokens[] = array('token' => $this->tokens[$state], 'string' => $cur_state_string);
				$state = $new_state;
				$cur_state_string = $char;
				continue;
			}
			$cur_state_string .= $char;
		}
		$ret_tokens[] = array('token' => $this->tokens[$state], 'string' => $cur_state_string);
		$starting_state = $this->tokens[$state];
		return $ret_tokens;
	}


	private function branchHeader(&$state, &$new_state, &$pointer, &$current_char, &$tokens, $contents)
	{
		$old_pointer = $pointer;
		$old_char    = $current_char;
		while(isset($contents[$pointer]))
		{
			$current_char = $contents[$pointer++];
			// ---
			if ($current_char !== self::HEADER_CHAR && ($pointer - $old_pointer) < 3)
			{
				$pointer = ++$old_pointer;
				$new_state = YML_OPERATOR;
				$current_char = $old_char . $current_char; // HACK
				return;
			}

			if ($current_char !== self::HEADER_CHAR)
			{
				$pointer = ++$old_pointer;
				$current_char = $old_char . $old_char; // HACK!
				$new_state = YML_HEADER;
				return;
			}
		}
	}
}

<?php
define('NORMAL', 1);
class DefaultLexer
{
	protected $starting_state = NORMAL;
	protected $state_table = array();
	protected $tokens = array('NORM');

	public function tokenize($output, &$starting_state = NULL)
	{
		$i = 0;
		$state = is_string($starting_state) ? array_search($starting_state, $this->tokens) : $this->starting_state;
		$ret_tokens = array();
		$cur_state_string = '';
		while(isset($output[$i])) {
			$char = $output[$i++];
			$new_state = $this->change_state($state, $char);
			if($new_state != $state) {
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

	protected function change_state($state, $char)
	{
		if(!isset($this->state_table[$state])) {
			return $state;
		}
		foreach($this->state_table[$state] as $event => $output) {
			if(is_int($event)) {
				return $this->change_state($output, $char);
			}
			if(preg_match('/'.$event.'/', $char)) {
				return $output;
			}
		}
		return $state;
	}
}

/* vim: set syn=php ft=php: */

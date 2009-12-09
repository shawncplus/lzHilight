<?php

/*
Bugs:
http://bugs.php.net/48446 - T_INLINE_HTML is split into 2 parts if the HTML
tag starts with an s. This breaks some syntax highlighting, est no bueno.
 */

class PhpLexer extends DefaultLexer
{
	private static $func_next = false;
	private static $function_table = array();

	public function tokenize($output, &$starting_state = NULL)
	{
		$ret_tokens = array();
		foreach (token_get_all($output) as $key => $token) {
			if(is_array($token)) {
				$ret_tokens[] = array('token' => token_name($token[0]), 'string' => $token[1]);
			} else {
				$ret_tokens[] = array('token' => 'T_NORMAL', 'string' => $token);
			}
		}
		return $ret_tokens;
	}
	
	public static function handlevar($string)
	{
		return !preg_match('#(\$)([a-z0-9_]+)#i', $string, $var) ? array() : array(
			array('token' => 'T_VAR_TOKEN', 'string' => $var[1]),
			array('token' => 'T_VAR_NAME',  'string' => $var[2])
		);
	}

	public static function handlefunc($string)
	{
		self::$func_next = true;
		return array(array('token' => 'PHP_FUNCTION', 'string' => $string));
	}

	public static function handlestring($string)
	{
		if (self::$func_next) {
			self::$function_table[]= trim($string);
			$string = '<a id="' . trim($string) . '">' . $string . '</a>';
			self::$func_next = false;
			return array(array('token' => 'H_FG', 'string' => $string, 'noentities' => 1));
		} if (in_array(trim($string), self::$function_table)) {
			$string = '<a href="#' . trim($string) .'">' . $string . '</a>';
			return array(array('token' => 'H_FG', 'string' => $string, 'noentities' => 1));
		} else {
			return array(array('token' => 'PHP_NORMAL', 'string' => $string));
		}
	}

	public static function handle_docblock($string)
	{
		if(strpos($string, '@') === false) {
			return array(array('token' => 'PHP_DOCBLOCK', 'string' => $string));
		}

		$doctoks = array();
		foreach(explode("\n", $string) as $docpart) {
			$doctag = array();
			if(preg_match('#(^\s*\*\s+)(@[a-z]+)(:?\s*.+)$#i', $docpart, $doctag)) {
				$doctoks[] = array('token' => 'PHP_DOCBLOCK', 'string' => $doctag[1]);
				$doctoks[] = array('token' => 'PHP_DOCTAG',    'string' => $doctag[2]);
				$doctoks[] = array('token' => 'PHP_DOCBLOCK', 'string' => $doctag[3]."\n");
			} else {
				$doctoks[] = array('token' => 'PHP_DOCBLOCK', 'string' => $docpart . (strpos($docpart, '*/') === false ? "\n" : ''));
			}
		}

		return $doctoks;
	}

}

/* vim: set syn=php: */
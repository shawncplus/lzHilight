<?php

require_once(dirname(__FILE__) . '/../autoload.php');

class Highlighter
{
	/**
	 * Cache of the config passed to the constructor
	 * @var array
	 */
	private $init_config = array();

	/**
	 * (html|cli)
	 * @var string
	 */
	private $mode        = 'cli';

	/**
	 * Array of tokens from a lexer
	 * @var array
	 */
	private $token_sets  = array();

	/**
	 * Map of tokens => colors from the parsed synfile
	 * @var array
	 */
	private $color_map   = array();

	/**
	 * CSS identifiers from CssHelper::getTokensetSelectors
	 * @var array
	 */
	private $identifiers = array();

	/**
	 * Acts as both a boolean to toggle line numbering and
	 * as a count of the max line number
	 * @var int
	 */
	private $lines = 0;

	/**
	 * Boolean to write the style or not
	 * @var bool
	 */
	private $write_style = false;

	/**#@+
	 * HTML options
	 */
	private $line_wrap_tag = 'pre';
	private $line_wrap_cls = 'LN_NUM_WRAP';
	private $code_wrap_tag = 'pre';
	private $code_wrap_cls = 'code';
	/**#@-*/


	/**
	 * Construct-o-matic 9000
	 * @param array $options see documentation for individual options
	 */
	public function __construct($options = array())
	{
		$valid_props = array(
			'mode', 'token_sets', 'color_map', 'identifiers', 'lines', 'line_wrap_tag', 'line_wrap_cls',
			'code_wrap_tag', 'code_wrap_cls', 'write_style',
		);

		foreach ($options as $key => $value) {
			if (in_array($key, $valid_props)) {
				$this->$key = $value;
				$this->init_config[$key] = $value;
			}
		}
	}


	/**
	 * Hooray, actually get down to business and highlight the tokens.
	 * This function is also recursive, it takes into account customized
	 * handler methods for tokens.
	 * @param  bool   $begin Beginning of highlight or is this a recursive call
	 * @return string The finished masterpiece
	 */
	public function highlight($begin = true)
	{
		$output = '';
		if ($this->write_style) {
			$output = '
			<style type="text/css">
				pre.code {
					background-color:' . $this->color_map['H_BG'] . ';
					border:2px solid #555;
					overflow-x:scroll;
					border-left:none;
					position:relative;
					padding-left:2px;
					margin: 0;
					float:left
				}
				pre.code b{font-weight:normal;}'
					. CssHelper::generateCss($this->identifiers['colormap']) . "
					pre.LN_NUM_WRAP{
						position:relative;
						float:left;
						border: 2px solid #555;
						border-right:1px solid #fff;
						background-color:" . $this->color_map['H_NBG'] . ";
						color:" . $this->color_map['H_NFG'] . ";
				}
				pre.code a {color:inherit !important}
			</style>\n";
		}

		// line numbering
		if ($this->mode == 'html' && $begin) {
			// add line numbering
			if ($this->lines) {
				$lines = '';
				$linecount = $this->lines;
				$linewidth = strlen('' . $linecount) + 1;
				for ($i = 1; $i <= $linecount; $i++) {
					$lines .= '<span id="' . $i . '">' . str_repeat(' ', $linewidth - strlen('' . $i)) . $i . "</span>\n";
				}
				$output .= '<' . $this->line_wrap_tag . ' class="' . $this->line_wrap_cls . '">' . $lines . '</' . $this->line_wrap_tag . '>';
			}
			$output .= '<' . $this->code_wrap_tag . ' class="' . $this->code_wrap_cls . '">';
		}

		$custom_func_prev_token = array();
		foreach($this->token_sets as $tokenset) {
			$color = isset($this->color_map[$tokenset['token']]) ? $this->color_map[$tokenset['token']] : DEFAULT_COLOR;
			$color = is_array($color) ? $color['fg'] : $color;

			// our token requires a special handler function
			if(strpos($color, '::') !== false) {
				list($class, $method) = explode('::', $color);
				if(!class_exists($class) || !method_exists($class, $method)) {
					$color = DEFAULT_COLOR;
				} else {
					// Support < 5.3 by creating an instance of the class
					$inst = new $class;
					$prev_token = isset($custom_func_prev_token[$color]) ? $custom_func_prev_token[$color] : NULL;
					$inner_tokens = $inst->$method($tokenset['string'], $prev_token);
					unset($inst);
					$custom_func_prev_token[$color] = $prev_token;

					$inner_highlighter = new Highlighter(array_merge($this->init_config, array(
						'token_sets' => $inner_tokens,
						'write_style' => false
					)));
					$output .= $inner_highlighter->highlight(false);
					unset($inner_highlighter);
					continue;
				}
			}
			if ($this->mode == 'html') {
				if (!isset($tokenset['noentities'])) {
					$tokenset['string'] = htmlentities($tokenset['string']);
				}
				$output .= '<b class="' . $this->identifiers['tokenmap'][$tokenset['token']] . '">'. $tokenset['string'] . '</b>';
			} else {
				$output .= '[' . $color . 'm' . $tokenset['string'] . '[0m';
			}
		}
		if ($this->mode == 'html' && $begin) {
			$output .= '</pre>';
		}

		return $output;
	}
}

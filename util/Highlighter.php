<?php
require_once(dirname(__FILE__) . '/../autoload.php');

/**
 * Workhorse of the highlighter
 * @uses CssHelper
 */
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

	/**
	 * @var string
	 */
	private $style = '';

	/**
	 * Try to produce smaller files by grouping adjacent tags that use the same token
	 * @var boolean
	 */
	private $reduce = false;

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
			'code_wrap_tag', 'code_wrap_cls', 'write_style', 'style', 'reduce'
		);

		foreach ($options as $key => $value)
		{
			if (in_array($key, $valid_props))
			{
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
		if ($this->write_style)
		{
			$output .= $this->getStyle($this->identifiers['colormap'], $this->color_map);
		}

		// line numbering
		if ($this->mode == 'html' && $begin)
		{
			// add line numbering
			if ($this->lines)
			{
				$lines = '';
				$linecount = $this->lines;
				$linewidth = strlen('' . $linecount) + 1;
				for ($i = 1; $i <= $linecount; $i++)
				{
					$lines .= '<span id="l_' . $i . '">' . str_repeat(' ', $linewidth - strlen('' . $i)) . $i . "</span>\n";
				}
				$output .= '<' . $this->line_wrap_tag . ' class="' . $this->line_wrap_cls . '">' . $lines . '</' . $this->line_wrap_tag . '>';
			}
			$output .= '<' . $this->code_wrap_tag . ' class="' . $this->code_wrap_cls . '">';
		}

		$custom_func_prev_token = array();
		foreach ($this->token_sets as $tokenset)
		{
			$color = isset($this->color_map[$tokenset['token']]) ? $this->color_map[$tokenset['token']] : DEFAULT_COLOR;
			if (!is_array($color))
			{
				$color = array('fg' => $color);
			}

			// our token requires a special handler function
			if (strpos($color['fg'], '::') !== false)
			{
				list($class, $method) = explode('::', $color['fg']);
				if (!class_exists($class) || !method_exists($class, $method))
				{
					$color['fg'] = DEFAULT_COLOR;
				}
				else
				{
					// Support < 5.3 by creating an instance of the class
					$inst = new $class;
					$prev_token = isset($custom_func_prev_token[$color['fg']]) ? $custom_func_prev_token[$color['fg']] : NULL;
					$inner_tokens = $inst->$method($tokenset['string'], $prev_token);
					unset($inst);
					$custom_func_prev_token[$color['fg']] = $prev_token;

					$inner_highlighter = new Highlighter(array_merge($this->init_config, array(
						'token_sets' => $inner_tokens,
						'write_style' => false
					)));
					$output .= $inner_highlighter->highlight(false);
					unset($inner_highlighter);
					continue;
				}
			}

			if ($this->mode == 'html')
			{
				if (!isset($tokenset['noentities']))
				{
					$tokenset['string'] = htmlentities($tokenset['string']);
				}

				if ($this->identifiers['tokenmap'][$tokenset['token']] === 'H_FG')
				{
					$output .= $tokenset['string'];
				}
				else
				{
					$output .= '<b class="' . $this->identifiers['tokenmap'][$tokenset['token']] . '">'. $tokenset['string'] . '</b>';
				}
			}
			else
			{
				$output .= "\033[48;5;" . (isset($color['bg']) ? $color['bg'] : $this->color_map['H_BG']) . 'm';
				$output .= "\033[38;5;" . $color['fg'] . 'm' . $tokenset['string'] . "\033[0m";
			}
		}

		if ($this->mode == 'html' && $begin)
		{
			$output .= '</pre>';
			if ($this->reduce)
			{
				$regex = '#<b class="([A-Z_]+?)">([^<]+?)\</b>(\s*)<b class="\1">([^<]+?)</b>#';
				while(preg_match($regex, $output))
				{
					$output = preg_replace($regex, '<b class="\1">\2\3\4</b>', $output);
				}
			}
		}

		return $output;
	}

	/**
	 * Fetch user styles.
	 * User styles may have variables in them in the format of:
	 * <code>
	 *   $TOKEN
	 * </code>
	 * Ie., pre.code { background-color: $H_FG; }
	 * Which will be replaced by the associated token in the color map
	 *
	 * @param array $identifiers {@see CssHelper::getTokensetSelectors}
	 * @param array $colormap {@see SynfileParser::parse}
	 * @return string
	 */
	public function getStyle(array $identifiers, array $colormap)
	{
		$output  = '<style type="text/css">';
		$output .= preg_replace('/\$([A-Z_]+)/e', "\$colormap[\"\\1\"]", $this->style);
		$output .= CssHelper::generateCss($identifiers);
		$output .= "</style>\n";
		return $output;
	}

	/**
	 * Mutator for style
	 * @param string $style
	 */
	public function setStyle($style)
	{
		$this->style = $style;
	}
}

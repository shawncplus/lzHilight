<?php

/**
 * Utility class to parse <syntax>.syn files.
 * Uses a PHP port of xterm color approximation originally written by
 * Wolfgang Frisch (xororand AT unfoog de) http://www.frexx.de/xterm-256-notes/
 */
class SynfileParser
{

	/**#@+
	 * basic options
	 */
	private $synfile_dir = './';
	/**#@-*/

	private static $_color_cache = array();

	private static $basic16 = array(
		array( 0x00, 0x00, 0x00 ), // 0
		array( 0xCD, 0x00, 0x00 ), // 1
		array( 0x00, 0xCD, 0x00 ), // 2
		array( 0xCD, 0xCD, 0x00 ), // 3
		array( 0x00, 0x00, 0xEE ), // 4
		array( 0xCD, 0x00, 0xCD ), // 5
		array( 0x00, 0xCD, 0xCD ), // 6
		array( 0xE5, 0xE5, 0xE5 ), // 7
		array( 0x7F, 0x7F, 0x7F ), // 8
		array( 0xFF, 0x00, 0x00 ), // 9
		array( 0x00, 0xFF, 0x00 ), // 10
		array( 0xFF, 0xFF, 0x00 ), // 11
		array( 0x5C, 0x5C, 0xFF ), // 12
		array( 0xFF, 0x00, 0xFF ), // 13
		array( 0x00, 0xFF, 0xFF ), // 14
		array( 0xFF, 0xFF, 0xFF )  // 15
	);

	private static $valuerange = array( 0x00, 0x5F, 0x87, 0xAF, 0xD7, 0xFF );

	/**
	 * Parse a syntax file into a color map to be used by the highlight function.
	 * This is recursive to allow #LINK directives in syntax files.
	 * @param string  Filename of the syntax file to be parsed
	 * @param boolean Use HTML colors?
	 * @return array  Completed color map
	 */
	public function parse($synfile, $html_col = false)
	{
		$sgr_colors = array(
			'black'  => '30', 'bblack'  => '1;30', // not sure how bold black works but oh well
			'red'    => '31', 'bred'    => '1;31',
			'green'  => '32', 'bgreen'  => '1;32',
			'yellow' => '33', 'byellow' => '1;33',
			'blue'   => '34', 'bblue'   => '1;34',
			'purple' => '35', 'bpurple' => '1;35',
			'cyan'   => '36', 'bcyan'   => '1;36',
			'white'  => '37', 'bwhite'  => '1;37'
		);

		$linked_files = array();

		$synfile = file($this->getSynfileDir() . $synfile);
		$color_map = array();
		foreach ($synfile as $line_no => $line)
		{
			if (trim($line) == '' || strpos($line, '//') === 0 ) continue;
			$parts = preg_split('/\s+/', trim($line));

			$token = $parts[0];
			$color = isset($parts[1]) ? $parts[1] : NULL;
			$bg_color = isset($parts[2]) ? $parts[2] : NULL;

			// #LINK directive
			if ($token == '#LINK')
			{
				$linked_synfile = $color;
				if (!in_array($linked_synfile, $linked_files))
				{
					$linked_files[] = $linked_synfile;
					$color_map = array_merge($color_map, $this->parse($linked_synfile, $html_col));
				}
				continue;
			} // HTML color
			elseif (preg_match('/^#[0-9A-F]{3,6}(%[ib])?/i', $color))
			{
				$decorators = '';
				if (strpos($color, '%'))
				{
					list($color, $decorators) = explode('%', $color);
				}

				$color = $html_col ? $color : self::htmlToSgr($color);
				if ($bg_color !== NULL && preg_match('/^#?[0-9A-F]{3,6}/i', $bg_color))
				{
					$bg_color = $bg_color !== NULL && $html_col ? $bg_color : self::htmlToSgr($bg_color);
					$color_map[$token] = array('fg' => $color, 'bg' => $bg_color, 'decorators' => $decorators);
					continue;
				}
				elseif ($html_col && $decorators !== '')
				{
					$color_map[$token] = array('fg' => $color, 'decorators' => $decorators);
					continue;
				}
			} // Token link
			elseif (preg_match('/[a-z_]/i', $color) && isset($color_map[$color]))
			{
				$color_map[$token] = $color_map[$color];
				continue;
			} // SGR Sequence
			elseif (array_key_exists($color, $sgr_colors))
			{
				$color = $sgr_colors[$color];
			}
			// handler function
			elseif (strpos($color, '|'))
			{
				$toggle = $bg_color;
				list($custom_func, $backup) = explode('|', $color);
				if ($toggle == 'HTMLONLY' && $html_col)
				{
					$color = $custom_func;
				}
				elseif ($toggle == 'CONSONLY' && !$html_col)
				{
					$color = $custom_func;
				}
				else
				{
					$color = (preg_match('/^[a-z_]+$/i', $backup)) ? $color_map[$backup] : $backup;
				}
			}
			$color_map[$token] = $color;
		}
		return $color_map;
	}

	/**
	 * Turn an HTML color into one of the 8 colors in the SGR sequences. Modify
	 * the different and bold thresholds to your liking for colors, ie.,
	 * #ff8800 = red, ffaa00 = yellow with a threshold of 0x50. The bold threshold
	 * controls how bright an HTML color has to be before it is bolded. #AA0000 is
	 * bold whereas #550000 is not, both are red however.
	 * @param string HTML color to convert
	 * @return string SGR sequence
	 */
	public static function htmlToSgr($color)
	{
		if (isset(self::$_color_cache[$color]))
		{
			return self::$_color_cache[$color];
		}

		$color = trim(str_replace('#', '', $color));
		if (!preg_match('/^[0-9A-F]{3,6}$/i', $color))
		{
			return DEFAULT_COLOR;
		}

		$color = strlen($color) == 3 ? (str_repeat($color[0], 2) . str_repeat($color[1], 2) . str_repeat($color[2], 2)) : $color;

		$colparts  = array_map('hexdec', array(substr($color, 0, 2), substr($color, 2, 2), substr($color, 4, 2)));
		$xterm = self::rgb2xterm($colparts);
		self::$_color_cache[$color] = $xterm;
		return $xterm;
	}

	/**
	* Convert an xterm color value (0-253) to the appropriate RGB values
	* @param integer $color xterm color
	* @return array [r, g, b]
	 */
	public static function xterm2rgb($color)
	{
		$rgb = array();

		if($color < 16)
		{
			return self::$basic16[$color];
		}

		// color cube color
		if($color >= 16 && $color <= 232)
		{
			$color -= 16;
			$rgb = array(self::$valuerange[($color / 36) % 6], self::$valuerange[($color / 6)  % 6], self::$valuerange[$color  % 6]);
		}

		// gray tone
		if($color >= 233 && $color <= 253)
		{
			$rgb[0] = $rgb[1] = $rgb[2] = 8 + ($color - 232) * 0x0a;
		}
		return $rgb;
	}

	/**
	 * Fill the colortable for use with rgb2xterm
	 * @return array
	 */
	public static function maketable()
	{
		$colortable = array();
		for($c = 0;$c <= 253;$c++)
		{
			$colortable[$c] = self::xterm2rgb($c);
		}
		return $colortable;
	}

	/**
	* Selects the nearest xterm color for a 3xBYTE rgb value
	* @param array $rgb [r, g, b]
	* @return integer
	 */
	public static function rgb2xterm($rgb)
	{
		$best_match = 0;
		$colortable = self::maketable();
		$smallest_distance = 10000000000.0;

		for($c = 0;$c <= 253;$c++)
		{
			$d = pow($colortable[$c][0]-$rgb[0],2.0) + pow($colortable[$c][1]-$rgb[1],2.0) + pow($colortable[$c][2]-$rgb[2],2.0);
			if ($d < $smallest_distance)
			{
				$smallest_distance = $d;
				$best_match=$c;
			}
		}

		return $best_match;
	}

	/**
	 * synfile_dir getter
	 * @return string
	 */
	public function getSynfileDir()
	{
		return $this->synfile_dir;
	}

	/**
	 * synfile_dir setter
	 * @param string $dir
	 */
	public function setSynfileDir($dir)
	{
		$this->synfile_dir = $dir;
	}
}

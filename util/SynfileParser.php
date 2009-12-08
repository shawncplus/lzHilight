<?php

class SynfileParser
{

	/**#@+
	 * basic options
	 */
	private $synfile_dir = './';
	private $difference_threshold = 0x70;
	private $bold_threshold = 0xA0;
	/**#@-*/

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
		foreach($synfile as $line_no => $line) {
			if (trim($line) == '' || strpos($line, '//') === 0 ) continue;
			$parts = preg_split('/\s+/', trim($line));

			$token = $parts[0];
			$color = $parts[1];
			$bg_color = isset($parts[2]) ? $parts[2] : NULL;

			// #LINK directive
			if ($token == '#LINK') {
				$linked_synfile = $color;
				if(!in_array($linked_synfile, $linked_files)) {
					$linked_files[] = $linked_synfile;
					$color_map = array_merge($color_map, $this->parse($linked_synfile, $html_col));
				}
				continue;
			} // HTML color
			else if(preg_match('/^#?[0-9A-F]{3,6}/i', $color)) {
				$color = $html_col ? $color : $this->htmlToSgr($color);
				if ($bg_color !== NULL && preg_match('/^#?[0-9A-F]{3,6}/i', $bg_color) && $html_col) {
					$color_map[$token] = array('fg' => $color, 'bg' => $bg_color);
					continue;
				}
			} // Token link
			else if(preg_match('/[a-z_]/i', $color) && isset($color_map[$color])) {
				$color_map[$token] = $color_map[$color];
				continue;
			} // SGR Sequence
			else if(array_key_exists($color, $sgr_colors)) {
				$color = $sgr_colors[$color];
			}
			// handler function
			else if(strpos($color, '|')) {
				$toggle = $bg_color;
				list($custom_func, $backup) = explode('|', $color);
				if($toggle == 'HTMLONLY' && $html_col) {
					$color = $custom_func;
				} else if($toggle == 'CONSONLY' && !$html_col) {
					$color = $custom_func;
				} else {
					$color = (preg_match('/[a-z_]/i', $backup)) ? $color_map[$backup] : $backup;
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
	public function htmlToSgr($color)
	{
		$color = trim(str_replace('#', '', $color));
		if (!preg_match('/^[0-9A-F]{3,6}$/i', $color)) {
			return DEFAULT_COLOR;
		}

		if(strlen($color) == 3) {
			$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
		}

		$colparts  = array_map('hexdec', array('red' => substr($color, 0, 2), 'green' => substr($color, 2, 2), 'blue' => substr($color, 4, 2)));
		$end_color = '';

		$difference_threshold = $this->getDifferenceThreshold();
		$bold = $this->getBoldThreshold();

		switch (true) {
			// yellow
			case ($colparts['red'] > $colparts['blue'] && $colparts['green'] > $colparts['blue'] &&
				abs($colparts['red'] - $colparts['green']) < $difference_threshold):
				$end_color = '33';
			if ($colparts['red'] > $bold || $colparts['green'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
			// cyan
			case ($colparts['green'] > $colparts['red'] && $colparts['blue'] > $colparts['red'] &&
				abs($colparts['green'] - $colparts['blue']) < $difference_threshold):
				$end_color = '36';
			if ($colparts['green'] > $bold || $colparts['blue'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
			// magenta
			case ($colparts['red'] > $colparts['green'] && $colparts['blue'] > $colparts['green'] &&
				abs($colparts['red'] - $colparts['blue']) < $difference_threshold):
				$end_color = '35';
			if ($colparts['red'] > $bold || $colparts['blue'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
			// red
		case ($colparts['red'] > $colparts['green'] && $colparts['red'] > $colparts['blue']):
			$end_color = '31';
			if ($colparts['red'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
			// green
		case ($colparts['green'] > $colparts['red'] && $colparts['green'] > $colparts['blue']):
			$end_color = '32';
			if ($colparts['green'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
			// blue
		case ($colparts['blue'] > $colparts['green'] && $colparts['blue'] > $colparts['red']):
			$end_color = '34';
			if ($colparts['blue'] > $bold) {
				$end_color = '1;' . $end_color;
			}
			break;
		default:
			$end_color = '37';
			break;
		}

		return $end_color;
	}

	public function getDifferenceThreshold()
	{
		return $this->difference_threshold;
	}

	public function setDifferenceThreshold($diff)
	{
		$this->difference_threshold = $diff;
	}

	public function getBoldThreshold()
	{
		return $this->bold_threshold;
	}

	public function setBoldThreshold($bold)
	{
		$this->bold_threshold = $bold;
	}

	public function getSynfileDir()
	{
		return $this->synfile_dir;
	}

	public function setSynfileDir($dir)
	{
		$this->synfile_dir = $dir;
	}
}

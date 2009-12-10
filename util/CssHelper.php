<?php
class CssHelper
{
	/**
	 * Generate CSS based on the basemost identifiers
	 * @see self::getTokensetSelectors
	 * @param array List of base identifiers(selectors)
	 * @return string The css to be printed to the file
	 */
	public static function generateCss($identifiers)
	{
		print_r($identifiers);
		$css = '';
		foreach ($identifiers as $color => $token) {
			$decorators = '';
			if(!preg_match('/^#?[0-9A-F]{3,6}$/i', $color) && !strpos($color, '|') && !strpos($color, '%')) {
				$color = '#fff'; // default to white
			} else if(strpos($color, '|')) {
				list($fg, $bg) = explode('|', $color);
				if (strpos($fg, '%')) {
					list($fg, $decorator) = explode('%', $fg);
					if (strpos($decorator, 'b') !== false) {
						$decorators .= "font-weight:bold !important;";
					}
					if (strpos($decorator, 'i') !== false) {
						$decorators .= "font-style:italic !important;";
					}
				}
				$css .= '.' . $token . '{color: ' . $fg . ';background-color: ' . $bg . ';' . $decorators . '}';
				continue;
			}

			if (strpos($color, '%')) {
				list($color, $decorator) = explode('%', $color);
				if (strpos($decorator, 'b') !== false) {
					$decorators .= "font-weight:bold !important;";
				}
				if (strpos($decorator, 'i') !== false) {
					$decorators .= "font-style:italic !important;";
				}
			}

			$css .= '.' . $token . '{color:' . $color . ';' .$decorators . '}';
		}
		return $css;
	}

	/**
	 * Creates two arrays. One being the array that generate_css will use to write
	 * out the CSS (containing only the base identifiers). The second being an
	 * array containing all of the tokens as keys and the basemost identifier as
	 * their values.
	 * @param array The color map returned by parse_synfile
	 * @return array
	 */
	public static function getTokensetSelectors($colormap)
	{
		$identifiers = array();
		$tokenmap = array();
		foreach($colormap as $token => $color) {
			$selector = $token;
			if (is_array($color)) {
				$icolor = '';
				if (isset($color['bg'])) {
					$icolor = $color['fg'] . '|' . $color['bg'];
				}
				if (isset($color['decorators']) && $color['decorators'] !== '') {
					$icolor = isset($color['bg']) ?
						str_replace('|', '%' . $color['decorators'] . '|', $icolor)
						 :
						($color['fg'] . '%' . $color['decorators']);
				}
				$color = $icolor;
			}

			if(isset($identifiers[$color]) && !isset($tokenmap[$token])) {
				$tokenmap[$token] = $identifiers[$color];
				continue;
			}

			$identifiers[$color] = $selector;
			if(!isset($tokenmap[$token])) {
				$tokenmap[$token] = $identifiers[$color];
			}
		}
		return array('colormap' => $identifiers, 'tokenmap' => $tokenmap);
	}
}

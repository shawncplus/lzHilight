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
		$css = '';
		foreach ($identifiers as $color => $token) {
			$weight = '';
			if(!preg_match('/^#?[0-9A-F]{3,6}$/i', $color) && !strpos($color, '|')) {
				$color = '#fff'; // default to white
			} else if(strpos($color, '|')) {
				list($fg, $bg) = explode('|', $color);
				$css .= '.' . $token . '{color: ' . $fg . ';background-color: ' . $bg . ";}";
				continue;
			}

			$css .= '.' . $token . '{color:' . $color . ';' . $weight . "}";
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
			$color = is_array($color) ? $color['fg'] . '|' . $color['bg'] : $color;
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

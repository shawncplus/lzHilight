<?php
/**
 * Simple FSM for tokenizing CSS, there are probably more accurate,
 * better written tokenizers out there but I didn't feel like searching
 * so I wrote this, it's fast enough for me.
 *
 * @author Shawn Biddle (shawn@shawnbiddle.com)
 * @version 0.6
 */


define('CSS_WHITESPACE',   0); define('CSS_IDENTIFIER',   1); define('CSS_CLASS',        2); define('CSS_ID',           3);
define('CSS_PSEUDO_CLASS', 4); define('CSS_PROPERTY',     5); define('CSS_MEASURE',      6); define('CSS_STRING_S',     7);
define('CSS_STRING_D',     8); define('CSS_COMMENT',      9); define('CSS_COMM_STAR',    10);define('CSS_OPEN_BRACE',   11);
define('CSS_CLOSE_BRACE',  12);define('CSS_BLOCK',        13);define('CSS_ERROR',        14);define('CSS_OPERATOR',     15);
define('CSS_BLOCK_COMM',   16);define('CSS_BLCK_COM_STAR',17);define('CSS_COMM_END',     18);define('CSS_B_COMM_END',   19);
define('CSS_MIXIN',        20);

class CssLexer extends DefaultLexer
{
    protected $starting_state = CSS_WHITESPACE;

    protected $tokens = array(
        'CSS_WHTSPC', 'CSS_IDEN', 'CSS_CLS', 'CSS_ID', 'CSS_PCLS', 'CSS_PROP', 'CSS_MEASURE', 'CSS_STR_S', 'CSS_STR_D', 'CSS_COMM', 'CSS_COMM_STAR',
        'CSS_OPEN_BRC', 'CSS_END_BRC', 'CSS_BLCK', 'CSS_ERR', 'CSS_OP', 'CSS_BLCK_COMM', 'CSS_BLCK_COM_STR', 'CSS_COMM_END', 'CSS_B_COMM_END'
    );

    protected $state_table = array(
        CSS_WHITESPACE  => array('[\'"]' => CSS_ERROR, '\/' => CSS_COMMENT, '\w' => CSS_IDENTIFIER, '#' => CSS_ID, '\.' => CSS_CLASS, '\{' => CSS_OPEN_BRACE, '\}' => CSS_CLOSE_BRACE, '[\>\+,\*]' => CSS_OPERATOR),
        CSS_IDENTIFIER  => array('\.' => CSS_CLASS,  '#'  => CSS_ID,      '\s' => CSS_WHITESPACE, '\{' => CSS_OPEN_BRACE, '\}' => CSS_CLOSE_BRACE, ':'  => CSS_PSEUDO_CLASS, '[\>\+,\*]' => CSS_OPERATOR),
        CSS_CLASS       => array('\w' => CSS_IDENTIFIER,  '\W' => CSS_ERROR),
        CSS_ID          => array(CSS_CLASS),
        CSS_PSEUDO_CLASS=> array('\s' => CSS_WHITESPACE, '\{' => CSS_OPEN_BRACE),
        CSS_PROPERTY    => array(':'  => CSS_MEASURE,    '[^\w-_]' => CSS_ERROR),
        CSS_MEASURE     => array(';'  => CSS_BLOCK,      '\'' => CSS_STRING_S, '"'  => CSS_STRING_D, '\}' => CSS_CLOSE_BRACE ),
        CSS_STRING_S    => array('\'' => CSS_MEASURE),
        CSS_STRING_D    => array('"'  => CSS_MEASURE),
        CSS_COMMENT     => array('\*' => CSS_COMM_STAR,),
        CSS_COMM_STAR   => array('\/' => CSS_COMM_END,   '.'  => CSS_COMMENT),
        CSS_OPEN_BRACE  => array('\s' => CSS_BLOCK,      '\w' => CSS_PROPERTY, '@' => CSS_MIXIN, '\}' => CSS_CLOSE_BRACE),
        CSS_MIXIN       => array(';' => CSS_BLOCK,),
        CSS_CLOSE_BRACE => array('\w' => CSS_IDENTIFIER, '\/' => CSS_COMMENT,  '#'  => CSS_ID, '\.' => CSS_CLASS, '\s' => CSS_WHITESPACE),
        CSS_BLOCK       => array('\w' => CSS_PROPERTY,   '@' => CSS_MIXIN, '\{' => CSS_ERROR,    '\}' => CSS_CLOSE_BRACE, '\/' => CSS_BLOCK_COMM),
        CSS_ERROR       => array('\s' => CSS_WHITESPACE, '\/' => CSS_COMMENT),
        CSS_OPERATOR    => array('\s' => CSS_WHITESPACE, '\w' => CSS_IDENTIFIER, '\.' => CSS_CLASS, '#' => CSS_ID, '\{' => CSS_OPEN_BRACE),
        CSS_BLOCK_COMM  => array('\*' => CSS_BLCK_COM_STAR),
        CSS_BLCK_COM_STAR => array('\/' => CSS_B_COMM_END, '.' => CSS_BLOCK_COMM),
        CSS_COMM_END    => array(CSS_WHITESPACE),
        CSS_B_COMM_END  => array(CSS_BLOCK),
    );

    public static function handleProperty($string)
    {
        $valid_props = array (
			'azimuth', 'background-attachment', 'background-color',
			'background-image', 'background-position', 'background-repeat',
			'background', 'border-collapse', 'border-color', 'border-spacing',
			'border-style', 'border-top', 'border-right', 'border-bottom',
			'border-left', 'border-top-color', 'border-right-color',
			'border-bottom-color', 'border-left-color', 'border-top-style',
			'border-right-style', 'border-bottom-style', 'border-left-style',
			'border-top-width', 'border-right-width', 'border-bottom-width',
			'border-left-width', 'border-width', 'border', 'bottom',
			'caption-side', 'clear', 'clip', 'color', 'content',
			'counter-increment', 'counter-reset', 'cue-after', 'cue-before',
			'cue', 'cursor', 'direction', 'display', 'elevation',
			'empty-cells', 'float', 'font-family', 'font-size', 'font-style',
			'font-variant', 'font-weight', 'font', 'height', 'left',
			'letter-spacing', 'line-height', 'list-style-image',
			'list-style-position', 'list-style-type', 'list-style',
			'margin-right', 'margin-left', 'margin-top', 'margin-bottom',
			'margin', 'max-height', 'max-width', 'min-height', 'min-width',
			'orphans', 'outline-color', 'outline-style', 'outline-width',
			'outline', 'overflow', 'padding-top', 'padding-right',
			'padding-bottom', 'padding-left', 'padding', 'page-break-after',
			'page-break-before', 'page-break-inside', 'pause-after',
			'pause-before', 'pause', 'pitch-range', 'pitch', 'play-during',
			'position', 'quotes', 'richness', 'right', 'speak-header',
			'speak-numeral', 'speak-punctuation', 'speak', 'speech-rate',
			'stress', 'table-layout', 'text-align', 'text-decoration',
			'text-indent', 'text-transform', 'top', 'transition',
			'unicode-bidi', 'vertical-align', 'visibility', 'voice-family',
			'volume', 'white-space', 'widows', 'width', 'word-spacing',
			'z-index','overflow-x','overflow-y'
		);

        $token = in_array(trim($string), $valid_props) ? 'CSS_VALP' : 'CSS_INVP';
        return array(array('token' => $token, 'string' => $string));
    }

}

/* vim: set syn=php: */

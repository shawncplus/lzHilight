<?php

  function spacer($fact, $words = array())
  {
    // Let's display the name and the fact correctly
    if(empty($words)) {
      $words = array("'"); // More to come as example present themselves.
    }
    $clean_words = array_map('preg_quote', $words);
    return preg_match('/^\s*('.join('|', $clean_words).')/', $fact) ? $fact : ' '.$fact;
  }


$fact1 = 'has awesome pants';
$fact2 = '\'s pants are awesome';

$user = 'Shawn';

echo $user.spacer($fact1);
echo "\n".$user.spacer($fact2);
?>
This is some example text
<html>
<head>
  <title>TEST!!!</title>
</head>
<body>WTF?</body>
</html>

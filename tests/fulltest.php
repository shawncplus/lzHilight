<?php
function add($a, $b)
{
	return $a + $b;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 10 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en test">
<head>
	<title>TEST!!!</title>
	<style type="text/css">
	body {
		font-family: "Arial";
	}
	div#test > p.someclass { bad-property: "Blah"; text-decoration: none; }
	</style>
	<script type="text/javascript" src="dummy.js"></script>
</head>
<body>
	<h1><?php echo add(1, 2) ?></h1>
	<p>WTF?</p>
	<div class="body">
		<img src="helloworld.jpg" /><span class="caption">Hello World!</span>
		<h6>"This awesome string shouldn't be highlighted!"</h6>
		<p> This is some awesome &copy;copyrighted content</p>
	</div>
</body>
<!-- this is a comment -- this stuff is invalid what will happen? >
</html>

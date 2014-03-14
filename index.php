<?php

header('Content-type: text/css');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=2628000');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2628000));
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime(__FILE__)));
header('Pragma: Public');

// Templates segments
$BASE = "@font-face{font-family:'%s';font-style:%s;font-weight:%s;src:%s}";
$URI = array(
	'LOCAL' => "local('%s')",
	'WOFF' => "url(%s) format('woff')"
	);

$query = explode("/", preg_replace("/\/$|^\//", "", urldecode($_SERVER['REQUEST_URI'])));
$cat = json_decode(file_get_contents('./cat.json'), true);

if ($query[0] == "") {
	header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	exit();
}

$SERVERS = array(
	"//sea.cdn.brick.im/" => array( 47.7542, -122.2444 )
	);

$server = "//get.brick.im/";

foreach ($query as $key=>$val) {
	$val = explode(":", $val);
	$family = preg_replace("/\+/", " ", $val[0]);
	$weights = explode(",", $val[1]);
	$flags = isset($val[2]) ? $val[2] : '';

	foreach ($weights as $weight) {
		$base_url = $server . strtolower(preg_replace("/\s/", '', $family)) . "/";
		$local = $cat[$family][$weight];

		// Font URLs
		$woff = $base_url . $weight . ".woff";

		if (preg_match("/i$/", $weight)) {
			$style = 'italic';
			$weight = rtrim($weight, "i");
		} else {
			$style = 'normal';
		}

		// Start with no URI's
		$uri = array();

		// Process flags
		if (strpos($flags,'f') === false)
			array_push($uri, sprintf($URI['LOCAL'], $local));
		// Add font URL
		array_push($uri, sprintf($URI['WOFF'], $woff));

		echo sprintf($BASE, $family, $style, $weight, implode(',', $uri));
	}
}
?>

---
layout: null
---
<?php

/*
 * Brick. Webfonts that actually look good
 */

// Catalogue array
{% assign fonts = site.fonts %}
$catalogue = array(
    {% for font in fonts %}
    {% if font.layout == "font" %}
    "{{ font.family }}" => array(
        {% for style in font.styles %}
        "{{ style[0] }}" => "{{ style[1] }}"{% unless forloop.last %},{% endunless %}
        {% endfor %}
    ){% unless forloop.last %},{% endunless %}
    {% endif %}
    {% endfor %}
);

// Headers
header('Content-type: text/css');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=2628000');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2628000));
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime(__FILE__)));
header('Pragma: Public');

// Template
$BASE = "@font-face{font-family:'%s';font-style:%s;font-weight:%s;src:%s}";
$SVG = "@media screen and (-webkit-min-device-pixel-ratio:0){@font-face{font-family:'%s';font-style:%s;font-weight:%s;src:%s}}";

$query = explode("/", preg_replace("/\/$|^\//", "", urldecode($_SERVER['REQUEST_URI'])));

if ($query[0] == "") {
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    exit();
}

foreach ($query as $key=>$val) {
    // Query sections
    $val = explode(":", $val);
    $family = str_replace("+", "", $val[0]);
    $weights = explode(",", $val[1]);
    $flags = isset($val[2]) ? $val[2] : '';

    foreach ($weights as $weight) {
        // Build font URL
        $woff = "//brick.a.ssl.fastly.net/fonts/"
              . strtolower(str_replace(" ", '', $family))
              . "/"
              . $weight
              . ".woff";

        $svg = "//brick.a.ssl.fastly.net/fonts/"
              . strtolower(str_replace(" ", '', $family))
              . "/"
              . $weight
              . ".svg"
              . "#"
              . strtolower(str_replace(" ", '', $family));


        // Start with no URI's
        $uri = '';

        // Process flags
        if (strpos($flags, 'f') === false) {
            $local = $catalogue[$family][$weight];
            $uri .= "local('" . $local . "'),";
        }

        // Add font URL
        $uri .= "url(" . $woff . ") format('woff')";
        
        if (substr($weight, -1) == "i") {
            $style = 'italic';
            $weight = substr($weight, 0, -1);
        } else {
            $style = 'normal';
        }

        echo sprintf($BASE, $family, $style, $weight, $uri);
        if (strpos($flags, 's') !== false) {
            echo sprintf($SVG, $family, $style, $weight, "url(" . $svg . ") format('svg')");
        }
        
    }
}

?>

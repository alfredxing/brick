<?php

require_once '../vendor/autoload.php';

use MaxMind\Db\Reader;

$reader = new Reader('GeoIP2-City.mmdb');
$count = 10000;
$startTime = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $ip = long2ip(rand(0, pow(2, 32) -1));
    $t = $reader->get($ip);
    if ($i % 1000 == 0) {
        print($i . ' ' . $ip . "\n");
        // print_r($t);
    }
}
$endTime = microtime(true);

$duration = $endTime - $startTime;
print('Requests per second: ' . $count / $duration . "\n");

<?php

if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    die('Project dependencies missing');
}

$loader->add('MaxMind\Db\Test', __DIR__);

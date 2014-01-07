<?php

namespace MaxMind\Db\Test\Reader;

use MaxMind\Db\Reader\Decoder;

class PointerTest extends \PHPUnit_Framework_TestCase
{

    public function testWithPointers()
    {
        $handle = fopen('tests/data/test-data/maps-with-pointers.raw', 'r');
        $decoder = new Decoder($handle, 0);

        $this->assertEquals(
            array(array('long_key' => 'long_value1'), 22),
            $decoder->decode(0)
        );

        $this->assertEquals(
            array(array('long_key' => 'long_value2'), 37),
            $decoder->decode(22)
        );

        $this->assertEquals(
            array(array('long_key2' => 'long_value1'), 50),
            $decoder->decode(37)
        );

        $this->assertEquals(
            array(array('long_key2' => 'long_value2'), 55),
            $decoder->decode(50)
        );

        $this->assertEquals(
            array(array('long_key' => 'long_value1'), 57),
            $decoder->decode(55)
        );

        $this->assertEquals(
            array(array('long_key2' => 'long_value2'), 59),
            $decoder->decode(57)
        );
    }
}

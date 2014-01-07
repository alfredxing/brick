<?php

namespace MaxMind\Db\Test\Reader;

use MaxMind\Db\Reader\Decoder;

class DecoderTest extends \PHPUnit_Framework_TestCase
{
    private $arrays = array(
        array(
            'expected' => array(),
            'input' => array(0x0, 0x4),
            'name' => 'empty',
        ),
        array(
            'expected' => array('Foo'),
            'input' => array(0x1, 0x4, /* Foo */
                0x43, 0x46, 0x6f, 0x6f),
            'name' => 'one element',
        ),
        array(
            'expected' => array('Foo', '人'),
            'input' => array(
                0x2, 0x4,
                /* Foo */
                0x43, 0x46, 0x6f, 0x6f,
                /* 人 */
                0x43, 0xe4, 0xba, 0xba
            ),
            'name' => 'two elements',
        ),
    );

    private $booleans = array(
        false => array(0x0, 0x7),
        true => array(0x1, 0x7),
    );

    private $doubles = array(
        '0.0' => array(0x68, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0),
        '0.5' => array(0x68, 0x3F, 0xE0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0),
        '3.14159265359' => array(0x68, 0x40, 0x9, 0x21, 0xFB, 0x54, 0x44,
            0x2E, 0xEA),
        '123.0' => array(0x68, 0x40, 0x5E, 0xC0, 0x0, 0x0, 0x0, 0x0, 0x0),
        '1073741824.12457' => array(0x68, 0x41, 0xD0, 0x0, 0x0, 0x0, 0x7,
            0xF8, 0xF4),
        '-0.5' => array(0x68, 0xBF, 0xE0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0),
        '-3.14159265359' => array(0x68, 0xC0, 0x9, 0x21, 0xFB, 0x54, 0x44,
            0x2E, 0xEA),
        '-1073741824.12457' => array(0x68, 0xC1, 0xD0, 0x0, 0x0, 0x0, 0x7,
            0xF8, 0xF4),
    );

    private $floats = array(
        '0.0' => array(0x4, 0x8, 0x0, 0x0, 0x0, 0x0),
        '1.0' => array(0x4, 0x8, 0x3F, 0x80, 0x0, 0x0),
        '1.1' => array(0x4, 0x8, 0x3F, 0x8C, 0xCC, 0xCD),
        '3.14' => array(0x4, 0x8, 0x40, 0x48, 0xF5, 0xC3),
        '9999.99' => array(0x4, 0x8, 0x46, 0x1C, 0x3F, 0xF6),
        '-1.0' => array(0x4, 0x8, 0xBF, 0x80, 0x0, 0x0),
        '-1.1' => array(0x4, 0x8, 0xBF, 0x8C, 0xCC, 0xCD),
        '-3.14' => array(0x4, 0x8, 0xC0, 0x48, 0xF5, 0xC3),
        '-9999.99' => array(0x4, 0x8, 0xC6, 0x1C, 0x3F, 0xF6)
    );

    // PHP can't have arrays/objects as keys. Maybe redo all of the tests
    // this way so that we can use one test runner
    private $maps = array(
        array(
            'expected' => array(),
            'input' => array(0xe0),
            'name' => 'empty',
        ),
        array(
            'expected' => array('en' => 'Foo'),
            'input' => array(0xe1, /* en */
                0x42, 0x65, 0x6e,
                /* Foo */
                0x43, 0x46, 0x6f, 0x6f),
            'name' => 'one key',
        ),
        array(
            'expected' => array('en' => 'Foo', 'zh' => '人'),
            'input' => array(
                0xe2,
                /* en */
                0x42, 0x65, 0x6e,
                /* Foo */
                0x43, 0x46, 0x6f, 0x6f,
                /* zh */
                0x42, 0x7a, 0x68,
                /* 人 */
                0x43, 0xe4, 0xba, 0xba
            ),
            'name' => 'two keys',
        ),
        array(
            'expected' => array('name' => array('en' => 'Foo', 'zh' => '人')),
            'input' => array(
                0xe1,
                /* name */
                0x44, 0x6e, 0x61, 0x6d, 0x65, 0xe2,
                /* en */
                0x42, 0x65, 0x6e,
                /* Foo */
                0x43, 0x46, 0x6f, 0x6f,
                /* zh */
                0x42, 0x7a, 0x68,
                /* 人 */
                0x43, 0xe4, 0xba, 0xba
            ),
            'name' => 'nested',
        ),
        array(
            'expected' => array('languages' => array('en', 'zh')),
            'input' => array(
                0xe1,
                /* languages */
                0x49, 0x6c, 0x61, 0x6e, 0x67, 0x75, 0x61,
                0x67, 0x65, 0x73,
                /* array */
                0x2, 0x4,
                /* en */
                0x42, 0x65, 0x6e,
                /* zh */
                0x42, 0x7a, 0x68
            ),
            'name' => 'map with array in it'
        ),
    );

    private $pointers = array(
        0 => array(0x20, 0x0),
        5 => array(0x20, 0x5),
        10 => array(0x20, 0xa),
        1023 => array(0x23, 0xff,),
        3017 => array(0x28, 0x3, 0xc9),
        524283 => array(0x2f, 0xf7, 0xfb),
        526335 => array(0x2f, 0xff, 0xff),
        134217726 => array(0x37, 0xf7, 0xf7, 0xfe),
        134744063 => array(0x37, 0xff, 0xff, 0xff),
        2147483647 => array(0x38, 0x7f, 0xff, 0xff, 0xff),
        4294967295 => array(0x38, 0xff, 0xff, 0xff, 0xff),
    );

    private $uint16 = array(
        0 => array(0xa0),
        255 => array(0xa1, 0xff),
        500 => array(0xa2, 0x1, 0xf4),
        10872 => array(0xa2, 0x2a, 0x78),
        65535 => array(0xa2, 0xff, 0xff),
    );


    private $int32 = array(
        '0' => array(0x0, 0x1),
        '-1' => array(0x4, 0x1, 0xff, 0xff, 0xff, 0xff),
        '255' => array(0x1, 0x1, 0xff),
        '-255' => array(0x4, 0x1, 0xff, 0xff, 0xff, 0x1),
        '500' => array(0x2, 0x1, 0x1, 0xf4),
        '-500' => array(0x4, 0x1, 0xff, 0xff, 0xfe, 0xc),
        '65535' => array(0x2, 0x1, 0xff, 0xff),
        '-65535' => array(0x4, 0x1, 0xff, 0xff, 0x0, 0x1),
        '16777215' => array(0x3, 0x1, 0xff, 0xff, 0xff),
        '-16777215' => array(0x4, 0x1, 0xff, 0x0, 0x0, 0x1),
        '2147483647' => array(0x4, 0x1, 0x7f, 0xff, 0xff, 0xff),
        '-2147483647' => array(0x4, 0x1, 0x80, 0x0, 0x0, 0x1),
    );

    private function strings()
    {
        $strings = array(
            '' => array(0x40),
            1 => array(0x41, 0x31),
            '人' => array(0x43, 0xE4, 0xBA, 0xBA),
            '123' => array(0x43, 0x31, 0x32, 0x33),
            '123456789012345678901234567' => array(0x5b, 0x31, 0x32, 0x33, 0x34,
                0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35,
                0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35, 0x36,
                0x37),
            '1234567890123456789012345678' => array(0x5c, 0x31, 0x32, 0x33, 0x34,
                0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35,
                0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35, 0x36,
                0x37, 0x38),
            '12345678901234567890123456789' => array(0x5d, 0x0, 0x31, 0x32, 0x33,
                0x34, 0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34,
                0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35,
                0x36, 0x37, 0x38, 0x39),
            '123456789012345678901234567890' => array(0x5d, 0x1, 0x31, 0x32, 0x33,
                0x34, 0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34,
                0x35, 0x36, 0x37, 0x38, 0x39, 0x30, 0x31, 0x32, 0x33, 0x34, 0x35,
                0x36, 0x37, 0x38, 0x39, 0x30),
        );

        $strings[str_repeat('x', 500)] =
            array_pad(
                array(0x5e, 0x0, 0xd7),
                503,
                0x78
            );

        $strings[str_repeat('x', 2000)] =
            array_pad(
                array(0x5e, 0x6, 0xb3),
                2003,
                0x78
            );

        $strings[str_repeat('x', 70000)] =
            array_pad(
                array(0x5f, 0x0, 0x10, 0x53),
                70004,
                0x78
            );

        return $strings;
    }

    private $uint32 = array(
        0 => array(0xc0),
        255 => array(0xc1, 0xff),
        500 => array(0xc2, 0x1, 0xf4),
        10872 => array(0xc2, 0x2a, 0x78),
        65535 => array(0xc2, 0xff, 0xff),
        16777215 => array(0xc3, 0xff, 0xff, 0xff),
        4294967295 => array(0xc4, 0xff, 0xff, 0xff, 0xff),
    );

    private function bytes()
    {
        // ugly deep clone
        $bytes = unserialize(serialize($this->strings()));

        foreach ($bytes as $key => $byte_array) {
            $byte_array[0] ^= 0xc0;
            $bytes[$key] = $byte_array;

        }
        return $bytes;
    }

    public function generateLargeUint($bits)
    {

        $ctrlByte = $bits == 64 ? 0x2 : 0x3;

        $uints = array(
            0 => array(0x0, $ctrlByte),
            500 => array(0x2, $ctrlByte, 0x1, 0xf4),
            10872 => array(0x2, $ctrlByte, 0x2a, 0x78),
        );

        for ($power = 1; $power <= $bits / 8; $power++) {
            $expected = bcsub(bcpow(2, 8 * $power), 1);
            $input = array($power, $ctrlByte);
            for ($i = 2; $i < 2 + $power; $i++) {
                $input[$i] = 0xff;
            }
            $uints[$expected] = $input;
        }
        return $uints;
    }

    public function testArrays()
    {
        $this->validateTypeDecodingList('array', $this->arrays);
    }

    public function testBooleans()
    {
        $this->validateTypeDecoding('boolean', $this->booleans);
    }

    public function testBytes()
    {
        $this->validateTypeDecoding('byte', $this->bytes());
    }

    public function testDoubles()
    {
        $this->validateTypeDecoding('double', $this->doubles);
    }

    public function testFloats()
    {
        $this->validateTypeDecoding('float', $this->floats);
    }

    public function testInt32()
    {
        $this->validateTypeDecoding('int32', $this->int32);
    }

    public function testMaps()
    {
        $this->validateTypeDecodingList('map', $this->maps);
    }

    public function testPointers()
    {
        $this->validateTypeDecoding('pointers', $this->pointers);
    }

    public function testStrings()
    {
        $this->validateTypeDecoding('utf8_string', $this->strings());
    }

    public function testUint16()
    {
        $this->validateTypeDecoding('uint16', $this->uint16);
    }

    public function testUint32()
    {
        $this->validateTypeDecoding('uint32', $this->uint32);
    }

    public function testUint64()
    {
        $this->validateTypeDecoding('uint64', $this->generateLargeUint(64));
    }

    public function testUint128()
    {
        $this->validateTypeDecoding('uint128', $this->generateLargeUint(128));
    }

    private function validateTypeDecoding($type, $tests)
    {

        foreach ($tests as $expected => $input) {
            $this->checkDecoding($type, $input, $expected);
        }
    }

    private function validateTypeDecodingList($type, $tests)
    {
        foreach ($tests as $test) {
            $this->checkDecoding(
                $type,
                $test['input'],
                $test['expected'],
                $test['name']
            );
        }
    }

    private function checkDecoding($type, $input, $expected, $name = null)
    {
        $name = $name || $expected;
        $description = "decoded $type - $name";
        $handle = fopen('php://memory', 'rw');

        foreach ($input as $byte) {
            fwrite($handle, pack('C', $byte));
        }
        fseek($handle, 0);
        $decoder = new Decoder($handle, 0, true);
        list($actual) = $decoder->decode(0);

        if ($type == 'float') {
            $actual = round($actual, 2);
        }

        $this->assertEquals($expected, $actual, $description);

    }
}

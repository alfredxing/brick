<?php

namespace MaxMind\Db\Test\Reader;

use MaxMind\Db\Reader;

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testReader()
    {
        foreach (array(24, 28, 32) as $recordSize) {
            foreach (array(4, 6) as $ipVersion) {
                $fileName = 'tests/data/test-data/MaxMind-DB-test-ipv'
                    . $ipVersion . '-' . $recordSize . '.mmdb';
                $reader = new Reader($fileName);

                $this->checkMetadata($reader, $ipVersion, $recordSize);

                if ($ipVersion == 4) {
                    $this->checkIpV4($reader, $fileName);
                } else {
                    $this->checkIpV6($reader, $fileName);
                }
            }
        }
    }

    public function testDecoder()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-decoder.mmdb');
        $record = $reader->get('::1.1.1.0');

        $this->assertEquals(true, $record['boolean']);
        $this->assertEquals(pack('N', 42), $record['bytes']);
        $this->assertEquals('unicode! ☯ - ♫', $record['utf8_string']);

        $this->assertEquals(array(1, 2, 3), $record['array']);

        $this->assertEquals(
            array(
                'mapX' => array(
                    'arrayX' => array(7, 8, 9),
                    'utf8_stringX' => 'hello'
                ),
            ),
            $record['map']
        );

        $this->assertEquals(42.123456, $record['double']);
        $this->assertEquals(1.1, $record['float'], 'float', 0.000001);

        $this->assertEquals(-268435456, $record['int32']);
        $this->assertEquals(100, $record['uint16']);
        $this->assertEquals(268435456, $record['uint32']);
        $this->assertEquals('1152921504606846976', $record['uint64']);

        $this->assertEquals(
            '1329227995784915872903807060280344576',
            $record['uint128']
        );
    }

    public function testZeros()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-decoder.mmdb');
        $record = $reader->get('::');

        $this->assertEquals(false, $record['boolean']);
        $this->assertEquals('', $record['bytes']);
        $this->assertEquals('', $record['utf8_string']);

        $this->assertEquals(array(), $record['array']);
        $this->assertEquals(array(), $record['map']);

        $this->assertEquals(0, $record['double']);
        $this->assertEquals(0, $record['float'], 'float', 0.000001);
        $this->assertEquals(0, $record['int32']);
        $this->assertEquals(0, $record['uint16']);
        $this->assertEquals(0, $record['uint32']);
        $this->assertEquals(0, $record['uint64']);
        $this->assertEquals(0, $record['uint128']);
    }

    public function testNoIpV4SearchTree()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-no-ipv4-search-tree.mmdb'
        );
        $this->assertEquals('::/64', $reader->get('1.1.1.1'));
        $this->assertEquals('::/64', $reader->get('192.1.1.1'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Error looking up 2001::. You attempted to look up an IPv6 address in an IPv4-only database
     */
    public function testV6AddressV4Database()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-ipv4-24.mmdb');
        $reader->get('2001::');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The value "not_ip" is not a valid IP address.
     */
    public function testIpValidation()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-decoder.mmdb');
        $reader->get('not_ip');
    }

    /**
     * @expectedException MaxMind\Db\Reader\InvalidDatabaseException
     * @expectedExceptionMessage The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)
     */
    public function testBrokenDatabase()
    {
        $reader = new Reader('tests/data/test-data/GeoIP2-City-Test-Broken-Double-Format.mmdb');
        $reader->get('2001:220::');
    }

    /**
     * @expectedException MaxMind\Db\Reader\InvalidDatabaseException
     * @expectedExceptionMessage The MaxMind DB file's search tree is corrupt
     */
    public function testBrokenSearchTreePointer()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-broken-pointers-24.mmdb');
        $reader->get('1.1.1.32');
    }

    /**
     * @expectedException MaxMind\Db\Reader\InvalidDatabaseException
     * @expectedExceptionMessage The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)
     */
    public function testBrokenDataPointer()
    {
        $reader = new Reader('tests/data/test-data/MaxMind-DB-test-broken-pointers-24.mmdb');
        $reader->get('1.1.1.16');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The file "file-does-not-exist.mmdb" does not exist or is not readable.
     */
    public function testMissingDatabase()
    {
        new Reader('file-does-not-exist.mmdb');
    }

    /**
     * @expectedException MaxMind\Db\Reader\InvalidDatabaseException
     * @expectedExceptionMessage Error opening database file (README.md). Is this a valid MaxMind DB file?
     */
    public function testNonDatabase()
    {
        new Reader('README.md');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The constructor takes exactly one argument.
     */
    public function testTooManyConstructorArgs()
    {
        new Reader('README.md', 1);
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * This test only matters for the extension.
     */
    public function testNoConstructorArgs()
    {
        if (extension_loaded('maxminddb')) {
            new Reader();
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Method takes exactly one argument.
     */
    public function testTooManyGetAgs()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->get('1.1.1.1', 'blah');
    }

    /**
     * @expectedException InvalidArgumentException
     *
     * This test only matters for the extension.
     */
    public function testNoGetArgs()
    {
        if (extension_loaded('maxminddb')) {
            $reader = new Reader(
                'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
            );
            $reader->get();
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Method takes no arguments.
     */
    public function testMetadataAgs()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->metadata('blah');
    }

    public function testClose()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->close();
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Attempt to close a closed MaxMind DB.
     */
    public function testDoubleClose()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->close();
        $reader->close();
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Attempt to read from a closed MaxMind DB.
     */
    public function testClosedGet()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->close();
        $reader->get('1.1.1.1');
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Attempt to read from a closed MaxMind DB.
     */
    public function testClosedMetadata()
    {
        $reader = new Reader(
            'tests/data/test-data/MaxMind-DB-test-decoder.mmdb'
        );
        $reader->close();
        $reader->metadata();
    }

    private function checkMetadata($reader, $ipVersion, $recordSize)
    {
        $metadata = $reader->metadata();

        $this->assertEquals(
            2,
            $metadata->binaryFormatMajorVersion,
            'major version'
        );
        $this->assertEquals(0, $metadata->binaryFormatMinorVersion);
        $this->assertGreaterThan(1373571901, $metadata->buildEpoch);
        $this->assertEquals('Test', $metadata->databaseType);

        $this->assertEquals(
            array('en' => 'Test Database', 'zh' => 'Test Database Chinese'),
            $metadata->description
        );

        $this->assertEquals($ipVersion, $metadata->ipVersion);
        $this->assertEquals(array('en', 'zh'), $metadata->languages);
        $this->assertEquals($recordSize / 4, $metadata->nodeByteSize);
        $this->assertGreaterThan(36, $metadata->nodeCount);

        $this->assertEquals($recordSize, $metadata->recordSize);
        $this->assertGreaterThan(200, $metadata->searchTreeSize);
    }

    private function checkIpV4(Reader $reader, $fileName)
    {
        for ($i = 0; $i <= 5; $i++) {
            $address = '1.1.1.' . pow(2, $i);
            $this->assertEquals(
                array('ip' => $address),
                $reader->get($address),
                'found expected data record for '
                . $address . ' in ' . $fileName
            );
        }

        $pairs = array(
            '1.1.1.3' => '1.1.1.2',
            '1.1.1.5' => '1.1.1.4',
            '1.1.1.7' => '1.1.1.4',
            '1.1.1.9' => '1.1.1.8',
            '1.1.1.15' => '1.1.1.8',
            '1.1.1.17' => '1.1.1.16',
            '1.1.1.31' => '1.1.1.16'
        );
        foreach ($pairs as $keyAddress => $valueAddress) {
            $data = array('ip' => $valueAddress);

            $this->assertEquals(
                $data,
                $reader->get($keyAddress),
                'found expected data record for ' . $keyAddress . ' in '
                . $fileName
            );
        }

        foreach (array('1.1.1.33', '255.254.253.123') as $ip) {
            $this->assertNull($reader->get($ip));
        }
    }

    // XXX - logic could be combined with above
    private function checkIpV6(Reader $reader, $fileName)
    {
        $subnets = array('::1:ffff:ffff', '::2:0:0',
            '::2:0:40', '::2:0:50', '::2:0:58');

        foreach ($subnets as $address) {
            $this->assertEquals(
                array('ip' => $address),
                $reader->get($address),
                'found expected data record for ' . $address . ' in '
                . $fileName
            );
        }

        $pairs = array(
            '::2:0:1' => '::2:0:0',
            '::2:0:33' => '::2:0:0',
            '::2:0:39' => '::2:0:0',
            '::2:0:41' => '::2:0:40',
            '::2:0:49' => '::2:0:40',
            '::2:0:52' => '::2:0:50',
            '::2:0:57' => '::2:0:50',
            '::2:0:59' => '::2:0:58'
        );

        foreach ($pairs as $keyAddress => $valueAddress) {
            $this->assertEquals(
                array('ip' => $valueAddress),
                $reader->get($keyAddress),
                'found expected data record for ' . $keyAddress . ' in '
                . $fileName
            );
        }

        foreach (array('1.1.1.33', '255.254.253.123', '89fa::') as $ip) {
            $this->assertNull($reader->get($ip));
        }
    }
}

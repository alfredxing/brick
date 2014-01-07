<?php

namespace MaxMind\Db\Reader;

use MaxMind\Db\Reader\InvalidDatabaseException;

class Decoder
{

    private $fileStream;
    private $pointerBase;
    // This is only used for unit testing
    private $pointerTestHack;
    private $switchByteOrder;

    private $types = array(
        0 => 'extended',
        1 => 'pointer',
        2 => 'utf8_string',
        3 => 'double',
        4 => 'bytes',
        5 => 'uint16',
        6 => 'uint32',
        7 => 'map',
        8 => 'int32',
        9 => 'uint64',
        10 => 'uint128',
        11 => 'array',
        12 => 'container',
        13 => 'end_marker',
        14 => 'boolean',
        15 => 'float',
    );

    public function __construct(
        $fileStream,
        $pointerBase = 0,
        $pointerTestHack = false
    ) {
        $this->fileStream = $fileStream;
        $this->pointerBase = $pointerBase;
        $this->pointerTestHack = $pointerTestHack;

        $this->switchByteOrder = $this->isPlatformLittleEndian();
    }


    public function decode($offset)
    {
        list(, $ctrlByte) = unpack('C', $this->read($offset, 1));
        $offset++;

        $type = $this->types[$ctrlByte >> 5];

        // Pointers are a special case, we don't read the next $size bytes, we
        // use the size to determine the length of the pointer and then follow
        // it.
        if ($type == 'pointer') {
            list($pointer, $offset) = $this->decodePointer($ctrlByte, $offset);

            // for unit testing
            if ($this->pointerTestHack) {
                return array($pointer);
            }

            list($result) = $this->decode($pointer);

            return array($result, $offset);
        }

        if ($type == 'extended') {
            list(, $nextByte) = unpack('C', $this->read($offset, 1));

            $typeNum = $nextByte + 7;

            if ($typeNum < 8) {
                throw new InvalidDatabaseException(
                    "Something went horribly wrong in the decoder. An extended type "
                    . "resolved to a type number < 8 ("
                    . $this->types[$typeNum]
                    . ")"
                );
            }

            $type = $this->types[$typeNum];
            $offset++;
        }

        list($size, $offset) = $this->sizeFromCtrlByte($ctrlByte, $offset);

        return $this->decodeByType($type, $offset, $size);
    }

    private function decodeByType($type, $offset, $size)
    {
        switch ($type) {
            case 'map':
                return $this->decodeMap($size, $offset);
            case 'array':
                return $this->decodeArray($size, $offset);
            case 'boolean':
                return array($this->decodeBoolean($size), $offset);
        }

        $newOffset = $offset + $size;
        $bytes = $this->read($offset, $size);
        switch ($type) {
            case 'utf8_string':
                return array($this->decodeString($bytes), $newOffset);
            case 'double':
                $this->verifySize(8, $size);
                return array($this->decodeDouble($bytes), $newOffset);
            case 'float':
                $this->verifySize(4, $size);
                return array($this->decodeFloat($bytes), $newOffset);
            case 'bytes':
                return array($bytes, $newOffset);
            case 'uint16':
                return array($this->decodeUint16($bytes), $newOffset);
            case 'uint32':
                return array($this->decodeUint32($bytes), $newOffset);
            case 'int32':
                return array($this->decodeInt32($bytes), $newOffset);
            case 'uint64':
                return array($this->decodeUint64($bytes), $newOffset);
            case 'uint128':
                return array($this->decodeUint128($bytes), $newOffset);
            default:
                throw new InvalidDatabaseException(
                    "Unknown or unexpected type: " . $type
                );
        }
    }

    private function verifySize($expected, $actual)
    {
        if ($expected != $actual) {
            throw new InvalidDatabaseException(
                "The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)"
            );
        }
    }

    private function decodeArray($size, $offset)
    {
        $array = array();

        for ($i = 0; $i < $size; $i++) {
            list($value, $offset) = $this->decode($offset);
            array_push($array, $value);
        }

        return array($array, $offset);
    }

    private function decodeBoolean($size)
    {
        return $size == 0 ? false : true;
    }

    private function decodeDouble($bits)
    {
        // XXX - Assumes IEEE 754 double on platform
        list(, $double) = unpack('d', $this->maybeSwitchByteOrder($bits));
        return $double;
    }

    private function decodeFloat($bits)
    {
        // XXX - Assumes IEEE 754 floats on platform
        list(, $float) = unpack('f', $this->maybeSwitchByteOrder($bits));
        return $float;
    }

    private function decodeInt32($bytes)
    {
        $bytes = $this->zeroPadLeft($bytes, 4);
        list(, $int) = unpack('l', $this->maybeSwitchByteOrder($bytes));
        return $int;
    }

    private function decodeMap($size, $offset)
    {

        $map = array();

        for ($i = 0; $i < $size; $i++) {
            list($key, $offset) = $this->decode($offset);
            list($value, $offset) = $this->decode($offset);
            $map[$key] = $value;
        }

        return array($map, $offset);
    }

    private $pointerValueOffset = array(
        1 => 0,
        2 => 2048,
        3 => 526336,
        4 => 0,
    );

    private function decodePointer($ctrlByte, $offset)
    {
        $pointerSize = (($ctrlByte >> 3) & 0x3) + 1;

        $buffer = $this->read($offset, $pointerSize);
        $offset = $offset + $pointerSize;

        $packed = $pointerSize == 4
            ? $buffer
            : (pack('C', $ctrlByte & 0x7)) . $buffer;

        $unpacked = $this->decodeUint32($packed);
        $pointer = $unpacked + $this->pointerBase
            + $this->pointerValueOffset[$pointerSize];

        return array($pointer, $offset);
    }


    private function decodeUint16($bytes)
    {
        // No big-endian unsigned short format
        return $this->decodeUint32($bytes);
    }

    private function decodeUint32($bytes)
    {
        list(, $int) = unpack('N', $this->zeroPadLeft($bytes, 4));
        return $int;
    }

    private function decodeUint64($bytes)
    {
        return $this->decodeBigUint($bytes, 8);
    }

    private function decodeUint128($bytes)
    {
        return $this->decodeBigUint($bytes, 16);
    }

    private function decodeBigUint($bytes, $size)
    {
        $numberOfLongs = $size / 4;
        $integer = 0;
        $bytes = $this->zeroPadLeft($bytes, $size);
        $unpacked = array_merge(unpack("N$numberOfLongs", $bytes));
        foreach ($unpacked as $part) {
            // No bitwise operators with bcmath :'-(
            $integer = bcadd(bcmul($integer, bcpow(2, 32)), $part);
        }
        return $integer;
    }

    private function decodeString($bytes)
    {
        // XXX - NOOP. As far as I know, the end user has to explicitly set the
        // encoding in PHP. Strings are just bytes.
        return $bytes;
    }

    private function read($offset, $numberOfBytes)
    {
        if ($numberOfBytes == 0) {
            return '';
        }
        if (fseek($this->fileStream, $offset) == 0) {
            $value = fread($this->fileStream, $numberOfBytes);
            if (strlen($value) === $numberOfBytes) {
                return $value;
            }
        }
        throw new InvalidDatabaseException(
            "The MaxMind DB file's data section contains bad data "
            . "(unknown data type or corrupt data)"
        );
    }

    private function sizeFromCtrlByte($ctrlByte, $offset)
    {
        $size = $ctrlByte & 0x1f;
        $bytesToRead = $size < 29 ? 0 : $size - 28;
        $bytes = $this->read($offset, $bytesToRead);
        $decoded = $this->decodeUint32($bytes);

        if ($size == 29) {
            $size = 29 + $decoded;
        } elseif ($size == 30) {
            $size = 285 + $decoded;
        } elseif ($size > 30) {

            $size = ($decoded & (0x0FFFFFFF >> (32 - (8 * $bytesToRead))))
                + 65821;
        }

        return array($size, $offset + $bytesToRead);
    }

    private function zeroPadLeft($content, $desiredLength)
    {
        return str_pad($content, $desiredLength, "\x00", STR_PAD_LEFT);
    }

    private function maybeSwitchByteOrder($bytes)
    {
        return $this->switchByteOrder ? strrev($bytes) : $bytes;
    }

    private function isPlatformLittleEndian()
    {
        $testint = 0x00FF;
        $packed = pack('S', $testint);
        return $testint === current(unpack('v', $packed));
    }
}

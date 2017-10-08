<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\EmptyLine;
use PHPUnit\Framework\TestCase;

class EmptyLineTest extends TestCase
{
    public function testConstructor()
    {
        $emptyLine = new EmptyLine();
        $this->assertInstanceOf('\\HAProxy\\Config\\EmptyLine', $emptyLine);
        return $emptyLine;
    }

    public function testToString()
    {
        $emptyLine = new EmptyLine();
        $this->assertEquals("\n", (string) $emptyLine);
    }
}

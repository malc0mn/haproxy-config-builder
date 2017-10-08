<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testEof()
    {
        $text = new Text('server server1 127.0.0.1:8000 maxconn 32');

        $this->assertTrue($text->eof(40));

    }

    public function testEol()
    {
        $config = <<<CONFIG
frontend http-in
    bind *:80
    default_backend servers
CONFIG;

        $text = new Text($config);

        $this->assertTrue($text->eol(16));

    }

    public function testInc()
    {
        $text = new Text('server server1 127.0.0.1:8000 maxconn 32');
        $text->inc(20);

        $this->assertEquals('.', $text->getChar());
    }

    public function testGetCharOnPosition()
    {
        $text = new Text('server server1 127.0.0.1:8000 maxconn 32');
        $this->assertEquals('1', $text->getChar(13));
    }

    public function testGetCharOnPositionError()
    {
        $this->expectException('\HAProxy\Config\Exception\TextException');
        $this->expectExceptionMessage('Expected position to be integer, got double!');

        $text = new Text('');
        $text->getChar(.5);
    }

    public function testGetCharOutOfRange()
    {
        $this->expectException('\HAProxy\Config\Exception\TextException');
        $this->expectExceptionMessage('Index out of range. Position: 1.');

        $text = new Text('');
        $text->getChar(1);
    }

    public function testGetRestOfTheLine()
    {
        $text = new Text('server server1 127.0.0.1:8000 maxconn 32');
        $this->assertEquals('r1 127.0.0.1:8000 maxconn 32', $text->getRestOfTheLine(12));
    }

    public function testFirstWordMatches()
    {
        $config = <<<CONFIG
frontend http-in
    bind *:80
    default_backend servers
CONFIG;

        $text = new Text($config);

        $this->assertTrue($text->firstWordMatches('frontend'));
    }

    public function testEolNewline()
    {
        $text = new Text("\n");
        $this->assertTrue($text->eol());
    }

    public function testEolCarriageReturn()
    {
        $text = new Text("\r");
        $this->assertTrue($text->eol());
    }

    public function testIsEmptyLine()
    {
        $text = new Text("    \r\n");
        $this->assertTrue($text->isEmptyLine());
    }

    public function testGetCurrentLine()
    {
        $config = <<<CONFIG
frontend http-in
    bind *:80
    default_backend servers
CONFIG;

        $text = new Text($config);

        $this->assertEquals("\n    bind *:80", $text->getCurrentLine(20));
    }

    public function testGetLastEol()
    {
        $config = <<<CONFIG
frontend http-in
    bind *:80
    default_backend servers
CONFIG;

        $text = new Text($config);

        $this->assertEquals(30, $text->getPreviousEol(50));
    }

    public function testGetNextEolNewline()
    {
        $text = new Text("\n");
        $this->assertEquals(0, $text->getNextEol());
    }

    public function testGetNextEolNoNewline()
    {
        $text = new Text('timeout connect 5000ms');
        $this->assertEquals(21, $text->getNextEol());
    }
}
